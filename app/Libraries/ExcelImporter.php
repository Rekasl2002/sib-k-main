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
 * - Header jadi fleksibel: bisa pakai template lama A-Q atau format EMIS sekolah.
 * - Email siswa/orang tua opsional; username siswa dari NISN; gender "Laki-Laki/Perempuan" -> L/P.
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
use PhpOffice\PhpSpreadsheet\Cell\DataType;

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
     * @var array<string,string>
     */
    protected array $seenNisn   = [];
    protected array $seenNik    = [];
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
     * - strict_headers: bool (default false) kalau true, wajib format EMIS sekolah
     * - default_admission_date: string (default today Y-m-d)
     * - auto_create_classes: bool (default true) buat kelas rombel jika belum ada
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
        $this->seenNik    = [];
        $this->seenEmails = [];
        $this->headerMap  = [];

        $spreadsheet = IOFactory::load($filePath);

        $studentRole = $this->roleModel->where('role_name', 'Siswa')->first();
        if (!$studentRole) {
            throw new \Exception('Role "Siswa" tidak ditemukan dalam database.');
        }

        $parentRole = $this->roleModel->where('role_name', 'Orang Tua')->first();
        $parentRoleId = $parentRole ? (int) $parentRole['id'] : null;

        $worksheets = $this->resolveWorksheets($spreadsheet, $options);
        $validSheetCount = 0;

        foreach ($worksheets as $worksheet) {
            $this->headerMap = [];

            if (!$this->validateHeaders($worksheet, $options)) {
                $this->results['warnings'][] = 'Sheet "' . $worksheet->getTitle() . '" dilewati karena header tidak dikenali.';
                continue;
            }

            $validSheetCount++;
            $this->importWorksheet($worksheet, $options, (int) $studentRole['id'], $parentRoleId);
        }

        if ($validSheetCount === 0) {
            throw new \Exception('Header Excel tidak dikenali. Pastikan ada minimal: NISN, Nama, Tanggal Lahir, Jenis Kelamin.');
        }

        return $this->results;
    }

    /**
     * @return list<Worksheet>
     */
    protected function resolveWorksheets(Spreadsheet $spreadsheet, array $options): array
    {
        if (!empty($options['sheet_name'])) {
            $worksheet = $spreadsheet->getSheetByName((string) $options['sheet_name']);
            if (!$worksheet) {
                throw new \Exception('Sheet "' . $options['sheet_name'] . '" tidak ditemukan pada file Excel.');
            }

            return [$worksheet];
        }

        if (isset($options['sheet_index'])) {
            $idx = (int) $options['sheet_index'];
            if ($idx < 0 || $idx >= $spreadsheet->getSheetCount()) {
                throw new \Exception('Sheet index ' . $idx . ' tidak valid pada file Excel.');
            }

            return [$spreadsheet->getSheet($idx)];
        }

        $worksheets = [];
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $worksheets[] = $worksheet;
        }

        return $worksheets;
    }

    protected function importWorksheet(Worksheet $worksheet, array $options, int $studentRoleId, ?int $parentRoleId): void
    {
        $highestRow = (int) $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $this->results['total_rows']++;

            $this->db->transBegin();

            try {
                $rowData  = $this->extractRowData($worksheet, $row, $options);
                $rowLabel = $this->rowLabel($worksheet, $row);

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

                // Deteksi duplikat di dalam file (NISN, NIK, Email siswa jika ada)
                $dupErrors = [];

                if ($rowData['nisn'] !== '') {
                    if (isset($this->seenNisn[$rowData['nisn']])) {
                        $firstRow    = $this->seenNisn[$rowData['nisn']];
                        $dupErrors[] = "NISN {$rowData['nisn']} duplikat di file (pertama di {$firstRow})";
                    } else {
                        $this->seenNisn[$rowData['nisn']] = $rowLabel;
                    }
                }

                if ($rowData['nik'] !== '') {
                    if (isset($this->seenNik[$rowData['nik']])) {
                        $firstRow    = $this->seenNik[$rowData['nik']];
                        $dupErrors[] = "NIK {$rowData['nik']} duplikat di file (pertama di {$firstRow})";
                    } else {
                        $this->seenNik[$rowData['nik']] = $rowLabel;
                    }
                }

                if ($rowData['email'] !== '') {
                    if (isset($this->seenEmails[$rowData['email']])) {
                        $firstRow    = $this->seenEmails[$rowData['email']];
                        $dupErrors[] = "Email siswa {$rowData['email']} duplikat di file (pertama di {$firstRow})";
                    } else {
                        $this->seenEmails[$rowData['email']] = $rowLabel;
                    }
                }

                if (!empty($dupErrors)) {
                    $this->db->transRollback();
                    $this->results['failed']++;
                    $this->results['errors'][] = "{$rowLabel}: " . implode(', ', $dupErrors);
                    continue;
                }

                // Validate row data
                $validation = $this->validateRowData($rowData, $row);
                if (!$validation['valid']) {
                    $this->db->transRollback();
                    $this->results['failed']++;
                    $this->results['errors'][] = "{$rowLabel}: " . implode(', ', $validation['errors']);
                    continue;
                }

                $this->processStudentImport($rowData, $studentRoleId, $parentRoleId, $row);

                if ($this->db->transStatus() === false) {
                    $this->db->transRollback();
                    throw new \Exception('Terjadi kesalahan saat menyimpan data ke database.');
                }

                $this->db->transCommit();
                $this->results['success']++;
            } catch (\Exception $e) {
                $this->db->transRollback();
                $this->results['failed']++;
                $this->results['errors'][] = $this->rowLabel($worksheet, $row) . ': ' . $e->getMessage();
            }
        }
    }

    protected function rowLabel(Worksheet $worksheet, int $row): string
    {
        return 'Sheet "' . $worksheet->getTitle() . '" baris ' . $row;
    }

    /**
     * Validate Excel headers (flexible)
     * - strict_headers=true: wajib template EMIS aplikasi atau format EMIS sekolah lama
     * - default: build headerMap dan cek minimal kolom penting
     */
    protected function validateHeaders(Worksheet $worksheet, array $options = []): bool
    {
        $strict = !empty($options['strict_headers']);

        if ($strict) {
            $expectedHeaderSets = [[
                'A1' => 'No',
                'B1' => 'Nama Lengkap',
                'C1' => 'NISN',
                'D1' => 'NIK',
                'E1' => 'Tempat Lahir',
                'F1' => 'Tanggal Lahir',
                'G1' => 'Tingkat - Rombel',
                'H1' => 'Status',
                'I1' => 'Jenis Kelamin',
                'J1' => 'Alamat',
                'K1' => 'No Telepon',
                'L1' => 'Kebutuhan Khusus',
                'M1' => 'Disabilitas',
                'N1' => 'Nomor KIP/PIP',
                'O1' => 'Nama Ayah Kandung',
                'P1' => 'Nama Ibu Kandung',
                'Q1' => 'Nama Wali',
            ], [
                'A1' => 'No',
                'B1' => 'Nama Lengkap',
                'C1' => 'NISN',
                'D1' => 'NIK',
                'E1' => 'Tempat Lahir',
                'F1' => 'Tanggal Lahir',
                'G1' => 'Tingkat - Rombel',
                'H1' => 'Umur',
                'I1' => 'Status',
                'J1' => 'Jenis Kelamin',
                'K1' => 'Alamat',
                'L1' => 'No Telepon',
                'M1' => 'Kebutuhan Khusus',
                'N1' => 'Disabilitas',
                'O1' => 'Nomor KIP/PIP',
                'P1' => 'Nama Ayah Kandung',
                'Q1' => 'Nama Ibu Kandung',
                'R1' => 'Nama Wali',
            ]];

            $matches = false;
            foreach ($expectedHeaderSets as $expectedHeaders) {
                $matches = true;
                foreach ($expectedHeaders as $cell => $expectedValue) {
                    $actualValue = trim((string) $worksheet->getCell($cell)->getValue());
                    if (strcasecmp($actualValue, $expectedValue) !== 0) {
                        $matches = false;
                        break;
                    }
                }

                if ($matches) {
                    break;
                }
            }

            if (!$matches) {
                return false;
            }

            // Build headerMap juga supaya extract fleksibel tetap jalan
            $this->headerMap = $this->buildHeaderMap($worksheet);
            return true;
        }

        $this->headerMap = $this->buildHeaderMap($worksheet);

        // Minimal required (biar bisa auto-generate field lain)
        $hasId = $this->hasAnyHeader(['NISN', 'NOMOR INDUK NASIONAL']);
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
        $nikRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'NIK', 'Nomor Induk Kependudukan',
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

        $specialNeedsRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Kebutuhan Khusus',
        ]);
        $disabilityRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Disabilitas',
        ]);
        $kipPipRaw = $this->getCellByHeaderCandidates($worksheet, $row, [
            'Nomor KIP/PIP', 'No KIP/PIP', 'Nomor KIP', 'Nomor PIP',
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
            'nik'            => $this->normalizeNumericString($nikRaw),
            'full_name'      => trim((string) $nameRaw),
            'email'          => trim((string) $emailRaw),
            'password'       => trim((string) $passwordRaw),
            'gender'         => trim((string) $genderRaw),
            'birth_place'    => trim((string) $birthPlaceRaw),
            'birth_date'     => $this->parseDate($birthDateRaw),
            'religion'       => trim((string) $religionRaw),
            'address'        => trim((string) $addressRaw),
            'special_needs'  => trim((string) $specialNeedsRaw),
            'disability'     => trim((string) $disabilityRaw),
            'kip_pip_number' => $this->normalizeNumericString($kipPipRaw),
            'father_name'    => trim((string) $ayah),
            'mother_name'    => trim((string) $ibu),
            'guardian_name'  => trim((string) $wali),
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
        $defaultAdmission = !empty($options['default_admission_date'])
            ? (string) $options['default_admission_date']
            : $this->getDefaultAdmissionDate();

        // 1) Normalisasi NISN / NIK (pad NISN supaya 10 digit kalau hilang nol depan)
        $rowData['nisn'] = $this->normalizeIdDigits($rowData['nisn'], 10);
        $rowData['nik']  = $this->normalizeIdDigits($rowData['nik'], 0);

        // 2) Gender: bisa "L", "P", "Laki-Laki", "Perempuan", dll
        $rowData['gender'] = $this->normalizeGenderValue($rowData['gender']);

        // 3) Status normalisasi ulang (biar aman)
        $rowData['status'] = $this->normalizeStatusValue($rowData['status']);

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

        // 6) Email siswa/orangtua opsional. Jika ada, rapikan ke lowercase.
        $baseId = $rowData['nisn'];

        $rowData['email'] = $this->normalizeEmail($rowData['email']);
        $rowData['parent_email'] = $this->normalizeEmail($rowData['parent_email']);

        // Parent name: kalau kosong, jangan dipaksa bikin akun parent
        // Tapi kalau ada phone / ada nama, kita buat nama akun parent tanpa memaksa email.
        $hasParentSignal = ($rowData['parent_name'] !== '')
            || (($rowData['_raw_parent_phone'] ?? '') !== '')
            || ($rowData['parent_phone'] !== '');

        if ($hasParentSignal && $rowData['parent_name'] === '') {
            // Kalau sekolah tidak isi wali/ayah/ibu, minimal biar tidak gagal validasi parent
            $rowData['parent_name'] = 'Orang Tua ' . ($rowData['full_name'] !== '' ? $rowData['full_name'] : $baseId);
            $this->results['warnings'][] = "Baris {$rowNumber}: Nama orang tua kosong, dibuat otomatis: {$rowData['parent_name']}";
        }

        // Pastikan email parent tidak sama dengan email siswa jika dua-duanya diisi.
        if ($rowData['email'] !== '' && $rowData['parent_email'] !== '' && $rowData['parent_email'] === $rowData['email']) {
            $this->results['warnings'][] = "Baris {$rowNumber}: Email orang tua sama dengan email siswa, email orang tua dikosongkan.";
            $rowData['parent_email'] = '';
        }

        // 7) Class: jika ada tapi tidak ditemukan, buat otomatis sesuai rombel sekolah.
        if (!empty($rowData['class_name'])) {
            $resolved = $this->ensureClassExists($rowData['class_name'], $rowNumber, $options);
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

    protected function normalizeEmail(?string $value): string
    {
        return strtolower(trim((string) ($value ?? '')));
    }

    protected function normalizeStatusValue(string $value): string
    {
        $v = strtolower(trim($value));
        $v = preg_replace('/\s+/', ' ', (string) $v);

        if ($v === '') {
            return 'Aktif';
        }

        $map = [
            'aktif'       => 'Aktif',
            'alumni'      => 'Alumni',
            'lulus'       => 'Alumni',
            'pindah'      => 'Pindah',
            'keluar'      => 'Keluar',
            'nonaktif'    => 'Tidak Aktif',
            'non aktif'   => 'Tidak Aktif',
            'tidak aktif' => 'Tidak Aktif',
        ];

        return $map[$v] ?? ucwords($v);
    }

    protected function getDefaultAdmissionDate(): string
    {
        $activeYear = $this->db->table('academic_years')
            ->select('start_date')
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->orderBy('start_date', 'DESC')
            ->get(1)
            ->getRowArray();

        if (!empty($activeYear['start_date'])) {
            return (string) $activeYear['start_date'];
        }

        return date('Y-m-d');
    }

    protected function ensureClassExists(string $classInput, int $rowNumber, array $options = []): ?string
    {
        $input = trim($classInput);
        if ($input === '') {
            return null;
        }

        $resolved = $this->resolveClassNameIfExists($input);
        if ($resolved !== null) {
            return $resolved;
        }

        $autoCreate = !array_key_exists('auto_create_classes', $options) || (bool) $options['auto_create_classes'];
        if (!$autoCreate) {
            return null;
        }

        $academicYearId = $this->resolveAcademicYearId($options);
        if ($academicYearId === null) {
            $this->results['warnings'][] = "Baris {$rowNumber}: Kelas '{$input}' tidak dibuat karena tahun ajaran aktif tidak ditemukan.";
            return null;
        }

        $gradeLevel = $this->inferGradeLevel($input);
        if ($gradeLevel === null) {
            $this->results['warnings'][] = "Baris {$rowNumber}: Kelas '{$input}' tidak dibuat karena tingkat kelas tidak bisa dibaca.";
            return null;
        }

        $major = $this->inferMajor($input);
        $now = date('Y-m-d H:i:s');

        $payload = [
            'academic_year_id' => $academicYearId,
            'class_name'       => $input,
            'grade_level'      => $gradeLevel,
            'major'            => $major,
            'max_students'     => 50,
            'is_active'        => 1,
            'created_at'       => $now,
            'updated_at'       => $now,
        ];

        if (!$this->classModel->insert($payload)) {
            $this->results['warnings'][] = "Baris {$rowNumber}: Kelas '{$input}' gagal dibuat: " . implode(', ', $this->classModel->errors());
            return null;
        }

        $this->results['warnings'][] = "Baris {$rowNumber}: Kelas '{$input}' dibuat otomatis dari file impor.";
        return $input;
    }

    protected function resolveAcademicYearId(array $options = []): ?int
    {
        if (!empty($options['academic_year_id'])) {
            $row = $this->db->table('academic_years')
                ->select('id')
                ->where('id', (int) $options['academic_year_id'])
                ->where('deleted_at', null)
                ->get(1)
                ->getRowArray();

            if (!empty($row['id'])) {
                return (int) $row['id'];
            }
        }

        $row = $this->db->table('academic_years')
            ->select('id')
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->orderBy('start_date', 'DESC')
            ->get(1)
            ->getRowArray();

        if (!empty($row['id'])) {
            return (int) $row['id'];
        }

        $row = $this->db->table('academic_years')
            ->select('id')
            ->where('deleted_at', null)
            ->orderBy('start_date', 'DESC')
            ->get(1)
            ->getRowArray();

        return !empty($row['id']) ? (int) $row['id'] : null;
    }

    protected function inferGradeLevel(string $classInput): ?string
    {
        $input = strtoupper(trim($classInput));

        if (preg_match('/KELAS\s*(\d{1,2})\b/', $input, $m)) {
            $grade = (int) $m[1];
            return $this->toRomanGrade($grade) ?? (string) $grade;
        }

        if (preg_match('/\b(7|8|9|10|11|12)\b/', $input, $m)) {
            $grade = (int) $m[1];
            return $this->toRomanGrade($grade) ?? (string) $grade;
        }

        if (preg_match('/\b(XII|XI|X)\b/', $input, $m)) {
            return $m[1];
        }

        return null;
    }

    protected function inferMajor(string $classInput): ?string
    {
        $input = strtoupper($classInput);
        foreach (['IPA', 'IPS', 'MIPA', 'BAHASA', 'AGAMA'] as $major) {
            if (strpos($input, $major) !== false) {
                return $major;
            }
        }

        return null;
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

        // Kalau score rendah, anggap tidak ketemu agar "Kelas 11 - A"
        // tidak keliru masuk ke kelas lama seperti "XI-IPA-1".
        if ($best !== null && $bestScore >= 40) {
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
        return empty($rowData['nisn']) && empty($rowData['nik']) && empty($rowData['full_name']);
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
        } elseif (strlen($rowData['nisn']) !== 10 || !ctype_digit($rowData['nisn'])) {
            $errors[] = 'NISN harus tepat 10 digit angka';
        }

        if (!empty($rowData['nik']) && (strlen($rowData['nik']) !== 16 || !ctype_digit($rowData['nik']))) {
            $errors[] = 'NIK harus tepat 16 digit angka jika diisi';
        }

        if (empty($rowData['full_name'])) {
            $errors[] = 'Nama lengkap tidak boleh kosong';
        }

        if (!empty($rowData['email']) && !filter_var($rowData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid';
        }

        // Tanggal lahir & masuk: wajib dan harus berhasil diparse
        if ($rowData['birth_date'] === null) {
            $errors[] = 'Tanggal lahir tidak boleh kosong atau formatnya tidak dikenali.';
        }
        if ($rowData['admission_date'] === null) {
            $errors[] = 'Tanggal masuk tidak boleh kosong atau formatnya tidak dikenali.';
        }

        // Student phone: opsional, tapi jika ada harus valid.
        $rawStudentPhone = $rowData['_raw_student_phone'] ?? '';
        if ($rawStudentPhone !== '' && $rowData['student_phone'] === null) {
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
            if (!empty($rowData['parent_email']) && !filter_var($rowData['parent_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Format email orang tua tidak valid';
            }

            if ($rawParentPhone !== '' && $rowData['parent_phone'] === null) {
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
        $validStatus = ['Aktif', 'Alumni', 'Pindah', 'Keluar', 'Tidak Aktif'];
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

        if (!empty($rowData['nik'])) {
            $existingNik = $this->studentModel->withDeleted()->where('nik', $rowData['nik'])->first();
            if ($existingNik) {
                if (!empty($existingNik['deleted_at'])) {
                    $errors[] = "NIK {$rowData['nik']} pernah ada (sudah dihapus). Pulihkan (restore) atau gunakan NIK lain.";
                } else {
                    $errors[] = "NIK {$rowData['nik']} sudah terdaftar di database";
                }
            }
        }

        // Check duplicate email siswa di database (include soft deleted)
        if (!empty($rowData['email'])) {
            $existingEmail = $this->userModel->withDeleted()->where('email', $rowData['email'])->first();
            if ($existingEmail) {
                if (!empty($existingEmail['deleted_at'])) {
                    $errors[] = "Email {$rowData['email']} pernah dipakai (sudah dihapus). Pulihkan (restore) atau gunakan email lain.";
                } else {
                    $errors[] = "Email {$rowData['email']} sudah terdaftar di database";
                }
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

    protected function findExistingParent(array $rowData): ?array
    {
        if (!empty($rowData['parent_email'])) {
            return $this->userModel->withDeleted()
                ->where('email', $rowData['parent_email'])
                ->first();
        }

        if (!empty($rowData['parent_name']) && !empty($rowData['parent_phone'])) {
            return $this->userModel->withDeleted()
                ->where('full_name', $rowData['parent_name'])
                ->where('phone', $rowData['parent_phone'])
                ->first();
        }

        return null;
    }

    protected function generateUniqueUsername(string $base, string $fallback): string
    {
        $local = strtolower(trim($base));
        $local = preg_replace('/[^a-z0-9._-]+/', '_', (string) $local);
        $local = trim((string) $local, '._-');

        if ($local === '') {
            $local = strtolower(trim($fallback));
            $local = preg_replace('/[^a-z0-9._-]+/', '_', (string) $local);
            $local = trim((string) $local, '._-');
        }

        if ($local === '') {
            $local = 'user';
        }

        $local = substr($local, 0, 90);
        $candidate = $local;

        for ($i = 0; $i < 100; $i++) {
            $try = $i === 0 ? $candidate : substr($candidate, 0, 85) . '_' . $i;
            $exists = $this->userModel->withDeleted()->where('username', $try)->first();
            if (!$exists) {
                return $try;
            }
        }

        return substr($candidate, 0, 75) . '_' . uniqid();
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

        $username = $this->generateUniqueUsername($rowData['nisn'] ?: $rowData['nik'] ?: ('siswa' . $rowNumber), 'siswa');

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
            'email'     => $rowData['email'] !== '' ? $rowData['email'] : null,
            'password'  => $plainPassword,
            'full_name' => $rowData['full_name'],
            'phone'     => $rowData['student_phone'] !== '' ? $rowData['student_phone'] : null,
            'is_active' => 1,
        ];

        if (!$this->userModel->insert($userData)) {
            throw new \Exception('Gagal membuat akun user: ' . implode(', ', $this->userModel->errors()));
        }

        $userId = (int) $this->userModel->getInsertID();

        // Create parent account if parent info provided
        $parentId = null;
        $hasParent = !empty($rowData['parent_name']) || !empty($rowData['parent_email']) || !empty($rowData['parent_phone']);

        if ($hasParent) {
            $existingParent = $this->findExistingParent($rowData);

            if ($existingParent) {
                if (!empty($existingParent['deleted_at'])) {
                    throw new \Exception('Akun orang tua pernah dipakai (sudah dihapus). Pulihkan (restore) atau gunakan data orang tua lain.');
                }

                $parentId = (int) $existingParent['id'];

                if (!empty($rowData['parent_phone']) && empty($existingParent['phone'])) {
                    $this->userModel->update($parentId, [
                        'phone' => $rowData['parent_phone'],
                    ]);
                }

                $this->results['warnings'][] = "Baris {$rowNumber}: Akun orang tua sudah terdaftar, menggunakan akun yang ada.";
            } else {
                if ($parentRoleId === null) {
                    $this->results['warnings'][] = "Baris {$rowNumber}: Role 'Orang Tua' tidak ditemukan, akun orang tua tidak dibuat. Data parent di student dibiarkan kosong.";
                    $parentId = null;
                } else {
                    $parentUsernameBase = $rowData['parent_name'] . '_' . substr((string) $rowData['nisn'], -4);
                    $parentData = [
                        'role_id'   => $parentRoleId,
                        'username'  => $this->generateUniqueUsername($parentUsernameBase, 'ortu' . $rowNumber),
                        'email'     => $rowData['parent_email'] !== '' ? $rowData['parent_email'] : null,
                        'password'  => $plainPassword,
                        'full_name' => $rowData['parent_name'],
                        'phone'     => $rowData['parent_phone'] !== '' ? $rowData['parent_phone'] : null,
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
            'nik'                    => $rowData['nik'] !== '' ? $rowData['nik'] : null,
            'gender'                 => $rowData['gender'],
            'birth_place'            => $rowData['birth_place'] ?: null,
            'birth_date'             => $rowData['birth_date'],
            'religion'               => $rowData['religion'] ?: null,
            'address'                => $rowData['address'] ?: null,
            'special_needs'          => $rowData['special_needs'] ?: null,
            'disability'             => $rowData['disability'] ?: null,
            'kip_pip_number'         => $rowData['kip_pip_number'] ?: null,
            'father_name'            => $rowData['father_name'] ?: null,
            'mother_name'            => $rowData['mother_name'] ?: null,
            'guardian_name'          => $rowData['guardian_name'] ?: null,
            'parent_id'              => $parentId,
            'admission_date'         => $rowData['admission_date'],
            'status'                 => $rowData['status'],
        ];

        if (!$this->studentModel->insert($studentData)) {
            throw new \Exception('Gagal membuat data siswa: ' . implode(', ', $this->studentModel->errors()));
        }
    }

    /**
     * Generate Excel template mengikuti format EMIS sekolah.
     */
    public function generateTemplate(?string $savePath = null): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kelas 12 - A');

        $headers = [
            'No',
            'Nama Lengkap',
            'NISN',
            'NIK',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Tingkat - Rombel',
            'Status',
            'Jenis Kelamin',
            'Alamat',
            'No Telepon',
            'Kebutuhan Khusus',
            'Disabilitas',
            'Nomor KIP/PIP',
            'Nama Ayah Kandung',
            'Nama Ibu Kandung',
            'Nama Wali',
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
            '1',
            'Nama Siswa Contoh',
            '1000000003',
            '1000000000000003',
            'BANDUNG',
            '2007-09-19',
            'Kelas 12 - A',
            'Aktif',
            'Perempuan',
            'Kp. Contoh, BANJARAN, KABUPATEN BANDUNG, JAWA BARAT, 40377',
            '6281234567890',
            'Tidak Ada',
            'Tidak Ada',
            '',
            'NAMA AYAH',
            'NAMA IBU',
            'NAMA WALI',
        ];

        $sheet->fromArray([$sampleData], null, 'A2');
        $sheet->setCellValueExplicit('C2', '1000000003', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D2', '1000000000000003', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('K2', '6281234567890', DataType::TYPE_STRING);

        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getCell('A4')->setValue('PETUNJUK:');
        $sheet->getCell('A5')->setValue('1. Template ini mengikuti format file EMIS sekolah, tanpa kolom Umur. Umur dihitung otomatis dari Tanggal Lahir.');
        $sheet->getCell('A6')->setValue('2. Minimal data yang perlu ada: NISN, Nama Lengkap, Tanggal Lahir, Jenis Kelamin. NIK opsional mengikuti isi file EMIS.');
        $sheet->getCell('A7')->setValue('3. Email tidak wajib dan tidak ada di format EMIS ini. Login siswa memakai username/NISN.');
        $sheet->getCell('A8')->setValue('4. Tanggal Lahir boleh YYYY-MM-DD seperti file EMIS, atau DD-MM-YYYY / DD/MM/YYYY / DDMMYYYY.');
        $sheet->getCell('A9')->setValue('5. Tingkat - Rombel boleh seperti "Kelas 12 - A"; sistem akan membuat kelas otomatis jika belum ada.');
        $sheet->getCell('A10')->setValue('6. No Telepon opsional. Jika diisi boleh 08, 62, atau +62; sistem akan menormalkan ke 08.');
        $sheet->getCell('A11')->setValue('7. Nama Wali akan dipakai sebagai prioritas orang tua, lalu Nama Ibu Kandung, lalu Nama Ayah Kandung.');
        $sheet->getCell('A12')->setValue("8. Password default akun siswa/orang tua memakai tanggal lahir DDMMYYYY, atau 'password123' bila tanggal lahir tidak valid.");

        $maxRow = 500;

        $genderList = '"Laki-laki,Perempuan,L,P"';
        $genderDv = $sheet->getCell('I2')->getDataValidation();
        $genderDv->setType(DataValidation::TYPE_LIST);
        $genderDv->setErrorStyle(DataValidation::STYLE_WARNING);
        $genderDv->setAllowBlank(false);
        $genderDv->setShowInputMessage(true);
        $genderDv->setShowErrorMessage(true);
        $genderDv->setShowDropDown(true);
        $genderDv->setErrorTitle('Nilai tidak sesuai');
        $genderDv->setError('Pilih salah satu dari daftar jenis kelamin, atau lanjutkan dengan hati-hati.');
        $genderDv->setPromptTitle('Pilih Jenis Kelamin');
        $genderDv->setPrompt('Gunakan Laki-laki/Perempuan, atau L/P.');
        $genderDv->setFormula1($genderList);

        for ($row = 2; $row <= $maxRow; $row++) {
            $sheet->getCell('I' . $row)->setDataValidation(clone $genderDv);
        }

        $statusList = '"Aktif,Tidak Aktif,Alumni,Pindah,Keluar"';
        $statusDv = $sheet->getCell('H2')->getDataValidation();
        $statusDv->setType(DataValidation::TYPE_LIST);
        $statusDv->setErrorStyle(DataValidation::STYLE_WARNING);
        $statusDv->setAllowBlank(false);
        $statusDv->setShowInputMessage(true);
        $statusDv->setShowErrorMessage(true);
        $statusDv->setShowDropDown(true);
        $statusDv->setErrorTitle('Status tidak dikenal');
        $statusDv->setError('Pilih salah satu status yang tersedia, atau lanjutkan dengan hati-hati.');
        $statusDv->setPromptTitle('Pilih Status Siswa');
        $statusDv->setPrompt('Status: Aktif, Tidak Aktif, Alumni, Pindah, atau Keluar.');
        $statusDv->setFormula1($statusList);

        for ($row = 2; $row <= $maxRow; $row++) {
            $sheet->getCell('H' . $row)->setDataValidation(clone $statusDv);
        }

        $classRecords = $this->classModel
            ->where('is_active', 1)
            ->orderBy('class_name', 'ASC')
            ->findAll();

        $classNames = ['Kelas 12 - A', 'Kelas 12 - B', 'Kelas 12 - C'];
        foreach ($classRecords as $cls) {
            if (!empty($cls['class_name'])) {
                $classNames[] = $cls['class_name'];
            }
        }
        $classNames = array_values(array_unique($classNames));

        if (!empty($classNames)) {
            $safeClassNames = array_map(static function ($v) {
                return str_replace('"', '""', $v);
            }, $classNames);

            $classList = '"' . implode(',', $safeClassNames) . '"';

            if (strlen($classList) <= 255) {
                $classDv = $sheet->getCell('G2')->getDataValidation();
                $classDv->setType(DataValidation::TYPE_LIST);
                $classDv->setErrorStyle(DataValidation::STYLE_WARNING);
                $classDv->setAllowBlank(true);
                $classDv->setShowInputMessage(true);
                $classDv->setShowErrorMessage(true);
                $classDv->setShowDropDown(true);
                $classDv->setErrorTitle('Kelas tidak terdaftar');
                $classDv->setError('Pilih kelas yang sudah ada di sistem, atau lanjutkan dengan hati-hati.');
                $classDv->setPromptTitle('Pilih Rombel');
                $classDv->setPrompt('Contoh: Kelas 12 - A. Jika belum ada, sistem akan membuat kelas otomatis saat impor.');
                $classDv->setFormula1($classList);

                for ($row = 2; $row <= $maxRow; $row++) {
                    $sheet->getCell('G' . $row)->setDataValidation(clone $classDv);
                }
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
