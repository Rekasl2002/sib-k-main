<?php

/**
 * File Path: app/Libraries/ExcelImporter.php
 *
 * Excel Importer Library
 * Library untuk import data siswa dari file Excel menggunakan PhpSpreadsheet
 *
 * Catatan (Normalisasi Nama):
 * - Kolom students.full_name sudah DIHAPUS.
 * - Nama siswa disimpan di users.full_name.
 * - File Excel tetap punya kolom "Nama Lengkap" untuk mengisi users.full_name (bukan students.full_name).
 *
 * @package    SIB-K
 * @subpackage Libraries
 * @category   Data Import
 * @author     Development Team
 * @created    2025-01-01
 * @updated    2026-01-02
 */

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

use App\Models\UserModel;
use App\Models\StudentModel;
use App\Models\ClassModel;
use App\Models\RoleModel;

class ExcelImporter
{
    protected UserModel $userModel;
    protected StudentModel $studentModel;
    protected ClassModel $classModel;
    protected RoleModel $roleModel;
    protected $db;

    protected array $results = [
        'total_rows' => 0,
        'success'    => 0,
        'failed'     => 0,
        'errors'     => [],
        'warnings'   => [],
    ];

    /**
     * Penanda nilai yang sudah muncul dalam 1 file import
     * untuk deteksi duplikat di dalam file itu sendiri.
     *
     * @var array<string,int>
     */
    protected array $seenNisn   = [];
    protected array $seenNis    = [];
    protected array $seenEmails = [];

    public function __construct()
    {
        $this->userModel    = new UserModel();
        $this->studentModel = new StudentModel();
        $this->classModel   = new ClassModel();
        $this->roleModel    = new RoleModel();
        $this->db           = \Config\Database::connect();
    }

    /**
     * Import students from Excel file
     *
     * @param string $filePath Path to Excel file
     * @param array  $options  Import options
     * @return array Import results
     * @throws \Exception
     */
    public function importStudents(string $filePath, array $options = []): array
    {
        // Reset results & penanda duplikat per import
        $this->resetResults();
        $this->seenNisn   = [];
        $this->seenNis    = [];
        $this->seenEmails = [];

        // Load Excel file
        $spreadsheet = IOFactory::load($filePath);
        $worksheet   = $spreadsheet->getActiveSheet();
        $highestRow  = $worksheet->getHighestRow();

        // Validate headers
        if (!$this->validateHeaders($worksheet)) {
            throw new \Exception('Format template Excel tidak sesuai. Silakan gunakan template yang disediakan.');
        }

        // Get student role ID
        $studentRole = $this->roleModel->where('role_name', 'Siswa')->first();
        if (!$studentRole) {
            throw new \Exception('Role "Siswa" tidak ditemukan dalam database.');
        }

        // Get parent role ID (boleh null kalau sistem kamu belum menyediakan role ini)
        $parentRole = $this->roleModel->where('role_name', 'Orang Tua')->first();
        $parentRoleId = $parentRole ? (int) $parentRole['id'] : null;

        // Process each row (skip header)
        for ($row = 2; $row <= $highestRow; $row++) {
            $this->results['total_rows']++;

            // Per-row transaction supaya atomic dan tidak meninggalkan data "nyangkut"
            $this->db->transBegin();

            try {
                $rowData = $this->extractRowData($worksheet, $row);

                // Skip empty rows
                if ($this->isEmptyRow($rowData)) {
                    $this->results['total_rows']--;
                    $this->db->transRollback();
                    continue;
                }

                // Tambah field raw phone sebelum normalisasi
                $rowData['_raw_student_phone'] = $rowData['student_phone'];
                $rowData['_raw_parent_phone']  = $rowData['parent_phone'];

                // Normalisasi nomor HP (hapus spasi, '-', '()'; validasi digit / +62)
                $rowData['student_phone'] = $this->normalizePhone($rowData['student_phone']);
                $rowData['parent_phone']  = $this->normalizePhone($rowData['parent_phone']);

                // Deteksi duplikat di dalam file (NISN, NIS, Email siswa)
                $dupErrors = [];

                if ($rowData['nisn'] !== '') {
                    if (isset($this->seenNisn[$rowData['nisn']])) {
                        $firstRow    = $this->seenNisn[$rowData['nisn']];
                        $dupErrors[] = "NISN {$rowData['nisn']} duplikat di file (pertama di baris {$firstRow})";
                    } else {
                        $this->seenNisn[$rowData['nisn']] = $row;
                    }
                }

                if ($rowData['nis'] !== '') {
                    if (isset($this->seenNis[$rowData['nis']])) {
                        $firstRow    = $this->seenNis[$rowData['nis']];
                        $dupErrors[] = "NIS {$rowData['nis']} duplikat di file (pertama di baris {$firstRow})";
                    } else {
                        $this->seenNis[$rowData['nis']] = $row;
                    }
                }

                if ($rowData['email'] !== '') {
                    if (isset($this->seenEmails[$rowData['email']])) {
                        $firstRow    = $this->seenEmails[$rowData['email']];
                        $dupErrors[] = "Email siswa {$rowData['email']} duplikat di file (pertama di baris {$firstRow})";
                    } else {
                        $this->seenEmails[$rowData['email']] = $row;
                    }
                }

                if (!empty($dupErrors)) {
                    $this->db->transRollback();
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$row}: " . implode(', ', $dupErrors);
                    continue;
                }

                // Validate row data
                $validation = $this->validateRowData($rowData, $row);
                if (!$validation['valid']) {
                    $this->db->transRollback();
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$row}: " . implode(', ', $validation['errors']);
                    continue;
                }

                // Process import for this row
                $this->processStudentImport(
                    $rowData,
                    (int) $studentRole['id'],
                    $parentRoleId,
                    $row
                );

                // Commit per row
                if ($this->db->transStatus() === false) {
                    $this->db->transRollback();
                    throw new \Exception('Terjadi kesalahan saat menyimpan data ke database.');
                }
                $this->db->transCommit();

                $this->results['success']++;
            } catch (\Exception $e) {
                $this->db->transRollback();
                $this->results['failed']++;
                $this->results['errors'][] = "Baris {$row}: " . $e->getMessage();
            }
        }

        return $this->results;
    }

    /**
     * Validate Excel headers
     *
     * @param Worksheet $worksheet
     * @return bool
     */
    protected function validateHeaders(Worksheet $worksheet): bool
    {
        $expectedHeaders = [
            'A1' => 'NISN',
            'B1' => 'NIS',
            'C1' => 'Nama Lengkap',
            'D1' => 'Email',
            'E1' => 'Password',
            'F1' => 'Jenis Kelamin',
            'G1' => 'Tempat Lahir',
            'H1' => 'Tanggal Lahir',
            'I1' => 'Agama',
            'J1' => 'Alamat',
            'K1' => 'Kelas',
            'L1' => 'Tanggal Masuk',
            'M1' => 'Status',
            'N1' => 'Nama Orang Tua',
            'O1' => 'Email Orang Tua',
            'P1' => 'No. HP Siswa',
            'Q1' => 'No. HP Orang Tua',
        ];

        foreach ($expectedHeaders as $cell => $expectedValue) {
            $actualValue = trim((string) $worksheet->getCell($cell)->getValue());
            if (strcasecmp($actualValue, $expectedValue) !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract data from row
     *
     * @param Worksheet $worksheet
     * @param int       $row
     * @return array
     */
    protected function extractRowData(Worksheet $worksheet, int $row): array
    {
        // Normalisasi status di sini (ucwords) supaya case-insensitive
        $statusRaw  = trim((string) $worksheet->getCell("M{$row}")->getValue());
        $statusNorm = $statusRaw !== '' ? ucwords(strtolower($statusRaw)) : 'Aktif';

        return [
            'nisn'           => trim((string) $worksheet->getCell("A{$row}")->getValue()),
            'nis'            => trim((string) $worksheet->getCell("B{$row}")->getValue()),
            'full_name'      => trim((string) $worksheet->getCell("C{$row}")->getValue()),
            'email'          => trim((string) $worksheet->getCell("D{$row}")->getValue()),
            'password'       => trim((string) $worksheet->getCell("E{$row}")->getValue()),
            'gender'         => strtoupper(trim((string) $worksheet->getCell("F{$row}")->getValue())),
            'birth_place'    => trim((string) $worksheet->getCell("G{$row}")->getValue()),
            'birth_date'     => $this->parseDate($worksheet->getCell("H{$row}")->getValue()),
            'religion'       => trim((string) $worksheet->getCell("I{$row}")->getValue()),
            'address'        => trim((string) $worksheet->getCell("J{$row}")->getValue()),
            'class_name'     => trim((string) $worksheet->getCell("K{$row}")->getValue()),
            'admission_date' => $this->parseDate($worksheet->getCell("L{$row}")->getValue()),
            'status'         => $statusNorm,
            'parent_name'    => trim((string) $worksheet->getCell("N{$row}")->getValue()),
            'parent_email'   => trim((string) $worksheet->getCell("O{$row}")->getValue()),
            'student_phone'  => trim((string) $worksheet->getCell("P{$row}")->getValue()),
            'parent_phone'   => trim((string) $worksheet->getCell("Q{$row}")->getValue()),
        ];
    }

    /**
     * Parse date from Excel (prioritas format Indonesia: DD-MM-YYYY / DD/MM/YYYY / DDMMYYYY)
     *
     * @param mixed $value
     * @return string|null  Format akhir: Y-m-d atau null jika tidak valid
     */
    protected function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Jika numeric dan cukup besar, kemungkinan Excel date serial (jaga kompatibilitas)
        if (is_numeric($value) && (float) $value > 25569) { // 25569 ≈ 1970-01-01
            try {
                $date = Date::excelToDateTimeObject((float) $value);
                return $date->format('Y-m-d');
            } catch (\Throwable $e) {
                // lanjut ke parsing string biasa
            }
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        // 1) DD-MM-YYYY / DD/MM/YYYY / DD.MM.YYYY
        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $str, $m)) {
            $d  = (int) $m[1];
            $mo = (int) $m[2];
            $y  = (int) $m[3];

            if (checkdate($mo, $d, $y)) {
                return sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
            return null;
        }

        // 2) DDMMYYYY
        if (preg_match('/^(\d{2})(\d{2})(\d{4})$/', $str, $m)) {
            $d  = (int) $m[1];
            $mo = (int) $m[2];
            $y  = (int) $m[3];

            if (checkdate($mo, $d, $y)) {
                return sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
            return null;
        }

        // 3) YYYY-MM-DD / YYYY/MM/DD
        if (preg_match('/^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$/', $str, $m)) {
            $y  = (int) $m[1];
            $mo = (int) $m[2];
            $d  = (int) $m[3];

            if (checkdate($mo, $d, $y)) {
                return sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
            return null;
        }

        // 4) Fallback: strtotime
        $timestamp = strtotime($str);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Normalisasi nomor HP:
     * - Hilangkan spasi, tanda '-' dan '()'
     * - Boleh diawali '+' lalu hanya digit
     * - Selain itu harus hanya digit
     *
     * @param string|null $phone
     * @return string|null '' = kosong, null = format tidak valid, string = nomor bersih
     */
    protected function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim((string) $phone);
        if ($phone === '') {
            return '';
        }

        $phone = str_replace([' ', '-', '(', ')'], '', $phone);
        if ($phone === '') {
            return '';
        }

        if ($phone[0] === '+') {
            $numberPart = substr($phone, 1);
            if ($numberPart === '' || !ctype_digit($numberPart)) {
                return null;
            }
            return '+' . $numberPart;
        }

        if (!ctype_digit($phone)) {
            return null;
        }

        return $phone;
    }

    /**
     * Check if row is empty
     *
     * @param array $rowData
     * @return bool
     */
    protected function isEmptyRow(array $rowData): bool
    {
        return empty($rowData['nisn']) && empty($rowData['nis']) && empty($rowData['full_name']);
    }

    /**
     * Validate row data
     *
     * @param array $rowData
     * @param int   $rowNumber
     * @return array{valid:bool,errors:array}
     */
    protected function validateRowData(array $rowData, int $rowNumber): array
    {
        $errors = [];

        // Required fields
        if (empty($rowData['nisn'])) {
            $errors[] = 'NISN tidak boleh kosong';
        } elseif (strlen($rowData['nisn']) < 10 || !is_numeric($rowData['nisn'])) {
            $errors[] = 'NISN minimal 10 digit angka';
        }

        if (empty($rowData['nis'])) {
            $errors[] = 'NIS tidak boleh kosong';
        } elseif (strlen($rowData['nis']) < 5) {
            $errors[] = 'NIS minimal 5 karakter';
        }

        if (empty($rowData['full_name'])) {
            $errors[] = 'Nama lengkap tidak boleh kosong';
        }

        if (empty($rowData['email'])) {
            $errors[] = 'Email tidak boleh kosong';
        } elseif (!filter_var($rowData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid';
        }

        // Tanggal lahir & masuk: wajib dan harus berhasil diparse
        if ($rowData['birth_date'] === null) {
            $errors[] = 'Tanggal lahir tidak boleh kosong atau formatnya tidak dikenali. Gunakan DD-MM-YYYY, DD/MM/YYYY, atau DDMMYYYY.';
        }
        if ($rowData['admission_date'] === null) {
            $errors[] = 'Tanggal masuk tidak boleh kosong atau formatnya tidak dikenali. Gunakan DD-MM-YYYY, DD/MM/YYYY, atau DDMMYYYY.';
        }

        // Student phone: wajib
        $rawStudentPhone = $rowData['_raw_student_phone'] ?? '';
        if ($rawStudentPhone === '') {
            $errors[] = 'No. HP siswa tidak boleh kosong';
        } elseif ($rowData['student_phone'] === null) {
            $errors[] = 'Format No. HP siswa tidak valid. Gunakan hanya angka, boleh diawali +62.';
        } elseif (strlen($rowData['student_phone']) > 15) {
            $errors[] = 'No. HP siswa maksimal 15 karakter';
        }

        // Parent info validation (jika ada salah satu diisi)
        $rawParentPhone = $rowData['_raw_parent_phone'] ?? '';
        $hasParentData = !empty($rowData['parent_name'])
            || !empty($rowData['parent_email'])
            || $rawParentPhone !== '';

        if ($hasParentData) {
            if (empty($rowData['parent_name'])) {
                $errors[] = 'Nama orang tua tidak boleh kosong jika data orang tua diisi';
            }
            if (empty($rowData['parent_email'])) {
                $errors[] = 'Email orang tua tidak boleh kosong jika data orang tua diisi';
            } elseif (!filter_var($rowData['parent_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Format email orang tua tidak valid';
            }

            if ($rawParentPhone === '') {
                $errors[] = 'No. HP orang tua tidak boleh kosong jika data orang tua diisi';
            } elseif ($rowData['parent_phone'] === null) {
                $errors[] = 'Format No. HP orang tua tidak valid. Gunakan hanya angka, boleh diawali +62.';
            } elseif (strlen($rowData['parent_phone']) > 15) {
                $errors[] = 'No. HP orang tua maksimal 15 karakter';
            }
        }

        // Gender validation
        if (!in_array($rowData['gender'], ['L', 'P'], true)) {
            $errors[] = 'Jenis kelamin harus L atau P';
        }

        // Status validation
        $validStatus = ['Aktif', 'Alumni', 'Pindah', 'Keluar'];
        if (!in_array($rowData['status'], $validStatus, true)) {
            $errors[] = 'Status harus salah satu dari: ' . implode(', ', $validStatus);
        }

        // Check duplicate NISN di database (include soft deleted untuk antisipasi unique constraint)
        $existingNisn = $this->studentModel->withDeleted()->where('nisn', $rowData['nisn'])->first();
        if ($existingNisn) {
            if (!empty($existingNisn['deleted_at'])) {
                $errors[] = "NISN {$rowData['nisn']} pernah ada (sudah dihapus). Pulihkan (restore) atau gunakan NISN lain.";
            } else {
                $errors[] = "NISN {$rowData['nisn']} sudah terdaftar di database";
            }
        }

        // Check duplicate NIS di database (include soft deleted)
        $existingNis = $this->studentModel->withDeleted()->where('nis', $rowData['nis'])->first();
        if ($existingNis) {
            if (!empty($existingNis['deleted_at'])) {
                $errors[] = "NIS {$rowData['nis']} pernah ada (sudah dihapus). Pulihkan (restore) atau gunakan NIS lain.";
            } else {
                $errors[] = "NIS {$rowData['nis']} sudah terdaftar di database";
            }
        }

        // Check duplicate email di database (include soft deleted)
        $existingEmail = $this->userModel->withDeleted()->where('email', $rowData['email'])->first();
        if ($existingEmail) {
            if (!empty($existingEmail['deleted_at'])) {
                $errors[] = "Email {$rowData['email']} pernah dipakai (sudah dihapus). Pulihkan (restore) atau gunakan email lain.";
            } else {
                $errors[] = "Email {$rowData['email']} sudah terdaftar di database";
            }
        }

        // Validate class if provided
        if (!empty($rowData['class_name'])) {
            $class = $this->classModel->where('class_name', $rowData['class_name'])->first();
            if (!$class) {
                $errors[] = "Kelas '{$rowData['class_name']}' tidak ditemukan";
            }
        }

        // Validate parent email if provided (jaga-jaga dobel pesan)
        if (!empty($rowData['parent_email']) && !filter_var($rowData['parent_email'], FILTER_VALIDATE_EMAIL)) {
            if (!in_array('Format email orang tua tidak valid', $errors, true)) {
                $errors[] = 'Format email orang tua tidak valid';
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Process student import
     *
     * @param array    $rowData
     * @param int      $studentRoleId
     * @param int|null $parentRoleId
     * @param int      $rowNumber
     * @return void
     * @throws \Exception
     */
    protected function processStudentImport(array $rowData, int $studentRoleId, ?int $parentRoleId, int $rowNumber): void
    {
        // Get class ID if class name provided
        $classId = null;
        if (!empty($rowData['class_name'])) {
            $class   = $this->classModel->where('class_name', $rowData['class_name'])->first();
            $classId = $class ? (int) $class['id'] : null;
        }

        // Generate username from NISN
        $username = $rowData['nisn'];

        // Build base password from birth date (DDMMYYYY) atau fallback
        $birthPassword = null;
        if (!empty($rowData['birth_date'])) {
            try {
                $dateObj       = new \DateTime($rowData['birth_date']); // Y-m-d dari parseDate()
                $birthPassword = $dateObj->format('dmY'); // contoh: 02052009
            } catch (\Throwable $e) {
                $birthPassword = null;
            }
        }

        if (!empty($rowData['password'])) {
            $plainPassword = $rowData['password'];
        } elseif ($birthPassword !== null) {
            $plainPassword = $birthPassword;
        } else {
            $plainPassword = 'password123';
        }

        // Create user account for student
        // Penting: password dikirim PLAIN, biar UserModel yang hash ke password_hash
        $userData = [
            'role_id'   => $studentRoleId,
            'username'  => $username,
            'email'     => $rowData['email'],
            'password'  => $plainPassword,          // <–– TIDAK di-hash di sini
            'full_name' => $rowData['full_name'],   // disimpan di users
            'phone'     => $rowData['student_phone'],
            'is_active' => 1,
        ];

        if (!$this->userModel->insert($userData)) {
            throw new \Exception('Gagal membuat akun user: ' . implode(', ', $this->userModel->errors()));
        }

        $userId = (int) $this->userModel->getInsertID();

        // Create parent account if parent info provided
        $parentId = null;
        $hasParent = !empty($rowData['parent_name']) && !empty($rowData['parent_email']);

        if ($hasParent) {
            // Check if parent email already exists (include soft deleted)
            $existingParent = $this->userModel->withDeleted()->where('email', $rowData['parent_email'])->first();

            if ($existingParent) {
                if (!empty($existingParent['deleted_at'])) {
                    // Soft-deleted email akan bentrok kalau kita buat baru
                    throw new \Exception("Email orang tua {$rowData['parent_email']} pernah dipakai (sudah dihapus). Pulihkan (restore) atau gunakan email lain.");
                }

                $parentId = (int) $existingParent['id'];

                // Optional: update phone jika sebelumnya kosong
                if (!empty($rowData['parent_phone']) && empty($existingParent['phone'])) {
                    $this->userModel->update($parentId, [
                        'phone' => $rowData['parent_phone'],
                    ]);
                }

                $this->results['warnings'][] = "Baris {$rowNumber}: Email orang tua sudah terdaftar, menggunakan akun yang ada.";
            } else {
                if ($parentRoleId === null) {
                    // Sistem belum punya role Orang Tua, jangan bikin akun parent
                    $this->results['warnings'][] = "Baris {$rowNumber}: Role 'Orang Tua' tidak ditemukan, akun orang tua tidak dibuat. Data parent di student dibiarkan kosong.";
                    $parentId = null;
                } else {
                    // Create new parent account
                    $parentData = [
                        'role_id'   => $parentRoleId,
                        'username'  => strtolower(str_replace(' ', '_', $rowData['parent_name'])) . '_' . substr((string) $rowData['nisn'], -4),
                        'email'     => $rowData['parent_email'],
                        'password'  => $plainPassword,          // <–– PLAIN, tidak di-hash
                        'full_name' => $rowData['parent_name'],
                        'phone'     => $rowData['parent_phone'],
                        'is_active' => 1,
                    ];

                    if (!$this->userModel->insert($parentData)) {
                        throw new \Exception('Gagal membuat akun orang tua: ' . implode(', ', $this->userModel->errors()));
                    }

                    $parentId = (int) $this->userModel->getInsertID();
                }
            }
        }

        /**
         * Create student record
         * IMPORTANT:
         * - students.full_name sudah DIHAPUS, jadi jangan insert field itu.
         * - Nama siswa berasal dari users.full_name.
         */
        $studentData = [
            'user_id'                => $userId,
            'class_id'               => $classId,
            'nisn'                   => $rowData['nisn'],
            'nis'                    => $rowData['nis'],
            'gender'                 => $rowData['gender'],
            'birth_place'            => $rowData['birth_place'] ?: null,
            'birth_date'             => $rowData['birth_date'],
            'religion'               => $rowData['religion'] ?: null,
            'address'                => $rowData['address'] ?: null,
            'parent_id'              => $parentId,
            'admission_date'         => $rowData['admission_date'],
            'status'                 => $rowData['status'],
            'total_violation_points' => 0,
        ];

        if (!$this->studentModel->insert($studentData)) {
            throw new \Exception('Gagal membuat data siswa: ' . implode(', ', $this->studentModel->errors()));
        }
    }

    /**
     * Generate Excel template for student import
     *
     * @param string|null $savePath Path to save template
     * @return string Path to generated file
     */
    public function generateTemplate(?string $savePath = null): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'NISN',
            'NIS',
            'Nama Lengkap',
            'Email',
            'Password',
            'Jenis Kelamin',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Agama',
            'Alamat',
            'Kelas',
            'Tanggal Masuk',
            'Status',
            'Nama Orang Tua',
            'Email Orang Tua',
            'No. HP Siswa',
            'No. HP Orang Tua',
        ];

        $sheet->fromArray($headers, null, 'A1');

        // Style headers
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);

        // PAKSA SEMUA KOLOM A–Q MENJADI TEKS (@)
        foreach (range('A', 'Q') as $col) {
            $sheet->getStyle($col . ':' . $col)
                ->getNumberFormat()
                ->setFormatCode('@'); // @ = Text format
        }

        // Contoh data
        $sampleData = [
            '1234567890',              // NISN
            '001',                     // NIS
            'Ahmad Fauzi',             // Nama Lengkap
            'ahmad.fauzi@example.com', // Email
            '',                        // Password (kosong -> pakai default dari tgl lahir)
            'L',                       // Jenis Kelamin
            'Bandung',                 // Tempat Lahir
            '15-05-2008',              // Tanggal Lahir
            'Islam',                   // Agama
            'Jl. Contoh No. 123',      // Alamat
            'X-IPA-1',                 // Kelas
            '01-07-2024',              // Tanggal Masuk
            'Aktif',                   // Status
            'Bapak Ahmad',             // Nama Orang Tua
            'bapak.ahmad@example.com', // Email Orang Tua
            '081234567890',            // No. HP Siswa
            '081298765432',            // No. HP Orang Tua
        ];

        $sheet->fromArray([$sampleData], null, 'A2');

        // Set column widths
        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add notes / petunjuk
        $sheet->getCell('A4')->setValue('PETUNJUK:');
        $sheet->getCell('A5')->setValue('1. Semua kolom sudah di-set sebagai TEKS. Jangan ubah format sel.');
        $sheet->getCell('A6')->setValue('2. NISN & NIS: isi tanpa spasi. Nol di depan akan dipertahankan karena format TEKS.');
        $sheet->getCell('A7')->setValue('3. Tanggal Lahir & Tanggal Masuk: gunakan DD-MM-YYYY atau DD/MM/YYYY atau DDMMYYYY.');
        $sheet->getCell('A8')->setValue('4. Status: Aktif, Alumni, Pindah, atau Keluar.');
        $sheet->getCell('A9')->setValue("5. Password: kosongkan untuk menggunakan default (tanggal lahir DDMMYYYY), atau 'password123' jika tanggal lahir kosong/tidak valid.");
        $sheet->getCell('A10')->setValue('6. Kelas: harus sesuai dengan nama kelas yang sudah terdaftar di sistem.');
        $sheet->getCell('A11')->setValue('7. No. HP: isi seperti 081234567890. Karena format TEKS, nol di depan tidak akan hilang.');
        $sheet->getCell('A12')->setValue('8. Jika menyalin dari file lain, pastikan Paste Values saja (tanpa membawa format angka dari file lama).');
        $sheet->getCell('A13')->setValue('9. Jika orang tua memiliki lebih dari satu anak, gunakan email orang tua yang sama untuk semua anak agar akun orang tua tidak dibuat ganda.');
        $sheet->getCell('A14')->setValue("10. Password Orang Tua akan mengikuti password anak (tanggal lahir atau 'password123').");
        $sheet->getCell('A15')->setValue('11. Jika orang tua memiliki lebih dari satu anak, password orang tua akan mengikuti password anak (siswa) yang dimasukan pertama (paling atas).');
        $sheet->getCell('A16')->setValue('Jika salah, coba cek password anak (siswa) lain.');
        $sheet->getCell('A17')->setValue('12. Hapus contoh isi dan petunjuk jika sudah tidak digunakan.');

        // ======================
        // DATA VALIDATION / DROPDOWN
        // ======================
        $maxRow = 500;

        // DROPDOWN JENIS KELAMIN (F)
        $genderList = '"L,P"';

        $genderDv = $sheet->getCell('F2')->getDataValidation();
        $genderDv->setType(DataValidation::TYPE_LIST);
        $genderDv->setErrorStyle(DataValidation::STYLE_WARNING);
        $genderDv->setAllowBlank(false);
        $genderDv->setShowInputMessage(true);
        $genderDv->setShowErrorMessage(true);
        $genderDv->setShowDropDown(true);
        $genderDv->setErrorTitle('Nilai tidak sesuai');
        $genderDv->setError('Pilih salah satu dari daftar L atau P, atau lanjutkan dengan hati-hati.');
        $genderDv->setPromptTitle('Pilih Jenis Kelamin');
        $genderDv->setPrompt('Gunakan L untuk Laki-laki, P untuk Perempuan.');
        $genderDv->setFormula1($genderList);

        for ($row = 2; $row <= $maxRow; $row++) {
            $sheet->getCell('F' . $row)->setDataValidation(clone $genderDv);
        }

        // DROPDOWN STATUS (M)
        $statusList = '"Aktif,Alumni,Pindah,Keluar"';

        $statusDv = $sheet->getCell('M2')->getDataValidation();
        $statusDv->setType(DataValidation::TYPE_LIST);
        $statusDv->setErrorStyle(DataValidation::STYLE_WARNING);
        $statusDv->setAllowBlank(false);
        $statusDv->setShowInputMessage(true);
        $statusDv->setShowErrorMessage(true);
        $statusDv->setShowDropDown(true);
        $statusDv->setErrorTitle('Status tidak dikenal');
        $statusDv->setError('Pilih salah satu status yang tersedia, atau lanjutkan dengan hati-hati.');
        $statusDv->setPromptTitle('Pilih Status Siswa');
        $statusDv->setPrompt('Status: Aktif, Alumni, Pindah, atau Keluar.');
        $statusDv->setFormula1($statusList);

        for ($row = 2; $row <= $maxRow; $row++) {
            $sheet->getCell('M' . $row)->setDataValidation(clone $statusDv);
        }

        // DROPDOWN AGAMA (I)
        $religions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'];
        $religionList = '"' . implode(',', $religions) . '"';

        $religionDv = $sheet->getCell('I2')->getDataValidation();
        $religionDv->setType(DataValidation::TYPE_LIST);
        $religionDv->setErrorStyle(DataValidation::STYLE_WARNING);
        $religionDv->setAllowBlank(true);
        $religionDv->setShowInputMessage(true);
        $religionDv->setShowErrorMessage(true);
        $religionDv->setShowDropDown(true);
        $religionDv->setErrorTitle('Agama tidak ada di daftar');
        $religionDv->setError('Pilih salah satu dari daftar, atau lanjutkan jika memang perlu isi lain.');
        $religionDv->setPromptTitle('Pilih Agama');
        $religionDv->setPrompt('Pilih agama dari daftar, atau ketik manual jika tidak tersedia.');
        $religionDv->setFormula1($religionList);

        for ($row = 2; $row <= $maxRow; $row++) {
            $sheet->getCell('I' . $row)->setDataValidation(clone $religionDv);
        }

        // DROPDOWN KELAS (K) - dari database
        $classRecords = $this->classModel
            ->where('is_active', 1)
            ->orderBy('class_name', 'ASC')
            ->findAll();

        $classNames = [];
        foreach ($classRecords as $cls) {
            if (!empty($cls['class_name'])) {
                $classNames[] = $cls['class_name'];
            }
        }

        if (!empty($classNames)) {
            $safeClassNames = array_map(static function ($v) {
                return str_replace('"', '""', $v);
            }, $classNames);

            $classList = '"' . implode(',', $safeClassNames) . '"';

            $classDv = $sheet->getCell('K2')->getDataValidation();
            $classDv->setType(DataValidation::TYPE_LIST);
            $classDv->setErrorStyle(DataValidation::STYLE_WARNING);
            $classDv->setAllowBlank(true);
            $classDv->setShowInputMessage(true);
            $classDv->setShowErrorMessage(true);
            $classDv->setShowDropDown(true);
            $classDv->setErrorTitle('Kelas tidak terdaftar');
            $classDv->setError('Pilih kelas yang sudah ada di sistem, atau lanjutkan dengan hati-hati.');
            $classDv->setPromptTitle('Pilih Kelas');
            $classDv->setPrompt('Pilih kelas sesuai kelas yang terdaftar di sistem.');
            $classDv->setFormula1($classList);

            for ($row = 2; $row <= $maxRow; $row++) {
                $sheet->getCell('K' . $row)->setDataValidation(clone $classDv);
            }
        }

        // Save file
        if (!$savePath) {
            $savePath = WRITEPATH . 'uploads/template_import_siswa.xlsx';
        }

        // Ensure directory exists
        $directory = dirname($savePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($savePath);

        return $savePath;
    }

    /**
     * Reset results
     *
     * @return void
     */
    protected function resetResults(): void
    {
        $this->results = [
            'total_rows' => 0,
            'success'    => 0,
            'failed'     => 0,
            'errors'     => [],
            'warnings'   => [],
        ];
    }

    /**
     * Get import results
     *
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
