<?php
use App\Models\SettingModel;

if (! function_exists('settings_cache_key')) {
    /**
     * Bentuk cache key yang aman untuk FileHandler (tanpa {}()/\@: dan sejenisnya)
     */
    function settings_cache_key(string $group, string $key): string
    {
        // satukan dan sanitasi => huruf kecil, non [A-Za-z0-9_-] diganti underscore
        $safe = strtolower($group . '_' . $key);
        $safe = preg_replace('/[^a-z0-9_-]/', '_', $safe);
        return 'settings_' . $safe;
    }
}

if (! function_exists('setting')) {
    /**
     * Ambil nilai setting
     * @param string      $key
     * @param mixed|null  $default
     * @param string      $group
     */
    function setting(string $key, $default = null, string $group = 'general')
    {
        $cache = cache();
        $ckey  = settings_cache_key($group, $key);

        // Coba dari cache dulu
        $cached = $cache->get($ckey);
        if ($cached !== null) {
            return $cached;
        }

        // Ambil dari DB
        $model = model(SettingModel::class);
        $val   = $model->getValue($key, $group, $default);

        // Simpan ke cache (TTL 1 jam; sesuaikan bila perlu)
        $cache->save($ckey, $val, 3600);

        return $val;
    }
}

if (! function_exists('set_setting')) {
    /**
     * Simpan/update setting lalu invalidasi cache kuncinya
     * Signature sesuai pemakaian di SettingService: set_setting('group','key', $value, $type='string')
     */
    function set_setting(string $group, string $key, $value, string $type = 'string'): bool
    {
        $model = model(SettingModel::class);

        $existing = $model->where(['group' => $group, 'key' => $key])->first();
        if ($existing) {
            $ok = $model->update($existing['id'], [
                'value' => (string) $value,
                'type'  => $type,
            ]);
        } else {
            $ok = (bool) $model->insert([
                'group'    => $group,
                'key'      => $key,
                'value'    => (string) $value,
                'type'     => $type,
                'autoload' => 0,
            ]);
        }

        // Hapus cache key yg aman (TIDAK ada karakter terlarang)
        $ckey = settings_cache_key($group, $key);
        cache()->delete($ckey);

        return (bool) $ok;
    }
}

if (! function_exists('forget_setting')) {
    /**
     * Hapus cache utk sebuah setting (opsional dipakai kalau perlu)
     */
    function forget_setting(string $group, string $key): void
    {
        cache()->delete(settings_cache_key($group, $key));
    }
}
