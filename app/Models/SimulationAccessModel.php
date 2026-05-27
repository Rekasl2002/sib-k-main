<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class SimulationAccessModel extends Model
{
    protected $table            = 'simulation_access_grants';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'user_id',
        'is_active',
        'granted_by',
        'granted_at',
        'revoked_by',
        'revoked_at',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function ensureTable(): bool
    {
        $db = Database::connect();
        if ($db->tableExists($this->table)) {
            return true;
        }

        $forge = Database::forge();
        $forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'granted_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'granted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'revoked_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'revoked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'notes' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('user_id');
        $forge->addKey('is_active');

        return (bool) $forge->createTable($this->table, true);
    }

    public function tableReady(): bool
    {
        try {
            return Database::connect()->tableExists($this->table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function hasActiveAccess(int $userId): bool
    {
        if ($userId <= 0 || ! $this->tableReady()) {
            return false;
        }

        return (bool) $this->where('user_id', $userId)
            ->where('is_active', 1)
            ->first();
    }

    public function grant(int $userId, int $adminId, ?string $notes = null): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $this->ensureTable();
        $now = date('Y-m-d H:i:s');
        $existing = $this->where('user_id', $userId)->first();
        $payload = [
            'user_id'    => $userId,
            'is_active' => 1,
            'granted_by' => $adminId ?: null,
            'granted_at' => $now,
            'revoked_by' => null,
            'revoked_at' => null,
            'notes'      => $notes !== null ? trim($notes) : null,
        ];

        if ($existing) {
            return (bool) $this->update((int) $existing['id'], $payload);
        }

        return (bool) $this->insert($payload);
    }

    public function revoke(int $userId, int $adminId): bool
    {
        if ($userId <= 0 || ! $this->tableReady()) {
            return false;
        }

        $existing = $this->where('user_id', $userId)->first();
        if (! $existing) {
            return true;
        }

        return (bool) $this->update((int) $existing['id'], [
            'is_active' => 0,
            'revoked_by' => $adminId ?: null,
            'revoked_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
