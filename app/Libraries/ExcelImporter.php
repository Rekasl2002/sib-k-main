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
 * Update (2026-01-30):
 * - Header jadi fleksibel: bisa pakai Template A–Q atau file sekolah (kolom berbeda/kurang).
 * - Auto-fill: NIS dari NISN, email siswa/orangtua dari NISN, gender "Laki-Laki/Perempuan" -> L/P.
 * - Mapping parent_name dari (Nama Wali -> Nama Ibu Kandung -> Nama Ayah Kandung).
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

    /**
     * Header map: normalized header => column letter
     * @var array<string,string>
     */
    protected array $headerMap = [];

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
     * Options:
     * - sheet_name: string (optional) pilih sheet tertentu
     * - sheet_index: int (optional) pilih sheet by index (0-based)
     * - strict_headers: bool (default false) kalau true, wajib template A1..Q1 persis
     * - default_email_domain: string (default "gmail.com")
     * - default_admission_date: string (default today Y-m-d)
     *
     * @param string $filePath Path to Excel file
     * @param array  $options  Import options
     * @return array Import results
     * @throws \Exception
     */
    public function importStudents(string $filePath, array $options = []): array
    {
        $this->resetResults();
        $this->seenNisn   = [];
        $this->seenNis    = [];
        $this->seenEmails = [];
        $this->headerMap  = [];

        $spreadsheet = IOFactory::load($filePath);

        // Pilih sheet (kalau file sekolah punya banyak sheet: Kelas 10 - A/B/C)
        if (!empty($options['sheet_name'])) {
            $worksheet = $spreadsheet->getSheetByName((string) $options['sheet_name']);
            if (!$worksheet) {
                throw new \Exception('Sheet "' . $options['sheet_name'] . '" tidak ditemukan pada file Excel.');
            }
        } elseif (isset($options['sheet_index'])) {
            $idx = (int) $options['sheet_index'];
            $worksheet = $spreadsheet->getSheet($idx);
            if (!$worksheet) {
                throw new \Exception('Sheet index ' . $idx . ' tidak valid pada file Excel.');
            }
        } else {
            $worksheet = $spreadsheet->getActiveSheet();
        }

        $highestRow = $worksheet->getHighestRow();

        // Validate / build header map
        if (!$this->validateHeaders($worksheet, $options)) {
            throw new \Exception('Header Excel tidak dikenali. Pastikan ada minimal: NISN/NIS, Nama, Tanggal Lahir, Jenis Kelamin.');
        }

        $studentRole = $this->roleModel->where('role_name', 'Siswa')->first();
        if (!$studentRole) {
            throw new \Exception('Role "Siswa" tidak ditemukan dalam database.');
        }

        $parentRole = $this->roleModel->where('role_name', 'Orang Tua')->first();
        $parentRoleId = $parentRole ? (int) $parentRole['id'] : null;

        for ($row = 2; $row <= $highestRow; $row++) {
            $this->results['total_rows']++;

            $this->db->transBegin();

            try {
                $rowData = $this->extractRowData($worksheet, $row, $options);

                if ($this->isEmptyRow($rowData)) {
                    $this->results['total_rows']--;
                    $this->db->transRollback();
                    continue;
                }

                // Defaults + normalisasi sesuai kebutuhan sekolah
                $this->applyDefaultsAndNormalize($rowData, $row, $options);

                // Raw phone for validation message clarity
                $rowData['_raw_student_phone'] = $rowData['_raw_student_phone'] ?? $rowData['student_phone'];
                $rowData['_raw_parent_phone']  = $rowData['_raw_parent_phone'] ?? $rowData['parent_phone'];

                // Normalisasi nomor HP
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

                $this->processStudentImport(
                    $rowData,
                    (int) $studentRole['id'],
                    $parentRoleId,
                    $row
                );

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
     * Validate Excel headers (flexible)
     * - strict_headers=true: wajib template A1..Q1 persis
     * - default: build headerMap dan cek minimal kolom penting
     */
    protected function validateHeaders(Worksheet $worksheet, array $options = []): bool
    {
        $strict = !empty($options['strict_headers']);

        if ($strict) {
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

            // Build headerMap juga supaya extract fleksibel tetap jalan
            $this->headerMap = $this->buildHeaderMap($worksheet);
            return true;
        }

        $this->headerMap = $this->buildHeaderMap($worksheet);

        // Minimal required (biar bisa auto-generate field lain)
        $hasId = $this->hasAnyHeader(['NISN', 'NIS', 'NOMOR INDUK', 'NOMOR INDUK SISWA', 'NOMOR INDUK NASIONAL']);
        $hasName = $this->hasAnyHeader(['NAMA LENGKAP', 'NAMA', 'NAMA SISWA']);
        $hasBirthDate = $this->hasAnyHeader(['TANGGAL LAHIR', 'TGL LAHIR', 'LAHIR', 'DATE OF BIRTH']);
        $hasGender = $this->hasAnyHeader(['JENIS KELAMIN', 'JK', 'GENDER']);

        return $hasId && $hasName && $hasBirthDate && $hasGender;
    }

    protected function buildHeaderMap(Worksheet $worksheet): array
    {
        $highestCol = $worksheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        $map = [];

        for ($colIndex = 1; $colIndex <= $highestColIndex; $colIndex++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $val = $worksheet->getCell($colLetter . '1')->getValue();
            $header = trim((string) $val);

            if ($header === '') {
                continue;
            }

            $norm = $this->normalizeHeader($header);
            $map[$norm] = $colLetter;
        }

        return $map;
    }

    protected function normalizeHeader(string $header): string
    {
        $h = strtolower(trim($header));
        $h = str_replace(["\r", "\n", "\t"], ' ', $h);
        $h = preg_replace('/\s+/', ' ', $h);
        $h = str_replace(['.', ':'], '', $h);
        $h = trim($h);
        return $h;
    }

    protected function hasAnyHeader(array $candidates): bool
    {
        foreach ($candidates as $c) {
            if (isset($this->headerMap[$this->normalizeHeader($c)])) {
                return true;
            }
        }
        return false;
    }

    protected function getCellByHeaderCandidates(Worksheet $worksheet, int $row, array $candidates)
    {
        foreach ($candidates as $c) {
            $key = $this->normalizeHeader($c);
            if (isset($this->headerMap[$key])) {
                $col = $this->headerMap[$key];
                return $worksheet->getCell($col . $row)->getValue();
            }
        }
        return null;
    }

    /**
     * Extract data from row (flexible header mapping)
     */
    protected function extractRowData(Worksheet $worksheet, int $row, array $options = []): array
    {
        $statusRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Status',
        ]);
        $statusRaw = trim((string) $statusRaw);
        $statusNorm = $statusRaw !== '' ? ucwords(strtolower($statusRaw)) : 'Aktif';

        // ID utama
        $nisnRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'NISN', 'Nomor Induk Nasional', 'Nomor Induk',
        ]);
        $nisRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'NIS', 'Nomor Induk Siswa',
        ]);

        // Nama
        $nameRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Nama Lengkap', 'Nama', 'Nama Siswa',
        ]);

        // Gender
        $genderRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Jenis Kelamin', 'JK', 'Gender',
        ]);

        // Tempat & tanggal lahir
        $birthPlaceRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Tempat Lahir', 'Kota Lahir',
        ]);
        $birthDateRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Tanggal Lahir', 'Tgl Lahir', 'Date of Birth',
        ]);

        // Kelas
        $classRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Kelas', 'Tingkat - Rombel', 'Rombel', 'Rombongan Belajar',
        ]);

        // Alamat
        $addressRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Alamat', 'Alamat Lengkap',
        ]);

        // Telepon (sekolah biasanya cuma 1 kolom "No Telepon")
        $phoneRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'No. HP Siswa', 'No HP Siswa', 'No. Telepon', 'No Telepon', 'Telepon', 'Nomor Telepon', 'HP',
        ]);
        $parentPhoneRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'No. HP Orang Tua', 'No HP Orang Tua', 'Telepon Orang Tua', 'No Telepon Orang Tua',
        ]);

        // Orang tua (template: Nama Orang Tua; file sekolah: Ayah/Ibu/Wali)
        $parentNameRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Nama Orang Tua', 'Nama Wali', 'Nama Ibu Kandung', 'Nama Ayah Kandung',
        ]);
        $parentEmailRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Email Orang Tua', 'Email Wali', 'Email Orangtua',
        ]);

        // Email siswa & password (mungkin tidak ada di file sekolah)
        $emailRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Email', 'Email Siswa',
        ]);
        $passwordRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Password',
        ]);

        // Tanggal masuk (mungkin tidak ada)
        $admissionRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Tanggal Masuk', 'Tgl Masuk',
        ]);

        // Agama (mungkin tidak ada)
        $religionRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Agama',
        ]);

        // Ambil parent name prioritas jika file sekolah: wali -> ibu -> ayah
        // (Kalau header yang ketemu "Nama Ayah Kandung" tapi "Nama Wali" ada juga, kita override dengan prioritas)
        $wali = $this->getCellByHeaderCandidates($worksheet, $row, ['Nama Wali']);
        $ibu  = $this->getCellByHeaderCandidates($worksheet, $row, ['Nama Ibu Kandung']);
        $ayah = $this->getCellByHeaderCandidates($worksheet, $row, ['Nama Ayah Kandung']);

        $parentName = trim((string) $wali);
        if ($parentName === '') {
            $parentName = trim((string) $ibu);
        }
        if ($parentName === '') {
            $parentName = trim((string) $ayah);
        }
        if ($parentName === '') {
            $parentName = trim((string) $parentNameRaw);
        }

        return [
            'nisn'           => $this->normalizeNumericString($nisnRaw),
            'nis'            => $this->normalizeNumericString($nisRaw),
            'full_name'      => trim((string) $nameRaw),
            'email'          => trim((string) $emailRaw),
            'password'       => trim((string) $passwordRaw),
            'gender'         => trim((string) $genderRaw),
            'birth_place'    => trim((string) $birthPlaceRaw),
            'birth_date'     => $this->parseDate($birthDateRaw),
            'religion'       => trim((string) $religionRaw),
            'address'        => trim((string) $addressRaw),
            'class_name'     => trim((string) $classRaw),
            'admission_date' => $this->parseDate($admissionRaw),
            'status'         => $statusNorm,
            'parent_name'    => $parentName,
            'parent_email'   => trim((string) $parentEmailRaw),

            // simpan raw dulu, nanti dinormalisasi
            'student_phone'  => $this->normalizeNumericString($phoneRaw),
            'parent_phone'   => $this->normalizeNumericString($parentPhoneRaw),

            // simpan juga untuk pesan error yang lebih jelas
            '_raw_student_phone' => $this->normalizeNumericString($phoneRaw),
            '_raw_parent_phone'  => $this->normalizeNumericString($parentPhoneRaw),
        ];
    }

    /**
     * Normalisasi string dari Excel yang kadang numeric besar (biar tidak jadi scientific notation)
     */
    protected function normalizeNumericString($value): string
    {
        if ($value === null) {
            return '';
        }

        // DateTime jangan diubah jadi angka
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        // Kalau numeric, pakai format tanpa desimal
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            // Hindari scientific notation
            return rtrim(sprintf('%.0f', $value), '.');
        }

        $str = trim((string) $value);

        // Hilangkan prefix apostrophe dari export Excel (mis: "'3204...")
        if ($str !== '' && $str[0] === "'") {
            $str = ltrim($str, "'");
        }

        return trim($str);
    }

    /**
     * Apply defaults + normalisasi sesuai kebutuhan sekolah
     */
    protected function applyDefaultsAndNormalize(array &$rowData, int $rowNumber, array $options = []): void
    {
        $domain = !empty($options['default_email_domain']) ? (string) $options['default_email_domain'] : 'gmail.com';
        $defaultAdmission = !empty($options['default_admission_date']) ? (string) $options['default_admission_date'] : date('Y-m-d');

        // 1) Normalisasi NISN / NIS (pad NISN supaya 10 digit kalau hilang nol depan)
        $rowData['nisn'] = $this->normalizeIdDigits($rowData['nisn'], 10);
        $rowData['nis']  = $this->normalizeIdDigits($rowData['nis'], 0);

        // Jika NIS kosong -> pakai NISN (sesuai permintaan)
        if ($rowData['nis'] === '' && $rowData['nisn'] !== '') {
            $rowData['nis'] = $rowData['nisn'];
        }

        // 2) Gender: bisa "L", "P", "Laki-Laki", "Perempuan", dll
        $rowData['gender'] = $this->normalizeGenderValue($rowData['gender']);

        // 3) Status normalisasi ulang (biar aman)
        $rowData['status'] = $rowData['status'] !== '' ? ucwords(strtolower($rowData['status'])) : 'Aktif';

        // 4) Admission date default jika kosong
        if (empty($rowData['admission_date'])) {
            $rowData['admission_date'] = $this->parseDate($defaultAdmission);
        }

        // 5) Phone fallback: kalau cuma ada 1 kolom telepon, pakai untuk siswa & orang tua
        $rawStudentPhone = $rowData['_raw_student_phone'] ?? $rowData['student_phone'];
        $rawParentPhone  = $rowData['_raw_parent_phone'] ?? $rowData['parent_phone'];

        if ($rowData['student_phone'] === '' && $rowData['parent_phone'] !== '') {
            $rowData['student_phone'] = $rowData['parent_phone'];
            $rowData['_raw_student_phone'] = $rawParentPhone;
        }
        if ($rowData['parent_phone'] === '' && $rowData['student_phone'] !== '') {
            $rowData['parent_phone'] = $rowData['student_phone'];
            $rowData['_raw_parent_phone'] = $rawStudentPhone;
        }

        // 6) Default email siswa/orangtua jika kosong
        $baseId = $rowData['nisn'] !== '' ? $rowData['nisn'] : $rowData['nis'];

        if ($rowData['email'] === '' && $baseId !== '') {
            $rowData['email'] = $this->generateUniqueEmail($baseId . 'siswa', $domain, $rowNumber);
        }

        // Parent name: kalau kosong, jangan dipaksa bikin akun parent
        // Tapi kalau ada phone / ada nama, kita buat email default (sesuai permintaan)
        $hasParentSignal = ($rowData['parent_name'] !== '')
            || (($rowData['_raw_parent_phone'] ?? '') !== '')
            || ($rowData['parent_phone'] !== '');

        if ($hasParentSignal && $rowData['parent_name'] === '') {
            // Kalau sekolah tidak isi wali/ayah/ibu, minimal biar tidak gagal validasi parent
            $rowData['parent_name'] = 'Orang Tua ' . ($rowData['full_name'] !== '' ? $rowData['full_name'] : $baseId);
            $this->results['warnings'][] = "Baris {$rowNumber}: Nama orang tua kosong, dibuat otomatis: {$rowData['parent_name']}";
        }

        if ($hasParentSignal && $rowData['parent_email'] === '' && $baseId !== '') {
            $rowData['parent_email'] = $this->generateUniqueEmail($baseId . 'orangtua', $domain, $rowNumber);
        }

        // Pastikan email parent tidak sama dengan email siswa
        if ($rowData['parent_email'] !== '' && $rowData['parent_email'] === $rowData['email']) {
            $rowData['parent_email'] = $this->generateUniqueEmail($baseId . 'orangtua' . $rowNumber, $domain, $rowNumber);
        }

        // 7) Class: jika ada tapi tidak ditemukan, jangan gagalkan import
        if (!empty($rowData['class_name'])) {
            $resolved = $this->resolveClassNameIfExists($rowData['class_name']);
            if ($resolved === null) {
                $this->results['warnings'][] = "Baris {$rowNumber}: Kelas '{$rowData['class_name']}' tidak ditemukan di DB, class_id dikosongkan (null).";
                $rowData['class_name'] = '';
            } else {
                $rowData['class_name'] = $resolved;
            }
        }

        // 8) Birth date wajib, tapi kalau ada DateTime string sudah diparse di extractRowData.
        // Tidak diubah di sini.

        // 9) Password tetap: kalau kosong, nanti pakai default (tgl lahir) di processStudentImport()
    }

    protected function normalizeIdDigits(string $value, int $padToLength = 0): string
    {
        $v = trim($value);
        if ($v === '') {
            return '';
        }

        // hanya digit
        $digits = preg_replace('/\D+/', '', $v);
        if ($digits === '') {
            return '';
        }

        if ($padToLength > 0 && strlen($digits) < $padToLength) {
            $digits = str_pad($digits, $padToLength, '0', STR_PAD_LEFT);
        }

        return $digits;
    }

    protected function normalizeGenderValue(string $value): string
    {
        $v = strtoupper(trim($value));
        if ($v === '') {
            return '';
        }

        $v = str_replace(['.', ',', '/', '\\', '-', '_'], ' ', $v);
        $v = preg_replace('/\s+/', ' ', $v);
        $v = trim($v);

        if ($v === 'L' || $v === 'LAKI' || $v === 'LAKI LAKI' || $v === 'LAKI-LAKI' || $v === 'MALE' || $v === 'PRIA') {
            return 'L';
        }

        if ($v === 'P' || $v === 'PEREMPUAN' || $v === 'FEMALE' || $v === 'WANITA') {
            return 'P';
        }

        // Kalau input seperti "Laki-Laki" / "Perempuan"
        if (strpos($v, 'LAKI') !== false) {
            return 'L';
        }
        if (strpos($v, 'PEREMPUAN') !== false) {
            return 'P';
        }

        // fallback: ambil huruf pertama kalau cocok
        $first = $v[0] ?? '';
        if ($first === 'L') return 'L';
        if ($first === 'P') return 'P';

        return '';
    }

    protected function generateUniqueEmail(string $localPartBase, string $domain, int $rowNumber): string
    {
        // bersihkan local part
        $local = strtolower(trim($localPartBase));
        $local = preg_replace('/[^a-z0-9]+/', '', $local);
        if ($local === '') {
            $local = 'user' . $rowNumber;
        }

        $candidate = $local . '@' . $domain;

        // Hindari duplikat di file & DB
        for ($i = 0; $i < 30; $i++) {
            $try = $candidate;
            if ($i > 0) {
                $try = $local . $rowNumber . $i . '@' . $domain;
            }

            if (isset($this->seenEmails[$try])) {
                continue;
            }

            $exists = $this->userModel->withDeleted()->where('email', $try)->first();
            if ($exists) {
                continue;
            }

            return $try;
        }

        // fallback keras
        return $local . $rowNumber . uniqid() . '@' . $domain;
    }

    protected function resolveClassNameIfExists(string $classInput): ?string
    {
        $input = trim($classInput);
        if ($input === '') {
            return null;
        }

        // 1) exact match
        $class = $this->classModel->where('class_name', $input)->first();
        if ($class) {
            return (string) $class['class_name'];
        }

        // 2) normalized compare
        $normInput = strtolower(preg_replace('/\s+/', '', $input));

        $classes = $this->classModel->where('is_active', 1)->orderBy('id', 'DESC')->findAll();
        $best = null;
        $bestScore = 0;

        // coba deteksi pola "Kelas 10 - A"
        $gradeRoman = null;
        $section = null;
        if (preg_match('/kelas\s*(\d{1,2})\s*-\s*([a-z])/i', $input, $m)) {
            $grade = (int) $m[1];
            $section = strtoupper($m[2]);
            $gradeRoman = $this->toRomanGrade($grade);
        }

        foreach ($classes as $c) {
            $name = (string) ($c['class_name'] ?? '');
            if ($name === '') continue;

            $normName = strtolower(preg_replace('/\s+/', '', $name));
            $score = 0;

            if ($normName === $normInput) $score += 100;

            // fuzzy contains
            if (strpos($normName, $normInput) !== false || strpos($normInput, $normName) !== false) {
                $score += 30;
            }

            // roman + section hint
            if ($gradeRoman && strpos($name, $gradeRoman) !== false) {
                $score += 25;
            }
            if ($section && (strpos($name, '-' . $section) !== false || preg_match('/\b' . preg_quote($section, '/') . '\b/', $name))) {
                $score += 15;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $name;
            }
        }

        // Kalau score sangat rendah, anggap tidak ketemu
        if ($best !== null && $bestScore >= 25) {
            return $best;
        }

        return null;
    }

    protected function toRomanGrade(int $grade): ?string
    {
        // untuk kelas 10/11/12
        if ($grade === 10) return 'X';
        if ($grade === 11) return 'XI';
        if ($grade === 12) return 'XII';
        return null;
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

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value) && (float) $value > 25569) {
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

        $timestamp = strtotime($str);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Normalisasi nomor HP (Indonesia):
     * - Hilangkan spasi, '-', '()'
     * - Ubah prefix +62 / 62 menjadi 0
     * - Ubah nomor yang diawali 8 menjadi 08
     * - Hasil akhir hanya digit, contoh:
     *   +6281234567890 -> 081234567890
     *   62845163525    -> 0845163525
     *   81234567890    -> 081234567890
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

        // buang karakter umum
        $phone = str_replace([' ', '-', '(', ')'], '', $phone);
        if ($phone === '') {
            return '';
        }

        // kalau masih ada '+' hanya di depan yang kita toleransi
        if ($phone[0] === '+') {
            $phone = substr($phone, 1); // buang '+'
        }

        // setelah ini harus digit semua
        if ($phone === '' || !ctype_digit($phone)) {
            return null;
        }

        // konversi prefix Indonesia
        // 1) 62xxxxxxxx -> 0xxxxxxxx
        if (strpos($phone, '62') === 0) {
            $phone = '0' . substr($phone, 2);
        }

        // 2) 8xxxxxxxx -> 08xxxxxxxx
        if (strpos($phone, '8') === 0) {
            $phone = '0' . $phone;
        }

        // validasi panjang (opsional, tapi konsisten dengan validasi kamu)
        if (strlen($phone) > 15) {
            return null;
        }

        return $phone;
    }

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
        } elseif (strlen($rowData['nisn']) < 10 || !ctype_digit($rowData['nisn'])) {
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
            $errors[] = 'Tanggal lahir tidak boleh kosong atau formatnya tidak dikenali.';
        }
        if ($rowData['admission_date'] === null) {
            $errors[] = 'Tanggal masuk tidak boleh kosong atau formatnya tidak dikenali.';
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
            || $rawParentPhone !== ''
            || !empty($rowData['parent_phone']);

        if ($hasParentData) {
            if (empty($rowData['parent_name'])) {
                $errors[] = 'Nama orang tua tidak boleh kosong jika data orang tua diisi';
            }
            if (empty($rowData['parent_email'])) {
                $errors[] = 'Email orang tua tidak boleh kosong jika data orang tua diisi';
            } elseif (!filter_var($rowData['parent_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Format email orang tua tidak valid';
            }

            if ($rawParentPhone === '' && empty($rowData['parent_phone'])) {
                $errors[] = 'No. HP orang tua tidak boleh kosong jika data orang tua diisi';
            } elseif ($rowData['parent_phone'] === null) {
                $errors[] = 'Format No. HP orang tua tidak valid. Gunakan hanya angka, boleh diawali +62.';
            } elseif (strlen($rowData['parent_phone']) > 15) {
                $errors[] = 'No. HP orang tua maksimal 15 karakter';
            }
        }

        // Gender validation
        if (!in_array($rowData['gender'], ['L', 'P'], true)) {
            $errors[] = 'Jenis kelamin harus L atau P (boleh input Laki-Laki/Perempuan, sistem akan normalisasi)';
        }

        // Status validation
        $validStatus = ['Aktif', 'Alumni', 'Pindah', 'Keluar'];
        if (!in_array($rowData['status'], $validStatus, true)) {
            $errors[] = 'Status harus salah satu dari: ' . implode(', ', $validStatus);
        }

        // Check duplicate NISN di database (include soft deleted)
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

        // Check duplicate email siswa di database (include soft deleted)
        $existingEmail = $this->userModel->withDeleted()->where('email', $rowData['email'])->first();
        if ($existingEmail) {
            if (!empty($existingEmail['deleted_at'])) {
                $errors[] = "Email {$rowData['email']} pernah dipakai (sudah dihapus). Pulihkan (restore) atau gunakan email lain.";
            } else {
                $errors[] = "Email {$rowData['email']} sudah terdaftar di database";
            }
        }

        // Parent email duplicate check (opsional tapi aman)
        if (!empty($rowData['parent_email'])) {
            $existingParentEmail = $this->userModel->withDeleted()->where('email', $rowData['parent_email'])->first();
            if ($existingParentEmail && !empty($existingParentEmail['deleted_at'])) {
                $errors[] = "Email orang tua {$rowData['parent_email']} pernah dipakai (sudah dihapus). Pulihkan (restore) atau gunakan email lain.";
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Process student import
     */
    protected function processStudentImport(array $rowData, int $studentRoleId, ?int $parentRoleId, int $rowNumber): void
    {
        $classId = null;
        if (!empty($rowData['class_name'])) {
            $class   = $this->classModel->where('class_name', $rowData['class_name'])->first();
            $classId = $class ? (int) $class['id'] : null;
        }

        $username = $rowData['nisn'];

        // Build base password from birth date (DDMMYYYY) atau fallback
        $birthPassword = null;
        if (!empty($rowData['birth_date'])) {
            try {
                $dateObj       = new \DateTime($rowData['birth_date']);
                $birthPassword = $dateObj->format('dmY');
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

        $userData = [
            'role_id'   => $studentRoleId,
            'username'  => $username,
            'email'     => $rowData['email'],
            'password'  => $plainPassword,
            'full_name' => $rowData['full_name'],
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
            $existingParent = $this->userModel->withDeleted()->where('email', $rowData['parent_email'])->first();

            if ($existingParent) {
                if (!empty($existingParent['deleted_at'])) {
                    throw new \Exception("Email orang tua {$rowData['parent_email']} pernah dipakai (sudah dihapus). Pulihkan (restore) atau gunakan email lain.");
                }

                $parentId = (int) $existingParent['id'];

                if (!empty($rowData['parent_phone']) && empty($existingParent['phone'])) {
                    $this->userModel->update($parentId, [
                        'phone' => $rowData['parent_phone'],
                    ]);
                }

                $this->results['warnings'][] = "Baris {$rowNumber}: Email orang tua sudah terdaftar, menggunakan akun yang ada.";
            } else {
                if ($parentRoleId === null) {
                    $this->results['warnings'][] = "Baris {$rowNumber}: Role 'Orang Tua' tidak ditemukan, akun orang tua tidak dibuat. Data parent di student dibiarkan kosong.";
                    $parentId = null;
                } else {
                    $parentData = [
                        'role_id'   => $parentRoleId,
                        'username'  => strtolower(str_replace(' ', '_', $rowData['parent_name'])) . '_' . substr((string) $rowData['nisn'], -4),
                        'email'     => $rowData['parent_email'],
                        'password'  => $plainPassword,
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
     * Generate Excel template for student import (tetap sama)
     */
    public function generateTemplate(?string $savePath = null): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

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

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);

        foreach (range('A', 'Q') as $col) {
            $sheet->getStyle($col . ':' . $col)
                ->getNumberFormat()
                ->setFormatCode('@');
        }

        $sampleData = [
            '1234567890',
            '001',
            'Ahmad Fauzi',
            'ahmad.fauzi@example.com',
            '',
            'L',
            'Bandung',
            '15-05-2008',
            'Islam',
            'Jl. Contoh No. 123',
            'X-IPA-1',
            '01-07-2024',
            'Aktif',
            'Bapak Ahmad',
            'bapak.ahmad@example.com',
            '081234567890',
            '081298765432',
        ];

        $sheet->fromArray([$sampleData], null, 'A2');

        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

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

        $maxRow = 500;

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

        if (!$savePath) {
            $savePath = WRITEPATH . 'uploads/template_import_siswa.xlsx';
        }

        $directory = dirname($savePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($savePath);

        return $savePath;
    }

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

    public function getResults(): array
    {
        return $this->results;
    }
}
