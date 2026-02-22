<?php

namespace App\Policies;

use App\Models\Services;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;
use Illuminate\Auth\Access\Response;

class ServicePolicy
{
    use HasPolicyAuthorization;

    public function export(User $user): bool
    {
        return  static::hasPermission('export', static::getResourceName());
    }

    public function import(User $user): bool
    {
        return  static::hasPermission('import', static::getResourceName());
    }
}
