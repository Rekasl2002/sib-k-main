<?php

/**
 * File Path: app/Validation/ClassValidation.php
 * 
 * Class Validation
 * Validation rules dan helper methods untuk Class management
 * 
 * @package    SIB-K
 * @subpackage Validation
 * @category   Class Management
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Validation;

class ClassValidation
{
    /**
     * Validation rules untuk create class
     * 
     * @return array
     */
    public static function createRules()
    {
        return [
            'academic_year_id' => [
                'label' => 'Tahun Ajaran',
                'rules' => 'required|integer|is_not_unique[academic_years.id]',
                'errors' => [
                    'required' => 'Tahun ajaran harus dipilih',
                    'integer' => 'Tahun ajaran tidak valid',
                    'is_not_unique' => 'Tahun ajaran tidak ditemukan',
                ]
            ],
            'class_name' => [
                'label' => 'Nama Kelas',
                'rules' => 'required|min_length[3]|max_length[50]|is_unique[classes.class_name]',
                'errors' => [
                    'required' => 'Nama kelas harus diisi',
                    'min_length' => 'Nama kelas minimal 3 karakter',
                    'max_length' => 'Nama kelas maksimal 50 karakter',
                    'is_unique' => 'Nama kelas sudah digunakan',
                ]
            ],
            'grade_level' => [
                'label' => 'Tingkat Kelas',
                'rules' => 'required|in_list[X,XI,XII]',
                'errors' => [
                    'required' => 'Tingkat kelas harus dipilih',
                    'in_list' => 'Tingkat kelas harus X, XI, atau XII',
                ]
            ],
            'major' => [
                'label' => 'Jurusan',
                'rules' => 'permit_empty|max_length[50]',
                'errors' => [
                    'max_length' => 'Jurusan maksimal 50 karakter',
                ]
            ],
            'homeroom_teacher_id' => [
                'label' => 'Wali Kelas',
                'rules' => 'permit_empty|integer|is_not_unique[users.id]',
                'errors' => [
                    'integer' => 'Wali kelas tidak valid',
                    'is_not_unique' => 'Wali kelas tidak ditemukan',
                ]
            ],
            'counselor_id' => [
                'label' => 'Guru BK',
                'rules' => 'permit_empty|integer|is_not_unique[users.id]',
                'errors' => [
                    'integer' => 'Guru BK tidak valid',
                    'is_not_unique' => 'Guru BK tidak ditemukan',
                ]
            ],
            'max_students' => [
                'label' => 'Kapasitas Maksimal',
                'rules' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[50]',
                'errors' => [
                    'integer' => 'Kapasitas harus berupa angka',
                    'greater_than' => 'Kapasitas minimal 1 siswa',
                    'less_than_equal_to' => 'Kapasitas maksimal 50 siswa',
                ]
            ],
            'is_active' => [
                'label' => 'Status',
                'rules' => 'permit_empty|in_list[0,1]',
                'errors' => [
                    'in_list' => 'Status tidak valid',
                ]
            ],
        ];
    }

    /**
     * Validation rules untuk update class
     * 
     * @param int $id Class ID yang sedang diedit
     * @return array
     */
    public static function updateRules($id)
    {
        return [
            'academic_year_id' => [
                'label' => 'Tahun Ajaran',
                'rules' => 'required|integer|is_not_unique[academic_years.id]',
                'errors' => [
                    'required' => 'Tahun ajaran harus dipilih',
                    'integer' => 'Tahun ajaran tidak valid',
                    'is_not_unique' => 'Tahun ajaran tidak ditemukan',
                ]
            ],
            'class_name' => [
                'label' => 'Nama Kelas',
                'rules' => "required|min_length[3]|max_length[50]|is_unique[classes.class_name,id,{$id}]",
                'errors' => [
                    'required' => 'Nama kelas harus diisi',
                    'min_length' => 'Nama kelas minimal 3 karakter',
                    'max_length' => 'Nama kelas maksimal 50 karakter',
                    'is_unique' => 'Nama kelas sudah digunakan',
                ]
            ],
            'grade_level' => [
                'label' => 'Tingkat Kelas',
                'rules' => 'required|in_list[X,XI,XII]',
                'errors' => [
                    'required' => 'Tingkat kelas harus dipilih',
                    'in_list' => 'Tingkat kelas harus X, XI, atau XII',
                ]
            ],
            'major' => [
                'label' => 'Jurusan',
                'rules' => 'permit_empty|max_length[50]',
                'errors' => [
                    'max_length' => 'Jurusan maksimal 50 karakter',
                ]
            ],
            'homeroom_teacher_id' => [
                'label' => 'Wali Kelas',
                'rules' => 'permit_empty|integer|is_not_unique[users.id]',
                'errors' => [
                    'integer' => 'Wali kelas tidak valid',
                    'is_not_unique' => 'Wali kelas tidak ditemukan',
                ]
            ],
            'counselor_id' => [
                'label' => 'Guru BK',
                'rules' => 'permit_empty|integer|is_not_unique[users.id]',
                'errors' => [
                    'integer' => 'Guru BK tidak valid',
                    'is_not_unique' => 'Guru BK tidak ditemukan',
                ]
            ],
            'max_students' => [
                'label' => 'Kapasitas Maksimal',
                'rules' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[50]',
                'errors' => [
                    'integer' => 'Kapasitas harus berupa angka',
                    'greater_than' => 'Kapasitas minimal 1 siswa',
                    'less_than_equal_to' => 'Kapasitas maksimal 50 siswa',
                ]
            ],
            'is_active' => [
                'label' => 'Status',
                'rules' => 'permit_empty|in_list[0,1]',
                'errors' => [
                    'in_list' => 'Status tidak valid',
                ]
            ],
        ];
    }

    /**
     * Get grade level options for dropdown
     * 
     * @return array
     */
    public static function getGradeLevelOptions()
    {
        return [
            'X' => 'Kelas X',
            'XI' => 'Kelas XI',
            'XII' => 'Kelas XII',
        ];
    }

    /**
     * Get major options for dropdown
     * 
     * @return array
     */
    public static function getMajorOptions()
    {
        return [
            'IPA' => 'IPA (Ilmu Pengetahuan Alam)',
            'IPS' => 'IPS (Ilmu Pengetahuan Sosial)',
            'Bahasa' => 'Bahasa',
            'Agama' => 'Agama',
        ];
    }

    /**
     * Get status options for dropdown
     * 
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            1 => 'Aktif',
            0 => 'Tidak Aktif',
        ];
    }

    /**
     * Generate class name automatically
     * Format: {GradeLevel}-{Major}-{Number}
     * Example: X-IPA-1, XI-IPS-2, XII-Bahasa-1
     * 
     * @param string $gradeLevel (X, XI, XII)
     * @param string $major (IPA, IPS, Bahasa, Agama)
     * @param int $number (1, 2, 3, ...)
     * @return string
     */
    public static function generateClassName($gradeLevel, $major, $number = 1)
    {
        if (empty($major)) {
            return "{$gradeLevel}-{$number}";
        }

        return "{$gradeLevel}-{$major}-{$number}";
    }

    /**
     * Parse class name to get components
     * 
     * @param string $className (e.g., "X-IPA-1")
     * @return array ['grade_level' => 'X', 'major' => 'IPA', 'number' => '1']
     */
    public static function parseClassName($className)
    {
        $parts = explode('-', $className);

        return [
            'grade_level' => $parts[0] ?? '',
            'major' => isset($parts[2]) ? $parts[1] : '',
            'number' => isset($parts[2]) ? $parts[2] : ($parts[1] ?? '1'),
        ];
    }

    /**
     * Sanitize class input data
     * 
     * @param array $data
     * @return array
     */
    public static function sanitizeInput($data)
    {
        $sanitized = [];

        // Trim all string values
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        // Set default values
        if (!isset($sanitized['is_active'])) {
            $sanitized['is_active'] = 1;
        }

        if (!isset($sanitized['max_students']) || empty($sanitized['max_students'])) {
            $sanitized['max_students'] = 36; // Default capacity
        }

        // Convert empty strings to null
        $nullableFields = ['homeroom_teacher_id', 'counselor_id', 'major'];
        foreach ($nullableFields as $field) {
            if (isset($sanitized[$field]) && $sanitized[$field] === '') {
                $sanitized[$field] = null;
            }
        }

        return $sanitized;
    }

    /**
     * Validate capacity constraint
     * Check if new max_students is not less than current student count
     * 
     * @param int $classId
     * @param int $newMaxStudents
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateCapacity($classId, $newMaxStudents)
    {
        $classModel = new \App\Models\ClassModel();
        $studentModel = new \App\Models\StudentModel();

        // Get current student count
        $currentCount = $studentModel->where('class_id', $classId)
            ->where('status', 'Aktif')
            ->countAllResults();

        if ($newMaxStudents < $currentCount) {
            return [
                'valid' => false,
                'message' => "Kapasitas tidak boleh kurang dari jumlah siswa aktif saat ini ({$currentCount} siswa)",
            ];
        }

        return [
            'valid' => true,
            'message' => 'Kapasitas valid',
        ];
    }

    /**
     * Check if teacher is already assigned as homeroom teacher
     * 
     * @param int $teacherId
     * @param int|null $excludeClassId (untuk saat update, exclude class yang sedang diedit)
     * @return bool
     */
    public static function isTeacherAlreadyHomeroom($teacherId, $excludeClassId = null)
    {
        $classModel = new \App\Models\ClassModel();

        $query = $classModel->where('homeroom_teacher_id', $teacherId)
            ->where('is_active', 1);

        if ($excludeClassId) {
            $query->where('id !=', $excludeClassId);
        }

        return $query->countAllResults() > 0;
    }

    /**
     * Get suggested class name based on existing classes
     * 
     * @param string $gradeLevel
     * @param string $major
     * @param int $academicYearId
     * @return string
     */
    public static function getSuggestedClassName($gradeLevel, $major, $academicYearId)
    {
        $classModel = new \App\Models\ClassModel();

        // Get highest number for this grade and major
        $builder = $classModel->where('grade_level', $gradeLevel)
            ->where('academic_year_id', $academicYearId);

        if (!empty($major)) {
            $builder->where('major', $major);
        }

        $existingClasses = $builder->findAll();

        // Extract numbers from existing class names
        $numbers = [];
        foreach ($existingClasses as $class) {
            $parsed = self::parseClassName($class['class_name']);
            if (is_numeric($parsed['number'])) {
                $numbers[] = (int)$parsed['number'];
            }
        }

        // Get next number
        $nextNumber = empty($numbers) ? 1 : (max($numbers) + 1);

        return self::generateClassName($gradeLevel, $major, $nextNumber);
    }
}
