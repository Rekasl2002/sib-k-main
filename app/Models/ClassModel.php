<?php

/**
 * File Path: app/Models/ClassModel.php
 * 
 * Class Model
 * Mengelola data kelas dengan relasi ke tahun ajaran, wali kelas, dan guru BK
 * 
 * @package    SIB-K
 * @subpackage Models
 * @category   Academic Data
 * @author     Development Team
 * @created    2025-01-01
 */

namespace App\Models;

use CodeIgniter\Model;

class ClassModel extends Model
/** @var array $class */
{
    protected $table            = 'classes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'academic_year_id',
        'class_name',
        'grade_level',
        'major',
        'homeroom_teacher_id',
        'counselor_id',
        'max_students',
        'is_active',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'academic_year_id'     => 'required|integer|is_not_unique[academic_years.id]',
        'class_name'           => 'required|min_length[3]|max_length[50]',
        'grade_level'          => 'required|in_list[X,XI,XII]',
        'major'                => 'permit_empty|max_length[50]',
        'homeroom_teacher_id'  => 'permit_empty|integer|is_not_unique[users.id]',
        'counselor_id'         => 'permit_empty|integer|is_not_unique[users.id]',
        'max_students'         => 'permit_empty|integer|greater_than[0]',
        'is_active'            => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'academic_year_id' => [
            'required'       => 'Tahun ajaran harus dipilih',
            'is_not_unique'  => 'Tahun ajaran tidak valid',
        ],
        'class_name' => [
            'required'   => 'Nama kelas harus diisi',
            'min_length' => 'Nama kelas minimal 3 karakter',
        ],
        'grade_level' => [
            'required' => 'Tingkat kelas harus dipilih',
            'in_list'  => 'Tingkat kelas harus X, XI, atau XII',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Get class with related data (academic year, teachers, student count)
     * 
     * @param int $classId
     * @return array|null
     */
    public function getClassWithDetails($classId)
    {
        return $this->select('classes.*, 
                             academic_years.year_name, 
                             academic_years.semester,
                             homeroom.full_name as homeroom_teacher_name,
                             counselor.full_name as counselor_name,
                             COUNT(students.id) as student_count')
            ->join('academic_years', 'academic_years.id = classes.academic_year_id', 'left')
            ->join('users as homeroom', 'homeroom.id = classes.homeroom_teacher_id', 'left')
            ->join('users as counselor', 'counselor.id = classes.counselor_id', 'left')
            ->join('students', 'students.class_id = classes.id', 'left')
            ->where('classes.id', $classId)
            ->groupBy('classes.id')
            ->first();
    }

    /**
     * Get all classes with details
     * 
     * @return array
     */
    public function getAllWithDetails()
    {
        return $this->select('classes.*, 
                             academic_years.year_name, 
                             academic_years.semester,
                             homeroom.full_name as homeroom_teacher_name,
                             counselor.full_name as counselor_name,
                             COUNT(students.id) as student_count')
            ->join('academic_years', 'academic_years.id = classes.academic_year_id', 'left')
            ->join('users as homeroom', 'homeroom.id = classes.homeroom_teacher_id', 'left')
            ->join('users as counselor', 'counselor.id = classes.counselor_id', 'left')
            ->join('students', 'students.class_id = classes.id', 'left')
            ->where('classes.deleted_at', null)
            ->groupBy('classes.id')
            ->orderBy('classes.grade_level', 'ASC')
            ->orderBy('classes.class_name', 'ASC')
            ->findAll();
    }

    /**
     * Get classes by academic year
     * 
     * @param int $academicYearId
     * @return array
     */
    public function getByAcademicYear($academicYearId)
    {
        return $this->where('academic_year_id', $academicYearId)
            ->where('is_active', 1)
            ->orderBy('grade_level', 'ASC')
            ->orderBy('class_name', 'ASC')
            ->findAll();
    }

    /**
     * Get classes by grade level
     * 
     * @param string $gradeLevel
     * @return array
     */
    public function getByGradeLevel($gradeLevel)
    {
        return $this->where('grade_level', $gradeLevel)
            ->where('is_active', 1)
            ->orderBy('class_name', 'ASC')
            ->findAll();
    }

    /**
     * Get classes by homeroom teacher
     * 
     * @param int $teacherId
     * @return array
     */
    public function getByHomeroomTeacher($teacherId)
    {
        return $this->select('classes.*, 
                             academic_years.year_name,
                             COUNT(students.id) as student_count')
            ->join('academic_years', 'academic_years.id = classes.academic_year_id', 'left')
            ->join('students', 'students.class_id = classes.id', 'left')
            ->where('classes.homeroom_teacher_id', $teacherId)
            ->where('classes.is_active', 1)
            ->groupBy('classes.id')
            ->findAll();
    }

    /**
     * Get classes by counselor
     * 
     * @param int $counselorId
     * @return array
     */
    public function getByCounselor($counselorId)
    {
        return $this->select('classes.*, 
                             academic_years.year_name,
                             COUNT(students.id) as student_count')
            ->join('academic_years', 'academic_years.id = classes.academic_year_id', 'left')
            ->join('students', 'students.class_id = classes.id', 'left')
            ->where('classes.counselor_id', $counselorId)
            ->where('classes.is_active', 1)
            ->groupBy('classes.id')
            ->findAll();
    }

    /**
     * Get active classes for current academic year
     * 
     * @return array
     */
    public function getActiveClasses()
    {
        $db = \Config\Database::connect();

        $activeYear = $db->table('academic_years')
            ->where('is_active', 1)
            ->get()
            ->getRowArray();

        if (!$activeYear) {
            return [];
        }

        return $this->getByAcademicYear($activeYear['id']);
    }

    /**
     * Assign homeroom teacher to class
     * 
     * @param int $classId
     * @param int $teacherId
     * @return bool
     */
    public function assignHomeroomTeacher($classId, $teacherId)
    {
        return $this->update($classId, ['homeroom_teacher_id' => $teacherId]);
    }

    /**
     * Assign counselor to class
     * 
     * @param int $classId
     * @param int $counselorId
     * @return bool
     */
    public function assignCounselor($classId, $counselorId)
    {
        return $this->update($classId, ['counselor_id' => $counselorId]);
    }

    /**
     * Check if class is full
     * 
     * @param int $classId
     * @return bool
     */
    public function isFull($classId)
    {
        $class = $this->getClassWithDetails($classId);

        if (!$class) {
            return true;
        }

        return $class['student_count'] >= $class['max_students'];
    }

    /**
     * Get available slots in class
     * 
     * @param int $classId
     * @return int
     */
    public function getAvailableSlots($classId)
    {
        $class = $this->getClassWithDetails($classId);

        if (!$class) {
            return 0;
        }

        return max(0, $class['max_students'] - $class['student_count']);
    }

    /**
     * Get class statistics
     * 
     * @return array
     */
    public function getStatistics()
    {
        $db = \Config\Database::connect();

        $total = $this->where('deleted_at', null)->countAllResults(false);
        $active = $this->where('is_active', 1)->countAllResults(false);

        $byGrade = $db->table($this->table)
            ->select('grade_level, COUNT(*) as total')
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->groupBy('grade_level')
            ->get()
            ->getResultArray();

        return [
            'total'    => $total,
            'active'   => $active,
            'by_grade' => $byGrade,
        ];
    }

    /**
     * Check if class can be deleted
     * 
     * @param int $classId
     * @return bool
     */
    public function canDelete($classId)
    {
        $db = \Config\Database::connect();

        // Check if class has students
        $studentCount = $db->table('students')
            ->where('class_id', $classId)
            ->countAllResults();

        return $studentCount === 0;
    }
}
