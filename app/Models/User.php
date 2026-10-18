<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role_id' => 'integer',
            'last_seen_at' => 'datetime',
            'last_logged_out_at' => 'datetime',
            'inactivity_reference_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->inactivity_reference_at ??= now();
        });
    }

    public function isAdmin(): bool
    {
        return $this->role()->where('is_super_admin', true)->exists();
    }

    public function isTeacher(): bool
    {
        $name = mb_strtolower($this->role?->name ?? '');
        return str_contains($name, 'teacher') || str_contains($name, 'faculty');
    }

    public function isStudent(): bool
    {
        return str_contains(mb_strtolower($this->role?->name ?? ''), 'student');
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gt(now()->subMinutes(5))
            && ($this->last_logged_out_at === null || $this->last_seen_at->gt($this->last_logged_out_at));
    }

    public function canAccess(string $module, string $action): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $allowedActions = config("permissions.modules.{$module}.actions", []);
        if (! in_array($action, $allowedActions, true)) {
            return false;
        }

        $permission = Permission::query()->where('module', $module)->where('action', $action)->first();
        if (! $permission) {
            return false;
        }

        $override = $this->userPermissions()->where('permissions.id', $permission->id)->first();
        if ($override) {
            return (bool) $override->pivot->is_allowed;
        }

        if ($this->userPermissions()->exists()) {
            return false;
        }

        return (bool) ($this->role?->permissions[$module][$action] ?? false);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function userPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')->withPivot('is_allowed');
    }

    public function syncPermissions(?array $map): void
    {
        $this->userPermissions()->detach();

        if ($map === null) {
            return;
        }

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

        $this->userPermissions()->sync($sync);
    }

    public function hasAdminAccess(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        foreach (config('permissions.modules', []) as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                if ($this->canAccess($module, $action)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function canManage(self $target, string $action = 'edit'): bool
    {
        if (! $this->canAccess('accounts', $action)) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        if ($target->isAdmin()) {
            return false;
        }

        foreach (config('permissions.modules', []) as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                if ($target->canAccess($module, $action) && ! $this->canAccess($module, $action)) {
                    return false;
                }
            }
        }

        return true;
    }
}
