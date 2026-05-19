<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Config\Database;

class ClassApiController extends BaseController
{
    private \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getActive()
    {
        $rows = $this->db->table('classes')
            ->select('id, class_name, grade_level, major, academic_year_id')
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->orderBy('grade_level', 'ASC')
            ->orderBy('class_name', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON($rows);
    }

    public function getStudents($classId)
    {
        $rows = $this->db->table('students s')
            ->select('s.id, s.user_id, s.nis, s.nisn, u.full_name, u.email')
            ->join('users u', 'u.id = s.user_id', 'left')
            ->where('s.class_id', (int) $classId)
            ->where('s.deleted_at', null)
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON($rows);
    }
}
