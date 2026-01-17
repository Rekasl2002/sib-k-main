<?php

/**
 * File Path: app/Libraries/ValidationHelper.php
 * 
 * Validation Helper
 * Menyediakan custom validation rules untuk aplikasi
 * 
 * @package    SIB-K
 * @subpackage Libraries
 * @category   Validation
 * @author     Development Team
 * @created    2025-01-01
 */

namespace App\Libraries;

class ValidationHelper
{
    /**
     * Validate Indonesian phone number (lokal)
     * 
     * Aturan:
     * - Boleh dikosongkan (akan ditangani rule permit_empty / required)
     * - Jika diisi: harus diawali 08 dan total 10–15 digit (08 + 8–13 digit)
     * 
     * @param string $phone
     * @return bool
     */
    public static function valid_phone($phone)
    {
        // Kalau kosong, anggap valid di level rule ini.
        // Kewajiban isi diatur oleh "required" atau "permit_empty".
        if ($phone === '' || $phone === null) {
            return true;
        }

        // Format lokal: 08xxxxxxxx dengan total 10–15 digit
        // 08 + 8–13 digit = 10–15 digit
        $pattern = '/^08[0-9]{8,13}$/';
        return preg_match($pattern, $phone) === 1;
    }

    /**
     * Validate NISN (10 digits)
     * 
     * @param string $nisn
     * @return bool
     */
    public static function valid_nisn($nisn)
    {
        return preg_match('/^[0-9]{10}$/', $nisn) === 1;
    }

    /**
     * Validate NIS format
     * 
     * Aturan baru:
     * - Hanya angka
     * - 4–20 digit
     * 
     * @param string $nis
     * @return bool
     */
    public static function valid_nis($nis)
    {
        return preg_match('/^[0-9]{4,20}$/', $nis) === 1;
    }

    /**
     * Validate Indonesian date format (d-m-Y or d/m/Y)
     * 
     * @param string $date
     * @return bool
     */
    public static function valid_indo_date($date)
    {
        $patterns = [
            '/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/',  // dd-mm-yyyy
            '/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', // dd/mm/yyyy
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $date)) {
                $parts = preg_split('/[-\/]/', $date);
                return checkdate((int)$parts[1], (int)$parts[0], (int)$parts[2]);
            }
        }

        return false;
    }

    /**
     * Validate academic year format (YYYY/YYYY)
     * 
     * @param string $year
     * @return bool
     */
    public static function valid_academic_year($year)
    {
        if (!preg_match('/^[0-9]{4}\/[0-9]{4}$/', $year)) {
            return false;
        }

        list($year1, $year2) = explode('/', $year);

        // Year2 should be year1 + 1
        return ((int)$year2 - (int)$year1) === 1;
    }

    /**
     * Validate password strength
     * Min 6 characters, at least 1 letter and 1 number
     * 
     * @param string $password
     * @return bool
     */
    public static function strong_password($password)
    {
        // At least 6 chars, 1 letter, 1 number
        return strlen($password) >= 6
            && preg_match('/[a-zA-Z]/', $password)
            && preg_match('/[0-9]/', $password);
    }

    /**
     * Validate time format (HH:MM)
     * 
     * @param string $time
     * @return bool
     */
    public static function valid_time($time)
    {
        if (!preg_match('/^[0-9]{2}:[0-9]{2}$/', $time)) {
            return false;
        }

        list($hour, $minute) = explode(':', $time);

        return (int)$hour >= 0 && (int)$hour <= 23
            && (int)$minute >= 0 && (int)$minute <= 59;
    }

    /**
     * Validate unique with soft delete
     * Check uniqueness excluding soft deleted records
     * 
     * @param string $value
     * @param string $params (format: table.field,id)
     * @param array $data
     * @return bool
     */
    public static function unique_with_soft_delete($value, $params, $data)
    {
        $db = \Config\Database::connect();

        list($table, $field, $ignore) = explode(',', $params);

        $builder = $db->table($table);
        $builder->where($field, $value);
        $builder->where('deleted_at', null);

        if (!empty($ignore)) {
            $builder->where('id !=', $ignore);
        }

        return $builder->countAllResults() === 0;
    }

    /**
     * Validate file extension
     * 
     * @param mixed $file
     * @param string $extensions (comma separated)
     * @return bool
     */
    public static function valid_file_extension($file, $extensions)
    {
        if (!is_object($file)) {
            return false;
        }

        $allowedExts = array_map('trim', explode(',', $extensions));
        $fileExt = strtolower($file->getExtension());

        return in_array($fileExt, $allowedExts);
    }

    /**
     * Validate file size (in KB)
     * 
     * @param mixed $file
     * @param int $maxSize
     * @return bool
     */
    public static function valid_file_size($file, $maxSize)
    {
        if (!is_object($file)) {
            return false;
        }

        $fileSize = $file->getSize() / 1024; // Convert to KB

        return $fileSize <= $maxSize;
    }

    /**
     * Validate image file
     * 
     * @param mixed $file
     * @return bool
     */
    public static function valid_image($file)
    {
        if (!is_object($file)) {
            return false;
        }

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExt = strtolower($file->getExtension());

        return in_array($fileExt, $allowedTypes) && $file->isValid();
    }

    /**
     * Validate date range (start < end)
     * 
     * @param string $startDate
     * @param string $endDate
     * @return bool
     */
    public static function valid_date_range($startDate, $endDate)
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);

        return $start !== false && $end !== false && $start < $end;
    }

    /**
     * Validate username format
     * Alphanumeric, can include underscore and dot
     * 
     * @param string $username
     * @return bool
     */
    public static function valid_username($username)
    {
        // 3-50 chars, alphanumeric with underscore and dot
        return preg_match('/^[a-zA-Z0-9._]{3,50}$/', $username) === 1;
    }

    /**
     * Validate Indonesian ID card number (NIK)
     * 
     * @param string $nik
     * @return bool
     */
    public static function valid_nik($nik)
    {
        // NIK must be 16 digits
        return preg_match('/^[0-9]{16}$/', $nik) === 1;
    }

    /**
     * Validate grade level
     * 
     * @param string $grade
     * @return bool
     */
    public static function valid_grade_level($grade)
    {
        return in_array($grade, ['X', 'XI', 'XII']);
    }

    /**
     * Validate semester
     * 
     * @param string $semester
     * @return bool
     */
    public static function valid_semester($semester)
    {
        return in_array($semester, ['Ganjil', 'Genap']);
    }

    /**
     * Validate gender
     * 
     * @param string $gender
     * @return bool
     */
    public static function valid_gender($gender)
    {
        return in_array($gender, ['L', 'P']);
    }

    /**
     * Validate religion
     * 
     * @param string $religion
     * @return bool
     */
    public static function valid_religion($religion)
    {
        $validReligions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
        return in_array($religion, $validReligions);
    }

    /**
     * Sanitize input string
     * 
     * @param string $input
     * @return string
     */
    public static function sanitize_input($input)
    {
        // Remove HTML tags and special characters
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return trim($input);
    }

    /**
     * Sanitize filename
     * 
     * @param string $filename
     * @return string
     */
    public static function sanitize_filename($filename)
    {
        // Remove special characters from filename
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        return strtolower($filename);
    }

    /**
     * Format phone number to Indonesian format
     * 
     * @param string $phone
     * @return string
     */
    public static function format_phone($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert to 08xx format jika sebelumnya 62...
        if (substr($phone, 0, 2) === '62') {
            $phone = '0' . substr($phone, 2);
        }

        return $phone;
    }

    /**
     * Format date to MySQL format (Y-m-d)
     * 
     * @param string $date (d-m-Y or d/m/Y)
     * @return string|null
     */
    public static function format_date_to_mysql($date)
    {
        if (self::valid_indo_date($date)) {
            $parts = preg_split('/[-\/]/', $date);
            return sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
        }

        return null;
    }

    /**
     * Format date from MySQL to Indonesian format
     * 
     * @param string $date (Y-m-d)
     * @param string $separator
     * @return string|null
     */
    public static function format_date_to_indo($date, $separator = '/')
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return null;
        }

        return date("d{$separator}m{$separator}Y", $timestamp);
    }

    /**
     * Get validation error messages in Indonesian
     * 
     * @return array
     */
    public static function getCustomMessages()
    {
        return [
            'valid_phone'         => '{field} harus diawali 08 dan terdiri dari 10–15 digit (format 08xxxxxxxxxx)',
            'valid_nisn'          => '{field} harus 10 digit angka',
            'valid_nis'           => '{field} harus 4–20 digit angka',
            'valid_indo_date'     => '{field} harus berformat tanggal Indonesia (dd-mm-yyyy)',
            'valid_academic_year' => '{field} harus berformat tahun ajaran (YYYY/YYYY)',
            'strong_password'     => '{field} minimal 6 karakter dengan kombinasi huruf dan angka',
            'valid_time'          => '{field} harus berformat waktu (HH:MM)',
            'valid_file_extension'=> 'Ekstensi file {field} tidak diizinkan',
            'valid_file_size'     => 'Ukuran file {field} terlalu besar',
            'valid_image'         => '{field} harus berupa file gambar yang valid',
            'valid_username'      => '{field} hanya boleh mengandung huruf, angka, titik, dan underscore',
            'valid_nik'           => '{field} harus 16 digit angka',
            'valid_grade_level'   => '{field} harus X, XI, atau XII',
            'valid_semester'      => '{field} harus Ganjil atau Genap',
            'valid_gender'        => '{field} harus L atau P',
            'valid_religion'      => '{field} tidak valid',
        ];
    }
}
