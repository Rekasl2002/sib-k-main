<?php

namespace App\Services;

use Config\Database;
use Throwable;

/**
 * File Path: app/Services/TrashService.php
 *
 * Layanan Tempat Sampah (pemulihan soft delete).
 * Mendata, memulihkan, dan menghapus permanen data yang dihapus secara soft delete.
 * Aturan kerahasiaan: data hanya terlihat oleh pengguna yang menghapusnya
 * (difilter berdasarkan kolom `deleted_by` = id pengguna saat ini).
 */
class TrashService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Daftar entitas yang ikut fitur Tempat Sampah.
     * Catatan: layanan BK (bimbingan/konseling/dll) diwakili oleh tabel induk
     * bk_service_records agar tidak tampil ganda dengan tabel detailnya.
     *
     * @return array<string,array<string,mixed>>
     */
    private function registry(): array
    {
        return [
            'bk_service'   => ['table' => 'bk_service_records', 'label' => 'Layanan BK', 'titleCol' => 'title'],
            'consultation' => ['table' => 'consultation_complaints', 'label' => 'Konsultasi & Pengaduan', 'titleCol' => 'title'],
            'assignment'   => ['table' => 'bk_assignments', 'label' => 'Penugasan', 'titleCol' => 'title'],
            'note'         => ['table' => 'session_notes', 'label' => 'Catatan Layanan BK', 'titleCol' => 'note_content', 'truncate' => 80],
            'message'      => ['table' => 'messages', 'label' => 'Pesan', 'titleCol' => 'subject'],
            'notification' => ['table' => 'notifications', 'label' => 'Notifikasi', 'titleCol' => 'title'],
            'career'       => ['table' => 'career_options', 'label' => 'Info Karier', 'titleCol' => 'title'],
            'university'   => ['table' => 'university_info', 'label' => 'Info Studi Lanjut', 'titleCol' => 'university_name'],
            'student'      => ['table' => 'students', 'label' => 'Data Siswa', 'titleCol' => 'nisn', 'titlePrefix' => 'NISN ', 'nameJoin' => ['users', 'users.id = students.user_id', 'users.full_name']],
        ];
    }

    /**
     * Ambil semua data terhapus milik pengguna tertentu.
     *
     * @return list<array<string,mixed>>
     */
    public function listForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $out = [];

        foreach ($this->registry() as $key => $cfg) {
            $table = $cfg['table'];
            if (! $this->db->tableExists($table) || ! $this->db->fieldExists('deleted_by', $table)) {
                continue;
            }

            $select   = "{$table}.id AS row_id, {$table}.deleted_at AS deleted_at";
            $titleCol = $cfg['titleCol'] ?? null;
            if ($titleCol && $this->db->fieldExists($titleCol, $table)) {
                $select .= ", {$table}.{$titleCol} AS row_title";
            }
            $hasNameJoin = isset($cfg['nameJoin']);
            if ($hasNameJoin) {
                $select .= ", {$cfg['nameJoin'][2]} AS row_name";
            }

            $builder = $this->db->table($table)->select($select);
            if ($hasNameJoin) {
                $builder->join($cfg['nameJoin'][0], $cfg['nameJoin'][1], 'left');
            }
            $builder->where("{$table}.deleted_by", $userId)
                ->where("{$table}.deleted_at IS NOT NULL")
                ->orderBy("{$table}.deleted_at", 'DESC');

            foreach ($builder->get()->getResultArray() as $row) {
                $out[] = [
                    'entity'     => $key,
                    'id'         => (int) $row['row_id'],
                    'label'      => $cfg['label'],
                    'title'      => $this->buildTitle($cfg, $row),
                    'deleted_at' => $row['deleted_at'] ?? null,
                ];
            }
        }

        usort($out, static fn ($a, $b) => strcmp((string) $b['deleted_at'], (string) $a['deleted_at']));

        return $out;
    }

    /**
     * Pulihkan satu data (set deleted_at & deleted_by ke null).
     */
    public function restore(string $entity, int $id, int $userId): bool
    {
        $cfg = $this->registry()[$entity] ?? null;
        if (! $cfg || $id <= 0 || $userId <= 0) {
            return false;
        }

        $table = $cfg['table'];
        if (! $this->db->tableExists($table) || ! $this->db->fieldExists('deleted_by', $table)) {
            return false;
        }

        $this->db->table($table)
            ->where('id', $id)
            ->where('deleted_by', $userId)
            ->where('deleted_at IS NOT NULL')
            ->update(['deleted_at' => null, 'deleted_by' => null]);

        return $this->db->affectedRows() > 0;
    }

    /**
     * Hapus permanen (hard delete) satu data milik penghapus.
     *
     * @return array{success:bool,message:string}
     */
    public function forceDelete(string $entity, int $id, int $userId): array
    {
        $cfg = $this->registry()[$entity] ?? null;
        if (! $cfg || $id <= 0 || $userId <= 0) {
            return ['success' => false, 'message' => 'Data tidak valid.'];
        }

        $table = $cfg['table'];
        if (! $this->db->tableExists($table) || ! $this->db->fieldExists('deleted_by', $table)) {
            return ['success' => false, 'message' => 'Data tidak ditemukan.'];
        }

        $exists = $this->db->table($table)
            ->where('id', $id)
            ->where('deleted_by', $userId)
            ->where('deleted_at IS NOT NULL')
            ->countAllResults();

        if ($exists === 0) {
            return ['success' => false, 'message' => 'Data tidak ditemukan di tempat sampah Anda.'];
        }

        try {
            $this->db->table($table)->where('id', $id)->where('deleted_by', $userId)->delete();

            return ['success' => true, 'message' => 'Data dihapus permanen.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Data tidak dapat dihapus permanen karena masih terkait data lain.'];
        }
    }

    /**
     * Bentuk judul tampilan dari sebuah baris.
     *
     * @param array<string,mixed> $cfg
     * @param array<string,mixed> $row
     */
    private function buildTitle(array $cfg, array $row): string
    {
        $name = $row['row_name'] ?? null;
        if (is_string($name) && $name !== '') {
            return $name;
        }

        $title = $row['row_title'] ?? null;
        if (is_string($title) && $title !== '') {
            if (isset($cfg['truncate']) && mb_strlen($title) > (int) $cfg['truncate']) {
                return mb_substr($title, 0, (int) $cfg['truncate']) . '…';
            }
            if (isset($cfg['titlePrefix'])) {
                return $cfg['titlePrefix'] . $title;
            }

            return $title;
        }

        return $cfg['label'] . ' #' . (int) ($row['row_id'] ?? 0);
    }
}
