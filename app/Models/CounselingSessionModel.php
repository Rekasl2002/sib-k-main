<?php

/**
 * File Path: app/Models/CounselingSessionModel.php
 * 
 * Counseling Session Model
 * Mengelola data sesi konseling dengan relasi ke students, counselors, classes, notes, dan participants
 * 
 * @package    SIB-K
 * @subpackage Models
 * @category   Counseling
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Models;

use CodeIgniter\Model;

class CounselingSessionModel extends Model
{
    protected $table            = 'counseling_sessions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'student_id',
        'counselor_id',
        'class_id',
        'session_type',
        'session_date',
        'session_time',
        'location',
        'topic',
        'problem_description',
        'session_summary',
        'follow_up_plan',
        'status',
        'is_confidential',
        'duration_minutes',
        'cancellation_reason',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'counselor_id'     => 'required|integer|is_not_unique[users.id]',
        'session_type'     => 'required|in_list[Individu,Kelompok,Klasikal]',
        'session_date'     => 'required|valid_date[Y-m-d]',
        'session_time'     => 'permit_empty|valid_date[H:i:s]',
        'topic'            => 'required|min_length[3]|max_length[255]',
        'location'         => 'permit_empty|max_length[100]',
        'status'           => 'permit_empty|in_list[Dijadwalkan,Selesai,Dibatalkan]',
        'is_confidential'  => 'permit_empty|in_list[0,1]',
        'duration_minutes' => 'permit_empty|integer|greater_than[0]',
    ];

    protected $validationMessages = [
        'counselor_id' => [
            'required'      => 'Konselor harus dipilih',
            'is_not_unique' => 'Konselor tidak valid',
        ],
        'session_type' => [
            'required' => 'Jenis sesi harus dipilih',
            'in_list'  => 'Jenis sesi harus Individu, Kelompok, atau Klasikal',
        ],
        'session_date' => [
            'required'   => 'Tanggal sesi harus diisi',
            'valid_date' => 'Format tanggal tidak valid',
        ],
        'topic' => [
            'required'   => 'Topik sesi harus diisi',
            'min_length' => 'Topik minimal 3 karakter',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['setDefaultStatus'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Set default status before insert
     * 
     * @param array $data
     * @return array
     */
    protected function setDefaultStatus(array $data)
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = 'Dijadwalkan';
        }

        if (!isset($data['data']['is_confidential'])) {
            $data['data']['is_confidential'] = 1;
        }

        return $data;
    }

    /**
     * Get session with full details including relations
     * 
     * @param int $sessionId
     * @return array|null
     */
    public function getSessionWithDetails($sessionId)
    {
        /** @var array $session */
        $session = $this->select('counseling_sessions.*,
                                  students.nisn, students.nis,
                                  student_users.full_name as student_name,
                                  student_users.email as student_email,
                                  counselor_users.full_name as counselor_name,
                                  counselor_users.email as counselor_email,
                                  classes.class_name')
            ->join('students', 'students.id = counseling_sessions.student_id', 'left')
            ->join('users as student_users', 'student_users.id = students.user_id', 'left')
            ->join('users as counselor_users', 'counselor_users.id = counseling_sessions.counselor_id')
            ->join('classes', 'classes.id = counseling_sessions.class_id', 'left')
            ->find($sessionId);

        if (!$session) {
            return null;
        }

        // Get session notes
        // IMPORTANT: filter soft-deleted notes (deleted_at IS NULL)
        $noteModel = new SessionNoteModel();
        $session['notes'] = $noteModel
            ->select('session_notes.*, users.full_name as author_name')
            ->join('users', 'users.id = session_notes.created_by')
            ->where('session_notes.session_id', (int) $sessionId)
            ->where('session_notes.deleted_at', null)
            ->orderBy('session_notes.created_at', 'DESC')
            ->findAll();

        // Get participants (for group/class sessions)
        if (in_array($session['session_type'], ['Kelompok', 'Klasikal'])) {
            $participantModel = new SessionParticipantModel();
            $session['participants'] = $participantModel
                ->select('session_participants.*, 
                          students.nisn, students.nis,
                          users.full_name as student_name')
                ->join('students', 'students.id = session_participants.student_id')
                ->join('users', 'users.id = students.user_id')
                ->where('session_participants.session_id', (int) $sessionId)
                ->where('session_participants.is_active', 1)
                ->orderBy('users.full_name', 'ASC')
                ->findAll();
        } else {
            $session['participants'] = [];
        }

        return $session;
    }

    /**
     * Get sessions by counselor
     * 
     * @param int $counselorId
     * @param array $filters
     * @return array
     */
    public function getSessionsByCounselor($counselorId, $filters = [])
    {
        $builder = $this->select('counseling_sessions.*,
                                  students.nisn, students.nis,
                                  users.full_name as student_name,
                                  classes.class_name')
            ->join('students', 'students.id = counseling_sessions.student_id', 'left')
            ->join('users', 'users.id = students.user_id', 'left')
            ->join('classes', 'classes.id = counseling_sessions.class_id', 'left')
            ->where('counseling_sessions.counselor_id', $counselorId);

        // Apply filters
        if (!empty($filters['session_type'])) {
            $builder->where('counseling_sessions.session_type', $filters['session_type']);
        }

        if (!empty($filters['status'])) {
            $builder->where('counseling_sessions.status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('counseling_sessions.session_date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('counseling_sessions.session_date <=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('counseling_sessions.topic', $filters['search'])
                ->orLike('users.full_name', $filters['search'])
                ->orLike('students.nisn', $filters['search'])
                ->groupEnd();
        }

        $builder->orderBy('counseling_sessions.session_date', 'DESC');

        return $builder->findAll();
    }

    /**
     * Get sessions by student
     * 
     * @param int $studentId
     * @param int|null $limit
     * @return array
     */
    public function getSessionsByStudent($studentId, $limit = null)
    {
        $builder = $this->select('counseling_sessions.*,
                                  users.full_name as counselor_name')
            ->join('users', 'users.id = counseling_sessions.counselor_id')
            ->where('counseling_sessions.student_id', $studentId)
            ->orderBy('counseling_sessions.session_date', 'DESC');

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Get upcoming sessions
     * 
     * @param int|null $counselorId
     * @param int $limit
     * @return array
     */
    public function getUpcomingSessions($counselorId = null, $limit = 10)
    {
        $builder = $this->select('counseling_sessions.*,
                                  students.nisn,
                                  users.full_name as student_name,
                                  classes.class_name')
            ->join('students', 'students.id = counseling_sessions.student_id', 'left')
            ->join('users', 'users.id = students.user_id', 'left')
            ->join('classes', 'classes.id = counseling_sessions.class_id', 'left')
            ->where('counseling_sessions.session_date >=', date('Y-m-d'))
            ->where('counseling_sessions.status', 'Dijadwalkan');

        if ($counselorId) {
            $builder->where('counseling_sessions.counselor_id', $counselorId);
        }

        $builder->orderBy('counseling_sessions.session_date', 'ASC')
            ->orderBy('counseling_sessions.session_time', 'ASC')
            ->limit($limit);

        return $builder->findAll();
    }

    /**
     * Get session statistics
     * 
     * @param int|null $counselorId
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getStatistics($counselorId = null, $dateFrom = null, $dateTo = null)
    {
        $builder = $this->builder();

        if ($counselorId) {
            $builder->where('counselor_id', $counselorId);
        }

        if ($dateFrom) {
            $builder->where('session_date >=', $dateFrom);
        }

        if ($dateTo) {
            $builder->where('session_date <=', $dateTo);
        }

        $stats = [
            'total'     => (clone $builder)->countAllResults(false),
            'by_type'   => [],
            'by_status' => [],
        ];

        // Count by type
        $typeStats = (clone $builder)
            ->select('session_type, COUNT(*) as count')
            ->groupBy('session_type')
            ->get()
            ->getResultArray();

        foreach ($typeStats as $stat) {
            $stats['by_type'][$stat['session_type']] = (int) $stat['count'];
        }

        // Count by status
        $statusStats = (clone $builder)
            ->select('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->getResultArray();

        foreach ($statusStats as $stat) {
            $stats['by_status'][$stat['status']] = (int) $stat['count'];
        }

        return $stats;
    }

    /**
     * Check if counselor has session on specific date/time
     * 
     * @param int $counselorId
     * @param string $date
     * @param string|null $time
     * @param int|null $excludeSessionId
     * @return bool
     */
    public function hasConflictingSession($counselorId, $date, $time = null, $excludeSessionId = null)
    {
        $builder = $this->where('counselor_id', $counselorId)
            ->where('session_date', $date)
            ->where('status !=', 'Dibatalkan');

        if ($time) {
            $builder->where('session_time', $time);
        }

        if ($excludeSessionId) {
            $builder->where('id !=', $excludeSessionId);
        }

        return $builder->countAllResults() > 0;
    }
}
