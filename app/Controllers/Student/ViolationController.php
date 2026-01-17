<?php

namespace App\Controllers\Student;

use App\Controllers\BaseController;
use App\Models\ViolationCategoryModel;

class ViolationController extends BaseController
{
    /**
     * Ambil data siswa yang sedang login berdasarkan user_id di session.
     *
     * @param \CodeIgniter\Database\BaseConnection $db
     * @return object|null
     */
    protected function getCurrentStudent($db)
    {
        $userId = session('user_id');

        if (!$userId) {
            return null;
        }

        // Ambil minimal id siswa (cukup untuk filter)
        return $db->table('students')
            ->select('id')
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->get()
            ->getRow();
    }

    /**
     * GET /student/violations
     * Daftar pelanggaran milik siswa yang login.
     *
     * - Ambil student_id dari user_id session.
     * - Join ke violation_categories untuk nama kategori & poin.
     * - Join ke sanctions (jika ada) untuk jenis & status sanksi.
     * - Hitung total poin pelanggaran (sum dari poin per pelanggaran).
     */
    public function index()
    {
        $db = \Config\Database::connect();

        // Pastikan user adalah siswa yang punya record di tabel students
        $student = $this->getCurrentStudent($db);

        if (!$student) {
            // Aman saja: kalau user tidak punya record siswa, tampilkan 404
            return view('errors/html/error_404');
        }

        // Deteksi nama kolom dinamis di tabel violations (projek lama vs baru)
        $violationFields = $db->getFieldData('violations');
        $fieldNames      = array_map(static fn($f) => $f->name, $violationFields);

        // Kolom kategori: category_id (baru) atau violation_category_id (lama)
        $catCol = in_array('category_id', $fieldNames, true)
            ? 'category_id'
            : (in_array('violation_category_id', $fieldNames, true)
                ? 'violation_category_id'
                : null);

        // Kolom pelapor: reported_by (baru) / recorder_id / created_by (fallback)
        $recCol = in_array('reported_by', $fieldNames, true)
            ? 'reported_by'
            : (in_array('recorder_id', $fieldNames, true)
                ? 'recorder_id'
                : (in_array('created_by', $fieldNames, true) ? 'created_by' : null));

        $builder = $db->table('violations v');

        // Kolom dasar yang selalu ada
        $select = [
            'v.id',
            'COALESCE(v.violation_date, v.created_at) AS recorded_at',
            'v.violation_date',
            'v.violation_time',
            'v.description',
        ];

        // Kolom kategori & poin
        if ($catCol) {
            // Di database proyek ini, poin pelanggaran yang dipakai adalah point_deduction
            $select[] = 'COALESCE(vc.category_name, "-") AS violation_type';
            $select[] = 'COALESCE(vc.point_deduction, 0) AS points';
        } else {
            $select[] = "'-' AS violation_type";
            $select[] = '0   AS points';
        }

        // Kolom nama guru/pelapor (jika struktur tabel mendukung)
        if ($recCol) {
            $select[] = 'u.full_name AS recorder';
        } else {
            $select[] = "'-' AS recorder";
        }

        // Ringkasan semua sanksi dalam satu kolom (agar tidak duplikat baris)
        $select[] = "GROUP_CONCAT(DISTINCT CONCAT(s.sanction_type, ' (', s.status, ')') SEPARATOR '; ') AS sanctions_summary";

        $builder->select(implode(', ', $select));

        if ($catCol) {
            $builder->join('violation_categories vc', "vc.id = v.$catCol", 'left');
        }

        if ($recCol) {
            $builder->join('users u', "u.id = v.$recCol", 'left');
        }

        // Join ke sanctions, tapi tetap satu baris per pelanggaran dengan GROUP BY
        $builder->join('sanctions s', 's.violation_id = v.id AND s.deleted_at IS NULL', 'left');

        // Urutkan dari yang terbaru
        $builder
            ->where('v.student_id', $student->id)
            ->where('v.deleted_at', null)
            ->groupBy('v.id')
            ->orderBy('v.violation_date', 'DESC')
            ->orderBy('v.created_at', 'DESC');

        // Ambil data sebagai object (supaya kompatibel dengan view lama)
        $rows = $builder->get()->getResult();

        // Hitung total poin pelanggaran (sum dari alias "points")
        $totalPoints = 0;
        foreach ($rows as $row) {
            $totalPoints += (int) ($row->points ?? 0);
        }

        $data = [
            'title'        => 'Kasus & Pelanggaran',
            'violations'   => $rows,
            'total_points' => $totalPoints,
        ];

        return view('student/violations/index', $data);
    }

    /**
     * GET /student/violations/{id}
     * Detail satu pelanggaran milik siswa yang login, termasuk daftar sanksi.
     *
     * Hanya boleh diakses jika:
     * - violation.student_id = student_id dari user yang sedang login
     */
    public function detail($id = null)
    {
        $db = \Config\Database::connect();
        $id = (int) $id;

        if ($id <= 0) {
            return redirect()
                ->to('/student/violations')
                ->with('error', 'Data pelanggaran tidak ditemukan.');
        }

        $student = $this->getCurrentStudent($db);
        if (!$student) {
            return view('errors/html/error_404');
        }

        // Deteksi kolom untuk kompatibilitas schema
        $violationFields = $db->getFieldData('violations');
        $fieldNames      = array_map(static fn($f) => $f->name, $violationFields);

        // Kolom kategori
        $catCol = in_array('category_id', $fieldNames, true)
            ? 'category_id'
            : (in_array('violation_category_id', $fieldNames, true)
                ? 'violation_category_id'
                : null);

        // Kolom pelapor
        $reportedCol = in_array('reported_by', $fieldNames, true)
            ? 'reported_by'
            : (in_array('recorder_id', $fieldNames, true)
                ? 'recorder_id'
                : (in_array('created_by', $fieldNames, true) ? 'created_by' : null));

        // Kolom penangan (opsional)
        $handledCol = in_array('handled_by', $fieldNames, true) ? 'handled_by' : null;

        // Helper kecil untuk select kolom violations yang mungkin ada/tidak
        $v = static function (string $col, array $names, string $alias = null): string {
            $alias = $alias ?: $col;
            return in_array($col, $names, true) ? "v.$col" : "NULL AS $alias";
        };

        // Detail pelanggaran + data siswa lengkap
        // FIX PENTING:
        // - students.full_name sudah dihapus
        // - Nama siswa diambil dari users.full_name via students.user_id
        $select = [
            'v.id',
            'v.student_id',
            $v('violation_date', $fieldNames, 'violation_date'),
            $v('violation_time', $fieldNames, 'violation_time'),
            $v('location', $fieldNames, 'location'),
            $v('description', $fieldNames, 'description'),
            $v('witness', $fieldNames, 'witness'),
            $v('evidence', $fieldNames, 'evidence'),
            $v('status', $fieldNames, 'status'),
            $v('resolution_notes', $fieldNames, 'resolution_notes'),
            $v('resolution_date', $fieldNames, 'resolution_date'),
            $v('parent_notified', $fieldNames, 'parent_notified'),
            $v('parent_notified_at', $fieldNames, 'parent_notified_at'),

            // kategori
            'vc.category_name',
            'vc.severity_level',
            'vc.point_deduction',
            'vc.description AS category_description',
            'vc.examples',

            // data siswa (nama dari users)
            'su.full_name AS student_full_name',
            's.nis       AS student_nis',
            's.nisn      AS student_nisn',
            'c.class_name AS class_name',
        ];

        // petugas (dinamis)
        if ($reportedCol) {
            $select[] = 'reporter.full_name AS reporter_name';
        } else {
            $select[] = "NULL AS reporter_name";
        }

        if ($handledCol) {
            $select[] = 'handler.full_name  AS handler_name';
        } else {
            $select[] = "NULL AS handler_name";
        }

        $builder = $db->table('violations v')
            ->select($select)
            ->join('students s', 's.id = v.student_id')
            ->join('users su', 'su.id = s.user_id', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left');

        // join kategori pakai kolom yang tersedia
        if ($catCol) {
            $builder->join('violation_categories vc', "vc.id = v.$catCol", 'left');
        } else {
            // tetap join left agar select vc.* tidak bikin error? (tidak, karena tidak ada join)
            // jadi kita set minimal: tidak join, tapi select vc.* akan NULL jika tidak ada.
            // (aman untuk kebanyakan DB, tapi kalau strict, lebih aman tidak select vc.* jika tidak join)
            // Di sini diasumsikan catCol ada di schema proyekmu.
        }

        // join reporter/handler kalau kolomnya ada
        if ($reportedCol) {
            $builder->join('users reporter', "reporter.id = v.$reportedCol", 'left');
        }
        if ($handledCol) {
            $builder->join('users handler', "handler.id = v.$handledCol", 'left');
        }

        $violation = $builder
            ->where('v.id', $id)
            ->where('v.student_id', $student->id)
            ->where('v.deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$violation) {
            return redirect()
                ->to('/student/violations')
                ->with('error', 'Data pelanggaran tidak ditemukan atau tidak dapat diakses.');
        }

        // Daftar sanksi
        $sanctions = $db->table('sanctions s')
            ->select([
                's.id',
                's.violation_id',
                's.sanction_type',
                's.sanction_date',
                's.start_date',
                's.end_date',
                's.duration_days',
                's.description AS sanction_description',
                's.status',
                's.completed_date',
                's.completion_notes AS sanction_notes',
                's.parent_acknowledged',
                's.parent_acknowledged_at',
                'assigner.full_name AS assigned_by_name',
                'verifier.full_name AS verified_by_name',
            ])
            ->join('users assigner', 'assigner.id = s.assigned_by', 'left')
            ->join('users verifier', 'verifier.id = s.verified_by', 'left')
            ->where('s.violation_id', $violation['id'])
            ->where('s.deleted_at', null)
            ->orderBy('s.sanction_date', 'ASC')
            ->orderBy('s.created_at', 'ASC')
            ->get()
            ->getResultArray();

        $data = [
            'title'     => 'Detail Kasus & Pelanggaran',
            'violation' => $violation,
            'sanctions' => $sanctions,
            // kirim juga array student siap pakai ke view
            'student'   => [
                'full_name'  => $violation['student_full_name'] ?? '',
                'nis'        => $violation['student_nis'] ?? '',
                'nisn'       => $violation['student_nisn'] ?? '',
                'class_name' => $violation['class_name'] ?? '',
            ],
        ];

        return view('student/violations/detail', $data);
    }

    /**
     * GET /student/violations/categories
     * Daftar kategori pelanggaran (ringan/sedang/berat) yang boleh dilihat siswa.
     *
     * Menggunakan ViolationCategoryModel::getActiveCategories()
     * supaya konsisten dengan sisi admin/guru BK.
     */
    public function categories()
    {
        $db      = \Config\Database::connect();
        $student = $this->getCurrentStudent($db);

        if (!$student) {
            // Kalau bukan akun siswa, kembalikan 404 supaya tidak bocor ke role lain
            return view('errors/html/error_404');
        }

        $categoryModel = new ViolationCategoryModel();
        $categories    = $categoryModel->getActiveCategories();

        $data = [
            'title'      => 'Kategori Kasus & Pelanggaran',
            'categories' => $categories,
        ];

        return view('student/violations/categories', $data);
    }
}
