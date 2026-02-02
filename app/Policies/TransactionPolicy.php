<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\HasPolicyAuthorization;
use Illuminate\Database\Eloquent\Model;

class TransactionPolicy
{
    use HasPolicyAuthorization;
    public function update(User $user, Model $record): bool
    {
        return  static::hasPermission('update', static::getResourceName()) && in_array($record->status, [
            'pending',
        ]);
    }

    public function delete(User $user, Model $record): bool
    {
        return static::hasPermission('delete', static::getResourceName()) && in_array($record->status, [
            'pending',
        ]);
    }
}
