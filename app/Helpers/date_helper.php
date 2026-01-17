<?php

/**
 * File Path: app/Helpers/date_helper.php
 * 
 * Indonesian Date Helper Functions
 * Menyediakan fungsi-fungsi untuk format tanggal dalam Bahasa Indonesia
 * 
 * Load this helper: helper('date');
 * 
 * @package    SIB-K
 * @subpackage Helpers
 * @category   Utilities
 * @author     Development Team
 * @created    2025-01-07
 */

if (!function_exists('format_indo_date')) {
    /**
     * Format tanggal dalam bahasa Indonesia (Lengkap)
     * 
     * @param string|null $date Date string or timestamp
     * @param bool        $with_day Include day name
     * @return string Format: "Senin, 7 Januari 2025" atau "7 Januari 2025"
     */
    function format_indo_date($date = null, bool $with_day = true): string
    {
        if (empty($date)) {
            return '-';
        }

        $timestamp = is_numeric($date) ? $date : strtotime($date);

        if (!$timestamp) {
            return '-';
        }

        $days = [
            'Minggu',
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu'
        ];

        $months = [
            '',
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        ];

        $day = $days[date('w', $timestamp)];
        $date_num = date('j', $timestamp);
        $month = $months[date('n', $timestamp)];
        $year = date('Y', $timestamp);

        if ($with_day) {
            return "{$day}, {$date_num} {$month} {$year}";
        }

        return "{$date_num} {$month} {$year}";
    }
}

if (!function_exists('format_indo_short')) {
    /**
     * Format tanggal singkat Indonesia
     * 
     * @param string|null $date Date string or timestamp
     * @return string Format: "7 Jan 2025"
     */
    function format_indo_short($date = null): string
    {
        if (empty($date)) {
            return '-';
        }

        $timestamp = is_numeric($date) ? $date : strtotime($date);

        if (!$timestamp) {
            return '-';
        }

        $months_short = [
            '',
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'Mei',
            'Jun',
            'Jul',
            'Agu',
            'Sep',
            'Okt',
            'Nov',
            'Des'
        ];

        $date_num = date('j', $timestamp);
        $month = $months_short[date('n', $timestamp)];
        $year = date('Y', $timestamp);

        return "{$date_num} {$month} {$year}";
    }
}

if (!function_exists('format_indo_datetime')) {
    /**
     * Format tanggal dan waktu Indonesia
     * 
     * @param string|null $datetime Datetime string or timestamp
     * @param bool        $with_seconds Include seconds
     * @return string Format: "7 Januari 2025, 14:30" atau "7 Januari 2025, 14:30:45"
     */
    function format_indo_datetime($datetime = null, bool $with_seconds = false): string
    {
        if (empty($datetime)) {
            return '-';
        }

        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);

        if (!$timestamp) {
            return '-';
        }

        $date = format_indo_date($datetime, false);
        $time_format = $with_seconds ? 'H:i:s' : 'H:i';
        $time = date($time_format, $timestamp);

        return "{$date}, {$time}";
    }
}

if (!function_exists('format_time_only')) {
    /**
     * Format waktu saja
     * 
     * @param string|null $time Time string
     * @param bool        $with_seconds Include seconds
     * @return string Format: "14:30" atau "14:30:45"
     */
    function format_time_only($time = null, bool $with_seconds = false): string
    {
        if (empty($time)) {
            return '-';
        }

        $timestamp = is_numeric($time) ? $time : strtotime($time);

        if (!$timestamp) {
            return '-';
        }

        $time_format = $with_seconds ? 'H:i:s' : 'H:i';
        return date($time_format, $timestamp);
    }
}

if (!function_exists('relative_time')) {
    /**
     * Format waktu relatif (time ago)
     * 
     * @param string|null $datetime Datetime string or timestamp
     * @return string Format: "2 jam yang lalu", "3 hari yang lalu"
     */
    function relative_time($datetime = null): string
    {
        if (empty($datetime)) {
            return '-';
        }

        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);

        if (!$timestamp) {
            return '-';
        }

        $diff = time() - $timestamp;

        if ($diff < 0) {
            return 'baru saja';
        }

        $time_rules = [
            12 * 30 * 24 * 60 * 60 => 'tahun',
            30 * 24 * 60 * 60      => 'bulan',
            7 * 24 * 60 * 60       => 'minggu',
            24 * 60 * 60           => 'hari',
            60 * 60                => 'jam',
            60                     => 'menit',
            1                      => 'detik',
        ];

        foreach ($time_rules as $secs => $str) {
            $d = $diff / $secs;

            if ($d >= 1) {
                $r = round($d);
                return $r . ' ' . $str . ' yang lalu';
            }
        }

        return 'baru saja';
    }
}

if (!function_exists('get_current_academic_year')) {
    /**
     * Dapatkan tahun ajaran saat ini
     * Asumsi: Tahun ajaran dimulai Juli
     * 
     * @return string Format: "2024/2025"
     */
    function get_current_academic_year(): string
    {
        $current_month = date('n');
        $current_year = date('Y');

        // Jika bulan >= Juli (7), tahun ajaran dimulai dari tahun ini
        if ($current_month >= 7) {
            $start_year = $current_year;
            $end_year = $current_year + 1;
        } else {
            // Jika bulan < Juli, tahun ajaran dimulai dari tahun lalu
            $start_year = $current_year - 1;
            $end_year = $current_year;
        }

        return "{$start_year}/{$end_year}";
    }
}

if (!function_exists('get_academic_year_range')) {
    /**
     * Dapatkan range tahun ajaran berdasarkan tahun
     * 
     * @param int $start_year Tahun mulai
     * @return array ['start_year' => 2024, 'end_year' => 2025, 'label' => '2024/2025']
     */
    function get_academic_year_range(int $start_year): array
    {
        return [
            'start_year' => $start_year,
            'end_year'   => $start_year + 1,
            'label'      => "{$start_year}/" . ($start_year + 1),
        ];
    }
}

if (!function_exists('is_weekday')) {
    /**
     * Cek apakah tanggal adalah hari kerja (Senin-Jumat)
     * 
     * @param string|null $date Date string or timestamp
     * @return bool
     */
    function is_weekday($date = null): bool
    {
        $timestamp = empty($date) ? time() : (is_numeric($date) ? $date : strtotime($date));

        if (!$timestamp) {
            return false;
        }

        $day = date('N', $timestamp); // 1 (Monday) to 7 (Sunday)
        return $day >= 1 && $day <= 5;
    }
}

if (!function_exists('is_weekend')) {
    /**
     * Cek apakah tanggal adalah akhir pekan (Sabtu-Minggu)
     * 
     * @param string|null $date Date string or timestamp
     * @return bool
     */
    function is_weekend($date = null): bool
    {
        return !is_weekday($date);
    }
}

if (!function_exists('get_day_name')) {
    /**
     * Dapatkan nama hari dalam bahasa Indonesia
     * 
     * @param string|null $date Date string or timestamp
     * @return string
     */
    function get_day_name($date = null): string
    {
        $timestamp = empty($date) ? time() : (is_numeric($date) ? $date : strtotime($date));

        if (!$timestamp) {
            return '-';
        }

        $days = [
            'Minggu',
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu'
        ];

        return $days[date('w', $timestamp)];
    }
}

if (!function_exists('get_month_name')) {
    /**
     * Dapatkan nama bulan dalam bahasa Indonesia
     * 
     * @param int $month Month number (1-12)
     * @return string
     */
    function get_month_name(int $month): string
    {
        $months = [
            '',
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        ];

        return $months[$month] ?? '-';
    }
}

if (!function_exists('date_diff_in_days')) {
    /**
     * Hitung selisih hari antara 2 tanggal
     * 
     * @param string $date1 First date
     * @param string $date2 Second date (default: today)
     * @return int Number of days
     */
    function date_diff_in_days(string $date1, string $date2 = null): int
    {
        $timestamp1 = strtotime($date1);
        $timestamp2 = $date2 ? strtotime($date2) : time();

        if (!$timestamp1 || !$timestamp2) {
            return 0;
        }

        $diff = abs($timestamp2 - $timestamp1);
        return floor($diff / (60 * 60 * 24));
    }
}

if (!function_exists('add_days')) {
    /**
     * Tambah hari ke tanggal
     * 
     * @param string $date Base date
     * @param int    $days Number of days to add
     * @param string $format Output format
     * @return string
     */
    function add_days(string $date, int $days, string $format = 'Y-m-d'): string
    {
        $timestamp = strtotime($date);

        if (!$timestamp) {
            return $date;
        }

        return date($format, strtotime("+{$days} days", $timestamp));
    }
}

if (!function_exists('subtract_days')) {
    /**
     * Kurangi hari dari tanggal
     * 
     * @param string $date Base date
     * @param int    $days Number of days to subtract
     * @param string $format Output format
     * @return string
     */
    function subtract_days(string $date, int $days, string $format = 'Y-m-d'): string
    {
        $timestamp = strtotime($date);

        if (!$timestamp) {
            return $date;
        }

        return date($format, strtotime("-{$days} days", $timestamp));
    }
}

if (!function_exists('format_date_range')) {
    /**
     * Format range tanggal
     * 
     * @param string $start_date Start date
     * @param string $end_date   End date
     * @return string Format: "7-10 Januari 2025" atau "7 Jan - 10 Feb 2025"
     */
    function format_date_range(string $start_date, string $end_date): string
    {
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);

        if (!$start_timestamp || !$end_timestamp) {
            return '-';
        }

        $months_short = [
            '',
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'Mei',
            'Jun',
            'Jul',
            'Agu',
            'Sep',
            'Okt',
            'Nov',
            'Des'
        ];

        $start_day = date('j', $start_timestamp);
        $start_month = $months_short[date('n', $start_timestamp)];
        $start_year = date('Y', $start_timestamp);

        $end_day = date('j', $end_timestamp);
        $end_month = $months_short[date('n', $end_timestamp)];
        $end_year = date('Y', $end_timestamp);

        // Same month and year
        if (date('Y-m', $start_timestamp) === date('Y-m', $end_timestamp)) {
            return "{$start_day}-{$end_day} {$end_month} {$end_year}";
        }

        // Same year, different month
        if (date('Y', $start_timestamp) === date('Y', $end_timestamp)) {
            return "{$start_day} {$start_month} - {$end_day} {$end_month} {$end_year}";
        }

        // Different year
        return "{$start_day} {$start_month} {$start_year} - {$end_day} {$end_month} {$end_year}";
    }
}

if (!function_exists('is_past_date')) {
    /**
     * Cek apakah tanggal sudah lewat
     * 
     * @param string $date Date to check
     * @return bool
     */
    function is_past_date(string $date): bool
    {
        $timestamp = strtotime($date);

        if (!$timestamp) {
            return false;
        }

        return $timestamp < strtotime('today');
    }
}

if (!function_exists('is_future_date')) {
    /**
     * Cek apakah tanggal di masa depan
     * 
     * @param string $date Date to check
     * @return bool
     */
    function is_future_date(string $date): bool
    {
        $timestamp = strtotime($date);

        if (!$timestamp) {
            return false;
        }

        return $timestamp > strtotime('today');
    }
}

if (!function_exists('is_today')) {
    /**
     * Cek apakah tanggal adalah hari ini
     * 
     * @param string $date Date to check
     * @return bool
     */
    function is_today(string $date): bool
    {
        $timestamp = strtotime($date);

        if (!$timestamp) {
            return false;
        }

        return date('Y-m-d', $timestamp) === date('Y-m-d');
    }
}

if (!function_exists('get_semester')) {
    /**
     * Dapatkan semester berdasarkan bulan
     * Semester 1: Juli - Desember
     * Semester 2: Januari - Juni
     * 
     * @param string|null $date Date (default: today)
     * @return int 1 or 2
     */
    function get_semester($date = null): int
    {
        $timestamp = empty($date) ? time() : (is_numeric($date) ? $date : strtotime($date));

        if (!$timestamp) {
            return 1;
        }

        $month = date('n', $timestamp);

        // Januari - Juni = Semester 2
        // Juli - Desember = Semester 1
        return ($month >= 1 && $month <= 6) ? 2 : 1;
    }
}

if (!function_exists('mysql_date')) {
    /**
     * Convert to MySQL date format (Y-m-d)
     * 
     * @param string|null $date Date string
     * @return string|null
     */
    function mysql_date($date = null): ?string
    {
        if (empty($date)) {
            return null;
        }

        $timestamp = is_numeric($date) ? $date : strtotime($date);

        if (!$timestamp) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }
}

if (!function_exists('mysql_datetime')) {
    /**
     * Convert to MySQL datetime format (Y-m-d H:i:s)
     * 
     * @param string|null $datetime Datetime string
     * @return string|null
     */
    function mysql_datetime($datetime = null): ?string
    {
        if (empty($datetime)) {
            return null;
        }

        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);

        if (!$timestamp) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
