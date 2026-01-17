<?php

namespace App\Controllers\Parents;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;

class DashboardController extends BaseController
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Lebih robust: beberapa proyek menyimpan id di user_id atau id
        $parentId = (int) (session('user_id') ?? session('id') ?? 0);
        if (!$parentId) {
            return redirect()->to('/login');
        }

        // Anak milik parent + ringkasan per anak
        // FIX: students.full_name -> users.full_name (karena kolom students.full_name bisa sudah dihapus)
        $children = $this->db->table('students s')
            ->select("
                s.id,
                u.full_name AS full_name,
                s.nisn, s.nis, s.class_id,
                c.class_name, c.grade_level, c.major,
                u.email, u.phone, u.profile_photo,

                /* Ringkasan pelanggaran per anak */
                (SELECT COUNT(*)
                   FROM violations v
                  WHERE v.student_id = s.id
                    AND v.deleted_at IS NULL) AS violations_count,

                (SELECT COALESCE(SUM(COALESCE(vc.point_deduction, vc.points, 0)), 0)
                   FROM violations v
              LEFT JOIN violation_categories vc ON vc.id = v.category_id
                  WHERE v.student_id = s.id
                    AND v.deleted_at IS NULL) AS points_sum,

                (SELECT MAX(v.violation_date)
                   FROM violations v
                  WHERE v.student_id = s.id
                    AND v.deleted_at IS NULL) AS last_violation_date
            ")
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();

        // Agregat lintas anak
        $stats = [
            'children'          => count($children),
            'violations_total'  => 0,
            'points_total'      => 0,
            'upcoming_sessions' => 0,
        ];
        foreach ($children as $r) {
            $stats['violations_total'] += (int) ($r['violations_count'] ?? 0);
            $stats['points_total']     += (int) ($r['points_sum'] ?? 0);
        }
        $violationTotal = $stats['violations_total'];

        // ID anak & kelas
        $childIds = array_column($children, 'id');
        $classIds = array_values(array_unique(array_filter(array_column($children, 'class_id'))));

        // Jadwal konseling mendatang (Individu, Kelompok, Klasikal) — hanya yang relevan untuk parent
        $upcoming = [];
        if (!empty($childIds) || !empty($classIds)) {
            $today = date('Y-m-d');

            $qb = $this->db->table('counseling_sessions cs')
                ->select("
                    cs.id,
                    cs.session_date,
                    cs.session_time,

                    /* Samarkan topik jika confidential dan bukan status Dijadwalkan */
                    CASE
                        WHEN cs.is_confidential = 1 AND cs.status <> 'Dijadwalkan'
                            THEN 'Sesi Konseling (Terbatas)'
                        ELSE cs.topic
                    END AS topic,

                    cs.status,
                    cs.location,

                    /* Siapa yang ditampilkan di kolom 'Anak' */
                    CASE
                        WHEN cs.session_type = 'Klasikal'
                            THEN NULL
                        ELSE COALESCE(s_ind.id, s_sp.id)
                    END AS student_id,

                    CASE
                        WHEN cs.session_type = 'Klasikal'
                            THEN CONCAT('Kelas ', COALESCE(c.class_name, ''))
                        ELSE COALESCE(u_ind.full_name, u_sp.full_name)
                    END AS full_name
                ")
                // Individu: murid yang dituju
                ->join('students s_ind', 's_ind.id = cs.student_id AND s_ind.deleted_at IS NULL', 'left')
                ->join('users u_ind', 'u_ind.id = s_ind.user_id AND u_ind.deleted_at IS NULL', 'left')

                // Kelompok: peserta (join student + user), tapi hanya yang parent-nya adalah parent login
                ->join('session_participants sp', 'sp.session_id = cs.id AND sp.deleted_at IS NULL', 'left')
                ->join('students s_sp', 's_sp.id = sp.student_id AND s_sp.deleted_at IS NULL AND s_sp.parent_id = ' . (int) $parentId, 'left')
                ->join('users u_sp', 'u_sp.id = s_sp.user_id AND u_sp.deleted_at IS NULL', 'left')

                // Klasikal: ambil nama kelas untuk label
                ->join('classes c', 'c.id = cs.class_id', 'left')

                ->where('cs.deleted_at', null)
                ->where('cs.session_date >=', $today)
                ->where('cs.status', 'Dijadwalkan')
                ->groupStart();

            // Agar OR-group aman ketika salah satu list kosong
            $added = false;

            // Individu: sesi untuk anak parent
            if (!empty($childIds)) {
                $qb->whereIn('cs.student_id', $childIds);
                $added = true;
            }

            // Kelompok: sesi yang punya peserta anak parent (pakai indikator s_sp.id tidak null)
            if ($added) {
                $qb->orWhere('s_sp.id IS NOT NULL', null, false);
            } else {
                $qb->where('s_sp.id IS NOT NULL', null, false);
                $added = true;
            }

            // Klasikal: sesi untuk kelas anak parent
            if (!empty($classIds)) {
                if ($added) {
                    $qb->orGroupStart();
                } else {
                    $qb->groupStart();
                }

                $qb->where('cs.session_type', 'Klasikal')
                   ->whereIn('cs.class_id', $classIds)
                   ->groupEnd();

                $added = true;
            }

            $upcoming = $qb->groupEnd()
                ->distinct()
                ->orderBy('cs.session_date', 'ASC')
                ->orderBy('cs.session_time', 'ASC')
                ->limit(5)
                ->get()
                ->getResultArray();

            $stats['upcoming_sessions'] = count($upcoming);
        }

        // Pelanggaran terbaru lintas anak (opsional)
        $recentViolations = [];
        if (!empty($childIds)) {
            $recentViolations = $this->db->table('violations v')
                ->select("
                    v.id,
                    v.student_id,
                    v.violation_date,
                    v.description,
                    COALESCE(vc.point_deduction, vc.points, 0) AS points,
                    vc.category_name,
                    su.full_name AS full_name
                ")
                ->join('students s', 's.id = v.student_id AND s.deleted_at IS NULL', 'left')
                ->join('users su', 'su.id = s.user_id AND su.deleted_at IS NULL', 'left')
                ->join('violation_categories vc', 'vc.id = v.category_id', 'left')
                ->whereIn('v.student_id', $childIds)
                ->where('v.deleted_at', null)
                ->orderBy('v.violation_date', 'DESC')
                ->orderBy('v.created_at', 'DESC')
                ->limit(5)
                ->get()
                ->getResultArray();
        }

        return view('parent/dashboard', [
            'title'            => 'Dashboard Orang Tua',
            'children'         => $children,
            'stats'            => $stats,
            'violationTotal'   => $violationTotal,
            'upcoming'         => $upcoming,
            'recentViolations' => $recentViolations,
        ]);
    }
}
