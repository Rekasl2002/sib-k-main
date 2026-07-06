<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * File Path: app/Database/Seeds/BaseDataSeeder.php
 *
 * Kelas dasar untuk seeder SIB-K: menyediakan helper insert yang menyaring
 * kolom sesuai struktur tabel sebenarnya, sehingga seeder tetap aman bila
 * ada perbedaan kecil antar versi skema.
 */
abstract class BaseDataSeeder extends Seeder
{
    /** @var array<string,list<string>> */
    private array $fieldCache = [];

    /**
     * @param list<array<string,mixed>> $rows
     */
    protected function insertRows(string $table, array $rows): void
    {
        if (! $this->db->tableExists($table)) {
            return;
        }

        $fields = array_flip($this->tableFields($table));

        foreach ($rows as $row) {
            $filtered = array_intersect_key($row, $fields);
            if ($filtered !== []) {
                $this->db->table($table)->insert($filtered);
            }
        }
    }

    /**
     * @return list<string>
     */
    protected function tableFields(string $table): array
    {
        if (! isset($this->fieldCache[$table])) {
            $this->fieldCache[$table] = $this->db->getFieldNames($table);
        }

        return $this->fieldCache[$table];
    }

    protected function updateRow(string $table, array $data, array $where): void
    {
        if ($this->db->tableExists($table)) {
            $this->db->table($table)->where($where)->update(array_intersect_key($data, array_flip($this->tableFields($table))));
        }
    }
}
