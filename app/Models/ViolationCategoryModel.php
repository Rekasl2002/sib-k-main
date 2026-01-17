<?php

/**
 * File Path: app/Models/ViolationCategoryModel.php
 * 
 * Violation Category Model
 * Model untuk mengelola data kategori pelanggaran siswa
 * 
 * @package    SIB-K
 * @subpackage Models
 * @category   Model
 * @author     Development Team
 * @created    2025-01-06
 */

namespace App\Models;

use CodeIgniter\Model;

class ViolationCategoryModel extends Model
{
    protected $table            = 'violation_categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'category_name',
        'severity_level',
        'point_deduction',
        'description',
        'examples',
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
        'category_name' => 'required|max_length[100]',
        'severity_level' => 'required|in_list[Ringan,Sedang,Berat]',
        'point_deduction' => 'required|integer|greater_than_equal_to[0]',
        'description' => 'permit_empty|max_length[1000]',
        'examples' => 'permit_empty|max_length[1000]',
        'is_active' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'category_name' => [
            'required' => 'Nama kategori harus diisi',
            'max_length' => 'Nama kategori maksimal 100 karakter',
        ],
        'severity_level' => [
            'required' => 'Tingkat keparahan harus dipilih',
            'in_list' => 'Tingkat keparahan tidak valid',
        ],
        'point_deduction' => [
            'required' => 'Poin pengurangan harus diisi',
            'integer' => 'Poin pengurangan harus berupa angka',
            'greater_than_equal_to' => 'Poin pengurangan tidak boleh negatif',
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
     * Get all active categories
     * 
     * @return array
     */
    public function getActiveCategories()
    {
        return $this->where('is_active', 1)
            ->orderBy('severity_level', 'ASC')
            ->orderBy('point_deduction', 'ASC')
            ->findAll();
    }

    /**
     * Get categories by severity level
     * 
     * @param string $severityLevel (Ringan|Sedang|Berat)
     * @return array
     */
    public function getBySeverityLevel($severityLevel)
    {
        return $this->where('severity_level', $severityLevel)
            ->where('is_active', 1)
            ->orderBy('point_deduction', 'ASC')
            ->findAll();
    }

    /**
     * Get category with usage statistics
     * 
     * @param int $id
     * @return array|null
     */
    public function getCategoryWithStats(int $id): ?array
    {
        /** @var array|null $category */
        $category = $this->asArray()->find($id);
        if (!$category) {
            return null;
        }

        // Count violations using this category
        $db = \Config\Database::connect();
        $violationCount = $db->table('violations')
            ->where('category_id', $id)
            ->where('deleted_at', null)
            ->countAllResults();

        $category['violation_count'] = (int) $violationCount;

        return $category;
    }

    /**
     * Get all categories with violation counts
     * 
     * @return array
     */
    public function getCategoriesWithStats()
    {
        /** @var array $categories */
        $categories = $this->asArray()->findAll();

        $db = \Config\Database::connect();

        foreach ($categories as &$category) {
            // Count violations for this category
            $violationCount = $db->table('violations')
                ->where('category_id', $category['id'])
                ->where('deleted_at', null)
                ->countAllResults();

            $category['violation_count'] = (int) $violationCount;
        }

        return $categories;
    }

    /**
     * Get statistics grouped by severity level
     * 
     * @return array
     */
    public function getStatsBySeverity()
    {
        $db = \Config\Database::connect();

        $stats = $db->table('violation_categories')
            ->select('severity_level, 
                      COUNT(id) as category_count,
                      SUM(is_active) as active_count,
                      AVG(point_deduction) as avg_points,
                      MIN(point_deduction) as min_points,
                      MAX(point_deduction) as max_points')
            ->where('deleted_at', null)
            ->groupBy('severity_level')
            ->get()
            ->getResultArray();

        return $stats;
    }

    /**
     * Search categories by name or description
     * 
     * @param string $keyword
     * @return array
     */
    public function searchCategories($keyword)
    {
        return $this->groupStart()
            ->like('category_name', $keyword)
            ->orLike('description', $keyword)
            ->orLike('examples', $keyword)
            ->groupEnd()
            ->where('is_active', 1)
            ->orderBy('severity_level', 'ASC')
            ->orderBy('point_deduction', 'ASC')
            ->findAll();
    }

    /**
     * Toggle category active status
     * 
     * @param int $id
     * @return bool
     */
    public function toggleActiveStatus($id)
    {
        /** @var array|null $category */
        $category = $this->asArray()->find($id);

        if (!$category) {
            return false;
        }

        $current = isset($category['is_active']) ? (int) $category['is_active'] : 0;
        $newStatus = $current ? 0 : 1;

        return $this->update($id, ['is_active' => $newStatus]);
    }

    /**
     * Get most used categories
     * 
     * @param int $limit
     * @return array
     */
    public function getMostUsedCategories($limit = 10)
    {
        $db = \Config\Database::connect();

        return $db->table('violation_categories')
            ->select('violation_categories.*,
                      COUNT(violations.id) as usage_count')
            ->join('violations', 'violations.category_id = violation_categories.id', 'left')
            ->where('violation_categories.deleted_at', null)
            ->where('violation_categories.is_active', 1)
            ->groupBy('violation_categories.id')
            ->orderBy('usage_count', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Get categories for dropdown/select
     * Format: [id => category_name (severity_level - points)]
     * 
     * @return array
     */
    public function getCategoriesForSelect()
    {
        $categories = $this->getActiveCategories();
        $result = [];

        foreach ($categories as $category) {
            $result[$category['id']] = sprintf(
                '%s (%s - %d poin)',
                $category['category_name'],
                $category['severity_level'],
                $category['point_deduction']
            );
        }

        return $result;
    }

    /**
     * Validate if category can be deleted
     * (Check if it has any violations)
     * 
     * @param int $id
     * @return array [can_delete => bool, message => string]
     */
    public function canDelete($id)
    {
        $db = \Config\Database::connect();

        $violationCount = $db->table('violations')
            ->where('category_id', $id)
            ->where('deleted_at', null)
            ->countAllResults();

        if ($violationCount > 0) {
            return [
                'can_delete' => false,
                'message' => "Kategori ini tidak dapat dihapus karena masih digunakan oleh {$violationCount} pelanggaran.",
            ];
        }

        return [
            'can_delete' => true,
            'message' => 'Kategori dapat dihapus.',
        ];
    }

    /**
     * Get severity level options for forms
     * 
     * @return array
     */
    public function getSeverityLevelOptions()
    {
        return [
            'Ringan' => 'Ringan',
            'Sedang' => 'Sedang',
            'Berat' => 'Berat',
        ];
    }
}
