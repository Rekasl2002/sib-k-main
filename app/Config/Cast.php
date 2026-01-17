<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cast extends BaseConfig
{
    /** Handler resmi di CI 4.6.x */
    public array $handlers = [
        'array'     => \CodeIgniter\Entity\Cast\ArrayCast::class,
        'json'      => \CodeIgniter\Entity\Cast\JsonCast::class,
        'string'    => \CodeIgniter\Entity\Cast\StringCast::class,
        'integer'   => \CodeIgniter\Entity\Cast\IntegerCast::class,
        'float'     => \CodeIgniter\Entity\Cast\FloatCast::class,
        'double'    => \CodeIgniter\Entity\Cast\FloatCast::class, // alias
        'boolean'   => \CodeIgniter\Entity\Cast\BooleanCast::class,
        'datetime'  => \CodeIgniter\Entity\Cast\DatetimeCast::class,
        'timestamp' => \CodeIgniter\Entity\Cast\TimestampCast::class,
        // Jika Anda ingin ada alias 'date', arahkan ke DatetimeCast:
        // 'date'   => \CodeIgniter\Entity\Cast\DatetimeCast::class,
    ];

    /** Alias bawaan yang berguna */
    public array $aliases = [
        'int'  => 'integer',
        'bool' => 'boolean',
    ];
}
