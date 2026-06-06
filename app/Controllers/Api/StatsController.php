<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Config\Database;

class StatsController extends BaseController
{
    private \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function adminStats()
    {
        return $this->response->setJSON([
            'users'       => $this->countRows('users'),
            'students'    => $this->countRows('students'),
            'classes'     => $this->countRows('classes'),
            'sessions'    => $this->countRows('counseling_sessions'),
            'assessments' => $this->countRows('assessments'),
        ]);
    }

    public function counselorStats()
    {
        $userId = (int) session('user_id');

        return $this->response->setJSON([
            'sessions'    => $this->countRows('counseling_sessions', ['counselor_id' => $userId]),
            'assessments' => $this->countRows('assessments', ['created_by' => $userId]),
        ]);
    }

    public function studentStats()
    {
        $studentId = $this->currentStudentId();

        return $this->response->setJSON([
            'sessions'    => $studentId ? $this->countRows('counseling_sessions', ['student_id' => $studentId]) : 0,
            'assessments' => $studentId ? $this->countRows('assessment_results', ['student_id' => $studentId]) : 0,
        ]);
    }

    private function countRows(string $table, array $where = []): int
    {
        if (! $this->db->tableExists($table)) {
            return 0;
        }

        $builder = $this->db->table($table);
        foreach ($where as $field => $value) {
            if ($this->db->fieldExists($field, $table)) {
                $builder->where($field, $value);
            }
        }
        if ($this->db->fieldExists('deleted_at', $table)) {
            $builder->where('deleted_at', null);
        }

        return (int) $builder->countAllResults();
    }

    private function currentStudentId(): ?int
    {
        if (! $this->db->tableExists('students')) {
            return null;
        }

        $row = $this->db->table('students')
            ->select('id')
            ->where('user_id', (int) session('user_id'))
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ? (int) $row['id'] : null;
    }
}
