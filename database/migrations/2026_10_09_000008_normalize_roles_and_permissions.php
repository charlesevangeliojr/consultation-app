<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('action');
            $table->timestamps();
            $table->unique(['module', 'action']);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_allowed')->default(false);
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_allowed')->default(false);
            $table->primary(['user_id', 'permission_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
        });

        $now = now();
        foreach (config('permissions.modules', []) as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                DB::table('permissions')->insert([
                    'module' => $module,
                    'action' => $action,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $permissionIds = DB::table('permissions')->get()->keyBy(fn ($permission) => "{$permission->module}.{$permission->action}");
        $roleIds = [];

        if (Schema::hasTable('access_roles')) {
            foreach (DB::table('access_roles')->orderBy('id')->get() as $legacyRole) {
                $roleId = DB::table('roles')->insertGetId([
                    'name' => $legacyRole->name,
                    'is_super_admin' => false,
                    'created_at' => $legacyRole->created_at ?? $now,
                    'updated_at' => $legacyRole->updated_at ?? $now,
                ]);
                $roleIds[$legacyRole->id] = $roleId;

                foreach ((array) json_decode($legacyRole->permissions, true) as $module => $actions) {
                    foreach ((array) $actions as $action => $allowed) {
                        $permissionId = $permissionIds["{$module}.{$action}"]->id ?? null;
                        if ($permissionId) {
                            DB::table('role_permissions')->insert([
                                'role_id' => $roleId,
                                'permission_id' => $permissionId,
                                'is_allowed' => (bool) $allowed,
                            ]);
                        }
                    }
                }
            }
        }

        $superAdminRoleId = DB::table('roles')->insertGetId([
            'name' => 'Super Admin',
            'is_super_admin' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            $roleId = ($user->role ?? null) === 'super_admin'
                ? $superAdminRoleId
                : ($roleIds[$user->access_role_id ?? 0] ?? null);

            if ($roleId) {
                DB::table('users')->where('id', $user->id)->update(['role_id' => $roleId]);
            }

            foreach ((array) json_decode($user->permissions ?? '[]', true) as $module => $actions) {
                foreach ((array) $actions as $action => $allowed) {
                    $permissionId = $permissionIds["{$module}.{$action}"]->id ?? null;
                    if ($permissionId) {
                        DB::table('user_permissions')->insert([
                            'user_id' => $user->id,
                            'permission_id' => $permissionId,
                            'is_allowed' => (bool) $allowed,
                        ]);
                    }
                }
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('access_role_id');
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'permissions']);
        });
        Schema::dropIfExists('access_roles');
    }

    public function down(): void
    {
        Schema::create('access_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->json('permissions');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user');
            $table->json('permissions')->nullable();
            $table->foreignId('access_role_id')->nullable()->constrained('access_roles')->nullOnDelete();
        });

        $accessRoleIds = [];
        foreach (DB::table('roles')->where('is_super_admin', false)->get() as $role) {
            $permissions = [];
            foreach (DB::table('role_permissions')->where('role_id', $role->id)->get() as $grant) {
                $permission = DB::table('permissions')->where('id', $grant->permission_id)->first();
                if ($permission) {
                    $permissions[$permission->module][$permission->action] = (bool) $grant->is_allowed;
                }
            }
            $accessRoleIds[$role->id] = DB::table('access_roles')->insertGetId([
                'name' => $role->name,
                'permissions' => json_encode($permissions),
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
            ]);
        }

        foreach (DB::table('users')->get() as $user) {
            $newRole = DB::table('roles')->where('id', $user->role_id)->first();
            $permissions = [];
            foreach (DB::table('user_permissions')->where('user_id', $user->id)->get() as $grant) {
                $permission = DB::table('permissions')->where('id', $grant->permission_id)->first();
                if ($permission) {
                    $permissions[$permission->module][$permission->action] = (bool) $grant->is_allowed;
                }
            }
            DB::table('users')->where('id', $user->id)->update([
                'role' => $newRole?->is_super_admin ? 'super_admin' : 'user',
                'access_role_id' => $accessRoleIds[$user->role_id] ?? null,
                'permissions' => $permissions ? json_encode($permissions) : null,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
