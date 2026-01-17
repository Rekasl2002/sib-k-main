<?php
/**
 * File Path: app/Services/UserService.php
 *
 * User Service
 * Business logic layer untuk User management (CodeIgniter 4)
 *
 * Catatan perbaikan:
 * - Mode "ambil semua data" untuk kebutuhan DataTables (pagination di View):
 *   Jika $perPage <= 0 maka query tidak memakai paginate() (return pager = null).
 * - getUserStatistics() menambahkan key 'admin' agar card Admin bisa dihitung langsung.
 * - OrderBy dari GET disaring (whitelist) agar lebih aman.
 * - Setiap hasil query yang diakses sebagai array dipaksa ->asArray() (find/first/paginate).
 * - Hasil getUserWithRole($userId) dipastikan array (konversi jika object).
 * - Sanitasi profile_photo:
 *   - Jika berisi avatar bawaan template (assets/...) atau default lama → dianggap kosong (NULL) agar fallback ke default-avatar.svg.
 * - Upload foto profil:
 *   - Dipindahkan ke public/uploads/users agar bisa ditampilkan langsung (sesuai user_avatar() yang cek FCPATH).
 *
 * Tambahan:
 * - Tidak menambah file baru (hanya perbaikan logic & lokasi penyimpanan upload).
 */

namespace App\Services;

use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\StudentModel;
use App\Models\ClassModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class UserService
{
    /** @var ClassModel */
    protected $classModel;

    /** @var UserModel */
    protected $userModel;

    /** @var RoleModel */
    protected $roleModel;

    /** @var StudentModel */
    protected $studentModel;

    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;

    public function __construct()
    {
        $this->userModel    = new UserModel();
        $this->roleModel    = new RoleModel();
        $this->studentModel = new StudentModel();
        $this->classModel   = new ClassModel();
        $this->db           = \Config\Database::connect();
    }

    /**
     * Get all users with role information.
     *
     * NOTE:
     * - Jika $perPage > 0 : gunakan paginate (server-side pagination).
     * - Jika $perPage <= 0: ambil semua data (untuk DataTables pagination di VIEW).
     *
     * @param array $filters
     *   - role_id?: int
     *   - is_active?: 0|1
     *   - search?: string
     *   - order_by?: string
     *   - order_dir?: 'ASC'|'DESC'
     * @param int $perPage
     * @return array{users: array<int, array>, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getAllUsers($filters = [], $perPage = 10)
    {
        $builder = $this->userModel
            ->asArray()
            ->select('users.*, roles.role_name')
            ->join('roles', 'roles.id = users.role_id', 'left');

        // Filters
        if (isset($filters['role_id']) && $filters['role_id'] !== '') {
            $builder->where('users.role_id', (int) $filters['role_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $builder->where('users.is_active', (int) $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $q = trim((string) $filters['search']);
            $builder->groupStart()
                ->like('users.username', $q)
                ->orLike('users.email', $q)
                ->orLike('users.full_name', $q)
            ->groupEnd();
        }

        /**
         * Order (WHITELIST)
         * Mencegah input order_by yang aneh/berbahaya dari query string.
         */
        $allowedOrderBy = [
            'users.created_at' => 'users.created_at',
            'users.updated_at' => 'users.updated_at',
            'users.last_login' => 'users.last_login',
            'users.full_name'  => 'users.full_name',
            'users.username'   => 'users.username',
            'users.email'      => 'users.email',
            'users.is_active'  => 'users.is_active',
            'roles.role_name'  => 'roles.role_name',
        ];

        $rawOrderBy = (string)($filters['order_by'] ?? 'users.created_at');
        $orderBy    = $allowedOrderBy[$rawOrderBy] ?? 'users.created_at';

        $orderDir = strtoupper((string)($filters['order_dir'] ?? 'DESC'));
        $orderDir = $orderDir === 'ASC' ? 'ASC' : 'DESC';

        $builder->orderBy($orderBy, $orderDir);

        // ✅ Mode VIEW-only (ambil semua data, DataTables yang paginate)
        if ((int)$perPage <= 0) {
            $users = $builder->findAll();

            // Sanitasi avatar agar "avatar template" tidak dianggap foto user
            foreach ($users as &$u) {
                if (is_array($u) && array_key_exists('profile_photo', $u)) {
                    $u['profile_photo'] = $this->sanitizeProfilePhotoOutput($u['profile_photo'] ?? null);
                }
            }
            unset($u);

            return [
                'users' => $users,
                'pager' => null,
            ];
        }

        // Default: paginate server-side
        $users = $builder->paginate((int)$perPage);

        // Sanitasi avatar agar "avatar template" tidak dianggap foto user
        foreach ($users as &$u) {
            if (is_array($u) && array_key_exists('profile_photo', $u)) {
                $u['profile_photo'] = $this->sanitizeProfilePhotoOutput($u['profile_photo'] ?? null);
            }
        }
        unset($u);

        return [
            'users' => $users,
            'pager' => $this->userModel->pager,
        ];
    }

    /**
     * Get user by ID with full details.
     *
     * @param int $userId
     * @return array|null
     */
    public function getUserById($userId)
    {
        /** @var array|object|null $user */
        $user = $this->userModel->getUserWithRole($userId);

        // Pastikan array jika model mengembalikan object
        if (is_object($user)) {
            if (method_exists($user, 'toArray')) {
                $user = $user->toArray();
            } else {
                $user = json_decode(json_encode($user), true) ?? [];
            }
        }

        if (!$user) {
            return null;
        }

        // Sanitasi profile_photo output (template avatar / default lama → null)
        if (array_key_exists('profile_photo', $user)) {
            $user['profile_photo'] = $this->sanitizeProfilePhotoOutput($user['profile_photo'] ?? null);
        }

        // Tambahkan info siswa jika ada
        $student = $this->studentModel->asArray()->where('user_id', $userId)->first();
        if ($student) {
            $user['is_student']   = true;
            $user['student_data'] = $student;
        } else {
            $user['is_student'] = false;
        }

        return $user;
    }

    /**
     * Create new user (+ sinkron students jika role = Siswa).
     * Mengandalkan callback di UserModel untuk meng-hash "password" → "password_hash".
     *
     * @param array $data
     * @return array{success: bool, message: string, user_id: int|null}
     */
    public function createUser($data)
    {
        $this->db->transBegin();

        try {
            // Normalisasi input user
            $userData = [
                'role_id'   => (int)($data['role_id'] ?? 0),
                'username'  => trim((string)($data['username'] ?? '')),
                'email'     => trim((string)($data['email'] ?? '')),
                'password'  => (string)($data['password'] ?? ''), // model callback akan meng-hash
                'full_name' => trim((string)($data['full_name'] ?? '')),
                'phone'     => isset($data['phone']) ? trim((string)$data['phone']) : null,
                'is_active' => (int)($data['is_active'] ?? 1),
            ];

            if ($userData['password'] === '') {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Password diperlukan', 'user_id' => null];
            }

            // Jika ada input profile_photo dari import/form (kadang berisi avatar template), sanitasi dulu
            if (array_key_exists('profile_photo', $data)) {
                $pp = $this->sanitizeProfilePhotoStorage($data['profile_photo'] ?? null);
                if ($pp !== null && $pp !== '') {
                    $userData['profile_photo'] = $pp;
                }
            }

            // Cek unik email & username
            $existsEmail = $this->userModel->asArray()->where('email', $userData['email'])->first();
            if ($existsEmail) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Email sudah digunakan', 'user_id' => null];
            }
            $existsUsername = $this->userModel->asArray()->where('username', $userData['username'])->first();
            if ($existsUsername) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Username sudah digunakan', 'user_id' => null];
            }

            // Insert User
            if (!$this->userModel->insert($userData)) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Gagal menyimpan data user: ' . implode(', ', $this->userModel->errors() ?: []),
                    'user_id' => null,
                ];
            }
            $userId = (int)$this->userModel->getInsertID();

            // Jika role = Siswa → buat record students
            $studentRoleId = $this->getStudentRoleId();
            if ((int)$userData['role_id'] === $studentRoleId) {
                $student = (array)($data['student'] ?? []);

                // Wajib: nisn & nis
                $nisn = trim((string)($student['nisn'] ?? ''));
                $nis  = trim((string)($student['nis']  ?? ''));
                if ($nisn === '' || $nis === '') {
                    throw new \RuntimeException('NISN dan NIS wajib diisi untuk user ber-peran Siswa.');
                }

                // Cek unik NISN / NIS
                $dupNisn = $this->studentModel->asArray()->where('nisn', $nisn)->first();
                if ($dupNisn) {
                    throw new \RuntimeException('NISN sudah terdaftar pada siswa lain.');
                }
                $dupNis = $this->studentModel->asArray()->where('nis', $nis)->first();
                if ($dupNis) {
                    throw new \RuntimeException('NIS sudah terdaftar pada siswa lain.');
                }

                // Siapkan payload lengkap untuk tabel students
                $birthPlace     = isset($student['birth_place']) && $student['birth_place'] !== '' ? trim((string)$student['birth_place']) : null;
                $birthDate      = isset($student['birth_date']) && $student['birth_date'] !== '' ? $student['birth_date'] : null;
                $religion       = isset($student['religion']) && $student['religion'] !== '' ? (string)$student['religion'] : null;
                $address        = isset($student['address']) && $student['address'] !== '' ? trim((string)$student['address']) : null;
                $classId        = isset($student['class_id']) && $student['class_id'] !== '' ? (int)$student['class_id'] : null;
                $parentId       = isset($student['parent_id']) && $student['parent_id'] !== '' ? (int)$student['parent_id'] : null;
                $admissionDate  = isset($student['admission_date']) && $student['admission_date'] !== '' ? $student['admission_date'] : date('Y-m-d');
                $status         = !empty($student['status']) ? (string)$student['status'] : 'Aktif';

                $studentInsert = [
                    'user_id'    => $userId,
                    'full_name'  => $userData['full_name'] ?: null,
                    'nisn'       => $nisn,
                    'nis'        => $nis,
                    'gender'     => $student['gender']   ?? null,
                    'class_id'   => $classId,
                    'birth_place'=> $birthPlace,
                    'birth_date' => $birthDate,
                    'religion'   => $religion,
                    'address'    => $address,
                    'parent_id'  => $parentId,
                    'admission_date' => $admissionDate,
                    'status'     => $status,
                    'total_violation_points' => 0,
                ];

                if (!$this->studentModel->insert($studentInsert)) {
                    $err = implode(', ', $this->studentModel->errors() ?: []);
                    throw new \RuntimeException('Gagal menyimpan data siswa: ' . $err);
                }
            }

            $this->db->transCommit();

            $this->logActivity('create_user', $userId, "User baru dibuat: {$userData['username']}");

            return [
                'success' => true,
                'message' => ((int)$userData['role_id'] === $studentRoleId)
                    ? 'User & Siswa berhasil ditambahkan'
                    : 'User berhasil ditambahkan',
                'user_id' => $userId,
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Error creating user (with student sync): ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'user_id' => null];
        }
    }

    /**
     * Update user data.
     *
     * @param int   $userId
     * @param array $data
     * @return array{success: bool, message: string}
     */
    public function updateUser($userId, $data)
    {
        $this->db->transStart();

        try {
            /** @var array|null $user */
            $user = $this->userModel->asArray()->find($userId);
            if (!$user) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'User tidak ditemukan',
                ];
            }

            $updateData = [
                'role_id'   => $data['role_id'],
                'username'  => $data['username'],
                'email'     => $data['email'],
                'full_name' => $data['full_name'],
                'phone'     => $data['phone']    ?? null,
                'is_active' => $data['is_active'] ?? 1,
            ];

            // Password optional
            if (!empty($data['password'])) {
                $updateData['password'] = $data['password'];
            }

            // Sanitasi profile_photo bila ada
            if (array_key_exists('profile_photo', $data)) {
                $pp = $this->sanitizeProfilePhotoStorage($data['profile_photo'] ?? null);
                $updateData['profile_photo'] = $pp; // boleh null untuk menghapus
            }

            // Cek email unik jika berubah
            if ($data['email'] !== $user['email']) {
                $exists = $this->userModel->asArray()
                    ->where('email', $data['email'])
                    ->where('id !=', (int) $userId)
                    ->first();

                if ($exists) {
                    $this->db->transRollback();
                    return [
                        'success' => false,
                        'message' => 'Email sudah digunakan oleh akun lain',
                    ];
                }
            }

            if (!$this->userModel->update((int) $userId, $updateData)) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Gagal mengupdate data user: ' . implode(', ', $this->userModel->errors() ?: []),
                ];
            }

            // Sinkronkan nama lengkap di tabel students jika user ini adalah siswa
            $studentRoleId = $this->getStudentRoleId();
            if ((int) $data['role_id'] === $studentRoleId) {
                $student = $this->studentModel->asArray()
                    ->select('id')
                    ->where('user_id', (int) $userId)
                    ->first();

                if ($student) {
                    if (!$this->studentModel->update((int) $student['id'], [
                        'full_name' => $data['full_name'],
                    ])) {
                        $this->db->transRollback();
                        return [
                            'success' => false,
                            'message' => 'Gagal mengupdate nama lengkap siswa: ' . implode(', ', $this->studentModel->errors() ?: []),
                        ];
                    }
                }
            }

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                return [
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat mengupdate data',
                ];
            }

            $this->logActivity('update_user', (int) $userId, "User diupdate: {$data['username']}");

            return [
                'success' => true,
                'message' => 'Data user berhasil diupdate',
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Error updating user: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete user (soft delete).
     *
     * @param int $userId
     * @return array{success: bool, message: string}
     */
    public function deleteUser($userId)
    {
        try {
            /** @var array|null $user */
            $user = $this->userModel->asArray()->find($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User tidak ditemukan',
                ];
            }

            if ($userId == session()->get('user_id')) {
                return [
                    'success' => false,
                    'message' => 'Anda tidak dapat menghapus akun Anda sendiri',
                ];
            }

            $student = $this->studentModel->asArray()->where('user_id', $userId)
                ->where('status', 'Aktif')
                ->first();

            if ($student) {
                return [
                    'success' => false,
                    'message' => 'User tidak dapat dihapus karena masih terkait dengan data siswa aktif',
                ];
            }

            if (!$this->userModel->delete($userId)) {
                return [
                    'success' => false,
                    'message' => 'Gagal menghapus user',
                ];
            }

            $this->logActivity('delete_user', $userId, "User dihapus: {$user['username']}");

            return [
                'success' => true,
                'message' => 'User berhasil dihapus',
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error deleting user: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Toggle user active status.
     *
     * @param int $userId
     * @return array{success: bool, message: string, is_active: int}
     */
    public function toggleActive($userId)
    {
        try {
            /** @var array|null $user */
            $user = $this->userModel->asArray()->find($userId);
            if (!$user) {
                return [
                    'success'   => false,
                    'message'   => 'User tidak ditemukan',
                    'is_active' => 0,
                ];
            }

            if ($userId == session()->get('user_id')) {
                return [
                    'success'   => false,
                    'message'   => 'Anda tidak dapat menonaktifkan akun Anda sendiri',
                    'is_active' => $user['is_active'],
                ];
            }

            $newStatus = $user['is_active'] == 1 ? 0 : 1;

            if (!$this->userModel->update($userId, ['is_active' => $newStatus])) {
                return [
                    'success'   => false,
                    'message'   => 'Gagal mengubah status user',
                    'is_active' => $user['is_active'],
                ];
            }

            $statusText = $newStatus == 1 ? 'diaktifkan' : 'dinonaktifkan';
            $this->logActivity('toggle_user_status', $userId, "User {$statusText}: {$user['username']}");

            return [
                'success'   => true,
                'message'   => 'Status user berhasil diubah',
                'is_active' => $newStatus,
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error toggling user status: ' . $e->getMessage());

            return [
                'success'   => false,
                'message'   => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
                'is_active' => 0,
            ];
        }
    }

    /**
     * Change user password.
     *
     * @param int         $userId
     * @param string      $newPassword
     * @param string|null $oldPassword
     * @return array{success: bool, message: string}
     */
    public function changePassword($userId, $newPassword, $oldPassword = null)
    {
        try {
            /** @var array|null $user */
            $user = $this->userModel->asArray()->find($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User tidak ditemukan',
                ];
            }

            if (!empty($oldPassword)) {
                if (!password_verify($oldPassword, $user['password_hash'])) {
                    return [
                        'success' => false,
                        'message' => 'Password lama tidak sesuai',
                    ];
                }
            }

            $update = $this->userModel->update($userId, [
                'password' => (string) $newPassword
            ]);

            if (!$update) {
                return [
                    'success' => false,
                    'message' => 'Gagal mengubah password',
                ];
            }

            $this->logActivity('change_password', $userId, "Password diubah untuk user: {$user['username']}");

            return [
                'success' => true,
                'message' => 'Password berhasil diubah',
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error changing password: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Upload profile photo.
     *
     * @param int                                  $userId
     * @param \CodeIgniter\HTTP\Files\UploadedFile $file
     * @return array{success: bool, message: string, file_path: string|null}
     */
    public function uploadProfilePhoto($userId, $file)
    {
        try {
            /** @var array|null $user */
            $user = $this->userModel->asArray()->find($userId);
            if (!$user) {
                return [
                    'success'   => false,
                    'message'   => 'User tidak ditemukan',
                    'file_path' => null,
                ];
            }

            if (!$file || !$file->isValid()) {
                return [
                    'success'   => false,
                    'message'   => 'File tidak valid',
                    'file_path' => null,
                ];
            }

            // Validasi ukuran/tipe
            if ($file->getSize() > 2 * 1024 * 1024) {
                return [
                    'success'   => false,
                    'message'   => 'Ukuran file melebihi 2MB',
                    'file_path' => null,
                ];
            }
            $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file->getMimeType(), $allowed, true)) {
                return [
                    'success'   => false,
                    'message'   => 'Format file tidak didukung',
                    'file_path' => null,
                ];
            }

            // Generate unique filename
            $newName = 'user_' . (int) $userId . '_' . time() . '.' . $file->getExtension();

            // Simpan ke PUBLIC agar bisa ditampilkan via base_url('uploads/users/...').
            $publicUploadRel = 'uploads/users/';
            $uploadDir = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $publicUploadRel);

            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0775, true);
                }
            }

            if (!is_dir($uploadDir)) {
                return [
                    'success'   => false,
                    'message'   => 'Folder upload tidak tersedia / tidak bisa dibuat: ' . $publicUploadRel,
                    'file_path' => null,
                ];
            }

            if (!$file->move($uploadDir, $newName)) {
                return [
                    'success'   => false,
                    'message'   => 'Gagal mengupload file',
                    'file_path' => null,
                ];
            }

            // Delete old photo if exists (jangan hapus jika itu avatar template/default)
            $oldPhoto = $user['profile_photo'] ?? null;
            $oldRel   = $this->sanitizeProfilePhotoStorage($oldPhoto);

            if (!empty($oldPhoto) && $oldRel !== null) {
                $oldNorm = ltrim(str_replace('\\', '/', (string) $oldPhoto), '/');

                // Jika cuma filename, anggap di uploads/users/
                if (strpos($oldNorm, '/') === false) {
                    $oldAbs = $uploadDir . $oldNorm;
                } else {
                    $oldAbs = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldNorm);
                }

                if (is_file($oldAbs)) {
                    @unlink($oldAbs);
                }
            }

            // Save new photo to database (simpan filename saja; helper akan cari di uploads/users/)
            $update = $this->userModel->update((int) $userId, ['profile_photo' => $newName]);
            if (!$update) {
                return [
                    'success'   => false,
                    'message'   => 'Gagal menyimpan data foto',
                    'file_path' => null,
                ];
            }

            $this->logActivity('upload_photo', (int) $userId, "Foto profil diupload untuk user: {$user['username']}");

            return [
                'success'   => true,
                'message'   => 'Foto profil berhasil diupload',
                'file_path' => $newName,
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error uploading photo: ' . $e->getMessage());

            return [
                'success'   => false,
                'message'   => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
                'file_path' => null,
            ];
        }
    }

    /**
     * Get user statistics.
     *
     * @return array{
     *   total: int,
     *   active: int,
     *   inactive: int,
     *   admin: int,
     *   by_role: array<string, int>
     * }
     */
    public function getUserStatistics()
    {
        $total = (int) $this->db->table('users')
            ->select('COUNT(id) as c')
            ->where('deleted_at', null)
            ->get()->getRow('c');

        $active = (int) $this->db->table('users')
            ->select('COUNT(id) as c')
            ->where(['deleted_at' => null, 'is_active' => 1])
            ->get()->getRow('c');

        $inactive = (int) $this->db->table('users')
            ->select('COUNT(id) as c')
            ->where(['deleted_at' => null, 'is_active' => 0])
            ->get()->getRow('c');

        $roleStats = $this->db->table('users')
            ->select('roles.role_name, COUNT(users.id) as total')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.deleted_at', null)
            ->groupBy('roles.id')
            ->get()
            ->getResultArray();

        $byRole = [];
        foreach ($roleStats as $stat) {
            $byRole[(string) $stat['role_name']] = (int) $stat['total'];
        }

        // ✅ Card Admin butuh angka langsung (fallback jika nama role beda)
        $adminCount = 0;
        if (isset($byRole['Admin'])) {
            $adminCount = (int) $byRole['Admin'];
        } elseif (isset($byRole['Administrator'])) {
            $adminCount = (int) $byRole['Administrator'];
        } elseif (isset($byRole['Administrator Sistem'])) {
            $adminCount = (int) $byRole['Administrator Sistem'];
        }

        return [
            'total'    => $total,
            'active'   => $active,
            'inactive' => $inactive,
            'admin'    => $adminCount,
            'by_role'  => $byRole,
        ];
    }

    /**
     * Get all roles for dropdown.
     *
     * @return array<int, array>
     */
    public function getRoles()
    {
        return $this->roleModel
            ->asArray()
            ->orderBy('role_name', 'ASC')
            ->findAll();
    }

    /**
     * Log user activity.
     */
    private function logActivity($action, $targetUserId, $description)
    {
        log_message('info', "[UserService] Action: {$action}, Target User: {$targetUserId}, Description: {$description}");
    }

    /**
     * Ambil role_id untuk "Siswa". Fallback ke 5 jika tidak ditemukan.
     */
    private function getStudentRoleId(): int
    {
        $role = $this->roleModel->asArray()
            ->select('id')
            ->where('role_name', 'Siswa')
            ->first();

        return (int)($role['id'] ?? 5);
    }

    /**
     * Get classes for dropdown.
     * @return array<int, array{id:int, class_name:string}>
     */
    public function getClasses(): array
    {
        return $this->classModel
            ->asArray()
            ->select('id, class_name')
            ->orderBy('class_name', 'ASC')
            ->findAll();
    }

    /**
     * Sanitasi profile_photo untuk OUTPUT.
     * Jika value menunjuk ke avatar template (assets/...) atau default lama, kembalikan null.
     *
     * @param mixed $value
     * @return string|null
     */
    private function sanitizeProfilePhotoOutput($value): ?string
    {
        $v = trim((string) ($value ?? ''));
        if ($v === '') return null;

        $norm = strtolower(ltrim(str_replace('\\', '/', $v), '/'));
        $base = strtolower(basename($norm));

        // Default baru yang kamu inginkan (jangan dianggap "foto user")
        $defaultNew = 'assets/images/users/default-avatar.svg';

        // Jika menunjuk ke assets/ (avatar bawaan template), anggap belum upload
        if ((str_starts_with($norm, 'assets/') || str_starts_with($norm, 'public/assets/')) && $norm !== $defaultNew) {
            return null;
        }

        // Nilai default lama (atau placeholder umum) → anggap belum upload
        if ($this->isLegacyOrPlaceholderAvatar($norm, $base)) {
            return null;
        }

        return $v;
    }

    /**
     * Sanitasi profile_photo untuk STORAGE.
     * Jika kosong/default lama/template → return null.
     *
     * @param mixed $value
     * @return string|null
     */
    private function sanitizeProfilePhotoStorage($value): ?string
    {
        $v = trim((string) ($value ?? ''));
        if ($v === '') return null;

        $norm = strtolower(ltrim(str_replace('\\', '/', $v), '/'));
        $base = strtolower(basename($norm));

        $defaultNew = 'assets/images/users/default-avatar.svg';

        // Jika template assets (kecuali defaultNew), anggap bukan foto user
        if ((str_starts_with($norm, 'assets/') || str_starts_with($norm, 'public/assets/')) && $norm !== $defaultNew) {
            return null;
        }

        if ($this->isLegacyOrPlaceholderAvatar($norm, $base)) {
            return null;
        }

        // Jika melewati filter, simpan seperti aslinya (bisa filename atau relative path)
        return $v;
    }

    /**
     * Deteksi avatar default/placeholder yang tidak boleh dianggap foto user.
     */
    private function isLegacyOrPlaceholderAvatar(string $norm, string $base): bool
    {
        $legacy = [
            'default-avatar.png',
            'default-avatar.jpg',
            'default-avatar.jpeg',
            'default-avatar.svg',

            'assets/images/default-avatar.png',
            'assets/images/default-avatar.svg',
            'assets/images/users/default-avatar.png',
            'assets/images/users/default-avatar.svg',

            'public/assets/images/default-avatar.png',
            'public/assets/images/default-avatar.svg',
            'public/assets/images/users/default-avatar.png',
            'public/assets/images/users/default-avatar.svg',

            // placeholder umum yang kadang dipakai template/seed
            'avatar.png',
            'avatar.jpg',
            'avatar.jpeg',
            'user.png',
            'user.jpg',
            'user.jpeg',
            'placeholder.png',
            'no-image.png',
            'noimage.png',
            'blank.png',
        ];

        return in_array($norm, $legacy, true) || in_array($base, $legacy, true);
    }
}
