<?php

namespace App\Controllers\RoleFeatures;

use App\Controllers\BaseController;
use App\Services\TrashService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * File Path: app/Controllers/RoleFeatures/BaseTrashController.php
 *
 * Controller dasar Tempat Sampah (pemulihan soft delete) untuk semua peran.
 * Setiap peran membuat turunan tipis yang menetapkan routePrefix & viewPrefix.
 */
abstract class BaseTrashController extends BaseController
{
    protected string $routePrefix = '';
    protected string $viewPrefix  = '';
    protected string $roleLabel   = '';

    protected TrashService $trash;

    public function __construct()
    {
        $this->trash = new TrashService();
        helper(['url', 'form', 'app']);
    }

    public function index()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        return view($this->viewPrefix . '/index', [
            'title'     => 'Tempat Sampah',
            'items'     => $this->trash->listForUser($uid),
            'basePath'  => trim($this->routePrefix, '/'),
            'roleLabel' => $this->roleLabel,
        ]);
    }

    public function restore()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        $entity = (string) $this->request->getPost('entity');
        $id     = (int) $this->request->getPost('id');
        $ok     = $this->trash->restore($entity, $id, $uid);

        return redirect()->to(site_url($this->routePrefix . '/trash'))
            ->with($ok ? 'success' : 'error', $ok ? 'Data berhasil dipulihkan.' : 'Data tidak dapat dipulihkan.');
    }

    public function forceDelete()
    {
        $uid = $this->currentUserId();
        if ($uid <= 0) {
            return $this->denyUnauthenticated();
        }

        $entity = (string) $this->request->getPost('entity');
        $id     = (int) $this->request->getPost('id');
        $res    = $this->trash->forceDelete($entity, $id, $uid);

        return redirect()->to(site_url($this->routePrefix . '/trash'))
            ->with($res['success'] ? 'success' : 'error', $res['message']);
    }

    protected function currentUserId(): int
    {
        return (int) (session('user_id') ?? 0);
    }

    protected function denyUnauthenticated(): ResponseInterface
    {
        return redirect()->to(site_url('/login'));
    }
}
