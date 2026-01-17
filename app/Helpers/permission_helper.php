<?php

/**
 * File Path: app/Helpers/permission_helper.php
 *
 * Single-source RBAC helper (role + permission)
 * - Permission diambil dari DB via role_id (role_permissions -> permissions)
 * - Default: no TTL cache (perubahan DB langsung berlaku)
 * - Backward compatible: session keys lama masih didukung
 */

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        $u = session('auth_user');
        if (is_array($u)) return $u;

        // fallback legacy session keys
        if (session('user_id')) {
            return [
                'id'            => session('user_id'),
                'username'      => session('username'),
                'email'         => session('email'),
                'full_name'     => session('full_name'),
                'role_id'       => session('role_id'),
                'role_name'     => session('role_name'),
                'profile_photo' => session('profile_photo'),
            ];
        }
        return null;
    }
}

if (!function_exists('auth_role_id')) {
    function auth_role_id(): ?int
    {
        $u = auth_user();
        return $u['role_id'] ?? session('role_id');
    }
}

if (!function_exists('auth_role')) {
    function auth_role(): ?string
    {
        $u = auth_user();
        return $u['role_name'] ?? session('role_name');
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return (bool) (session('user_id') ?: (auth_user()['id'] ?? null));
    }
}

if (!function_exists('has_role')) {
    function has_role($roles): bool
    {
        $role = strtolower(trim((string) auth_role()));
        if ($role === '') return false;

        if (is_array($roles)) {
            foreach ($roles as $r) {
                if ($role === strtolower(trim((string) $r))) return true;
            }
            return false;
        }

        return $role === strtolower(trim((string) $roles));
    }
}

if (!function_exists('user_permissions')) {
    /**
     * Ambil permission untuk role saat ini.
     * Default TTL = 0 agar perubahan role_permissions langsung terasa.
     */
    function user_permissions(bool $forceReload = false): array
    {
        $roleId = auth_role_id();
        if (!$roleId) return [];

        $cacheKey = 'rbac_permissions';
        $cacheAt  = 'rbac_permissions_at';

        // TTL 0 = selalu reload (supaya cabut permission langsung berpengaruh)
        $ttlSeconds = 0;

        if (!$forceReload && $ttlSeconds > 0) {
            $cached = session($cacheKey);
            $at     = session($cacheAt);
            if (is_array($cached) && is_int($at) && (time() - $at) < $ttlSeconds) {
                return $cached;
            }
        }

        $db = \Config\Database::connect();
        $rows = $db->table('role_permissions rp')
            ->select('p.permission_name')
            ->join('permissions p', 'p.id = rp.permission_id', 'inner')
            ->where('rp.role_id', $roleId)
            ->get()->getResultArray();

        $perms = array_values(array_unique(array_map(
            fn($r) => (string) ($r['permission_name'] ?? ''),
            $rows ?? []
        )));
        $perms = array_values(array_filter($perms, fn($x) => $x !== ''));

        // simpan untuk kompatibilitas (sidebar/legacy code)
        session()->set([
            $cacheKey     => $perms,
            $cacheAt      => time(),
            'permissions' => $perms, // legacy key
        ]);

        return $perms;
    }
}

if (!function_exists('has_permission')) {
    /**
     * @param string|array $permissions
     * True jika punya minimal 1 dari permission yang diminta.
     * Support wildcard pada REQUEST, mis: manage_*.
     */
    function has_permission($permissions): bool
    {
        // Admin bypass
        if (has_role(['Admin'])) return true;

        $owned = user_permissions();
        if (empty($owned)) return false;

        $need = is_array($permissions) ? $permissions : [$permissions];

        foreach ($need as $perm) {
            $perm = (string) $perm;
            if ($perm === '') continue;

            if (in_array($perm, $owned, true)) return true;

            // wildcard di request: "manage_*"
            if (str_contains($perm, '*')) {
                $pattern = '/^' . str_replace('\*', '.*', preg_quote($perm, '/')) . '$/i';
                foreach ($owned as $p) {
                    if (preg_match($pattern, $p)) return true;
                }
            }
        }
        return false;
    }
}

if (!function_exists('has_all_permissions')) {
    function has_all_permissions(array $permissions): bool
    {
        if (has_role(['Admin'])) return true;
        $owned = user_permissions();
        foreach ($permissions as $perm) {
            if (!in_array((string) $perm, $owned, true)) return false;
        }
        return true;
    }
}

if (!function_exists('get_dashboard_url')) {
    function get_dashboard_url(): string
    {
        helper('url');

        $role = (string) auth_role();
        $map = [
            'Admin'          => '/admin/dashboard',
            'Koordinator BK' => '/koordinator/dashboard',
            'Guru BK'        => '/counselor/dashboard',
            'Wali Kelas'     => '/homeroom/dashboard',
            'Siswa'          => '/student/dashboard',
            'Orang Tua'      => '/parent/dashboard',
        ];
        return base_url($map[$role] ?? '/');
    }
}

if (!function_exists('require_auth')) {
    function require_auth(): void
    {
        if (!is_logged_in()) {
            redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.')->send();
            exit;
        }
    }
}

if (!function_exists('require_role')) {
    function require_role($roles): void
    {
        require_auth();
        if (!has_role($roles)) {
            redirect()->to(get_dashboard_url())->with('error', 'Anda tidak memiliki akses ke halaman tersebut.')->send();
            exit;
        }
    }
}

if (!function_exists('require_permission')) {
    function require_permission($permissions): void
    {
        require_auth();
        if (!has_permission($permissions)) {
            redirect()->to(get_dashboard_url())->with('error', 'Anda tidak memiliki izin untuk mengakses halaman tersebut.')->send();
            exit;
        }
    }
}

/**
 * Avatar helper wrapper
 * Penting untuk konsistensi karena banyak view/layout memanggil helper('permission') saja.
 * - Jika user_avatar() sudah ada (mis. dari auth_helper.php), bagian ini tidak mengubah apa pun.
 * - Jika belum ada, definisikan user_avatar() dengan fallback yang benar.
 */
if (!function_exists('user_avatar')) {
    /**
     * Get user avatar URL
     * - Support path lengkap: uploads/profile_photos/xxx.jpg
     * - Support filename saja: akan dicari di beberapa folder
     * - Mengabaikan nilai default lama yang tersimpan di DB (anggap sebagai kosong)
     * - Fallback: public/assets/images/users/default-avatar.svg
     */
    function user_avatar(?string $photo = null): string
    {
        helper('url');

        $defaultRel = 'assets/images/users/default-avatar.svg';

        if (!$photo) {
            $photo = session('profile_photo');
        }

        if (!$photo) {
            return base_url($defaultRel);
        }

        $photo = trim((string) $photo);
        if ($photo === '') {
            return base_url($defaultRel);
        }

        // Abaikan nilai default lama (kalau pernah tersimpan di DB)
        $photoNorm = strtolower(ltrim(str_replace('\\', '/', $photo), '/'));
        $baseNorm  = strtolower(basename($photoNorm));

        $legacyDefaults = [
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
        ];

        if (in_array($photoNorm, $legacyDefaults, true) || in_array($baseNorm, $legacyDefaults, true)) {
            return base_url($defaultRel);
        }

        // URL penuh
        if (preg_match('~^https?://~i', $photo)) {
            return $photo;
        }

        // Normalisasi path
        $photo = ltrim(str_replace('\\', '/', $photo), '/');

        $candidates = [$photo];

        // Jika hanya filename
        if (strpos($photo, '/') === false) {
            $candidates[] = 'uploads/profile_photos/' . $photo;
            $candidates[] = 'uploads/users/' . $photo;
            $candidates[] = 'uploads/profiles/' . $photo; // legacy
        }

        // Jika sudah uploads/..., coba basename di folder lain
        if (strpos($photo, 'uploads/') === 0) {
            $base = basename($photo);
            $candidates[] = 'uploads/profile_photos/' . $base;
            $candidates[] = 'uploads/users/' . $base;
            $candidates[] = 'uploads/profiles/' . $base;
        }

        foreach (array_unique($candidates) as $rel) {
            $rel = ltrim(str_replace('\\', '/', $rel), '/');
            $abs = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);

            if (is_file($abs)) {
                return base_url($rel);
            }
        }

        return base_url($defaultRel);
    }
}

/** Role helpers (kompatibilitas) */
if (!function_exists('is_admin'))           { function is_admin(): bool { return has_role('Admin'); } }
if (!function_exists('is_coordinator'))     { function is_coordinator(): bool { return has_role(['Koordinator BK','Koordinator']); } }
if (!function_exists('is_koordinator'))     { function is_koordinator(): bool { return is_coordinator(); } } // ✅ buat view add_sanction.php
if (!function_exists('is_counselor'))       { function is_counselor(): bool { return has_role(['Guru BK','Counselor']); } }
if (!function_exists('is_homeroom_teacher')){ function is_homeroom_teacher(): bool { return has_role(['Wali Kelas','Homeroom']); } }
if (!function_exists('is_student'))         { function is_student(): bool { return has_role(['Siswa','Student']); } }
if (!function_exists('is_parent'))          { function is_parent(): bool { return has_role(['Orang Tua','Parent']); } }

if (!function_exists('forget_permissions_cache')) {
    function forget_permissions_cache(): void
    {
        session()->remove(['rbac_permissions','rbac_permissions_at','permissions']);
    }
}
