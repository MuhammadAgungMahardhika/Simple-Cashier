<?php

namespace App\Policies;

use App\Models\Enums\RoleStatusEnum;
use App\Models\Role;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class RolePolicy
{
    use HasPolicyAuthorization;
    /**
     * Override Authorization for deleting the Role model.
     */
    public function delete(User $user, Model $record): bool
    {
        // 1. Check if the role is a core system role
        if (RoleStatusEnum::isCoreSystemRole($record->id)) {
            return false; // Deny deletion on core system roles
        }

        // 2. Fallback to base permission check (e.g., 'delete-role')
        return static::hasPermission('delete', static::getResourceName());
    }
}
