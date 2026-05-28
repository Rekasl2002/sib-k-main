<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlignStudentsWithEmisIdentity extends Migration
{
    public function up()
    {
        $this->syncStudentsTable();
    }

    public function down()
    {
        $this->restoreLegacyNisColumn();
    }

    private function syncStudentsTable(): void
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
        if (in_array('nik', $fields, true) && !$this->hasUniqueIndex('students', 'nik')) {
            $this->db->query('ALTER TABLE students ADD UNIQUE KEY uq_students_nik (nik)');
        }

        if (in_array('nis', $fields, true)) {
            $this->dropIndexesForColumn('students', 'nis');
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

        foreach (['guardian_name', 'mother_name', 'father_name', 'kip_pip_number', 'disability', 'special_needs', 'nik'] as $column) {
            $fields = $this->db->getFieldNames('students');
            if (!in_array($column, $fields, true)) {
                continue;
            }

            if ($column === 'nik') {
                $this->dropIndexesForColumn('students', 'nik');
            }

            $this->db->query('ALTER TABLE students DROP COLUMN ' . $column);
        }
    }

    private function hasUniqueIndex(string $table, string $column): bool
    {
        foreach ($this->db->query("SHOW INDEX FROM {$table} WHERE Column_name = '{$column}'")->getResultArray() as $index) {
            if ((int) ($index['Non_unique'] ?? 1) === 0) {
                return true;
            }
        }

        return false;
    }

    private function dropIndexesForColumn(string $table, string $column): void
    {
        foreach ($this->db->query("SHOW INDEX FROM {$table} WHERE Column_name = '{$column}'")->getResultArray() as $index) {
            $keyName = $index['Key_name'] ?? '';
            if ($keyName !== '' && $keyName !== 'PRIMARY') {
                $this->db->query('ALTER TABLE ' . $table . ' DROP INDEX `' . str_replace('`', '``', $keyName) . '`');
            }
        }
    }
}
