<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait HasPolicyAuthorization
{
    /**
     * Dapatkan nama resource dari nama kelas kebijakan.
     */
    protected static array $permissionCache = [];
    protected static function getResourceName(): string
    {
        $policyName = class_basename(static::class);
        $modelName = str_replace('Policy', '', $policyName);

        return Str::kebab($modelName);
    }

    /**
     * Periksa izin Spatie untuk sebuah aksi dan resource.
     */
    protected static function hasPermission(string $action, string $resource): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        $permission = "{$action}-{$resource}";
        $cacheKey = "{$user->id}:{$permission}";
        if (! isset(static::$permissionCache[$cacheKey])) {
            static::$permissionCache[$cacheKey] = $user->can($permission);
        }

        return static::$permissionCache[$cacheKey];
    }

    public function viewAny(User $user): bool
    {
        return static::hasPermission('view-any', static::getResourceName());
    }

    public function view(User $user, Model $record): bool
    {
        return  static::hasPermission('view', static::getResourceName());
    }

    public function create(User $user): bool
    {
        return static::hasPermission('create', static::getResourceName());
    }

    public function update(User $user, Model $record): bool
    {
        return  static::hasPermission('update', static::getResourceName());
    }

    public function delete(User $user, Model $record): bool
    {
        return static::hasPermission('delete', static::getResourceName());
    }

    public function restore(User $user, Model $record): bool
    {
        return static::hasPermission('restore', static::getResourceName());
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return static::hasPermission('force-delete', static::getResourceName());
    }
}
