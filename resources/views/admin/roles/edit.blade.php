@extends('admin.layout')
@section('title', 'Edit Role')
@section('content')
    <div class="account-page-heading">
        <div><p class="account-eyebrow">User Management / Roles</p><h2>Edit Role</h2><p>Update the role name and its default module permissions.</p></div>
        <a href="{{ route('admin.roles.index') }}" class="account-button account-button-secondary">Cancel</a>
    </div>
    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="account-form" data-account-form>
        @csrf
        @method('PUT')
        <div class="account-panels">
            <section class="account-panel">
                <h3 class="account-panel-heading">Role Details</h3>
                <div class="account-panel-body">
                    <label for="name">Role Name</label>
                    <input id="name" name="name" value="{{ old('name', $role->name) }}" required maxlength="80" class="account-input">
                    @error('name') <p class="account-error">{{ $message }}</p> @enderror
                </div>
            </section>
            <section class="account-panel">
                <h3 class="account-panel-heading">Module Permissions</h3>
                <div class="account-panel-body">
                    <label class="account-notice account-full-access"><div><strong>Full Access</strong><p>Select all permissions you want this role to have by default.</p></div><input type="checkbox" data-permission-all aria-label="Select all available permissions"></label>
                    <div class="account-table-heading"><h4>Permissions</h4><button type="button" data-permission-clear>Clear All</button></div>
                    <div class="account-table-scroll">
                        <table class="account-permissions">
                            <thead><tr><th scope="col">Module</th>@foreach (config('permissions.actions') as $action => $label)<th scope="col"><input type="checkbox" data-permission-column="{{ $action }}" aria-label="Select all {{ strtolower($label) }} permissions"><span>{{ $label }}</span></th>@endforeach</tr></thead>
                            <tbody>
                                @foreach (config('permissions.modules') as $module => $definition)
                                    <tr>
                                        <th scope="row"><input type="checkbox" data-permission-row="{{ $module }}" aria-label="Select all {{ $definition['name'] }} permissions">{{ $definition['name'] }}</th>
                                        @foreach (config('permissions.actions') as $action => $label)
                                            <td>
                                                @if (in_array($action, $definition['actions'], true))
                                                    <input type="checkbox" name="permissions[{{ $module }}][{{ $action }}]" value="1" data-permission data-module="{{ $module }}" data-action="{{ $action }}" aria-label="{{ $definition['name'] }}: {{ $label }}" @checked(old("permissions.{$module}.{$action}", $role->permissions[$module][$action] ?? false)) @disabled(! auth()->user()->canAccess($module, $action))>
                                                @else <span class="account-unavailable">—</span> @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @error('permissions') <p class="account-error">{{ $message }}</p> @enderror
                </div>
            </section>
        </div>
        <div class="account-save-bar"><p>Changes update default permissions for users assigned this role.</p><div><a href="{{ route('admin.roles.index') }}" class="account-button account-button-secondary">Cancel</a><button type="submit" class="account-button account-button-primary">Save Changes</button></div></div>
    </form>
@endsection
