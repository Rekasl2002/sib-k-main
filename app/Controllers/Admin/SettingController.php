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
            'app_name' => ['label' => 'Nama Aplikasi', 'rules' => 'permit_empty|string|min_length[2]|max_length[100]'],
            'school_name' => ['label' => 'Nama Sekolah', 'rules' => 'permit_empty|string|max_length[150]'],
            'contact_email' => ['label' => 'Email Kontak', 'rules' => 'permit_empty|valid_email|max_length[150]'],
            'from_email' => ['label' => 'Email Pengirim', 'rules' => 'permit_empty|valid_email|max_length[150]'],

            // Academic year: optional, tapi kalau ada harus integer > 0
            'default_academic_year_id' => ['label' => 'Tahun Ajaran Aktif', 'rules' => 'permit_empty|is_natural_no_zero'],

            // Batas tingkat kelas (1-12)
            'grade_level_min' => ['label' => 'Tingkat Kelas Minimal', 'rules' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[12]'],
            'grade_level_max' => ['label' => 'Tingkat Kelas Maksimal', 'rules' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[12]'],

            // Security
            'session_timeout_minutes' => ['label' => 'Batas Waktu Sesi (menit)', 'rules' => 'permit_empty|integer|greater_than_equal_to[5]|less_than_equal_to[1440]'],
            'password_min_length' => ['label' => 'Panjang Password Minimal', 'rules' => 'permit_empty|integer|greater_than_equal_to[6]|less_than_equal_to[64]'],

            // Mail override (optional)
            'host' => ['label' => 'Host Email (SMTP)', 'rules' => 'permit_empty|string|max_length[150]'],
            'port' => ['label' => 'Port Email (SMTP)', 'rules' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[65535]'],
            'crypto' => ['label' => 'Enkripsi Email (SMTP)', 'rules' => 'permit_empty|in_list[tls,ssl,starttls,none]'],
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

            $this->service->saveGradeRange($post);
            $this->service->saveNotifications($post);
            $this->service->saveMail($post);
            $this->service->saveSecurity($post);
            $this->service->saveConsultation($post);
            $this->service->saveNotificationMatrix($post);

        } catch (Throwable $e) {
            // Jangan tampilkan stack trace di UI user biasa, cukup pesan ringkas
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan pengaturan: ' . $e->getMessage());
        }

        return redirect()->to(site_url('admin/settings'))
            ->with('success', 'Pengaturan tersimpan');
    }

    /**
     * Reset seluruh data aplikasi ke kondisi awal pakai (satu klik dari
     * Pengaturan Admin): semua tabel data dikosongkan lalu diisi ulang data
     * awal + data contoh (DatabaseSeeder), dan berkas unggahan dibersihkan.
     *
     * Pengaman: wajib mengetik "RESET" + memasukkan password akun admin
     * yang sedang login.
     */
    public function reset(): RedirectResponse
    {
        require_permission('manage_settings');

        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return redirect()->to(site_url('admin/settings'))
                ->with('error', 'Metode tidak valid.');
        }

        $confirmText     = trim((string) $this->request->getPost('confirm_text'));
        $confirmPassword = (string) $this->request->getPost('confirm_password');

        if ($confirmText !== 'RESET') {
            return redirect()->to(site_url('admin/settings'))
                ->with('error', 'Reset dibatalkan: ketik RESET (huruf besar) pada kolom konfirmasi.');
        }

        $userId = (int) (session('user_id') ?? session('id') ?? 0);
        $user   = $userId > 0
            ? (new \App\Models\UserModel())->asArray()->find($userId)
            : null;

        if (! $user || ! password_verify($confirmPassword, (string) ($user['password_hash'] ?? ''))) {
            return redirect()->to(site_url('admin/settings'))
                ->with('error', 'Reset dibatalkan: password konfirmasi salah.');
        }

        try {
            // 1) Bersihkan berkas unggahan (lampiran, foto profil, logo), sisakan
            //    struktur folder dan berkas pengaman index.html.
            $this->purgeUploadedFiles(WRITEPATH . 'uploads');
            $this->purgeUploadedFiles(FCPATH . 'uploads');

            // 2) Isi ulang basis data (kosongkan tabel + data awal + data contoh).
            //    Output echo seeder ditampung agar tidak bocor ke halaman.
            ob_start();
            \Config\Database::seeder()->call(\App\Database\Seeds\DatabaseSeeder::class);
            ob_end_clean();

            log_message('warning', 'Reset data aplikasi dijalankan oleh user #' . $userId . ' (' . ($user['username'] ?? '-') . ').');
        } catch (Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            log_message('error', 'Gagal reset data aplikasi: ' . $e->getMessage());

            return redirect()->to(site_url('admin/settings'))
                ->with('error', 'Reset gagal: ' . $e->getMessage());
        }

        // 3) Akun lama sudah diganti akun bawaan -> semua sesi wajib masuk ulang.
        session()->destroy();

        return redirect()->to(site_url('login'))
            ->with('success', 'Reset selesai. Data aplikasi kembali ke kondisi awal. Silakan masuk dengan akun bawaan (mis. admin_1 / admin123) lalu segera ganti password.');
    }

    /**
     * Hapus semua berkas di dalam $dir secara rekursif, kecuali berkas
     * penjaga struktur (index.html dan .gitkeep), tanpa menghapus foldernya.
     */
    private function purgeUploadedFiles(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $keep = ['index.html', '.gitkeep'];

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            /** @var \SplFileInfo $item */
            if ($item->isFile() && ! in_array(strtolower($item->getFilename()), $keep, true)) {
                @unlink($item->getPathname());
            }
        }
    }
}
