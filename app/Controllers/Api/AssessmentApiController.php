<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Database\BaseConnection;

// Catatan: service opsional; controller akan fallback ke DB bila service tidak tersedia.
use App\Services\AssessmentService;

class AssessmentApiController extends BaseController
{
    /** Service opsional (nullable agar aman bila class tidak ada) */
    protected ?AssessmentService $svc = null;

    /** @var BaseConnection */
    protected $db;

    public function __construct()
    {
        // Inisialisasi service bila tersedia
        if (class_exists(AssessmentService::class)) {
            $this->svc = new AssessmentService();
        }
        // Selalu siapkan koneksi DB untuk fallback
        $this->db = db_connect();
    }

    /**
     * GET /api/assessments/list
     * Query params:
     * - q           : string pencarian judul (contains)
     * - active      : 0|1 (dipakai bila service mendukung)
     * - published   : 0|1 (dipakai bila service mendukung)
     * - limit       : default 50, max 200
     *
     * Response: [{"id":1,"title":"..."}, ...]
     */
    public function list(): ResponseInterface
    {
        $q         = trim((string) $this->request->getGet('q'));
        $active    = $this->request->getGet('active');
        $published = $this->request->getGet('published');
        $limit     = (int) ($this->request->getGet('limit') ?? 50);
        $limit     = max(1, min($limit, 200));

        $rows = [];

        // 1) Coba gunakan service jika ada
        if ($this->svc && method_exists($this->svc, 'list')) {
            $filters = [];
            if ($active !== null && $active !== '') {
                $filters['active'] = (int) $active;
            }
            if ($published !== null && $published !== '') {
                $filters['published'] = (int) $published;
            }

            $rows = $this->svc->list($filters); // diasumsikan sudah soft-delete aware & aman
            // Pencarian ringan di PHP jika service belum sediakan 'q'
            if ($q !== '') {
                $rows = array_values(array_filter($rows, static function ($r) use ($q) {
                    return stripos((string) ($r['title'] ?? ''), $q) !== false;
                }));
            }
            // Batasi jumlah baris
            if (count($rows) > $limit) {
                $rows = array_slice($rows, 0, $limit);
            }
        } else {
            // 2) Fallback langsung ke DB (minimal fields, soft-delete aware)
            $b = $this->db->table('assessments')
                ->select('id, title')
                ->where('deleted_at', null);

            if ($q !== '') {
                $b->like('title', $q);
            }

            // Catatan: filter 'active'/'published' sengaja tidak dipakai di fallback
            // untuk menghindari error jika kolom tidak ada pada skema.

            $rows = $b->orderBy('title', 'ASC')
                      ->limit($limit)
                      ->get()
                      ->getResultArray();
        }

        // Bentuk payload minimal untuk dropdown
        $payload = array_map(static function ($r) {
            return [
                'id'    => (int) ($r['id'] ?? 0),
                'title' => (string) ($r['title'] ?? ''),
            ];
        }, $rows);

        return $this->response->setJSON($payload);
    }
}
