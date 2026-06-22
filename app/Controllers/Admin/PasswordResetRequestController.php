<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PasswordResetRequestModel;
use Config\Database;

/**
 * Kelola permintaan reset password yang masuk dari halaman "Lupa Password".
 *
 * Alur: pengguna mengisi email + nomor telepon di /forgot-password →
 * PasswordResetRequestService mencatat ke `password_reset_requests` dan
 * memberi tahu Admin (pesan + notifikasi). Di sini Admin dapat melihat
 * daftar permintaan, membuka halaman pengguna untuk mereset password, lalu
 * menandai permintaan sebagai "Selesai" (mengisi resolved_by / resolved_at).
 */
class PasswordResetRequestController extends BaseController
{
    private PasswordResetRequestModel $requests;

    public function __construct()
    {
        helper(['auth', 'permission']);
        $this->requests = new PasswordResetRequestModel();
    }

    public function index()
    {
        require_permission('manage_users');

        $status = trim((string) ($this->request->getGet('status') ?? ''));
        $search = trim((string) ($this->request->getGet('search') ?? ''));

        $builder = Database::connect()->table('password_reset_requests prr')
            ->select('prr.*, u.full_name AS user_full_name, u.username AS user_username, r.role_name AS user_role, resolver.full_name AS resolved_by_name')
            ->join('users u', 'u.id = prr.user_id', 'left')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->join('users resolver', 'resolver.id = prr.resolved_by', 'left')
            ->where('prr.deleted_at', null);

        if ($status === 'open') {
            $builder->whereIn('prr.status', ['pending', 'notified']);
        } elseif ($status === 'resolved') {
            $builder->where('prr.status', 'resolved');
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('prr.email', $search)
                ->orLike('prr.phone', $search)
                ->orLike('u.full_name', $search)
                ->orLike('u.username', $search)
                ->groupEnd();
        }

        $rows = $builder->orderBy('prr.requested_at', 'DESC')->get()->getResultArray();

        // Model memakai soft delete, jadi filter deleted_at otomatis diterapkan.
        // Setiap countAllResults() mereset builder (parameter reset default true).
        $stats = [
            'total'    => (int) $this->requests->countAllResults(),
            'open'     => (int) $this->requests->whereIn('status', ['pending', 'notified'])->countAllResults(),
            'resolved' => (int) $this->requests->where('status', 'resolved')->countAllResults(),
        ];

        return view('admin/password_resets/index', [
            'title'   => 'Permintaan Reset Password',
            'rows'    => $rows,
            'stats'   => $stats,
            'filters' => ['status' => $status, 'search' => $search],
        ]);
    }

    /**
     * Tandai sebuah permintaan reset password sebagai selesai.
     */
    public function resolve($id)
    {
        require_permission('manage_users');

        $id  = (int) $id;
        $row = $this->requests->find($id);
        if (! $row) {
            return redirect()->to('admin/password-reset-requests')->with('error', 'Permintaan tidak ditemukan.');
        }

        if (($row['status'] ?? '') === 'resolved') {
            return redirect()->back()->with('info', 'Permintaan ini sudah berstatus selesai.');
        }

        $notes = trim((string) $this->request->getPost('notes'));

        $this->requests->update($id, [
            'status'      => 'resolved',
            'resolved_by' => auth_id(),
            'resolved_at' => date('Y-m-d H:i:s'),
            'notes'       => $notes !== '' ? $notes : ($row['notes'] ?? null),
        ]);

        return redirect()->back()->with('success', 'Permintaan reset password ditandai selesai.');
    }
}
