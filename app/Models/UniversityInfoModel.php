<?php

namespace App\Models;

use CodeIgniter\Model;

class UniversityInfoModel extends Model
{
    protected $table            = 'university_info';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    // Kembalikan array agar mudah dipakai di view (opsional, default CI juga 'array')
    protected $returnType       = 'array';

    protected $useTimestamps    = true;
    protected $useSoftDeletes   = true;

    protected $allowedFields    = [
        'university_name', 'alias', 'accreditation', 'location', 'website', 'logo', 'description',
        'faculties', 'programs', 'admission_info', 'tuition_range', 'scholarships', 'contacts',
        'is_public', 'is_active', 'created_by', // created_by ditambahkan
    ];

    protected array $casts = [
        'is_public'  => 'integer',
        'is_active'  => 'integer',
        'created_by' => 'integer',
    ];

    // ---------------------------------------------------------------------
    // Scope publik (portal siswa/orang tua): Hanya aktif + published
    // ---------------------------------------------------------------------
    public function publicOnly(): self
    {
        return $this->where('is_active', 1)
                    ->where('is_public', 1);
    }

    // ---------------------------------------------------------------------
    // Listing publik dengan filter (q, acc, loc, sort)
    // q: nama/alias/deskripsi/lokasi/akreditasi
    // sort: ''|name_desc|acc|loc
    // ---------------------------------------------------------------------
    public function searchPaginatedPublic(array $filters = [], int $perPage = 12, ?string $group = null)
    {
        $b = $this->publicOnly();

        if (! empty($filters['q'])) {
            $q = trim($filters['q']);
            $b = $b->groupStart()
                ->like('university_name', $q)
                ->orLike('alias', $q)
                ->orLike('description', $q)
                ->orLike('location', $q)
                ->orLike('accreditation', $q)
            ->groupEnd();
        }

        if (! empty($filters['acc'])) {
            $b->where('accreditation', $filters['acc']);
        }

        if (! empty($filters['loc'])) {
            $b->where('location', $filters['loc']);
        }

        $sort = $filters['sort'] ?? '';
        if ($sort === 'name_desc') {
            $b->orderBy('university_name', 'DESC');
        } elseif ($sort === 'acc') {
            $b->orderBy('accreditation', 'ASC')
              ->orderBy('university_name', 'ASC');
        } elseif ($sort === 'loc') {
            $b->orderBy('location', 'ASC')
              ->orderBy('university_name', 'ASC');
        } else {
            $b->orderBy('university_name', 'ASC');
        }

        return $b->paginate($perPage, $group);
    }

    // ---------------------------------------------------------------------
    // Listing manajemen (Guru BK/Koordinator)
    // Mendukung filter: q, acc, loc, status (is_active), pub (is_public), sort
    // Tidak memaksa is_active/is_public (kecuali jika filter diisi)
    // ---------------------------------------------------------------------
    public function managementPaginated(array $filters = [], int $perPage = 10, ?string $group = 'universities')
    {
        $b = $this; // gunakan state model langsung

        if (! empty($filters['q'])) {
            $q = trim($filters['q']);
            $b = $b->groupStart()
                ->like('university_name', $q)
                ->orLike('alias', $q)
                ->orLike('description', $q)
            ->groupEnd();
        }

        if (! empty($filters['acc'])) {
            $b->where('accreditation', $filters['acc']);
        }

        if (! empty($filters['loc'])) {
            $b->where('location', $filters['loc']);
        }

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
            $b->where('is_active', (int) $filters['status']);
        }

        if (isset($filters['pub']) && $filters['pub'] !== '' && $filters['pub'] !== null) {
            $b->where('is_public', (int) $filters['pub']);
        }

        $sort = $filters['sort'] ?? '';
        if ($sort === 'name_desc') {
            $b->orderBy('university_name', 'DESC');
        } elseif ($sort === 'acc') {
            $b->orderBy('accreditation', 'ASC')
              ->orderBy('university_name', 'ASC');
        } elseif ($sort === 'loc') {
            $b->orderBy('location', 'ASC')
              ->orderBy('university_name', 'ASC');
        } else {
            $b->orderBy('university_name', 'ASC');
        }

        return $b->paginate($perPage, $group);
    }

    // ---------------------------------------------------------------------
    // Quick list untuk rekomendasi di UI portal
    // Hanya ambil yang aktif & published
    // containsProgram: LIKE ke kolom JSON "programs" (fallback sederhana)
    // ---------------------------------------------------------------------
    public function quickList(int $limit = 6, ?string $containsProgram = null)
    {
        $b = $this->where('is_active', 1)
            ->where('is_public', 1)
            ->orderBy('university_name', 'ASC');

        if ($containsProgram) {
            $b->like('programs', $containsProgram);
        }

        return $b->findAll($limit);
    }
}