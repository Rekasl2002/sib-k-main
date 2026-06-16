<?php

use App\Models\SettingModel;

if (! function_exists('settings_cache_key')) {
    /**
     * Bentuk cache key aman untuk FileHandler (tanpa {}()/\@: dsb)
     */
    function settings_cache_key(string $group, string $key): string
    {
        $safe = strtolower($group . '_' . $key);
        $safe = preg_replace('/[^a-z0-9_-]/', '_', $safe);
        return 'settings_' . $safe;
    }
}

if (! function_exists('settings_resolve_key')) {
    /**
     * Dukung dua format pemanggilan:
     * - setting('app_name', 'SIB-K', 'general')
     * - setting('general.app_name', 'SIB-K')
     */
    function settings_resolve_key(string $key, string $group): array
    {
        if ($group === 'general' && strpos($key, '.') !== false) {
            [$candidateGroup, $candidateKey] = explode('.', $key, 2);

            if ($candidateGroup !== '' && $candidateKey !== '') {
                return [$candidateGroup, $candidateKey];
            }
        }

        return [$group, $key];
    }
}

if (! function_exists('setting')) {
    /**
     * Ambil nilai setting
     * Catatan: default param tetap sama seperti punyamu (key, default, group)
     */
    function setting(string $key, $default = null, string $group = 'general')
    {
        [$group, $key] = settings_resolve_key($key, $group);
        $ckey = settings_cache_key($group, $key);

        // 1) Coba dari cache (pakai wrapper agar nilai null tetap bisa dicache)
        try {
            $cache  = cache();
            $cached = $cache->get($ckey);

            if (is_array($cached) && array_key_exists('__hit', $cached)) {
                return $cached['value'];
            }
        } catch (\Throwable $e) {
            // Cache error? lanjut saja tanpa cache
        }

        // 2) Ambil dari DB dengan aman
        try {
            /** @var SettingModel $model */
            $model = model(SettingModel::class);
            $val   = $model->getValue($key, $group, $default);
        } catch (\Throwable $e) {
            $val = $default;
        }

        // 3) Simpan ke cache (TTL 1 jam)
        try {
            $cache = cache();
            $cache->save($ckey, ['__hit' => 1, 'value' => $val], 3600);
        } catch (\Throwable $e) {
            // Abaikan kalau cache gagal
        }

        return $val;
    }
}

if (! function_exists('set_setting')) {
    /**
     * Simpan/update setting lalu invalidasi cache kuncinya
     * Signature: set_setting('group','key', $value, $type='string')
     *
     * @param mixed $value
     */
    function set_setting(string $group, string $key, $value, string $type = 'string'): bool
    {
        try {
            /** @var SettingModel $model */
            $model = model(SettingModel::class);

            // Normalisasi value sesuai type
            $storedValue = match ($type) {
                'int'  => (string) ((int) $value),
                'bool' => (string) ((int) (bool) $value),
                'json' => is_string($value)
                    ? $value
                    : (json_encode($value, JSON_UNESCAPED_UNICODE) ?: ''),
                default => (string) $value,
            };

            $existing = $model->where(['group' => $group, 'key' => $key])->first();

            if ($existing) {
                $ok = $model->update($existing['id'], [
                    'value' => $storedValue,
                    'type'  => $type,
                ]);
            } else {
                $ok = (bool) $model->insert([
                    'group'    => $group,
                    'key'      => $key,
                    'value'    => $storedValue,
                    'type'     => $type,
                    'autoload' => 0,
                ]);
            }
        } catch (\Throwable $e) {
            return false;
        }

        // Invalidate cache
        try {
            cache()->delete(settings_cache_key($group, $key));
        } catch (\Throwable $e) {
            // ignore
        }

        return (bool) $ok;
    }
}

if (! function_exists('forget_setting')) {
    /**
     * Hapus cache utk sebuah setting
     */
    function forget_setting(string $group, string $key): void
    {
        try {
            cache()->delete(settings_cache_key($group, $key));
        } catch (\Throwable $e) {
            // ignore
        }
    }
}

if (! function_exists('grade_level_bounds')) {
    /**
     * Batas tingkat kelas yang diizinkan (bisa diatur di Pengaturan Admin).
     * Bawaan 7-12 (MTs + MA). Dibatasi aman pada rentang 1-12.
     *
     * @return array{0:int,1:int} [min, max]
     */
    function grade_level_bounds(): array
    {
        $min = (int) setting('grade_level_min', 7, 'academic');
        $max = (int) setting('grade_level_max', 12, 'academic');

        // Jaga agar tetap masuk akal (jenjang Indonesia: 1-12).
        if ($min < 1)  $min = 1;
        if ($min > 12) $min = 12;
        if ($max < 1)  $max = 1;
        if ($max > 12) $max = 12;
        if ($max < $min) $max = $min;

        return [$min, $max];
    }
}

if (! function_exists('normalize_grade_level')) {
    /**
     * Seragamkan nilai tingkat menjadi ANGKA ("7".."12"), sesuai format database.
     * Menerima angka maupun angka Romawi (VII..XII). Mengembalikan null bila tidak dikenali.
     *
     * @param mixed $grade
     */
    function normalize_grade_level($grade): ?string
    {
        $g = strtoupper(trim((string) $grade));
        if ($g === '') {
            return null;
        }

        $roman = ['VII' => '7', 'VIII' => '8', 'IX' => '9', 'X' => '10', 'XI' => '11', 'XII' => '12'];
        if (isset($roman[$g])) {
            return $roman[$g];
        }

        if (preg_match('/^\d{1,2}$/', $g)) {
            return (string) (int) $g;
        }

        return null;
    }
}

if (! function_exists('allowed_grade_levels')) {
    /**
     * Daftar tingkat kelas yang diizinkan dalam bentuk angka string, mis. ["7","8",...,"12"].
     *
     * @return list<string>
     */
    function allowed_grade_levels(): array
    {
        [$min, $max] = grade_level_bounds();
        $out = [];
        for ($g = $min; $g <= $max; $g++) {
            $out[] = (string) $g;
        }
        return $out;
    }
}

if (! function_exists('is_grade_level_allowed')) {
    /**
     * Apakah tingkat kelas berada dalam rentang yang diizinkan?
     *
     * @param mixed $grade
     */
    function is_grade_level_allowed($grade): bool
    {
        $n = normalize_grade_level($grade);
        if ($n === null) {
            return false;
        }

        [$min, $max] = grade_level_bounds();
        $v = (int) $n;

        return $v >= $min && $v <= $max;
    }
}
