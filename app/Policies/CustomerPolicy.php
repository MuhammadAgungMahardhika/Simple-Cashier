<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;
use Illuminate\Auth\Access\Response;

class CustomerPolicy
{
    use HasPolicyAuthorization;

    public function export(User $user): bool
    {
        return  static::hasPermission('export', static::getResourceName());
    }
}
