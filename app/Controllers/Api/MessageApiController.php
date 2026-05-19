<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Config\Database;

class MessageApiController extends BaseController
{
    private \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getUnreadCount()
    {
        $count = $this->db->table('message_participants')
            ->where('user_id', (int) session('user_id'))
            ->where('is_read', 0)
            ->where('deleted_at', null)
            ->countAllResults();

        return $this->response->setJSON(['count' => (int) $count]);
    }

    public function getLatest()
    {
        $rows = $this->db->table('messages m')
            ->select('m.id, m.subject, m.body, m.created_at, m.created_by, mp.is_read, u.full_name AS sender_name')
            ->join('message_participants mp', 'mp.message_id = m.id', 'inner')
            ->join('users u', 'u.id = m.created_by', 'left')
            ->where('mp.user_id', (int) session('user_id'))
            ->where('m.deleted_at', null)
            ->where('mp.deleted_at', null)
            ->orderBy('m.created_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return $this->response->setJSON($rows);
    }
}
