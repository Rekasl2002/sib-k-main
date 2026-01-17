<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = ['group','key','value','type','autoload'];
    protected $useTimestamps    = true;

    public function getAllByGroup(string $group): array
    {
        return $this->where('group',$group)->findAll();
    }

    public function getValue(string $key, ?string $group=null, $default=null)
    {
        $builder = $this->builder();
        if ($group) $builder->where('group', $group);
        $row = $builder->where('key',$key)->get()->getRowArray();
        if (!$row) return $default;

        $val = $row['value'];
        return match($row['type']) {
            'int'  => (int)$val,
            'bool' => (int)$val === 1 || $val === '1',
            'json' => $val ? json_decode($val,true) : null,
            default=> $val,
        };
    }
}
