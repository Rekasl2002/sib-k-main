<?php

namespace App\Controllers\RoleFeatures;

use App\Controllers\BaseController;
use App\Services\ViolationSubmissionService;

abstract class BaseViolationSubmissionReviewController extends BaseController
{
    protected string $routePrefix = '';
    protected string $viewPrefix = '';
    protected string $roleLabel = '';

    protected ViolationSubmissionService $service;

    public function __construct()
    {
        $this->service = new ViolationSubmissionService();
        helper(['form', 'url']);
    }

    public function index()
    {
        $filters = [
            'q'             => trim((string)$this->request->getGet('q')),
            'status'        => trim((string)$this->request->getGet('status')),
            'reporter_type' => trim((string)$this->request->getGet('reporter_type')),
        ];

        return $this->render('index', [
            'title'   => 'Tinjau Pengaduan Pelanggaran',
            'rows'    => $this->service->listForReviewer($filters),
            'filters' => $filters,
        ]);
    }

    public function show($id)
    {
        $row = $this->service->getDetailForReviewer((int)$id);
        if (!$row) {
            return redirect()->to(site_url($this->routePrefix . '/violation-submissions'))
                ->with('error', 'Pengaduan tidak ditemukan.');
        }

        return $this->render('show', [
            'title' => 'Detail Pengaduan Pelanggaran',
            'row'   => $row,
        ]);
    }

    public function updateStatus($id)
    {
        $status = trim((string)$this->request->getPost('status'));
        $notes  = trim((string)$this->request->getPost('review_notes'));

        $result = $this->service->reviewStatus(
            (int)$id,
            $status,
            $notes,
            $this->currentUserId()
        );

        return redirect()->to(site_url($this->routePrefix . '/violation-submissions/show/' . (int)$id))
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    protected function currentUserId(): int
    {
        return (int)(session('user_id') ?? 0);
    }

    protected function render(string $view, array $data = [])
    {
        return view($this->viewPrefix . '/' . $view, array_merge([
            'basePath'  => trim($this->routePrefix, '/'),
            'roleLabel' => $this->roleLabel,
        ], $data));
    }
}
