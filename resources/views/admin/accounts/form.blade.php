@php
    $protectedAccount = $account?->isAdmin() ?? false;
    $submitted = session()->hasOldInput();
    $selectedRole = $roles->firstWhere('id', (int) old('role_id', $account?->role_id));
@endphp
<form method="POST" action="{{ $formAction }}" class="account-form" data-account-form>
    @csrf
    @if ($isEditing) @method('PUT') @endif
    <div class="account-panels">
        <section class="account-panel" aria-labelledby="account-information">
            <h3 id="account-information" class="account-panel-heading"><svg class="account-panel-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg> Account Information</h3>
            <div class="account-panel-body account-fields">
                <div>
                    <label for="name">Full Name <span>*</span></label>
                    <input id="name" name="name" type="text" value="{{ old('name', $account?->name) }}" required maxlength="255" autocomplete="name" class="account-input">
                    @error('name') <p class="account-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email">Email Address <span>*</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email', $account?->email) }}" required maxlength="255" autocomplete="email" class="account-input">
                    @error('email') <p class="account-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password">{{ $isEditing ? 'New Password' : 'Password' }} @if (! $isEditing)<span>*</span>@endif</label>
                    <p class="account-help">{{ $isEditing ? 'Leave this blank to keep the current password.' : 'Choose a password and confirm it below.' }}</p>
                    <div class="account-password">
                        <input id="password" name="password" type="password" @required(! $isEditing) autocomplete="new-password" placeholder="Enter password" class="account-input">
                        <button type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false">@include('admin.accounts.eye')</button>
                    </div>
                    @error('password') <p class="account-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation">Confirm Password @if (! $isEditing)<span>*</span>@endif</label>
                    <div class="account-password">
                        <input id="password_confirmation" name="password_confirmation" type="password" @required(! $isEditing) autocomplete="new-password" placeholder="Confirm password" class="account-input">
                        <button type="button" data-password-toggle="password_confirmation" aria-label="Show confirm password" aria-pressed="false">@include('admin.accounts.eye')</button>
                    </div>
                    @error('password_confirmation') <p class="account-error">{{ $message }}</p> @enderror
                </div>
                @if ($protectedAccount)
                    <div class="account-notice"><div><strong>Protected Account</strong><p>This Super Admin always has full system access.</p></div><span class="account-badge">Super Admin</span></div>
                @endif
            </div>
        </section>
        <section class="account-panel" aria-labelledby="account-access">
            <h3 id="account-access" class="account-panel-heading"><svg class="account-shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/><path d="m8 12 3 3 5-6"/></svg> Access</h3>
            <div class="account-panel-body">
                <label for="account-role">Roles</label>
                <select id="account-role" name="role_id" class="account-input" data-account-role @disabled($protectedAccount)>
                    <option value="" @selected(! old('role_id', $account?->role_id))>Custom permissions</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" data-permissions="{{ json_encode($role->permissions) }}" @selected((string) old('role_id', $account?->role_id) === (string) $role->id)>{{ $role->name }}</option>
                    @endforeach
                    @if ($protectedAccount)<option selected>Super Admin</option>@endif
                </select>
                @error('role_id') <p class="account-error">{{ $message }}</p> @enderror
                <p class="account-help">{{ $protectedAccount ? 'The protected Super Admin role has all available permissions.' : 'Choose a saved role or set custom module permissions below.' }}</p>
                <label class="account-notice account-full-access">
                    <div><strong>Full System Access</strong><p>{{ $protectedAccount ? 'All available permissions are enabled for this account.' : 'Check all permissions you can grant to this user.' }}</p></div>
                    <input type="checkbox" data-permission-all @checked($protectedAccount) @disabled($protectedAccount) aria-label="Select all available permissions">
                </label>
                <div class="account-table-heading"><h4>Module Permissions</h4>@if (! $protectedAccount)<button type="button" data-permission-clear>Clear All</button>@endif</div>
                <div class="account-table-scroll">
                    <table class="account-permissions">
                        <thead><tr><th scope="col">Module</th>@foreach (config('permissions.actions') as $action => $label)<th scope="col">@if (! $protectedAccount)<input type="checkbox" data-permission-column="{{ $action }}" aria-label="Select all {{ strtolower($label) }} permissions">@endif<span>{{ $label }}</span></th>@endforeach</tr></thead>
                        <tbody>
                        @foreach (config('permissions.modules') as $module => $definition)
                            <tr>
                                <th scope="row">@if (! $protectedAccount)<input type="checkbox" data-permission-row="{{ $module }}" aria-label="Select all {{ $definition['name'] }} permissions">@endif {{ $definition['name'] }}</th>
                                @foreach (config('permissions.actions') as $action => $label)
                                    <td>
                                        @if (in_array($action, $definition['actions'], true))
                                            <input type="checkbox" name="permissions[{{ $module }}][{{ $action }}]" value="1" data-permission data-module="{{ $module }}" data-action="{{ $action }}"
                                                aria-label="{{ $definition['name'] }}: {{ $label }}"
                                                @checked($protectedAccount || ($submitted ? old("permissions.{$module}.{$action}", false) : ($account ? $account->canAccess($module, $action) : ($selectedRole?->permissions[$module][$action] ?? false))))
                                                @disabled($protectedAccount || ! auth()->user()->canAccess($module, $action))>
                                        @else <span class="account-unavailable" aria-label="Not available">—</span> @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="account-help">Selected permissions become user-specific access settings. You can only grant permissions you have yourself.</p>
                @error('permissions') <p class="account-error">{{ $message }}</p> @enderror
                @foreach ($errors->get('permissions.*') as $messages)
                    @foreach ($messages as $message)<p class="account-error">{{ $message }}</p>@endforeach
                @endforeach
            </div>
        </section>
    </div>
    <div class="account-save-bar"><p>Review account details and module permissions before saving.</p><div><a href="{{ route('admin.accounts') }}" class="account-button account-button-secondary">Cancel</a><button type="submit" class="account-button account-button-primary">{{ $isEditing ? 'Save Changes' : 'Create User' }}</button></div></div>
</form>
