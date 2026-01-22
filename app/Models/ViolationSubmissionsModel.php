<?php

namespace App\Models;

use CodeIgniter\Model;

class ViolationSubmissionsModel extends Model
{
    protected $table            = 'violation_submissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';

    protected $useSoftDeletes   = true;

    /**
     * Penting: tetap protectFields ON (default CI4) agar hanya allowedFields yang bisa diisi.
     */
    protected $protectFields    = true;

    /**
     * Catatan keamanan:
     * - Jangan masukkan created_at/updated_at/deleted_at ke allowedFields (hindari override dari input).
     * - Field seperti handled_by/handled_at/converted_violation_id tetap ada karena dibutuhkan saat diproses BK,
     *   tapi controller harus memastikan siswa tidak bisa mengirim nilai field-field ini.
     */
    protected $allowedFields    = [
        'reporter_type',
        'reporter_user_id',
        'subject_student_id',
        'subject_other_name',
        'category_id',
        'occurred_date',
        'occurred_time',
        'location',
        'description',
        'witness',
        'evidence_json',
        'status',
        'handled_by',
        'handled_at',
        'review_notes',
        'converted_violation_id',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Validasi ringan (opsional tapi membantu menjaga data)
     * Jika kamu sudah validasi penuh di controller/service, ini tetap aman.
     */
    protected $validationRules = [
        'reporter_type'      => 'required|in_list[student,parent]',
        'reporter_user_id'   => 'required|is_natural_no_zero',

        // Minimal wajib supaya laporan tidak kosong
        'description'        => 'required|min_length[5]',

        // Target terlapor boleh salah satu
        'subject_student_id' => 'permit_empty|is_natural_no_zero',
        'subject_other_name' => 'permit_empty|max_length[190]',

        'category_id'        => 'permit_empty|is_natural_no_zero',
        'occurred_date'      => 'permit_empty|valid_date[Y-m-d]',
        'occurred_time'      => 'permit_empty|regex_match[/^\d{2}:\d{2}(:\d{2})?$/]',
        'location'           => 'permit_empty|max_length[190]',
        'witness'            => 'permit_empty|max_length[190]',

        // Status yang dipakai di view kamu
        'status'             => 'permit_empty|in_list[Diajukan,Ditinjau,Ditolak,Diterima,Dikonversi]',

        // Diproses BK (umumnya diisi staff, bukan siswa)
        'handled_by'             => 'permit_empty|is_natural_no_zero',
        'handled_at'             => 'permit_empty|valid_date',
        'review_notes'           => 'permit_empty',
        'converted_violation_id' => 'permit_empty|is_natural_no_zero',
    ];

    protected $validationMessages = [
        'reporter_type' => [
            'in_list' => 'Nilai reporter_type tidak valid.',
        ],
        'status' => [
            'in_list' => 'Nilai status tidak valid.',
        ],
    ];

    protected $skipValidation = false;

    /**
     * Pastikan evidence_json selalu konsisten:
     * - sebelum insert/update: simpan sebagai JSON string
     * - setelah find: kembalikan sebagai array (biar view aman foreach)
     */
    protected $beforeInsert = ['normalizeEvidenceJson'];
    protected $beforeUpdate = ['normalizeEvidenceJson'];
    protected $afterFind    = ['decodeEvidenceJson'];

    protected function normalizeEvidenceJson(array $data): array
    {
        if (!isset($data['data']) || !is_array($data['data'])) {
            return $data;
        }

        if (!array_key_exists('evidence_json', $data['data'])) {
            return $data;
        }

        $ev = $data['data']['evidence_json'];

        // Null/kosong -> []
        if ($ev === null || $ev === '') {
            $data['data']['evidence_json'] = json_encode([], JSON_UNESCAPED_SLASHES);
            return $data;
        }

        // Jika array -> encode
        if (is_array($ev)) {
            $data['data']['evidence_json'] = json_encode(array_values($ev), JSON_UNESCAPED_SLASHES);
            return $data;
        }

        // Jika object -> cast ke array lalu encode
        if (is_object($ev)) {
            $data['data']['evidence_json'] = json_encode(array_values((array) $ev), JSON_UNESCAPED_SLASHES);
            return $data;
        }

        // Jika string: coba decode sebagai JSON; kalau gagal, anggap single path
        if (is_string($ev)) {
            $trim = trim($ev);
            if ($trim === '') {
                $data['data']['evidence_json'] = json_encode([], JSON_UNESCAPED_SLASHES);
                return $data;
            }

            $decoded = json_decode($trim, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Pastikan bentuknya array
                $data['data']['evidence_json'] = json_encode(is_array($decoded) ? $decoded : [$decoded], JSON_UNESCAPED_SLASHES);
                return $data;
            }

            // Bukan JSON valid -> treat as single file path
            $data['data']['evidence_json'] = json_encode([$trim], JSON_UNESCAPED_SLASHES);
            return $data;
        }

        // Tipe lain -> amankan
        $data['data']['evidence_json'] = json_encode([], JSON_UNESCAPED_SLASHES);
        return $data;
    }

    protected function decodeEvidenceJson(array $data): array
    {
        if (!isset($data['data'])) {
            return $data;
        }

        $decodeRow = static function (&$row): void {
            if (!is_array($row)) {
                return;
            }

            if (!array_key_exists('evidence_json', $row)) {
                return;
            }

            $ev = $row['evidence_json'];

            // Jika sudah array, biarkan
            if (is_array($ev)) {
                return;
            }

            if ($ev === null || $ev === '') {
                $row['evidence_json'] = [];
                return;
            }

            if (is_string($ev)) {
                $decoded = json_decode($ev, true);
                $row['evidence_json'] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
                return;
            }

            // Tipe tak dikenal -> fallback aman
            $row['evidence_json'] = [];
        };

        // afterFind bisa mengembalikan 1 row atau banyak row
        if (is_array($data['data']) && isset($data['data'][0]) && is_array($data['data'][0])) {
            foreach ($data['data'] as &$row) {
                $decodeRow($row);
            }
            unset($row);
        } else {
            $decodeRow($data['data']);
        }

        return $data;
    }
}
