<?php

/**
 * File: app/Controllers/RoleFeatures/BaseBkServiceController.php
 * Fitur: Controller dasar Bimbingan, Konseling, Kolaborasi Orang Tua,
 * Kunjungan Rumah, dan Konferensi Kasus.
 * Peran/izin: Dipakai controller turunan per peran. Koordinator BK/Guru BK
 * dapat mengelola; Wali Kelas/Siswa/Orang Tua dibatasi lihat detail sesuai
 * cakupan data.
 * Berhubungan dengan: BkServiceService dan view per peran pada folder
 * koordinator/counselor/homeroom_teacher/student/parent.
 */

namespace App\Controllers\RoleFeatures;

use App\Controllers\BaseController;
use App\Services\BkServiceService;

abstract class BaseBkServiceController extends BaseController
{
    protected string $roleKey = '';
    protected string $roleLabel = '';
    protected string $routePrefix = '';
    protected string $viewPrefix = '';
    protected string $serviceType = '';
    protected bool $canManage = false;

    protected BkServiceService $service;

    public function __construct()
    {
        helper(['form', 'url', 'auth', 'permission', 'notification']);
        $this->service = new BkServiceService();
    }

    public function index()
    {
        $filters = [
            'q' => trim((string) $this->request->getGet('q')),
            'status' => trim((string) $this->request->getGet('status')),
            'class_id' => trim((string) $this->request->getGet('class_id')),
            'student_id' => trim((string) $this->request->getGet('student_id')),
            'date_from' => trim((string) $this->request->getGet('date_from')),
            'date_to' => trim((string) $this->request->getGet('date_to')),
        ];

        return $this->render('index', [
            'title' => $this->service->meta($this->serviceType)['title'],
            'rows' => $this->service->list($this->serviceType, $this->roleKey, $this->currentUserId(), $filters),
            'filters' => $filters,
            'options' => $this->service->formOptions($this->roleKey, $this->currentUserId()),
        ]);
    }

    public function create()
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        return $this->render('form', [
            'title' => 'Tambah ' . $this->service->meta($this->serviceType)['title'],
            'row' => [],
            'options' => $this->service->formOptions($this->roleKey, $this->currentUserId()),
            'action' => site_url($this->routePrefix . '/store'),
        ]);
    }

    public function store()
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $id = $this->service->create($this->serviceType, $this->request->getPost() ?? [], $this->currentUserId());

        $this->notifyScheduleParticipants((int) $id);

        return redirect()->to(site_url($this->routePrefix . '/show/' . $id))
            ->with('success', $this->service->meta($this->serviceType)['title'] . ' berhasil dicatat.');
    }

    public function show($id)
    {
        $row = $this->service->find((int) $id, $this->roleKey, $this->currentUserId());
        if (! $row) {
            return redirect()->to(site_url($this->routePrefix))->with('error', 'Data tidak ditemukan atau tidak bisa diakses.');
        }

        return $this->render('show', [
            'title' => 'Detail ' . $this->service->meta($this->serviceType)['title'],
            'row' => $row,
            'options' => $this->service->formOptions($this->roleKey, $this->currentUserId()),
        ]);
    }

    public function edit($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $row = $this->service->find((int) $id, $this->roleKey, $this->currentUserId());
        if (! $row) {
            return redirect()->to(site_url($this->routePrefix))->with('error', 'Data tidak ditemukan.');
        }

        return $this->render('form', [
            'title' => 'Edit ' . $this->service->meta($this->serviceType)['title'],
            'row' => $row,
            'options' => $this->service->formOptions($this->roleKey, $this->currentUserId()),
            'action' => site_url($this->routePrefix . '/update/' . (int) $id),
        ]);
    }

    public function update($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $this->service->update((int) $id, $this->serviceType, $this->request->getPost() ?? [], $this->currentUserId());

        $this->notifyScheduleParticipants((int) $id);

        return redirect()->to(site_url($this->routePrefix . '/show/' . (int) $id))
            ->with('success', 'Data berhasil diperbarui.');
    }

    public function delete($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $uid = $this->currentUserId();
        $db  = \Config\Database::connect();

        // Hanya pembuat data yang boleh menghapus (Koordinator BK & Guru BK sama-sama
        // hanya dapat menghapus data buatannya sendiri). Edit isi tetap boleh lintas peran.
        $rec = $db->table('bk_service_records')
            ->select('id, created_by')
            ->where('id', (int) $id)
            ->where('deleted_at', null)
            ->get()->getRowArray();

        if (! $rec) {
            return redirect()->to(site_url($this->routePrefix))->with('error', 'Data tidak ditemukan.');
        }

        if ((int) ($rec['created_by'] ?? 0) !== $uid) {
            return redirect()->to(site_url($this->routePrefix))
                ->with('error', 'Anda hanya dapat menghapus data yang Anda buat sendiri.');
        }

        // Soft delete + catat penghapus agar bisa dipulihkan lewat Tempat Sampah.
        if ($db->fieldExists('deleted_by', 'bk_service_records')) {
            $db->table('bk_service_records')->where('id', (int) $id)
                ->update(['deleted_at' => date('Y-m-d H:i:s'), 'deleted_by' => $uid]);
        } else {
            $this->service->delete((int) $id);
        }

        return redirect()->to(site_url($this->routePrefix))
            ->with('success', 'Data dipindahkan ke Tempat Sampah.');
    }

    public function addNote($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $this->service->addNote((int) $id, $this->request->getPost() ?? [], $this->currentUserId());

        return redirect()->to(site_url($this->routePrefix . '/show/' . (int) $id))
            ->with('success', 'Catatan berhasil ditambahkan.');
    }

    public function updateParticipant($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $recordId = (int) ($this->request->getPost('record_id') ?? 0);
        $this->service->updateParticipant((int) $id, $this->request->getPost() ?? []);

        return redirect()->to(site_url($this->routePrefix . '/show/' . $recordId))
            ->with('success', 'Kehadiran peserta berhasil diperbarui.');
    }

    public function deleteParticipant($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $recordId = (int) ($this->request->getPost('record_id') ?? 0);
        $back = (string) ($this->request->getPost('back') ?? 'show');
        $this->service->deleteParticipant((int) $id, $recordId, $this->currentUserId());

        $target = $back === 'edit'
            ? site_url($this->routePrefix . '/edit/' . $recordId)
            : site_url($this->routePrefix . '/show/' . $recordId);

        return redirect()->to($target)->with('success', 'Peserta berhasil dihapus.');
    }

    public function deleteNote($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $recordId = (int) ($this->request->getPost('record_id') ?? 0);
        $ok = $this->service->deleteNote((int) $id, $recordId, $this->currentUserId());

        return redirect()->to(site_url($this->routePrefix . '/show/' . $recordId))
            ->with($ok ? 'success' : 'error', $ok ? 'Catatan berhasil dihapus.' : 'Anda hanya dapat menghapus catatan yang Anda buat sendiri.');
    }

    protected function render(string $view, array $data = [])
    {
        $meta = $this->service->meta($this->serviceType);
        $viewPath = $this->viewPrefix . '/' . $meta['slug'] . '/' . $view;

        return view($viewPath, array_merge([
            'serviceType' => $this->serviceType,
            'meta' => $meta,
            'roleKey' => $this->roleKey,
            'roleLabel' => $this->roleLabel,
            'routePrefix' => trim($this->routePrefix, '/'),
            'canManage' => $this->canManage,
        ], $data));
    }

    protected function currentUserId(): int
    {
        return (int) (session('user_id') ?? session('id') ?? 0);
    }

    protected function deny()
    {
        return redirect()->to(site_url($this->routePrefix))->with('error', 'Akses tidak tersedia untuk peran ini.');
    }

    /**
     * Beritahu peserta/undangan (Siswa & Orang Tua) tentang jadwal kegiatan BK.
     * Hanya RINGKASAN AMAN (tanpa topik/detail) sesuai aturan kerahasiaan:
     * Siswa/Orang Tua hanya boleh melihat jadwal, bukan detail catatan layanan BK.
     */
    protected function notifyScheduleParticipants(int $recordId): void
    {
        if ($recordId <= 0) {
            return;
        }

        $me = $this->currentUserId();
        foreach ($this->service->notifiableUserIds($recordId) as $uid) {
            $uid = (int) $uid;
            if ($uid <= 0 || $uid === $me) {
                continue;
            }
            $prefix = role_route_prefix($uid);
            send_notification(
                $uid,
                'Jadwal Kegiatan/Acara BK',
                'Ada jadwal kegiatan/acara BK untuk Anda. Silakan cek halaman Jadwal Kegiatan/Acara BK.',
                'session',
                ['bk_service_record_id' => $recordId],
                $prefix !== '' ? site_url($prefix . '/dashboard') : null
            );
        }
    }
}
