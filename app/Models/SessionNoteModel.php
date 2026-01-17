<?php

/**
 * File Path  : app/Models/SessionNoteModel.php
 * Deskripsi  : Model untuk catatan sesi konseling (notes) sesuai skema DB.
 * Framework  : CodeIgniter 4
 */

namespace App\Models;

use CodeIgniter\Model;

class SessionNoteModel extends Model
{
    protected $table            = 'session_notes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    // Kolom sesuai tabel `session_notes`
    protected $allowedFields = [
        'session_id',        // FK -> counseling_sessions.id
        'created_by',        // FK -> users.id (pembuat catatan)
        'note_type',         // ENUM: Observasi/Diagnosis/Intervensi/Follow-up/Lainnya
        'note_content',      // isi catatan
        'is_confidential',   // 0/1
        'is_important',      // 0/1
        'attachments',       // JSON text (opsional)
        // boleh diisi manual bila perlu (mis. migrasi/seed):
        'created_at', 'updated_at',
    ];

    // Timestamps & soft delete
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validasi
    protected $validationRules = [
        'session_id'      => 'required|integer|is_not_unique[counseling_sessions.id]',
        'created_by'      => 'required|integer|is_not_unique[users.id]',
        'note_type'       => 'permit_empty|in_list[Observasi,Diagnosis,Intervensi,Follow-up,Lainnya]',
        'note_content'    => 'required|string|min_length[5]',
        'is_confidential' => 'permit_empty|in_list[0,1]',
        'is_important'    => 'permit_empty|in_list[0,1]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['_normalizeBeforeSave'];
    protected $beforeUpdate   = ['_normalizeBeforeSave'];

    /**
     * Normalisasi nilai default sebelum insert/update.
     */
    protected function _normalizeBeforeSave(array $data): array
    {
        if (empty($data['data'])) {
            return $data;
        }

        // Default jenis catatan
        if (empty($data['data']['note_type'])) {
            $data['data']['note_type'] = 'Observasi';
        }

        /**
         * Default flags:
         * - is_confidential: default 1 (lebih aman, selaras default sesi)
         * - is_important   : default 0
         */
        if (!array_key_exists('is_confidential', $data['data'])) {
            $data['data']['is_confidential'] = 1;
        }
        if (!array_key_exists('is_important', $data['data'])) {
            $data['data']['is_important'] = 0;
        }

        // Flags -> pastikan 0/1
        foreach (['is_confidential', 'is_important'] as $flag) {
            $data['data'][$flag] = (int) (bool) $data['data'][$flag];
        }

        // attachments: terima array → simpan sebagai JSON
        if (isset($data['data']['attachments']) && is_array($data['data']['attachments'])) {
            $data['data']['attachments'] = json_encode(array_values($data['data']['attachments']));
        }

        return $data;
    }

    // ---------------------------------------------------------------------
    // Getter
    // ---------------------------------------------------------------------

    /** Ambil 1 note by id. */
    public function getById(int $id): ?array
    {
        // find() sudah menghormati soft delete (useSoftDeletes = true)
        $row = $this->asArray()->find($id);
        return $row ?: null;
    }

    /**
     * Ambil daftar note untuk satu sesi (terurut terbaru) + nama/email pembuat.
     *
     * @param int  $sessionId
     * @param bool $includeConfidential  true: ambil semua; false: hanya is_confidential=0
     * @return array<int, array<string,mixed>>
     */
    public function getBySession(int $sessionId, bool $includeConfidential = true): array
    {
        $q = $this->asArray()
            ->select('session_notes.*,
                      u.full_name AS counselor_name,
                      u.email AS counselor_email,
                      u.full_name AS author_name')
            ->join('users u', 'u.id = session_notes.created_by', 'left')
            ->where('session_notes.session_id', (int) $sessionId)
            ->where('session_notes.deleted_at', null)
            ->orderBy('session_notes.created_at', 'DESC');

        if (!$includeConfidential) {
            $q->where('session_notes.is_confidential', 0);
        }

        return $q->findAll();
    }

    /** Ambil note terbaru untuk satu sesi. */
    public function getLatestBySession(int $sessionId): ?array
    {
        $row = $this->where('session_id', (int) $sessionId)
            ->where('deleted_at', null)
            ->orderBy('created_at', 'DESC')
            ->asArray()
            ->first();

        return $row ?: null;
    }

    /**
     * Alias: ambil note non-rahasia saja (untuk role yang dibatasi).
     * Koordinator tidak perlu pakai ini (karena boleh lihat rahasia).
     */
    public function getPublicBySession(int $sessionId): array
    {
        return $this->getBySession($sessionId, false);
    }

    // ---------------------------------------------------------------------
    // Mutator / Utility
    // ---------------------------------------------------------------------

    /** Buat note dan kembalikan insert id (null jika gagal). */
    public function createNote(array $data): ?int
    {
        if ($this->insert($data, true) === false) {
            return null;
        }
        $id = (int) $this->getInsertID();
        return $id ?: null;
    }

    /** Update isi catatan saja. */
    public function updateNoteContent(int $id, string $content): bool
    {
        return $this->update($id, ['note_content' => $content]);
    }

    /** Hapus note (soft delete). */
    public function deleteNote(int $id): bool
    {
        return (bool) $this->delete($id);
    }

    /**
     * Hitung jumlah note per jenis untuk satu sesi.
     * @return array<string,int> [note_type => count]
     */
    public function countByTypeForSession(int $sessionId): array
    {
        $rows = $this->select('note_type, COUNT(*) AS cnt')
            ->where('session_id', (int) $sessionId)
            ->where('deleted_at', null)
            ->groupBy('note_type')
            ->asArray()
            ->findAll();

        $out = [];
        foreach ($rows as $r) {
            $type = (string) ($r['note_type'] ?? 'Observasi');
            $out[$type] = (int) ($r['cnt'] ?? 0);
        }
        return $out;
    }

    /**
     * Sinkronisasi kerahasiaan semua catatan untuk satu sesi.
     * Dipakai ketika counseling_sessions.is_confidential diubah.
     */
    public function setConfidentialBySession(int $sessionId, bool $isConfidential): bool
    {
        return (bool) $this->where('session_id', (int) $sessionId)
            ->set('is_confidential', $isConfidential ? 1 : 0)
            ->update();
    }

    // ---------------------------------------------------------------------
    // Attachments helper (kolom: `attachments` berupa JSON)
    // ---------------------------------------------------------------------

    /**
     * Konversi kolom attachments menjadi array string.
     *
     * Bisa diberi:
     *  - seluruh row note (array dengan key 'attachments'), atau
     *  - langsung nilai kolom attachments (string JSON / array / null).
     *
     * @param array|string|null $noteRow
     * @return array<int,string>
     */
    public function attachmentsToArray($noteRow): array
    {
        // Jika diberi row lengkap, ambil kolomnya dulu
        if (is_array($noteRow) && array_key_exists('attachments', $noteRow)) {
            $raw = $noteRow['attachments'];
        } else {
            $raw = $noteRow;
        }

        if ($raw === null || $raw === '') {
            return [];
        }

        // Sudah array
        if (is_array($raw)) {
            return array_values(array_map('strval', $raw));
        }

        // Asumsikan string JSON
        $decoded = json_decode((string) $raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        return array_values(array_map('strval', $decoded));
    }

    /** Simpan attachments dalam bentuk JSON ke kolom `attachments`. */
    public function saveAttachments(int $id, array $files): bool
    {
        $files = array_values(array_map('strval', $files));

        return $this->update($id, [
            'attachments' => $files ? json_encode($files) : null,
        ]);
    }

    /** Set/clear flag penting. */
    public function setImportant(int $id, bool $flag): bool
    {
        return $this->update($id, ['is_important' => $flag ? 1 : 0]);
    }
}
