<?php

namespace App\Models;

use CodeIgniter\Model;
use Throwable;

class SettingModel extends Model
{
    protected $table          = 'settings';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';

    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields  = ['group', 'key', 'value', 'type', 'autoload'];

    // Optional tapi jelas
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $deletedField   = 'deleted_at';

    public function getAllByGroup(string $group): array
    {
        // Pakai method Model biar soft delete dihormati
        return $this->where('group', $group)->findAll();
    }

    /**
     * Ambil value setting berdasarkan key + optional group
     * Aman: tidak fatal walau DB/table belum siap.
     */
    public function getValue(string $key, ?string $group = null, $default = null)
    {
        try {
            $q = $this->select(['value', 'type'])
                ->where('key', $key);

            if ($group !== null && $group !== '') {
                $q->where('group', $group);
            }

            // first() akan return array|null (tidak false)
            $row = $q->first();
            if (empty($row)) {
                return $default;
            }

            $val  = $row['value'] ?? null;
            $type = $row['type'] ?? 'string';

            return match ($type) {
                'int'  => $this->toInt($val, $default),
                'bool' => $this->toBool($val),
                'json' => $this->toJson($val, $default),
                default => ($val !== null ? $val : $default),
            };
        } catch (Throwable $e) {
            // DB error / table missing / query error: jangan fatal, fallback
            return $default;
        }
    }

    private function toInt($val, $default): int
    {
        if (is_numeric($val)) {
            return (int) $val;
        }
        if (is_numeric($default)) {
            return (int) $default;
        }
        return 0;
    }

    private function toBool($val): bool
    {
        if (is_bool($val)) {
            return $val;
        }
        if (is_numeric($val)) {
            return ((int) $val) === 1;
        }
        $s = strtolower((string) $val);
        return in_array($s, ['1', 'true', 'yes', 'y', 'on'], true);
    }

    private function toJson($val, $default)
    {
        if ($val === null || $val === '') {
            return $default;
        }

        try {
            $decoded = json_decode((string) $val, true);
            return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $default;
        } catch (Throwable $e) {
            return $default;
        }
    }
}
