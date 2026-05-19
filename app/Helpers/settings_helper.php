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
