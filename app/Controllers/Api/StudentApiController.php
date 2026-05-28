<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Config\Database;

class StudentApiController extends BaseController
{
    private \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function search()
    {
        $q = trim((string) ($this->request->getGet('q') ?? $this->request->getGet('term') ?? ''));
        $builder = $this->baseQuery()->limit(20);

        if ($q !== '') {
            $builder->groupStart()
                ->like('u.full_name', $q)
                ->orLike('s.nisn', $q)
                ->orLike('s.nisn', $q)
                ->groupEnd();
        }

        return $this->response->setJSON($builder->get()->getResultArray());
    }

    public function getByClass($classId)
    {
        $rows = $this->baseQuery()
            ->where('s.class_id', (int) $classId)
            ->orderBy('u.full_name', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON($rows);
    }

    public function show($id)
    {
        $row = $this->baseQuery()
            ->where('s.id', (int) $id)
            ->get()
            ->getRowArray();

        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Siswa tidak ditemukan.']);
        }

        return $this->response->setJSON($row);
    }

    private function baseQuery()
    {
        return $this->db->table('students s')
            ->select('s.id, s.user_id, s.class_id, s.nisn, s.nik, s.gender, s.status, u.full_name, u.email, c.class_name, c.grade_level')
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.deleted_at', null);
    }
}
