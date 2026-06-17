<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Percakapan 1-lawan-1 antara dua pengguna (Pesan ala media sosial).
 * Pasangan disimpan terurut (user_one_id <= user_two_id). Soft delete PER PIHAK
 * dilakukan lewat kolom one_deleted_at / two_deleted_at (bukan deleted_at global).
 */
class ConversationModel extends Model
{
    protected $table          = 'conversations';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'user_one_id', 'user_two_id', 'last_message_id', 'last_message_at',
        'one_deleted_at', 'two_deleted_at', 'created_by',
    ];

    /**
     * Cari percakapan untuk sepasang pengguna (tanpa membuat baru). Mengembalikan
     * baris array atau null.
     */
    public function findPair(int $a, int $b): ?array
    {
        [$one, $two] = $a <= $b ? [$a, $b] : [$b, $a];

        $row = $this->where('user_one_id', $one)
            ->where('user_two_id', $two)
            ->where('deleted_at', null)
            ->first();

        return $row ?: null;
    }
}
