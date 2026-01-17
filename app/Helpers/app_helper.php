<?php

/**
 * File Path: app/Helpers/app_helper.php
 * 
 * Application Helper
 * Menyediakan utility functions yang digunakan di seluruh aplikasi
 * 
 * @package    SIB-K
 * @subpackage Helpers
 * @category   Utilities
 * @author     Development Team
 * @created    2025-01-06
 */

if (!function_exists('time_ago')) {
    /**
     * Convert timestamp to human readable time ago
     * 
     * @param string $datetime
     * @return string
     */
    function time_ago($datetime)
    {
        if (empty($datetime)) {
            return '-';
        }

        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 1) {
            return 'Baru saja';
        }

        $periods = [
            31536000 => 'tahun',
            2592000 => 'bulan',
            604800 => 'minggu',
            86400 => 'hari',
            3600 => 'jam',
            60 => 'menit',
            1 => 'detik'
        ];

        foreach ($periods as $seconds => $label) {
            $count = floor($diff / $seconds);
            if ($count > 0) {
                return $count . ' ' . $label . ' yang lalu';
            }
        }

        return 'Baru saja';
    }
}

if (!function_exists('indonesian_date')) {
    /**
     * Format date to Indonesian format
     * 
     * @param string $date
     * @param bool $withDay Include day name
     * @return string
     */
    function indonesian_date($date, $withDay = false)
    {
        if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
            return '-';
        }

        $months = [
            1 => 'Januari',
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

        $days = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];

        $timestamp = is_numeric($date) ? $date : strtotime($date);
        $day = date('j', $timestamp);
        $month = $months[(int)date('n', $timestamp)];
        $year = date('Y', $timestamp);

        $result = "$day $month $year";

        if ($withDay) {
            $dayName = $days[date('l', $timestamp)];
            $result = "$dayName, $result";
        }

        return $result;
    }
}

if (!function_exists('indonesian_datetime')) {
    /**
     * Format datetime to Indonesian format with time
     * 
     * @param string $datetime
     * @param bool $withDay
     * @return string
     */
    function indonesian_datetime($datetime, $withDay = false)
    {
        if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
            return '-';
        }

        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
        $date = indonesian_date($datetime, $withDay);
        $time = date('H:i', $timestamp);

        return "$date pukul $time WIB";
    }
}

if (!function_exists('format_number')) {
    /**
     * Format number to Indonesian format
     * 
     * @param mixed $number
     * @param int $decimals
     * @return string
     */
    function format_number($number, $decimals = 0)
    {
        if (!is_numeric($number)) {
            return $number;
        }

        return number_format($number, $decimals, ',', '.');
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format number as Indonesian Rupiah
     * 
     * @param mixed $number
     * @return string
     */
    function format_currency($number)
    {
        if (!is_numeric($number)) {
            return $number;
        }

        return 'Rp ' . number_format($number, 0, ',', '.');
    }
}

if (!function_exists('str_limit')) {
    /**
     * Limit string length and add suffix
     * 
     * @param string $text
     * @param int $limit
     * @param string $suffix
     * @return string
     */
    function str_limit($text, $limit = 100, $suffix = '...')
    {
        if (empty($text)) {
            return '';
        }

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return mb_substr($text, 0, $limit) . $suffix;
    }
}

if (!function_exists('status_badge')) {
    /**
     * Generate status badge HTML
     * 
     * @param string $status
     * @param array $config Custom color mapping
     * @return string
     */
    function status_badge($status, $config = [])
    {
        $defaultConfig = [
            'Aktif' => 'success',
            'Tidak Aktif' => 'danger',
            'Pending' => 'warning',
            'Dijadwalkan' => 'warning',
            'Selesai' => 'success',
            'Dibatalkan' => 'danger',
            'Hadir' => 'success',
            'Tidak Hadir' => 'danger',
            'Izin' => 'info',
            'Sakit' => 'warning',
        ];

        $colorMap = array_merge($defaultConfig, $config);
        $color = $colorMap[$status] ?? 'secondary';

        return '<span class="badge bg-' . $color . '">' . esc($status) . '</span>';
    }
}

if (!function_exists('session_type_badge')) {
    /**
     * Generate session type badge
     * 
     * @param string $type
     * @return string
     */
    function session_type_badge($type)
    {
        $colors = [
            'Individu' => 'info',
            'Kelompok' => 'warning',
            'Klasikal' => 'primary',
        ];

        $color = $colors[$type] ?? 'secondary';

        return '<span class="badge bg-' . $color . '">' . esc($type) . '</span>';
    }
}

if (!function_exists('generate_nisn')) {
    /**
     * Generate random NISN for demo purposes
     * 
     * @return string
     */
    function generate_nisn()
    {
        return date('Y') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generate_nis')) {
    /**
     * Generate random NIS for demo purposes
     * 
     * @return string
     */
    function generate_nis()
    {
        return str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('sanitize_filename')) {
    /**
     * Sanitize filename for safe file upload
     * 
     * @param string $filename
     * @return string
     */
    function sanitize_filename($filename)
    {
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Remove multiple underscores
        $filename = preg_replace('/_+/', '_', $filename);

        return strtolower($filename);
    }
}

if (!function_exists('get_file_icon')) {
    /**
     * Get icon class based on file extension
     * 
     * @param string $filename
     * @return string
     */
    function get_file_icon($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $icons = [
            'pdf' => 'mdi-file-pdf text-danger',
            'doc' => 'mdi-file-word text-primary',
            'docx' => 'mdi-file-word text-primary',
            'xls' => 'mdi-file-excel text-success',
            'xlsx' => 'mdi-file-excel text-success',
            'ppt' => 'mdi-file-powerpoint text-warning',
            'pptx' => 'mdi-file-powerpoint text-warning',
            'jpg' => 'mdi-file-image text-info',
            'jpeg' => 'mdi-file-image text-info',
            'png' => 'mdi-file-image text-info',
            'gif' => 'mdi-file-image text-info',
            'zip' => 'mdi-folder-zip text-warning',
            'rar' => 'mdi-folder-zip text-warning',
        ];

        return $icons[$extension] ?? 'mdi-file-document text-secondary';
    }
}

if (!function_exists('get_file_size')) {
    /**
     * Format file size to human readable format
     * 
     * @param int $bytes
     * @param int $decimals
     * @return string
     */
    function get_file_size($bytes, $decimals = 2)
    {
        $size = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
    }
}

if (!function_exists('alert_message')) {
    /**
     * Display flash message as Bootstrap alert
     * 
     * @param string $type success|error|warning|info
     * @return string
     */
    function alert_message($type = 'success')
    {
        $session = \Config\Services::session();
        $message = $session->getFlashdata($type);

        if (!$message) {
            return '';
        }

        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info',
        ];

        $iconClass = [
            'success' => 'mdi-check-circle',
            'error' => 'mdi-alert-circle',
            'warning' => 'mdi-alert',
            'info' => 'mdi-information',
        ];

        $class = $alertClass[$type] ?? 'alert-info';
        $icon = $iconClass[$type] ?? 'mdi-information';

        return <<<HTML
        <div class="alert {$class} alert-dismissible fade show" role="alert">
            <i class="mdi {$icon} me-2"></i>
            {$message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        HTML;
    }
}

if (!function_exists('show_alerts')) {
    /**
     * Show all flash messages
     * 
     * @return string
     */
    function show_alerts()
    {
        $html = '';
        $html .= alert_message('success');
        $html .= alert_message('error');
        $html .= alert_message('warning');
        $html .= alert_message('info');

        return $html;
    }
}

if (!function_exists('active_menu')) {
    /**
     * Check if current URL matches menu and return active class
     * 
     * @param string|array $path
     * @param string $activeClass
     * @return string
     */
    function active_menu($path, $activeClass = 'active')
    {
        $currentPath = uri_string();

        if (is_array($path)) {
            foreach ($path as $p) {
                if (strpos($currentPath, $p) === 0) {
                    return $activeClass;
                }
            }
            return '';
        }

        return strpos($currentPath, $path) === 0 ? $activeClass : '';
    }
}

if (!function_exists('set_active')) {
    /**
     * Set active class for navigation menu
     * Alias for active_menu
     * 
     * @param string|array $path
     * @return string
     */
    function set_active($path)
    {
        return active_menu($path);
    }
}

if (!function_exists('validation_errors')) {
    /**
     * Display validation errors as Bootstrap alert
     * 
     * @param array|null $errors
     * @return string
     */
    function validation_errors($errors = null)
    {
        $session = \Config\Services::session();

        if ($errors === null) {
            $errors = $session->getFlashdata('errors');
        }

        if (!$errors || !is_array($errors)) {
            return '';
        }

        $html = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        $html .= '<i class="mdi mdi-alert-circle me-2"></i>';
        $html .= '<strong>Terdapat kesalahan pada input:</strong>';
        $html .= '<ul class="mb-0 mt-2">';

        foreach ($errors as $error) {
            $html .= '<li>' . esc($error) . '</li>';
        }

        $html .= '</ul>';
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('old_value')) {
    /**
     * Get old input value after validation error
     * 
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    function old_value($field, $default = '')
    {
        $session = \Config\Services::session();
        $old = $session->getFlashdata('old');

        return $old[$field] ?? $default;
    }
}

if (!function_exists('create_breadcrumb')) {
    /**
     * Create breadcrumb HTML
     * 
     * @param array $items [['title' => '', 'url' => '', 'active' => false]]
     * @return string
     */
    function create_breadcrumb($items)
    {
        if (empty($items)) {
            return '';
        }

        $html = '<ol class="breadcrumb m-0">';

        foreach ($items as $item) {
            if (!empty($item['active'])) {
                $html .= '<li class="breadcrumb-item active">' . esc($item['title']) . '</li>';
            } else {
                $url = $item['url'] ?? '#';
                $html .= '<li class="breadcrumb-item"><a href="' . $url . '">' . esc($item['title']) . '</a></li>';
            }
        }

        $html .= '</ol>';

        return $html;
    }
}

if (!function_exists('percentage')) {
    /**
     * Calculate percentage
     * 
     * @param float $value
     * @param float $total
     * @param int $decimals
     * @return float
     */
    function percentage($value, $total, $decimals = 2)
    {
        if ($total == 0) {
            return 0;
        }

        return round(($value / $total) * 100, $decimals);
    }
}

if (!function_exists('get_academic_year')) {
    /**
     * Get current academic year
     * 
     * @return string
     */
    function get_academic_year()
    {
        $month = date('n');
        $year = date('Y');

        // If month is July (7) or later, academic year is current/next
        // Otherwise, it's previous/current
        if ($month >= 7) {
            return $year . '/' . ($year + 1);
        } else {
            return ($year - 1) . '/' . $year;
        }
    }
}
