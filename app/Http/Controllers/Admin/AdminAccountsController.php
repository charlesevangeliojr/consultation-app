<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAccountsController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('role')
            ->when(! request()->user()->isAdmin(), fn ($query) => $query->whereDoesntHave('role', fn ($roles) => $roles->where('is_super_admin', true)))
            ->orderBy('name')
            ->paginate(15);

        return view('admin.accounts', compact('users'));
    }

    public function create(): View
    {
        return view('admin.accounts.create', ['roles' => Role::query()->where('is_super_admin', false)->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedAccount($request);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role_id' => $validated['role_id'] ?? null,
        ]);
        $user->syncPermissions($this->permissionMap($validated));
        if ($user->isTeacher()) $user->teacher()->firstOrCreate();
        if ($user->isStudent()) $user->student()->firstOrCreate();

        return redirect()->route('admin.accounts')->with('status', 'Account created successfully.');
    }

    public function edit(User $user): View|RedirectResponse
    {
        abort_unless(request()->user()->canManage($user), 403, 'You cannot manage an account with greater access.');

        return view('admin.accounts.edit', [
            'account' => $user,
            'roles' => Role::query()->where('is_super_admin', false)->orderBy('name')->get(),
            'gradeLevels' => GradeLevel::with('sections')->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManage($user), 403, 'You cannot manage an account with greater access.');

        $validated = $this->validatedAccount($request, $user);
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role_id = $validated['role_id'] ?? null;

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();
        if ($user->isTeacher()) $user->teacher()->firstOrCreate();
        if ($user->isStudent()) $user->student()->firstOrCreate();
        $user->syncPermissions($this->permissionMap($validated));

        return redirect()->route('admin.accounts')->with('status', 'Account updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManage($user, 'delete'), 403, 'You cannot delete an account with greater access.');
        abort_if($user->isAdmin(), 403, 'The protected Super Admin account cannot be deleted.');
        abort_if($request->user()->is($user), 403, 'You cannot delete your own account.');

        $user->delete();

        return redirect()->route('admin.accounts')->with('status', 'User deleted successfully.');
    }

    private function validatedAccount(Request $request, ?User $user = null): array
    {
        $permissionRules = [];
        foreach (config('permissions.modules', []) as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                $permissionRules["permissions.{$module}.{$action}"] = ['sometimes', 'boolean'];
            }
        }

        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'.($user ? ','.$user->id : '')],
            'password' => [$user ? 'nullable' : 'required', 'string', 'confirmed'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'permissions' => ['sometimes', 'array'],
        ], $permissionRules));

        foreach (config('permissions.modules', []) as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                if (! empty($validated['permissions'][$module][$action])
                    && ! $request->user()->canAccess($module, $action)) {
                    throw ValidationException::withMessages([
                        'permissions' => 'You cannot grant a permission you do not have yourself.',
                    ]);
                }
            }
        }

        if (! empty($validated['role_id'])) {
            $role = Role::findOrFail($validated['role_id']);
            foreach (config('permissions.modules', []) as $module => $definition) {
                foreach ($definition['actions'] as $action) {
                    if (($role->permissions[$module][$action] ?? false) && ! $request->user()->canAccess($module, $action)) {
                        throw ValidationException::withMessages([
                            'role_id' => 'You cannot assign a role with permissions you do not have yourself.',
                        ]);
                    }
                }
            }
        }

        return $validated;
    }

    private function permissionMap(array $validated): ?array
    {
        if (! isset($validated['permissions']) && ! empty($validated['role_id'])) {
            return null;
        }

        $permissions = [];
        foreach (config('permissions.modules', []) as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                $permissions[$module][$action] = (bool) ($validated['permissions'][$module][$action] ?? false);
            }
        }

        if (! empty($validated['role_id'])) {
            $role = Role::find($validated['role_id']);
            if ($role && $permissions === $role->permissions) {
                return null;
            }
        } elseif (! isset($validated['permissions'])) {
            return $permissions;
        }

        return $permissions;
    }
}
