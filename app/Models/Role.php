<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'is_super_admin'];

    protected function casts(): array
    {
        return ['is_super_admin' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withPivot('is_allowed');
    }

    public function getPermissionMapAttribute(): array
    {
        $map = [];
        foreach (config('permissions.modules', []) as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                $map[$module][$action] = false;
            }
        }

        foreach ($this->permissions()->withPivot('is_allowed')->get() as $permission) {
            if (array_key_exists($permission->action, $map[$permission->module] ?? [])) {
                $map[$permission->module][$permission->action] = (bool) $permission->pivot->is_allowed;
            }
        }

        return $map;
    }

    public function getPermissionsAttribute(): array
    {
        return $this->permission_map;
    }

    public function syncPermissions(array $map): void
    {
        $permissions = Permission::query()->get()->keyBy(fn (Permission $permission) => "{$permission->module}.{$permission->action}");
        $sync = [];

        foreach (config('permissions.modules', []) as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                $permission = $permissions["{$module}.{$action}"] ?? null;
                if ($permission) {
                    $sync[$permission->id] = [
                        'is_allowed' => (bool) ($map[$module][$action] ?? false),
                    ];
                }
            }
        }

        $this->permissions()->sync($sync);
    }
}
