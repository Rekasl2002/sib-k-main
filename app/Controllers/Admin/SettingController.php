<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AcademicYearModel;
use App\Services\SettingService;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;
use Throwable;

class SettingController extends BaseController
{
    protected SettingService $service;

    public function __construct()
    {
        helper('settings');

        /**
         * Fallback: pastikan require_permission() tersedia.
         * Di beberapa proyek, fungsi ini ada di helper auth/permission/rbac.
         * Kita coba load tanpa bikin fatal jika helper tidak ada.
         */
        if (! function_exists('require_permission')) {
            foreach (['auth', 'permission', 'rbac'] as $h) {
                try {
                    helper($h);
                } catch (Throwable $e) {
                    // ignore
                }
                if (function_exists('require_permission')) {
                    break;
                }
            }
        }

        $this->service = new SettingService();
    }

    public function index()
    {
        require_permission('manage_settings'); // RBAC via Filter/Helper

        $years = (new AcademicYearModel())
            ->orderBy('start_date', 'DESC')
            ->findAll();

        $data = [
            'title'      => 'Pengaturan Aplikasi',
            'page_title' => 'Pengaturan Aplikasi',
            'groups'     => $this->service->listGroups(),
            'years'      => $years,
            'validation' => Services::validation(),
        ];

        return view('admin/settings/index', $data);
    }

    public function update(): RedirectResponse
    {
        require_permission('manage_settings');

        // Pastikan hanya menerima POST
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return redirect()->to(site_url('admin/settings'))
                ->with('error', 'Metode tidak valid.');
        }

        $post  = $this->request->getPost();
        $files = $this->request->getFiles();

        /**
         * Validasi ringkas tapi lebih aman:
         * - Angka dikunci minimal/ maksimal wajar
         * - Email valid
         * - Crypto dibatasi nilai umum
         * Catatan: Upload file divalidasi di SettingService (MIME/size/move).
         */
        $rules = [
            'app_name' => 'permit_empty|string|min_length[2]|max_length[100]',
            'school_name' => 'permit_empty|string|max_length[150]',
            'contact_email' => 'permit_empty|valid_email|max_length[150]',
            'from_email' => 'permit_empty|valid_email|max_length[150]',

            // Academic year: optional, tapi kalau ada harus integer > 0
            'default_academic_year_id' => 'permit_empty|is_natural_no_zero',

            // Points
            'warning_threshold' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100000]',
            'probation_threshold' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100000]',

            // Security
            'session_timeout_minutes' => 'permit_empty|integer|greater_than_equal_to[5]|less_than_equal_to[1440]',
            'password_min_length' => 'permit_empty|integer|greater_than_equal_to[6]|less_than_equal_to[64]',

            // Mail override (optional)
            'host' => 'permit_empty|string|max_length[150]',
            'port' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[65535]',
            'crypto' => 'permit_empty|in_list[tls,ssl,starttls,none]',
        ];

        if (! $this->validate($rules)) {
            // Kirim error detail ke session agar view bisa menampilkan
            return redirect()->back()
                ->withInput()
                ->with('error', 'Validasi gagal.')
                ->with('errors', $this->validator->getErrors());
        }

        try {
            // Persist per tab
            $this->service->saveGeneral($post);
            $this->service->saveBranding($files);

            // Academic: kalau input diisi tapi gagal, berikan warning
            $academicInput = (int) ($post['default_academic_year_id'] ?? 0);
            if ($academicInput > 0) {
                $ok = $this->service->saveAcademic($post);
                if (! $ok) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Gagal menyimpan Tahun Ajaran. Pastikan ID Tahun Ajaran valid.');
                }
            }

            $this->service->saveNotifications($post);
            $this->service->saveMail($post);
            $this->service->saveSecurity($post);
            $this->service->savePoints($post);

        } catch (Throwable $e) {
            // Jangan tampilkan stack trace di UI user biasa, cukup pesan ringkas
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan pengaturan: ' . $e->getMessage());
        }

        return redirect()->to(site_url('admin/settings'))
            ->with('success', 'Pengaturan tersimpan');
    }
}
