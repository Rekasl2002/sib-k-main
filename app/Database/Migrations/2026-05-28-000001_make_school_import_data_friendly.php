<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeSchoolImportDataFriendly extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
        $this->db->query('ALTER TABLE classes MODIFY grade_level VARCHAR(20) NOT NULL');
        $this->db->query("ALTER TABLE students MODIFY status ENUM('Aktif','Alumni','Pindah','Keluar','Tidak Aktif') NOT NULL DEFAULT 'Aktif'");

        $this->syncStudentsWithEmisFile();
    }

    public function down()
    {
        $this->restoreLegacyNisColumn();

        $this->db->query("UPDATE users SET email = CONCAT(username, '@sibk.local') WHERE email IS NULL OR email = ''");
        $this->db->query('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
        $this->db->query("UPDATE classes SET grade_level = CASE grade_level WHEN '10' THEN 'X' WHEN '11' THEN 'XI' WHEN '12' THEN 'XII' ELSE grade_level END");
        $this->db->query("UPDATE classes SET grade_level = 'X' WHERE grade_level NOT IN ('X','XI','XII')");
        $this->db->query("ALTER TABLE classes MODIFY grade_level ENUM('X','XI','XII') NOT NULL");
        $this->db->query("UPDATE students SET status = 'Keluar' WHERE status = 'Tidak Aktif'");
        $this->db->query("ALTER TABLE students MODIFY status ENUM('Aktif','Alumni','Pindah','Keluar') NOT NULL DEFAULT 'Aktif'");
    }

    private function syncStudentsWithEmisFile(): void
    {
        $fields = $this->db->getFieldNames('students');

        $columns = [
            'nik'            => "ALTER TABLE students ADD nik VARCHAR(20) NULL AFTER nisn",
            'special_needs'  => "ALTER TABLE students ADD special_needs VARCHAR(100) NULL AFTER address",
            'disability'     => "ALTER TABLE students ADD disability VARCHAR(100) NULL AFTER special_needs",
            'kip_pip_number' => "ALTER TABLE students ADD kip_pip_number VARCHAR(50) NULL AFTER disability",
            'father_name'    => "ALTER TABLE students ADD father_name VARCHAR(255) NULL AFTER kip_pip_number",
            'mother_name'    => "ALTER TABLE students ADD mother_name VARCHAR(255) NULL AFTER father_name",
            'guardian_name'  => "ALTER TABLE students ADD guardian_name VARCHAR(255) NULL AFTER mother_name",
        ];

        foreach ($columns as $column => $sql) {
            if (!in_array($column, $fields, true)) {
                $this->db->query($sql);
            }
        }

        $fields = $this->db->getFieldNames('students');
        if (in_array('nik', $fields, true)) {
            $hasNikIndex = false;
            foreach ($this->db->query("SHOW INDEX FROM students WHERE Column_name = 'nik'")->getResultArray() as $index) {
                if ((int) ($index['Non_unique'] ?? 1) === 0) {
                    $hasNikIndex = true;
                    break;
                }
            }

            if (!$hasNikIndex) {
                $this->db->query('ALTER TABLE students ADD UNIQUE KEY uq_students_nik (nik)');
            }
        }

        if (in_array('nis', $fields, true)) {
            foreach ($this->db->query("SHOW INDEX FROM students WHERE Column_name = 'nis'")->getResultArray() as $index) {
                $keyName = $index['Key_name'] ?? '';
                if ($keyName !== '' && $keyName !== 'PRIMARY') {
                    $this->db->query('ALTER TABLE students DROP INDEX `' . str_replace('`', '``', $keyName) . '`');
                }
            }

            $this->db->query('ALTER TABLE students DROP COLUMN nis');
        }
    }

    private function restoreLegacyNisColumn(): void
    {
        $fields = $this->db->getFieldNames('students');

        if (!in_array('nis', $fields, true)) {
            $this->db->query('ALTER TABLE students ADD nis VARCHAR(20) NULL AFTER nisn');
            $this->db->query("UPDATE students SET nis = nisn WHERE nis IS NULL OR nis = ''");
            $this->db->query('ALTER TABLE students ADD UNIQUE KEY nis (nis)');
        }

        $dropColumns = ['guardian_name', 'mother_name', 'father_name', 'kip_pip_number', 'disability', 'special_needs', 'nik'];
        foreach ($dropColumns as $column) {
            $fields = $this->db->getFieldNames('students');
            if (in_array($column, $fields, true)) {
                if ($column === 'nik') {
                    foreach ($this->db->query("SHOW INDEX FROM students WHERE Column_name = 'nik'")->getResultArray() as $index) {
                        $keyName = $index['Key_name'] ?? '';
                        if ($keyName !== '' && $keyName !== 'PRIMARY') {
                            $this->db->query('ALTER TABLE students DROP INDEX `' . str_replace('`', '``', $keyName) . '`');
                        }
                    }
                }

                $this->db->query('ALTER TABLE students DROP COLUMN ' . $column);
            }
        }
    }
}
