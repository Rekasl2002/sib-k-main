<?php

use App\Models\SimulationAccessModel;

if (! function_exists('simulation_access_is_admin')) {
    function simulation_access_is_admin(): bool
    {
        if (function_exists('is_admin') && is_admin()) {
            return true;
        }

        $roleId = (int) (session('role_id') ?? 0);
        $role   = strtolower(trim((string) (session('role_name') ?? '')));

        return $roleId === 1 || in_array($role, ['admin', 'administrator'], true);
    }
}

if (! function_exists('can_access_simulation_suite')) {
    function can_access_simulation_suite(?int $userId = null): bool
    {
        if (simulation_access_is_admin()) {
            return true;
        }

        $userId = $userId ?: (int) (session('user_id') ?? 0);
        if ($userId <= 0) {
            return false;
        }

        try {
            return (new SimulationAccessModel())->hasActiveAccess($userId);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
