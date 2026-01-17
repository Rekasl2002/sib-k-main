<?php

/**
 * Phone Helper
 *
 * Utilitas sederhana untuk normalisasi nomor HP (terutama Indonesia)
 * dan membuat URL WhatsApp (wa.me).
 */

if (! function_exists('wa_number_id')) {
    /**
     * Konversi nomor HP Indonesia ke format numerik internasional tanpa tanda "+"
     * agar bisa dipakai di URL https://wa.me/
     *
     * Contoh:
     *   '0812-3456-789'   => '628123456789'
     *   '+62812 3456 789' => '628123456789'
     *   '628123456789'    => '628123456789'
     *   '8123456789'      => '628123456789'
     *
     * @param string|null $phone Nomor telepon mentah (boleh berisi spasi, +, -)
     * @return string|null       Hanya digit dalam format 62xxxxxxxxx atau null jika kosong/tidak valid
     */
    function wa_number_id(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }

        // Ambil hanya digit
        $digits = preg_replace('/\D+/', '', $phone);
        if (!is_string($digits) || $digits === '') {
            return null;
        }

        // Normalisasi umum Indonesia:
        // 08xx -> 62xx
        // 8xx  -> 62xx (kadang disimpan tanpa 0)
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        // Validasi minimal panjang (biar tidak bikin link aneh)
        if (strlen($digits) < 10) {
            return null;
        }

        return $digits;
    }
}

if (! function_exists('wa_url')) {
    /**
     * Menghasilkan URL WhatsApp (wa.me) dari nomor HP.
     * Bisa isi teks default via query ?text=
     *
     * @param string|null $phone Nomor telepon mentah
     * @param string|null $text  Prefill message (opsional)
     * @return string|null       URL https://wa.me/62xxxx atau null jika tidak valid
     */
    function wa_url(?string $phone, ?string $text = null): ?string
    {
        $num = wa_number_id($phone);
        if (!$num) {
            return null;
        }

        $url = 'https://wa.me/' . $num;

        if ($text !== null && trim($text) !== '') {
            $url .= '?text=' . rawurlencode($text);
        }

        return $url;
    }
}
