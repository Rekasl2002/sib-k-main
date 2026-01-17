<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Academic Year Model
 * Mengelola data tahun ajaran dengan validasi agar hanya satu yang aktif.
 */
class AcademicYearModel extends Model
{
    protected $table          = 'academic_years'; // Ganti jika nama tabel Anda berbeda
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;

    protected $protectFields = true;
    protected $allowedFields = [
        'year_name',
        'start_date',
        'end_date',
        'is_active',
        'semester',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'year_name'  => 'required|string|max_length[100]',
        'start_date' => 'required|valid_date',
        'end_date'   => 'required|valid_date',
        'semester'   => 'permit_empty|string|max_length[20]',
        'is_active'  => 'permit_empty|in_list[0,1]',
    ];

    /**
     * Ambil tahun ajaran aktif.
     * @return array|null
     */
    public function getActiveYear(): ?array
    {
        /** @var array<string,mixed>|null $year */
        $year = $this->where('is_active', 1)->asArray()->first();
        return $year ?: null;
    }

    /**
     * Ambil tahun ajaran yang mencakup sebuah tanggal (Y-m-d).
     * @return array|null
     */
    public function getByDate(string $dateYmd): ?array
    {
        /** @var array<string,mixed>|null $year */
        $year = $this->where('start_date <=', $dateYmd)
                     ->where('end_date >=', $dateYmd)
                     ->asArray()
                     ->first();

        return $year ?: null;
    }

    /**
     * Ambil tahun ajaran saat ini (aktif atau yang mencakup hari ini),
     * jika tidak ada, kembalikan yang terbaru berdasarkan end_date.
     */
    public function getCurrentOrLatest(): ?array
    {
        $today = date('Y-m-d');

        $active = $this->getActiveYear();
        if ($active) {
            return $active;
        }

        $current = $this->getByDate($today);
        if ($current) {
            return $current;
        }

        /** @var array<string,mixed>|null $latest */
        $latest = $this->orderBy('end_date', 'DESC')->asArray()->first();
        return $latest ?: null;
    }

    /**
     * Set hanya satu tahun ajaran yang aktif.
     * Menonaktifkan semuanya, lalu mengaktifkan ID yang dipilih.
     */
    public function setActive(int $id): bool
    {
        /** @var array<string,mixed>|null $target */
        $target = $this->asArray()->find($id);
        if (!$target) {
            return false;
        }
        if (is_object($target)) {
            $target = (array) $target;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Nonaktifkan semua yang tidak terhapus
        $this->where('deleted_at', null)->set(['is_active' => 0])->update();

        // Aktifkan yang dipilih
        $ok = $this->update($id, ['is_active' => 1]);

        $db->transComplete();
        return $ok && $db->transStatus();
    }

    /**
     * Buat tahun ajaran baru; jika is_active=1 maka pastikan hanya satu yang aktif.
     * @param array<string,mixed> $data
     */
    public function createYear(array $data): ?int
    {
        $start = isset($data['start_date']) ? date('Y-m-d', strtotime((string) $data['start_date'])) : null;
        $end   = isset($data['end_date'])   ? date('Y-m-d', strtotime((string) $data['end_date']))   : null;

        if ($start && $end && $end < $start) {
            [$start, $end] = [$end, $start];
        }

        $payload = [
            'year_name'  => $data['year_name']  ?? null,
            'semester'   => $data['semester']   ?? null,
            'start_date' => $start,
            'end_date'   => $end,
            'is_active'  => (int) ($data['is_active'] ?? 0),
        ];

        $this->insert($payload, true);
        $newId = (int) $this->getInsertID();
        if (!$newId) {
            return null;
        }

        if (!empty($payload['is_active'])) {
            $this->setActive($newId);
        }

        return $newId;
    }

    /**
     * Perbarui tahun ajaran; jika is_active=1 maka pastikan hanya satu yang aktif.
     * @param array<string,mixed> $data
     */
    public function updateYear(int $id, array $data): bool
    {
        /** @var array<string,mixed>|null $current */
        $current = $this->asArray()->find($id);
        if (!$current) {
            return false;
        }
        if (is_object($current)) {
            $current = (array) $current;
        }

        $start = array_key_exists('start_date', $data)
            ? date('Y-m-d', strtotime((string) $data['start_date']))
            : ($current['start_date'] ?? null);

        $end = array_key_exists('end_date', $data)
            ? date('Y-m-d', strtotime((string) $data['end_date']))
            : ($current['end_date'] ?? null);

        if ($start && $end && $end < $start) {
            [$start, $end] = [$end, $start];
        }

        $payload = [
            'year_name'  => $data['year_name']  ?? ($current['year_name']  ?? null),
            'semester'   => $data['semester']   ?? ($current['semester']   ?? null),
            'start_date' => $start,
            'end_date'   => $end,
        ];

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (int) $data['is_active'];
        }

        $ok = $this->update($id, $payload);
        if (!$ok) {
            return false;
        }

        if (!empty($payload['is_active'])) {
            return $this->setActive($id);
        }

        return true;
    }

    /**
     * Daftar tahun ajaran dengan status (past/current/upcoming/active).
     * @return array<int,array<string,mixed>>
     */
    public function listWithStatus(): array
    {
        $rows  = $this->orderBy('start_date', 'DESC')->asArray()->findAll();
        $today = date('Y-m-d');

        foreach ($rows as &$r) {
            if (is_object($r)) {
                $r = (array) $r;
            }
            $r['status'] = 'past';
            if (!empty($r['start_date']) && !empty($r['end_date'])) {
                if ($r['start_date'] <= $today && $r['end_date'] >= $today) {
                    $r['status'] = 'current';
                } elseif ($r['start_date'] > $today) {
                    $r['status'] = 'upcoming';
                }
            }
            if (!empty($r['is_active'])) {
                $r['status'] = 'active';
            }
        }
        unset($r);

        return $rows;
    }

    /**
     * Statistik ringkas tahun ajaran.
     * @return array{total:int,active:int,upcoming:int,past:int}
     */
    public function getStats(): array
    {
        $today = date('Y-m-d');

        $total = (int) $this->builder()
            ->where('deleted_at', null)
            ->countAllResults();

        $active = (int) $this->builder()
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->countAllResults();

        $upcoming = (int) $this->builder()
            ->where('deleted_at', null)
            ->where('start_date >', $today)
            ->countAllResults();

        $past = max(0, $total - $active - $upcoming);

        return [
            'total'    => $total,
            'active'   => $active,
            'upcoming' => $upcoming,
            'past'     => $past,
        ];
    }
}
