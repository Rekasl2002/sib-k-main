<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menyesuaikan nama kelas yang ada di database agar mengikuti format file data
 * siswa sekolah, yaitu "Kelas <tingkat> - <rombel>" (contoh: "Kelas 12 - A").
 *
 * - Kelas seperti "10 A", "X B", "XII C" dirapikan menjadi "Kelas 10 - A" dst.
 * - grade_level dipertahankan dalam ANGKA (10/11/12), sesuai isi database & importer.
 * - major diset "IPA" (sekolah saat ini hanya IPA; sesuai default impor).
 * - Bila ada dua kelas yang menuju nama sama (mis. "10 C" dan "X IPA C" -> "Kelas 10 - C"),
 *   kelas dengan siswa terbanyak dijadikan acuan; siswa dari kelas kembarannya
 *   DIPINDAHKAN (hanya kolom students.class_id) ke kelas acuan, lalu kelas kembarannya
 *   di-soft delete.
 *
 * AMAN UNTUK AKUN: migrasi ini TIDAK menyentuh tabel users sama sekali
 * (akun siswa & orang tua tidak diubah/dihapus). Hanya nama kelas dan
 * penempatan kelas (class_id) siswa yang disesuaikan.
 */
class NormalizeClassNamesToSchoolFormat extends Migration
{
    public function up()
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        $classes = $db->query('
            SELECT c.id, c.class_name, c.grade_level,
                   (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id AND s.deleted_at IS NULL) AS jml
            FROM classes c
            WHERE c.deleted_at IS NULL
        ')->getResultArray();

        // Hitung target nama untuk tiap kelas (null = tidak dinormalkan, dibiarkan).
        $targetById = [];
        foreach ($classes as $c) {
            $target = $this->targetClassName((string) $c['class_name'], (string) $c['grade_level']);
            if ($target !== null) {
                $targetById[(int) $c['id']] = $target;
            }
        }

        // Kelas dengan siswa terbanyak diprioritaskan jadi "acuan" (keeper).
        usort($classes, static function ($a, $b) {
            return ((int) $b['jml'] <=> (int) $a['jml']) ?: ((int) $a['id'] <=> (int) $b['id']);
        });

        $keeperByTarget = [];
        foreach ($classes as $c) {
            $id = (int) $c['id'];
            if (! isset($targetById[$id])) {
                continue;
            }
            $target = $targetById[$id];

            if (! isset($keeperByTarget[$target])) {
                // Jadikan kelas acuan: rapikan nama + major IPA.
                $keeperByTarget[$target] = $id;
                $db->table('classes')->where('id', $id)->update([
                    'class_name' => $target,
                    'major'      => 'IPA',
                    'updated_at' => $now,
                ]);
            } else {
                // Kembaran: pindahkan siswanya ke kelas acuan, lalu soft-delete.
                $keeperId = $keeperByTarget[$target];

                $db->table('students')
                    ->where('class_id', $id)
                    ->where('deleted_at', null)
                    ->update(['class_id' => $keeperId]);

                $db->table('classes')->where('id', $id)->update([
                    'is_active'  => 0,
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        // Pembalikan bersifat sebagian: hanya mengembalikan penamaan
        // "Kelas 10 - A" menjadi "10 A" (major -> "Umum"). Penggabungan kelas
        // (pemindahan siswa & soft delete kelas kembaran) TIDAK dibalik otomatis.
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        $rows = $db->table('classes')
            ->select('id, class_name')
            ->like('class_name', 'Kelas ', 'after')
            ->get()->getResultArray();

        foreach ($rows as $r) {
            if (preg_match('/^Kelas\s+(\d{1,2})\s*-\s*([A-Za-z])$/', (string) $r['class_name'], $m)) {
                $db->table('classes')->where('id', (int) $r['id'])->update([
                    'class_name' => $m[1] . ' ' . strtoupper($m[2]),
                    'major'      => 'Umum',
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Hitung nama target "Kelas <tingkat> - <rombel>" dari nama kelas & grade_level.
     * Mengembalikan null bila pola tidak dikenali (kelas dibiarkan apa adanya).
     */
    private function targetClassName(string $className, string $gradeLevel): ?string
    {
        // Tingkat: utamakan grade_level numerik, jika tidak tebak dari nama.
        $grade = null;
        $gl = trim($gradeLevel);
        if (preg_match('/^\d{1,2}$/', $gl)) {
            $grade = (int) $gl;
        } elseif (preg_match('/(\d{1,2})/', $className, $m)) {
            $grade = (int) $m[1];
        } elseif (preg_match('/\bXII\b/i', $className)) {
            $grade = 12;
        } elseif (preg_match('/\bXI\b/i', $className)) {
            $grade = 11;
        } elseif (preg_match('/\bX\b/i', $className)) {
            $grade = 10;
        }

        if ($grade === null || $grade < 1 || $grade > 12) {
            return null;
        }

        // Rombel: token terakhir bila satu huruf, atau huruf terakhir pada nama.
        $section = null;
        $tokens  = preg_split('/\s+/', trim($className)) ?: [];
        $last    = strtoupper((string) end($tokens));
        if (preg_match('/^[A-Z]$/', $last)) {
            $section = $last;
        } elseif (preg_match('/([A-Za-z])\s*$/', $className, $m)) {
            $section = strtoupper($m[1]);
        }

        if ($section === null) {
            return null;
        }

        return sprintf('Kelas %d - %s', $grade, $section);
    }
}
