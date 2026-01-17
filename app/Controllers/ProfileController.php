<?php

/**
 * File Path: app/Controllers/ProfileController.php
 *
 * Profile Controller
 * - Tampilkan profil user + konteks siswa/orang tua (read-only)
 * - Update profil (field dibatasi policy per role)
 * - Upload foto profil (resize/fit)
 * - Ganti password
 *
 * Catatan:
 * - Controller ini mengandalkan filter route: auth (+ csrf untuk POST)
 */

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\StudentModel;
use Config\Services;
use CodeIgniter\Database\BaseConnection;

class ProfileController extends BaseController
{
    protected UserModel $userModel;
    protected StudentModel $studentModel;
    protected BaseConnection $db;

    /**
     * Folder relatif di public/ untuk menyimpan foto profil.
     * Contoh: public/uploads/profile_photos/{uid}/avatar_...
     */
    private string $profileDirRel = 'uploads/profile_photos';

    public function __construct()
    {
        $this->userModel    = new UserModel();
        $this->studentModel = new StudentModel();
        $this->db           = \Config\Database::connect();
    }

    /**
     * Halaman profil utama
     */
    public function index()
    {
        $uid = (int) session()->get('user_id');
        if (!$uid) {
            return redirect()->to('/login');
        }

        // Ambil user + minimal field
        $user = $this->userModel
            ->select('id, role_id, username, email, full_name, phone, profile_photo, is_active, created_at, updated_at')
            ->find($uid);

        if (!$user) {
            return redirect()->to('/login')->with('error', 'Akun tidak ditemukan.');
        }

        // Role ID sebaiknya mengacu ke DB (lebih stabil daripada session jika stale)
        $roleId = (int) ($user['role_id'] ?? session()->get('role_id') ?? 0);

        // (Opsional hardening) selaraskan session bila beda
        try {
            if ((int) session()->get('role_id') !== $roleId) {
                session()->set('role_id', $roleId);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Ambil nama role dari table roles
        $roleRow = $this->db->table('roles')
            ->select('role_name')
            ->where('id', $roleId)
            ->get()
            ->getRowArray();

        $roleName = $roleRow['role_name'] ?? 'User';

        // Kebijakan edit per role (dipakai view)
        $policy = $this->getEditPolicy($roleId);

        // Tambahan konteks untuk siswa & orang tua (read-only)
        $student  = null;
        $children = [];

        /**
         * FIX SCHEMA:
         * - students.full_name sudah dihapus
         * - Nama siswa/anak diambil dari users.full_name via students.user_id
         */
        if ($roleName === 'Siswa') {
            $student = $this->db->table('students s')
                ->select('
                    s.id,
                    s.user_id,
                    s.nisn,
                    s.nis,
                    s.class_id,
                    u.full_name,
                    c.class_name,
                    c.grade_level,
                    c.major
                ')
                ->join('users u', 'u.id = s.user_id', 'left')
                ->join('classes c', 'c.id = s.class_id', 'left')
                ->where('s.user_id', $uid)
                ->where('s.deleted_at', null)
                ->get()
                ->getRowArray();
        } elseif ($roleName === 'Orang Tua') {
            $children = $this->db->table('students s')
                ->select('
                    s.id,
                    s.user_id,
                    u.full_name,
                    s.nisn,
                    s.nis,
                    s.class_id,
                    c.class_name,
                    c.grade_level,
                    c.major
                ')
                ->join('users u', 'u.id = s.user_id', 'left')
                ->join('classes c', 'c.id = s.class_id', 'left')
                ->where('s.parent_id', $uid)
                ->where('s.deleted_at', null)
                ->orderBy('u.full_name', 'ASC')
                ->get()
                ->getResultArray();
        }

        return view('profile/index', [
            'title'    => 'Profil Pengguna',
            'user'     => $user,
            'roleName' => $roleName,
            'editable' => $policy['editable'],
            'readonly' => $policy['readonly'],
            'student'  => $student,
            'children' => $children,
        ]);
    }

    private function normalizeEditable(array $policy): array
    {
        if (isset($policy['editable']) && is_array($policy['editable'])) {
            return $policy['editable'];
        }
        // Kompatibel dengan struktur user_fields/student_fields/parent_fields
        $merged = array_merge(
            $policy['user_fields']    ?? [],
            $policy['student_fields'] ?? [],
            $policy['parent_fields']  ?? []
        );
        return array_values(array_unique($merged));
    }

    /**
     * Map angka role_id ke slug yang mudah dibaca.
     * Ada fallback query roles bila id tidak sesuai mapping default.
     */
    private function roleSlug(int $roleId): string
    {
        $slug = match ($roleId) {
            1 => 'admin',
            2 => 'koordinator',
            3 => 'gurubk',
            4 => 'walikelas',
            5 => 'siswa',
            6 => 'orangtua',
            default => '',
        };

        if ($slug !== '') {
            return $slug;
        }

        // Fallback: baca role_name dari DB, lalu normalisasi
        try {
            $row = $this->db->table('roles')
                ->select('role_name')
                ->where('id', $roleId)
                ->get()
                ->getRowArray();

            $name = strtolower(trim((string)($row['role_name'] ?? '')));
            return match ($name) {
                'admin' => 'admin',
                'koordinator', 'koordinator bk' => 'koordinator',
                'guru bk', 'konselor', 'counselor' => 'gurubk',
                'wali kelas', 'homeroom teacher' => 'walikelas',
                'siswa', 'student' => 'siswa',
                'orang tua', 'orangtua', 'parent' => 'orangtua',
                default => 'guest',
            };
        } catch (\Throwable $e) {
            return 'guest';
        }
    }

    /**
     * Policy field mana saja yang boleh diedit tiap role (versi field per tabel).
     * (Tidak dipakai langsung; disimpan bila suatu saat perlu.)
     */
    private function getEditPolicyForRole(string $role): array
    {
        $denyAll = [
            'user_fields'    => [],
            'student_fields' => [],
            'parent_fields'  => [],
        ];

        return match ($role) {
            // Penuh (untuk kebutuhan profil dasar)
            'admin', 'koordinator' => [
                'user_fields'    => ['full_name', 'email', 'phone', 'profile_photo'],
                'student_fields' => ['address', 'birth_date', 'gender', 'class_id'],
                'parent_fields'  => ['phone'],
            ],

            // Guru BK & Wali Kelas boleh ubah profil dirinya (bukan data siswa)
            'gurubk', 'walikelas' => [
                'user_fields'    => ['full_name', 'phone', 'profile_photo'],
                'student_fields' => [],
                'parent_fields'  => [],
            ],

            // Siswa (akun sendiri)
            'siswa' => [
                'user_fields'    => ['email', 'phone', 'profile_photo'],
                'student_fields' => [],
                'parent_fields'  => [],
            ],

            // Orang tua (akun sendiri; data anak via permohonan)
            'orangtua' => [
                'user_fields'    => ['email', 'phone', 'profile_photo'],
                'student_fields' => [],
                'parent_fields'  => [],
            ],

            default => $denyAll,
        };
    }

    /**
     * Kebijakan edit yang dipakai view & update():
     * Menghasilkan:
     * - editable: list field tabel users yang boleh diedit
     * - readonly: list field yang ditampilkan read-only (untuk kemudahan view)
     */
    private function getEditPolicy(int $roleId): array
    {
        $slug     = $this->roleSlug($roleId);
        $policy   = $this->getEditPolicyForRole($slug);
        $editable = $this->normalizeEditable($policy);

        // Field yang umum tampil di halaman profil
        $known    = ['username', 'full_name', 'email', 'phone', 'profile_photo'];
        $readonly = array_values(array_diff($known, $editable));

        return [
            'editable' => $editable,
            'readonly' => $readonly,
        ];
    }

    public function update()
    {
        helper(['form']);

        $uid = (int) session()->get('user_id');
        if (!$uid) {
            return redirect()->to('/login')->with('error', 'Sesi berakhir. Silakan login ulang.');
        }

        // Pastikan method POST (hardening)
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/profile');
        }

        // Kebijakan edit untuk role saat ini
        $roleId   = (int) session()->get('role_id');
        $policy   = $this->getEditPolicy($roleId);
        $editable = $policy['editable'] ?? [];

        // Ambil data lama (untuk cleanup file setelah sukses)
        $old = $this->getCurrentUserPhotoInfo($uid);

        $upload = null;

        // Ambil file (dukung alias lama "photo")
        $fileField = 'profile_photo';
        $file = $this->request->getFile('profile_photo');
        if ((!$file || $file->getError() === UPLOAD_ERR_NO_FILE)) {
            $file = $this->request->getFile('photo');
            $fileField = 'photo';
        }
        $hasUpload = $file && $file->isValid() && $file->getError() !== UPLOAD_ERR_NO_FILE;

        // Validasi file hanya jika ada unggahan & role boleh
        if (in_array('profile_photo', $editable, true) && $hasUpload) {
            $rules = [
                $fileField => 'is_image[' . $fileField . ']'
                    . '|mime_in[' . $fileField . ',image/jpg,image/jpeg,image/png,image/webp]'
                    . '|ext_in[' . $fileField . ',jpg,jpeg,png,webp]'
                    . '|max_size[' . $fileField . ',2048]',
            ];
            if (!$this->validate($rules)) {
                return redirect()->to('/profile')
                    ->with('error', 'Periksa kembali input Anda.')
                    ->with('errors', $this->validator->getErrors())
                    ->withInput();
            }
        }

        // Kumpulkan field teks yang boleh & benar-benar terkirim
        $data = [];
        foreach (['full_name', 'email', 'phone'] as $f) {
            if (!in_array($f, $editable, true)) {
                continue;
            }
            $raw = $this->request->getPost($f);
            if ($raw === null) {
                continue; // field disabled tidak terkirim -> jangan set
            }
            $val = trim((string) $raw);

            if ($f === 'full_name') {
                if ($val === '') {
                    return redirect()->to('/profile')
                        ->with('error', 'Nama lengkap tidak boleh kosong.')
                        ->withInput();
                }
                if (mb_strlen($val) < 3) {
                    return redirect()->to('/profile')
                        ->with('error', 'Nama lengkap terlalu pendek.')
                        ->withInput();
                }
            }

            if ($f === 'email') {
                if ($val === '') {
                    return redirect()->to('/profile')->with('error', 'Email tidak boleh kosong.')->withInput();
                }
                if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    return redirect()->to('/profile')->with('error', 'Format email tidak valid.')->withInput();
                }
                // Cek unik (kecuali milik sendiri)
                $dup = $this->db->table('users')
                    ->select('id')
                    ->where('LOWER(email)', strtolower($val))
                    ->where('id !=', $uid)
                    ->get()
                    ->getRowArray();
                if ($dup) {
                    return redirect()->to('/profile')->with('error', 'Email sudah dipakai pengguna lain.')->withInput();
                }
                $val = strtolower($val);
            }

            if ($f === 'phone' && $val !== '') {
                if (!preg_match('~^[0-9+()\s-]{6,20}$~', $val)) {
                    return redirect()->to('/profile')->with('error', 'Nomor telepon tidak valid.')->withInput();
                }
            }

            $data[$f] = ($val === '' && $f === 'phone') ? null : $val;
        }

        // Simpan foto & path relatif (public/)
        if (in_array('profile_photo', $editable, true) && $hasUpload) {
            $upload = $this->saveProfilePhotoProcessed($uid, $file);
            if (!$upload['ok']) {
                return redirect()->to('/profile')
                    ->with('error', $upload['message'] ?? 'Gagal mengunggah foto. Coba lagi.')
                    ->withInput();
            }

            $data['profile_photo'] = $upload['avatar_rel'];

            if (!empty($upload['original_rel']) && $this->usersTableHasColumn('profile_photo_original')) {
                $data['profile_photo_original'] = $upload['original_rel'];
            }
        }

        if (empty($data)) {
            return redirect()->to('/profile')->with('info', 'Tidak ada perubahan.');
        }

        // Atomic-ish: update DB, jika gagal -> hapus file baru (jika ada)
        $this->db->transBegin();

        try {
            // Set updated_at jika model tidak auto timestamps, aman juga jika ada
            $data['updated_at'] = date('Y-m-d H:i:s');

            $ok = $this->userModel->skipValidation(true)->update($uid, $data);
            if (!$ok) {
                $this->db->transRollback();

                // Cleanup file baru jika DB gagal
                if (is_array($upload) && !empty($upload['avatar_abs'])) {
                    @is_file($upload['avatar_abs']) && @unlink($upload['avatar_abs']);
                }
                if (is_array($upload) && !empty($upload['original_abs'])) {
                    @is_file($upload['original_abs']) && @unlink($upload['original_abs']);
                }

                log_message(
                    'error',
                    'Profile update failed: {errors}',
                    ['errors' => json_encode($this->userModel->errors() ?? [])]
                );

                return redirect()->to('/profile')
                    ->with('error', 'Gagal menyimpan perubahan.')
                    ->with('errors', $this->userModel->errors() ?? []);
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();

            // Cleanup file baru jika exception
            if (is_array($upload) && !empty($upload['avatar_abs'])) {
                @is_file($upload['avatar_abs']) && @unlink($upload['avatar_abs']);
            }
            if (is_array($upload) && !empty($upload['original_abs'])) {
                @is_file($upload['original_abs']) && @unlink($upload['original_abs']);
            }

            log_message('error', 'Profile update exception: {msg}', ['msg' => $e->getMessage()]);
            return redirect()->to('/profile')->with('error', 'Terjadi kesalahan saat menyimpan perubahan.');
        }

        // Refresh session supaya sidebar/topbar ikut ganti
        foreach (['full_name', 'email', 'phone', 'profile_photo'] as $k) {
            if (array_key_exists($k, $data)) {
                session()->set($k, $data[$k]);
            }
        }
        if (is_array($upload) && !empty($upload['original_rel'])) {
            session()->set('profile_photo_original', $upload['original_rel']);
        }

        // Cleanup foto lama (hanya jika path aman & berbeda)
        if (!empty($upload['avatar_rel'])) {
            $this->safeDeleteUserPhotoIfChanged($uid, $old['profile_photo'] ?? null, $upload['avatar_rel']);
        }
        if (!empty($upload['original_rel']) && $this->usersTableHasColumn('profile_photo_original')) {
            $this->safeDeleteUserPhotoIfChanged($uid, $old['profile_photo_original'] ?? null, $upload['original_rel']);
        }

        // (Opsional) rapikan folder, simpan N file terbaru
        $this->cleanupOldProfilePhotos($uid, 20);

        return redirect()->to('/profile')->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * POST /profile/change-password
     */
    public function changePassword()
    {
        $uid = (int) session('user_id');
        if (!$uid) {
            return redirect()->to('/login');
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->back();
        }

        $validation = Services::validation();

        $rules = [
            'current_password'       => 'required|min_length[6]|max_length[72]',
            'new_password'           => 'required|min_length[8]|max_length[72]',
            'new_password_confirm'   => 'required|matches[new_password]',
        ];

        $messages = [
            'current_password' => [
                'required'   => 'Password saat ini wajib diisi.',
                'min_length' => 'Password saat ini terlalu pendek.',
                'max_length' => 'Password saat ini terlalu panjang.',
            ],
            'new_password' => [
                'required'   => 'Password baru wajib diisi.',
                'min_length' => 'Password baru minimal 8 karakter.',
                'max_length' => 'Password baru terlalu panjang.',
            ],
            'new_password_confirm' => [
                'required' => 'Konfirmasi password baru wajib diisi.',
                'matches'  => 'Konfirmasi tidak sama dengan password baru.',
            ],
        ];

        $validation->setRules($rules, $messages);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('pw_errors', $validation->getErrors());
        }

        $current = (string) $this->request->getPost('current_password');
        $new     = (string) $this->request->getPost('new_password');

        // Ambil hash password user
        $row = $this->db->table('users')
            ->select('id, password_hash')
            ->where('id', $uid)
            ->get()
            ->getRowArray();

        if (!$row) {
            return redirect()->to('/login')->with('error', 'Akun tidak ditemukan.');
        }

        $hash = (string) ($row['password_hash'] ?? '');

        if ($hash === '' || !password_verify($current, $hash)) {
            return redirect()->back()
                ->with('pw_errors', ['current_password' => 'Password saat ini salah.']);
        }

        // Cegah pakai password yang sama
        if (password_verify($new, $hash)) {
            return redirect()->back()
                ->with('pw_errors', ['new_password' => 'Password baru tidak boleh sama dengan password saat ini.']);
        }

        $newHash = password_hash($new, PASSWORD_DEFAULT);

        $payload = [
            'password_hash' => $newHash,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        // Kompatibilitas bila project lama punya kolom "password"
        if ($this->usersTableHasColumn('password')) {
            $payload['password'] = $newHash;
        }

        // Opsional: simpan timestamp perubahan password jika kolom ada
        if ($this->usersTableHasColumn('password_changed_at')) {
            $payload['password_changed_at'] = date('Y-m-d H:i:s');
        }

        $this->db->table('users')
            ->where('id', $uid)
            ->update($payload);

        // Hardening kecil: ganti session id
        try { session()->regenerate(true); } catch (\Throwable $e) {}

        return redirect()->back()->with('pw_success', 'Password berhasil diganti.');
    }

    /**
     * POST /profile/upload-photo
     */
    public function uploadPhoto()
    {
        $uid = (int) session('user_id');
        if (!$uid) {
            return redirect()->to('/login')->with('error', 'Sesi berakhir. Silakan login ulang.');
        }

        // Pastikan method POST (hardening)
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/profile');
        }

        $roleId   = (int) session('role_id');
        $policy   = $this->getEditPolicy($roleId);
        $editable = $policy['editable'] ?? [];

        if (!in_array('profile_photo', $editable, true)) {
            return redirect()->to('/profile')->with('error', 'Anda tidak diizinkan mengubah foto profil.');
        }

        $old = $this->getCurrentUserPhotoInfo($uid);

        $file = $this->request->getFile('profile_photo');
        if (!$file || !$file->isValid() || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return redirect()->to('/profile')->with('error', 'Tidak ada file yang diunggah.');
        }

        $rules = [
            'profile_photo' => 'is_image[profile_photo]'
                . '|mime_in[profile_photo,image/jpg,image/jpeg,image/png,image/webp]'
                . '|ext_in[profile_photo,jpg,jpeg,png,webp]'
                . '|max_size[profile_photo,2048]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->to('/profile')
                ->with('error', 'Periksa kembali file Anda.')
                ->with('errors', $this->validator->getErrors());
        }

        $upload = $this->saveProfilePhotoProcessed($uid, $file);
        if (!$upload['ok']) {
            return redirect()->to('/profile')->with('error', $upload['message'] ?? 'Gagal menyimpan foto.');
        }

        $updateData = [
            'profile_photo' => $upload['avatar_rel'],
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        if (!empty($upload['original_rel']) && $this->usersTableHasColumn('profile_photo_original')) {
            $updateData['profile_photo_original'] = $upload['original_rel'];
        }

        // Update DB (kalau gagal, hapus file baru)
        $this->db->transBegin();
        try {
            $ok = $this->userModel->skipValidation(true)->update($uid, $updateData);
            if (!$ok) {
                $this->db->transRollback();

                if (!empty($upload['avatar_abs'])) {
                    @is_file($upload['avatar_abs']) && @unlink($upload['avatar_abs']);
                }
                if (!empty($upload['original_abs'])) {
                    @is_file($upload['original_abs']) && @unlink($upload['original_abs']);
                }

                return redirect()->to('/profile')->with('error', 'Gagal menyimpan perubahan foto profil.');
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();

            if (!empty($upload['avatar_abs'])) {
                @is_file($upload['avatar_abs']) && @unlink($upload['avatar_abs']);
            }
            if (!empty($upload['original_abs'])) {
                @is_file($upload['original_abs']) && @unlink($upload['original_abs']);
            }

            log_message('error', 'Upload photo exception: {msg}', ['msg' => $e->getMessage()]);
            return redirect()->to('/profile')->with('error', 'Terjadi kesalahan saat menyimpan foto profil.');
        }

        session()->set('profile_photo', $upload['avatar_rel']);
        if (!empty($upload['original_rel'])) {
            session()->set('profile_photo_original', $upload['original_rel']);
        }

        // Cleanup foto lama
        $this->safeDeleteUserPhotoIfChanged($uid, $old['profile_photo'] ?? null, $upload['avatar_rel']);
        if (!empty($upload['original_rel']) && $this->usersTableHasColumn('profile_photo_original')) {
            $this->safeDeleteUserPhotoIfChanged($uid, $old['profile_photo_original'] ?? null, $upload['original_rel']);
        }
        $this->cleanupOldProfilePhotos($uid, 20);

        return redirect()->to('/profile')->with('success', 'Foto profil berhasil diperbarui.');
    }

    /**
     * =========================
     * Helpers: proses foto avatar
     * =========================
     */
    private function saveProfilePhotoProcessed(int $uid, $file): array
    {
        try {
            $targetDir = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR
                . $this->profileDirRel . DIRECTORY_SEPARATOR . $uid;

            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            $ts = time();

            // Simpan original dulu
            $origExt  = strtolower((string) $file->getExtension());
            if (!in_array($origExt, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $origExt = 'jpg';
            }

            $origName = 'avatar_original_' . $uid . '_' . $ts . '.' . $origExt;
            if (!$file->move($targetDir, $origName, true)) {
                return [
                    'ok' => false,
                    'message' => 'Gagal menyimpan file original.',
                    'avatar_rel' => null,
                    'original_rel' => null,
                ];
            }

            $origAbs = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $origName;
            $origRel = $this->profileDirRel . '/' . $uid . '/' . $origName;

            // Output avatar (square)
            $size = 512;

            // Default output jpg (paling kompatibel), webp hanya jika tersedia & aman
            $wantWebp = function_exists('imagewebp');
            $outExt   = $wantWebp ? 'webp' : 'jpg';
            $avatarName = 'avatar_' . $uid . '_' . $ts . '.' . $outExt;

            $avatarAbs = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $avatarName;
            $avatarRel = $this->profileDirRel . '/' . $uid . '/' . $avatarName;

            $image = Services::image();

            try {
                $image->withFile($origAbs)
                    ->fit($size, $size, 'center')
                    ->save($avatarAbs, 90);
            } catch (\Throwable $e) {
                // Fallback: simpan sebagai JPG jika webp gagal
                if ($outExt === 'webp') {
                    $outExt = 'jpg';
                    $avatarName = 'avatar_' . $uid . '_' . $ts . '.jpg';
                    $avatarAbs = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $avatarName;
                    $avatarRel = $this->profileDirRel . '/' . $uid . '/' . $avatarName;

                    $image = Services::image();
                    $image->withFile($origAbs)
                        ->fit($size, $size, 'center')
                        ->save($avatarAbs, 90);
                } else {
                    throw $e;
                }
            }

            // Fallback kalau proses tidak menghasilkan file
            if (!is_file($avatarAbs)) {
                return [
                    'ok' => true,
                    'message' => 'Avatar dipakai dari file original (proses resize tidak tersedia).',
                    'avatar_rel' => $origRel,
                    'original_rel' => $origRel,
                    'avatar_abs' => $origAbs,
                    'original_abs' => $origAbs,
                ];
            }

            return [
                'ok' => true,
                'message' => null,
                'avatar_rel' => $avatarRel,
                'original_rel' => $origRel,
                // extra keys (internal) untuk cleanup jika DB gagal
                'avatar_abs' => $avatarAbs,
                'original_abs' => $origAbs,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Avatar process error: {msg}', ['msg' => $e->getMessage()]);
            return [
                'ok' => false,
                'message' => 'Gagal memproses gambar avatar.',
                'avatar_rel' => null,
                'original_rel' => null,
            ];
        }
    }

    private function usersTableHasColumn(string $column): bool
    {
        try {
            $fields = $this->db->getFieldNames('users');
            return in_array($column, $fields, true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Ambil path foto profil lama dari DB (untuk cleanup yang aman).
     */
    private function getCurrentUserPhotoInfo(int $uid): array
    {
        try {
            $select = ['profile_photo'];
            if ($this->usersTableHasColumn('profile_photo_original')) {
                $select[] = 'profile_photo_original';
            }

            $row = $this->db->table('users')
                ->select(implode(',', $select))
                ->where('id', $uid)
                ->get()
                ->getRowArray();

            return [
                'profile_photo' => $row['profile_photo'] ?? null,
                'profile_photo_original' => $row['profile_photo_original'] ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'profile_photo' => null,
                'profile_photo_original' => null,
            ];
        }
    }

    /**
     * Hapus foto lama dengan aman:
     * - Hanya kalau path berada di folder uploads/profile_photos/{uid}/
     * - Hanya kalau benar-benar berubah (old != new)
     */
    private function safeDeleteUserPhotoIfChanged(int $uid, ?string $oldRel, ?string $newRel): void
    {
        $oldRel = trim((string)$oldRel);
        $newRel = trim((string)$newRel);

        if ($oldRel === '' || $oldRel === $newRel) {
            return;
        }

        $oldRelNorm = ltrim(str_replace('\\', '/', $oldRel), '/');
        $prefix = $this->profileDirRel . '/' . $uid . '/';

        if (!str_starts_with($oldRelNorm, $prefix)) {
            return; // jangan hapus file di luar folder user
        }

        $abs = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . $oldRelNorm;
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    /**
     * Opsional: bersihkan file lama, simpan N file terbaru saja.
     * Aman karena hanya di folder user.
     */
    private function cleanupOldProfilePhotos(int $uid, int $keep = 20): void
    {
        try {
            $dir = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR
                . $this->profileDirRel . DIRECTORY_SEPARATOR . $uid;

            if (!is_dir($dir)) {
                return;
            }

            $files = glob($dir . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);
            if (!$files || count($files) <= $keep) {
                return;
            }

            // sort by mtime DESC
            usort($files, static function ($a, $b) {
                return (@filemtime($b) <=> @filemtime($a));
            });

            $toDelete = array_slice($files, $keep);
            foreach ($toDelete as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        } catch (\Throwable $e) {
            // ignore cleanup failures
        }
    }
}
