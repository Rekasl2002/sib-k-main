<?php

namespace App\Models;

use CodeIgniter\Model;

class CareerOptionModel extends Model
{
    protected $table            = 'career_options';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $allowedFields    = [
        'title','sector','min_education','description',
        'required_skills','pathways','avg_salary_idr','demand_level',
        'external_links','is_public','is_active','created_by'
    ];

    protected $useTimestamps = true;
    protected $useSoftDeletes = true;

    // Tambahkan ini persis dengan type agar aman di CI4 baru
    protected array $casts = [
        'is_public'      => 'integer',
        'is_active'      => 'integer',
        'demand_level'   => 'integer',
        'avg_salary_idr' => 'integer',
    ];


    /**
     * PUBLIC listing (Student/Parent portal).
     * Hanya menampilkan konten aktif & published.
     */
    public function searchPaginated(array $filters = [], int $perPage = 12)
    {
        $builder = $this->where('is_active', 1)->where('is_public', 1);

        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $builder = $builder->groupStart()
                ->like('title', $q)
                ->orLike('sector', $q)
                ->orLike('description', $q)
            ->groupEnd();
        }

        if (!empty($filters['sector'])) {
            $builder->where('sector', $filters['sector']);
        }

        if (!empty($filters['edu'])) {
            $builder->where('min_education', $filters['edu']);
        }

        if (!empty($filters['sort']) && $filters['sort'] === 'demand') {
            $builder->orderBy('demand_level', 'DESC');
        } else {
            $builder->orderBy('title', 'ASC');
        }

        return $builder->paginate($perPage);
    }

    /**
     * Scope publik untuk portal siswa/orang tua.
     */
    public function publicOnly(): self
    {
        return $this->where('is_active', 1)->where('is_public', 1);
    }

    /**
     * MANAGEMENT listing (Counselor/Koordinator).
     * Mendukung filter: q, sector, edu, status (is_active), pub (is_public), sort.
     * Tidak memaksa is_active/is_public jika filter tidak diisi.
     */
    public function managementPaginated(array $filters = [], int $perPage = 12)
    {
        $builder = $this;

        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $builder = $builder->groupStart()
                ->like('title', $q)
                ->orLike('sector', $q)
                ->orLike('description', $q)
            ->groupEnd();
        }

        if (!empty($filters['sector'])) {
            $builder->where('sector', $filters['sector']);
        }

        if (!empty($filters['edu'])) {
            $builder->where('min_education', $filters['edu']);
        }

        // Tambahan: filter status (0|1)
        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
            $builder->where('is_active', (int) $filters['status']);
        }

        // Tambahan: filter publikasi (0|1)
        if (isset($filters['pub']) && $filters['pub'] !== '' && $filters['pub'] !== null) {
            $builder->where('is_public', (int) $filters['pub']);
        }

        if (!empty($filters['sort']) && $filters['sort'] === 'demand') {
            $builder->orderBy('demand_level', 'DESC')->orderBy('title', 'ASC');
        } else {
            $builder->orderBy('title', 'ASC');
        }

        return $builder->paginate($perPage);
    }

    /**
     * Quick list karier untuk rekomendasi ringan di UI.
     * Cari di required_skills (JSON string) jika disediakan.
     */
    public function quickList(int $limit = 6, ?string $containsSkill = null)
    {
        $builder = $this->where('is_active', 1)
            ->where('is_public', 1)
            ->orderBy('title', 'ASC');

        if ($containsSkill) {
            $builder->like('required_skills', $containsSkill);
        }

        return $builder->findAll($limit);
    }
}
