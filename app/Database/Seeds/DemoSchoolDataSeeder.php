<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * File Path: app/Database/Seeds/DemoSchoolDataSeeder.php
 *
 * Fitur: data demo sekolah untuk mencoba aplikasi seperti kondisi nyata.
 * Peran/izin: menambah akun Siswa dan Orang Tua; kelas dapat dipakai Admin,
 * Koordinator BK, Guru BK, dan Wali Kelas sesuai hak akses masing-masing.
 * Berhubungan dengan: users, students, classes, academic_years, roles, dan
 * simulation_access_grants. Seeder ini idempotent dan tidak menghapus data lama.
 */
class DemoSchoolDataSeeder extends Seeder
{
    /** @var array<string,list<string>> */
    private array $fieldCache = [];

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $academicYearId = $this->activeAcademicYearId($now);
        $roleIds = $this->roleIds();
        $homeroomIds = $this->userIdsByRole($roleIds['Wali Kelas'] ?? 4);
        $counselorIds = $this->userIdsByRole($roleIds['Guru BK'] ?? 3);

        $targetClasses = [];
        foreach ([10, 11, 12] as $grade) {
            foreach (['A', 'B', 'C'] as $letter) {
                $targetClasses[] = [
                    'grade' => $grade,
                    'letter' => $letter,
                    'class_name' => $grade . ' ' . $letter,
                ];
            }
        }

        $createdStudents = 0;
        $createdParents = 0;
        $createdClasses = 0;

        foreach ($targetClasses as $index => $target) {
            $classId = $this->ensureClass(
                $target['class_name'],
                (string) $target['grade'],
                $academicYearId,
                $homeroomIds[$index % max(count($homeroomIds), 1)] ?? null,
                $counselorIds[$index % max(count($counselorIds), 1)] ?? null,
                $now,
                $createdClasses
            );

            $currentCount = $this->activeStudentCount($classId);
            for ($number = $currentCount + 1; $number <= 25; $number++) {
                $parentId = $this->parentForStudent(
                    (int) $target['grade'],
                    (string) $target['letter'],
                    $number,
                    $roleIds['Orang Tua'] ?? 6,
                    $now,
                    $createdParents
                );

                if ($this->createStudent(
                    $classId,
                    (int) $target['grade'],
                    (string) $target['letter'],
                    $number,
                    $roleIds['Siswa'] ?? 5,
                    $parentId,
                    $now
                )) {
                    $createdStudents++;
                }
            }
        }

        echo "Demo school data seeded.\n";
        echo "- Kelas ditambahkan: {$createdClasses}\n";
        echo "- Siswa ditambahkan: {$createdStudents}\n";
        echo "- Akun orang tua ditambahkan: {$createdParents}\n";
    }

    private function activeAcademicYearId(string $now): int
    {
        $row = $this->db->table('academic_years')
            ->select('id')
            ->where('is_active', 1)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if ($row) {
            return (int) $row['id'];
        }

        $this->insertRow('academic_years', [
            'year_name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'semester' => 'Genap',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @return array<string,int>
     */
    private function roleIds(): array
    {
        $rows = $this->db->table('roles')
            ->select('id, role_name')
            ->whereIn('role_name', ['Guru BK', 'Wali Kelas', 'Siswa', 'Orang Tua'])
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['role_name']] = (int) $row['id'];
        }

        return $map + [
            'Guru BK' => 3,
            'Wali Kelas' => 4,
            'Siswa' => 5,
            'Orang Tua' => 6,
        ];
    }

    /**
     * @return list<int>
     */
    private function userIdsByRole(int $roleId): array
    {
        $rows = $this->db->table('users')
            ->select('id')
            ->where('role_id', $roleId)
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $ids = array_values(array_filter(array_map(static fn ($row) => (int) ($row['id'] ?? 0), $rows)));

        return $ids ?: [null];
    }

    private function ensureClass(
        string $className,
        string $grade,
        int $academicYearId,
        ?int $homeroomId,
        ?int $counselorId,
        string $now,
        int &$createdClasses
    ): int {
        $row = $this->db->table('classes')
            ->select('id')
            ->where('academic_year_id', $academicYearId)
            ->where('grade_level', $grade)
            ->where('class_name', $className)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($row) {
            return (int) $row['id'];
        }

        $this->insertRow('classes', [
            'academic_year_id' => $academicYearId,
            'class_name' => $className,
            'grade_level' => $grade,
            'major' => 'Umum',
            'homeroom_teacher_id' => $homeroomId,
            'counselor_id' => $counselorId,
            'max_students' => 36,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $createdClasses++;

        return (int) $this->db->insertID();
    }

    private function activeStudentCount(int $classId): int
    {
        return (int) $this->db->table('students')
            ->where('class_id', $classId)
            ->where('status', 'Aktif')
            ->where('deleted_at', null)
            ->countAllResults();
    }

    private function parentForStudent(
        int $grade,
        string $letter,
        int $number,
        int $parentRoleId,
        string $now,
        int &$createdParents
    ): ?int {
        if ($number % 5 === 0) {
            return null;
        }

        if ($number % 7 === 0 || $number % 7 === 1) {
            $familyKey = 'shared_' . $grade . '_' . (int) ceil($number / 7);
        } else {
            $familyKey = 'kelas_' . $grade . strtolower($letter) . '_' . (int) ceil($number / 2);
        }

        $username = 'ortu_demo_' . $familyKey;
        $row = $this->db->table('users')
            ->select('id')
            ->where('username', $username)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($row) {
            return (int) $row['id'];
        }

        $serial = $this->numericSerial($grade, $letter, $number);
        $parentId = $this->createUser([
            'role_id' => $parentRoleId,
            'username' => $username,
            'email' => null,
            'password_hash' => password_hash('parent123', PASSWORD_BCRYPT),
            'full_name' => 'Orang Tua Demo ' . strtoupper(str_replace('_', ' ', $familyKey)),
            'phone' => '0822' . str_pad((string) $serial, 8, '0', STR_PAD_LEFT),
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($parentId > 0) {
            $createdParents++;
            $this->grantSimulationAccess($parentId, $now);
        }

        return $parentId > 0 ? $parentId : null;
    }

    private function createStudent(
        int $classId,
        int $grade,
        string $letter,
        int $number,
        int $studentRoleId,
        ?int $parentId,
        string $now
    ): bool {
        $classCode = $grade . strtolower($letter);
        $username = 'siswa_demo_' . $classCode . '_' . str_pad((string) $number, 2, '0', STR_PAD_LEFT);

        $existingUser = $this->db->table('users')
            ->select('id')
            ->where('username', $username)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($existingUser) {
            return false;
        }

        $gender = $number % 2 === 0 ? 'L' : 'P';
        $fullName = $this->studentName($gender, $grade, $letter, $number);
        $serial = $this->numericSerial($grade, $letter, $number);
        $birthYear = 2006 + (12 - $grade);
        $birthMonth = (($number - 1) % 12) + 1;
        $birthDay = (($number - 1) % 27) + 1;
        $nisn = $this->uniqueNumeric('students', 'nisn', sprintf('26%02d%d%05d', $grade, $this->letterNumber($letter), $number), 10);
        $nikDay = $gender === 'P' ? $birthDay + 40 : $birthDay;
        $nik = $this->uniqueNumeric('students', 'nik', sprintf('320413%02d%02d%02d%04d', $nikDay, $birthMonth, $birthYear % 100, $serial % 10000), 16);

        $userId = $this->createUser([
            'role_id' => $studentRoleId,
            'username' => $username,
            'email' => null,
            'password_hash' => password_hash('siswa123', PASSWORD_BCRYPT),
            'full_name' => $fullName,
            'phone' => '0813' . str_pad((string) $serial, 8, '0', STR_PAD_LEFT),
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($userId <= 0) {
            return false;
        }

        $this->insertRow('students', [
            'user_id' => $userId,
            'class_id' => $classId,
            'nisn' => $nisn,
            'nik' => $nik,
            'gender' => $gender,
            'birth_place' => $number % 3 === 0 ? 'Garut' : 'Bandung',
            'birth_date' => sprintf('%04d-%02d-%02d', $birthYear, $birthMonth, $birthDay),
            'religion' => 'Islam',
            'address' => 'Kp. Demo ' . strtoupper($letter) . ' No. ' . $number . ', Banjaran, Kabupaten Bandung',
            'special_needs' => 'Tidak Ada',
            'disability' => 'Tidak Ada',
            'kip_pip_number' => $number % 9 === 0 ? 'PIP-DEMO-' . $classCode . '-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT) : null,
            'father_name' => 'Ayah Demo ' . $number,
            'mother_name' => 'Ibu Demo ' . $number,
            'guardian_name' => $parentId ? 'Orang Tua Demo' : null,
            'parent_id' => $parentId,
            'admission_date' => '2025-07-14',
            'status' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->grantSimulationAccess($userId, $now);

        return true;
    }

    private function createUser(array $data): int
    {
        $this->insertRow('users', $data);
        return (int) $this->db->insertID();
    }

    private function grantSimulationAccess(int $userId, string $now): void
    {
        if (! $this->db->tableExists('simulation_access_grants')) {
            return;
        }

        $exists = $this->db->table('simulation_access_grants')
            ->where('user_id', $userId)
            ->countAllResults();

        if ($exists > 0) {
            return;
        }

        $this->insertRow('simulation_access_grants', [
            'user_id' => $userId,
            'is_active' => 1,
            'granted_by' => 1,
            'granted_at' => $now,
            'notes' => 'Akses demo untuk akun siswa/orang tua tambahan.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function studentName(string $gender, int $grade, string $letter, int $number): string
    {
        $maleFirst = ['Ahmad', 'Fahri', 'Rizky', 'Daffa', 'Rafi', 'Ilham', 'Fathan', 'Raka', 'Salman', 'Wildan', 'Naufal', 'Arkan'];
        $femaleFirst = ['Alya', 'Zahra', 'Nabila', 'Salsabila', 'Aisyah', 'Nayla', 'Kirana', 'Hana', 'Syifa', 'Rania', 'Fathiyah', 'Dinda'];
        $last = ['Maulana', 'Pratama', 'Ramadhan', 'Hidayat', 'Fauzan', 'Nurhadi', 'Saputra', 'Latifah', 'Rahmawati', 'Sakinah', 'Permana', 'Setiawan'];

        $firstList = $gender === 'L' ? $maleFirst : $femaleFirst;
        $first = $firstList[($number - 1) % count($firstList)];
        $surname = $last[($number + $this->letterNumber($letter)) % count($last)];

        return $first . ' ' . $surname . ' ' . $grade . $letter . '-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }

    private function letterNumber(string $letter): int
    {
        return ['A' => 1, 'B' => 2, 'C' => 3][strtoupper($letter)] ?? 0;
    }

    private function numericSerial(int $grade, string $letter, int $number): int
    {
        return (int) sprintf('%02d%d%03d', $grade, $this->letterNumber($letter), $number);
    }

    private function uniqueNumeric(string $table, string $column, string $base, int $length): string
    {
        $value = substr(str_pad(preg_replace('/\D/', '', $base) ?? '', $length, '0', STR_PAD_RIGHT), 0, $length);
        $counter = 0;

        while ($this->db->table($table)->where($column, $value)->countAllResults() > 0) {
            $counter++;
            $prefixLength = max(1, $length - strlen((string) $counter));
            $value = substr($value, 0, $prefixLength) . $counter;
            $value = substr(str_pad($value, $length, '0', STR_PAD_LEFT), -$length);
        }

        return $value;
    }

    private function insertRow(string $table, array $row): void
    {
        if (! $this->db->tableExists($table)) {
            return;
        }

        $fields = array_flip($this->tableFields($table));
        $payload = array_intersect_key($row, $fields);

        if ($payload !== []) {
            $this->db->table($table)->insert($payload);
        }
    }

    /**
     * @return list<string>
     */
    private function tableFields(string $table): array
    {
        if (! isset($this->fieldCache[$table])) {
            $this->fieldCache[$table] = $this->db->getFieldNames($table);
        }

        return $this->fieldCache[$table];
    }
}
