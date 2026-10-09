<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminRolesController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::query()->where('is_super_admin', false)->withCount('users')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.accounts.roles.create');
    }

    public function edit(Role $role): View
    {
        abort_if($role->is_super_admin, 404);
        abort_unless(request()->user()->canAccess('accounts', 'edit'), 403);

        return view('admin.roles.edit', compact('role'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRole($request);
        $permissions = $this->permissionMap($request, $validated);

        $role = Role::create(['name' => $validated['name'], 'is_super_admin' => false]);
        $role->syncPermissions($permissions);

        return redirect()->route('admin.roles.index')->with('status', 'Role created successfully.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->is_super_admin, 404);
        $validated = $this->validateRole($request, $role);
        $role->name = $validated['name'];
        $permissions = $this->permissionMap($request, $validated);
        $role->save();
        $role->syncPermissions($permissions);

        return redirect()->route('admin.roles.index')->with('status', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->is_super_admin, 404);
        abort_if($role->users()->exists(), 409, 'This role is assigned to users and cannot be deleted.');

        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Role deleted successfully.');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        $permissionRules = [];
        foreach (config('permissions.modules', []) as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                $permissionRules["permissions.{$module}.{$action}"] = ['sometimes', 'boolean'];
            }
        }

        $validated = $request->validate(array_merge([
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('roles', 'name')->ignore($role?->id),
                Rule::notIn(['Super Admin', 'super_admin']),
            ],
            'permissions' => ['sometimes', 'array'],
        ], $permissionRules));

        return $validated;
    }

    private function permissionMap(Request $request, array $validated): array
    {
        $permissions = [];
        foreach (config('permissions.modules', []) as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                $allowed = (bool) ($validated['permissions'][$module][$action] ?? false);
                if ($allowed && ! $request->user()->canAccess($module, $action)) {
                    throw ValidationException::withMessages([
                        'permissions' => 'You cannot grant a role permission you do not have yourself.',
                    ]);
                }
                $permissions[$module][$action] = $allowed;
            }
        }

        return $permissions;
    }
}
