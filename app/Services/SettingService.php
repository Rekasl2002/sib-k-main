<?php

namespace App\Services;

use App\Models\SettingModel;
use CodeIgniter\Database\Config as DB;
use CodeIgniter\HTTP\Files\UploadedFile;

class SettingService
{
    protected SettingModel $model;

    /**
     * Daftar group settings yang dikenali oleh halaman admin/settings.
     */
    protected array $groups = ['general', 'branding', 'academic', 'mail', 'security', 'notifications', 'points'];

    public function __construct()
    {
        $this->model = new SettingModel();
        helper('settings');
    }

    public function listGroups(): array
    {
        return $this->groups;
    }

    public function saveGeneral(array $data): void
    {
        set_setting('general', 'app_name',      trim($data['app_name'] ?? 'SIB-K'));
        set_setting('general', 'school_name',   trim($data['school_name'] ?? ''));
        set_setting('general', 'contact_email', trim($data['contact_email'] ?? ''));
        set_setting('general', 'contact_phone', trim($data['contact_phone'] ?? ''));
        set_setting('general', 'address',       trim($data['address'] ?? ''));
    }

    public function saveBranding(array $files): void
    {
        helper(['filesystem']);

        // public/uploads/branding
        $uploadDir = $this->ensureDir(rtrim(FCPATH . 'uploads/branding', '/\\'));

        /**
         * Konfigurasi per input upload:
         * - logo: lebih longgar (png/jpg/webp)
         * - favicon: biasanya ico/png
         *
         * Catatan keamanan:
         * - Tidak mengizinkan SVG (sering jadi jalur XSS saat disajikan sebagai file publik).
         * - MIME memakai server-side detection: getMimeType() (lebih aman dari getClientMimeType()).
         */
        $map = [
            'logo' => [
                'key'      => 'logo_path',
                'maxBytes' => 2 * 1024 * 1024, // 2 MB
                'allowed'  => ['image/png', 'image/jpeg', 'image/webp'],
            ],
            'favicon' => [
                'key'      => 'favicon_path',
                'maxBytes' => 512 * 1024, // 512 KB
                'allowed'  => [
                    'image/x-icon',
                    'image/vnd.microsoft.icon',
                    'image/png',
                    'image/jpeg',
                    'image/webp',
                ],
            ],
        ];

        foreach ($map as $input => $cfg) {
            $file = $files[$input] ?? null;

            if (!($file instanceof UploadedFile)) {
                continue;
            }
            if (!$file->isValid() || $file->hasMoved()) {
                continue;
            }

            // Batasi ukuran file
            if ($file->getSize() > $cfg['maxBytes']) {
                continue;
            }

            // MIME whitelist (server-side, lebih aman)
            $mime = $file->getMimeType() ?? '';
            if (!in_array($mime, $cfg['allowed'], true)) {
                continue;
            }

            $newName = $file->getRandomName();

            try {
                $file->move($uploadDir, $newName);
            } catch (\Throwable $e) {
                // Jika move gagal, jangan lanjut menyimpan setting
                continue;
            }

            // Simpan path relatif (akses di view via base_url())
            $newRel = 'uploads/branding/' . $newName;
            $oldRel = setting($cfg['key'], null, 'branding');

            set_setting('branding', $cfg['key'], $newRel);

            // Hapus file lama (kalau memang file branding)
            $this->deleteOldBrandingFile($oldRel);
        }
    }

    public function saveAcademic(array $data): bool
    {
        $yearId = (int) ($data['default_academic_year_id'] ?? 0);
        if ($yearId <= 0) {
            return false;
        }

        $db = DB::connect();

        // Pastikan ID tahun ajaran ada
        $exists = $db->table('academic_years')
            ->select('id')
            ->where('id', $yearId)
            ->get(1)
            ->getRowArray();

        if (!$exists) {
            return false;
        }

        $db->transStart();

        // 1) Simpan ke settings (fallback/konfigurasi)
        set_setting('academic', 'default_academic_year_id', $yearId, 'int');

        // 2) Sinkronkan ke tabel academic_years (jadikan yang dipilih = aktif)
        $db->table('academic_years')->set('is_active', 0)->update();
        $db->table('academic_years')->where('id', $yearId)->set('is_active', 1)->update();

        $db->transComplete();
        return $db->transStatus();
    }

    public function saveMail(array $data): void
    {
        /**
         * Catatan:
         * - Kredensial sensitif (password) tetap di .env (jangan simpan di DB).
         * - Agar tidak "mematikan" SMTP, field host/port hanya disimpan bila memang diisi.
         */
        set_setting('mail', 'from_name',  trim($data['from_name'] ?? ''));
        set_setting('mail', 'from_email', trim($data['from_email'] ?? ''));

        $host = trim((string) ($data['host'] ?? ''));
        if ($host !== '') {
            set_setting('mail', 'host', $host);
        }

        $port = (int) ($data['port'] ?? 0);
        if ($port > 0) {
            set_setting('mail', 'port', $port, 'int');
        }

        $crypto = trim((string) ($data['crypto'] ?? ''));
        if ($crypto !== '') {
            // Batasi nilai yang umum dipakai
            $allowedCrypto = ['tls', 'ssl', 'starttls', 'none'];
            $crypto = in_array(strtolower($crypto), $allowedCrypto, true) ? strtolower($crypto) : 'tls';
            set_setting('mail', 'crypto', $crypto);
        }
    }

    public function saveSecurity(array $data): void
    {
        // Clamp nilai agar tidak negatif/aneh
        $timeout = (int) ($data['session_timeout_minutes'] ?? 60);
        if ($timeout < 5) {
            $timeout = 5;
        }

        $minLen = (int) ($data['password_min_length'] ?? 8);
        if ($minLen < 6) {
            $minLen = 6;
        }
        if ($minLen > 64) {
            $minLen = 64;
        }

        set_setting('security', 'session_timeout_minutes', $timeout, 'int');
        set_setting('security', 'password_min_length', $minLen, 'int');
        set_setting('security', 'login_captcha', !empty($data['login_captcha']), 'bool');
    }

    public function savePoints(array $data): void
    {
        $probation = (int) ($data['probation_threshold'] ?? 50);
        $warning   = (int) ($data['warning_threshold'] ?? 25);

        if ($probation < 0) $probation = 0;
        if ($warning < 0) $warning = 0;

        set_setting('points', 'probation_threshold', $probation, 'int');
        set_setting('points', 'warning_threshold', $warning, 'int');
    }

    public function saveNotifications(array $data): void
    {
        // checkbox -> true jika ada isinya
        $email    = !empty($data['enable_email']);
        $internal = !empty($data['enable_internal']);

        set_setting('notifications', 'enable_email', $email, 'bool');
        set_setting('notifications', 'enable_internal', $internal, 'bool');

        /**
         * Penting:
         * - Jika form admin/settings saat ini tidak punya field enable_sms / enable_whatsapp,
         *   jangan paksa tersimpan false (biar tidak "mematikan" fitur future).
         * - Kalau nanti kamu menambahkan checkbox sms/whatsapp, sebaiknya sertakan hidden input
         *   value=0 agar key tetap terkirim saat checkbox tidak dicentang.
         */
        if (array_key_exists('enable_sms', $data)) {
            $sms = !empty($data['enable_sms']);
            set_setting('notifications', 'enable_sms', $sms, 'bool');
        }

        if (array_key_exists('enable_whatsapp', $data)) {
            $whatsapp = !empty($data['enable_whatsapp']);
            set_setting('notifications', 'enable_whatsapp', $whatsapp, 'bool');
        }
    }

    // ---------------------------------------------------------------------
    // Helpers (private)
    // ---------------------------------------------------------------------

    private function ensureDir(string $path): string
    {
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
        return $path;
    }

    private function deleteOldBrandingFile(?string $oldRel): void
    {
        if (!$oldRel) {
            return;
        }

        // Kompatibel PHP 7.4+ (tanpa str_starts_with)
        $prefix = 'uploads/branding/';
        if (strpos($oldRel, $prefix) !== 0) {
            return;
        }

        $full = FCPATH . $oldRel;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
