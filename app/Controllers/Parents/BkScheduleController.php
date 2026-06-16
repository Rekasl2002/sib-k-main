<?php

/**
 * File: app/Controllers/Parents/BkScheduleController.php
 * Fitur: Halaman terpadu "Jadwal Kegiatan/Acara BK" untuk Orang Tua.
 * Melebur 5 layanan BK menjadi satu halaman (JADWAL SAJA, tanpa detail). Asesmen
 * anak ditampilkan sebagai CARD PENGINGAT (judul + tenggat + ajakan), tanpa
 * status/soal/jawaban/hasil. Card hilang otomatis bila sudah dikerjakan/lewat tenggat.
 */

namespace App\Controllers\Parents;

use App\Controllers\BaseController;
use App\Services\BkServiceService;
use CodeIgniter\Database\BaseConnection;

class BkScheduleController extends BaseController
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        helper(['url']);
    }

    private function currentParentId(): int
    {
        return (int) (session('user_id') ?? session('id') ?? 0);
    }

    public function index()
    {
        $parentId = $this->currentParentId();
        $service = new BkServiceService();

        return view('role_features/bk_schedule/index', [
            'title'                  => 'Jadwal Kegiatan/Acara BK',
            'role'                   => 'orang-tua',
            'schedule'               => $service->scheduleByType('orang-tua', $parentId, 'upcoming'),
            'showDetailEye'          => false,
            'showAssessmentReminder' => true,
            'assessmentReminders'    => $this->assessmentReminders($parentId),
        ]);
    }

    /**
     * Asesmen anak yang MASIH terbuka & BELUM dikerjakan, untuk card pengingat.
     * Hanya judul + tenggat + nama anak (tanpa status/soal/jawaban/hasil).
     *
     * @return list<array<string,mixed>>
     */
    private function assessmentReminders(int $parentId): array
    {
        if ($parentId <= 0) {
            return [];
        }

        $today = date('Y-m-d');

        // Anak-anak milik orang tua ini.
        $children = $this->db->table('students s')
            ->select('s.id, s.class_id, c.grade_level, u.full_name')
            ->join('users u', 'u.id = s.user_id AND u.deleted_at IS NULL', 'left')
            ->join('classes c', 'c.id = s.class_id AND c.deleted_at IS NULL', 'left')
            ->where('s.parent_id', $parentId)
            ->where('s.deleted_at', null)
            ->get()->getResultArray();

        if (! $children) {
            return [];
        }

        $hasAssignees = $this->db->tableExists('assessment_assignees');
        $reminders = [];

        foreach ($children as $child) {
            $studentId = (int) $child['id'];
            $classId   = (int) ($child['class_id'] ?? 0);
            $grade     = (string) ($child['grade_level'] ?? '');

            $builder = $this->db->table('assessments a')
                ->select('a.id, a.title, a.end_date')
                ->where('a.is_active', 1)
                ->where('a.is_published', 1)
                ->where('a.deleted_at', null)
                // Masih terbuka: tanpa tenggat atau tenggat belum lewat.
                ->groupStart()
                    ->where("(a.end_date IS NULL OR a.end_date >= '{$today}')", null, false)
                ->groupEnd()
                // Sasaran mencakup anak ini.
                ->groupStart()
                    ->where('a.target_audience', 'All')
                    ->orGroupStart()
                        ->where('a.target_audience', 'Class')
                        ->where('a.target_class_id', $classId)
                    ->groupEnd()
                    ->orGroupStart()
                        ->where('a.target_audience', 'Grade')
                        ->where('a.target_grade', $grade)
                    ->groupEnd();

            if ($hasAssignees) {
                $builder->orGroupStart()
                    ->where('a.target_audience', 'Individual')
                    ->where('EXISTS(SELECT 1 FROM assessment_assignees x WHERE x.assessment_id = a.id AND x.student_id = ' . $studentId . ' AND x.deleted_at IS NULL)', null, false)
                    ->groupEnd();
            }

            $builder->groupEnd()
                // Belum dikerjakan (tidak ada result Completed/Graded milik anak).
                ->where('NOT EXISTS(SELECT 1 FROM assessment_results r WHERE r.assessment_id = a.id AND r.student_id = ' . $studentId . " AND r.status IN ('Completed','Graded') AND r.deleted_at IS NULL)", null, false)
                ->orderBy('a.end_date', 'ASC')
                ->limit(10);

            foreach ($builder->get()->getResultArray() as $a) {
                $reminders[] = [
                    'title'      => $a['title'] ?? 'Asesmen',
                    'due'        => $a['end_date'] ?? null,
                    'child_name' => $child['full_name'] ?? null,
                ];
            }
        }

        return $reminders;
    }
}
