<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_sign_in_and_open_the_dashboard(): void
    {
        $this->seed();

        $this->post('/admin/login', [
            'email' => 'admin@admin.com',
            'password' => '*',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('admin@admin.com');
    }

    public function test_non_admin_users_cannot_open_the_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_non_admin_users_cannot_sign_in_through_admin_login(): void
    {
        User::factory()->create([
            'email' => 'person@example.com',
            'password' => 'password',
            'role_id' => null,
        ]);

        $this->post('/admin/login', [
            'email' => 'person@example.com',
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_admin_can_update_project_name_and_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'name' => 'My New Project',
                'logo' => UploadedFile::fake()->image('brand.png'),
                'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('site_settings', ['name' => 'My New Project']);
        $this->assertTrue(Storage::disk('public')->allFiles('logos') !== []);
        $this->assertTrue(Storage::disk('public')->allFiles('favicons') !== []);
        $this->get('/')->assertSee('My New Project');
    }

    public function test_admin_can_create_an_account_with_admin_privileges(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.accounts.store'), [
                'name' => 'New Administrator',
                'email' => 'new-admin@example.com',
                'password' => 'a-secure-password-123',
                'password_confirmation' => 'a-secure-password-123',
                'permissions' => [
                    'dashboard' => ['view' => '1'],
                    'accounts' => ['view' => '1', 'add' => '1'],
                ],
            ])
            ->assertRedirect(route('admin.accounts'));

        $this->assertDatabaseHas('users', [
            'email' => 'new-admin@example.com',
            'role_id' => null,
        ]);

        $this->post('/admin/login', [
            'email' => 'new-admin@example.com',
            'password' => 'a-secure-password-123',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_module_permissions_restrict_routes(): void
    {
        $user = User::factory()->create([
        ]);
        $user->syncPermissions(['dashboard' => ['view' => true]]);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('admin.accounts'))->assertForbidden();
    }

    public function test_account_creator_cannot_grant_permissions_they_do_not_have(): void
    {
        $user = User::factory()->create([
        ]);
        $user->syncPermissions(['accounts' => ['view' => true, 'add' => true]]);

        $this->actingAs($user)->post(route('admin.accounts.store'), [
            'name' => 'Escalated Account',
            'email' => 'escalated@example.com',
            'password' => 'a-secure-password-123',
            'password_confirmation' => 'a-secure-password-123',
            'permissions' => ['dashboard' => ['view' => '1']],
        ])->assertSessionHasErrors('permissions');

        $this->assertDatabaseMissing('users', ['email' => 'escalated@example.com']);
    }

    public function test_admin_can_edit_user_details_and_module_permissions(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->create();
        $user->syncPermissions(['dashboard' => ['view' => true]]);

        $this->actingAs($admin)->get(route('admin.accounts.edit', $user))->assertOk();

        $this->put(route('admin.accounts.update', $user), [
            'name' => 'Updated User',
            'email' => 'updated-user@example.com',
            'password' => '',
            'password_confirmation' => '',
            'permissions' => ['settings' => ['view' => '1']],
        ])->assertRedirect(route('admin.accounts'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated User',
            'email' => 'updated-user@example.com',
        ]);

        $user->refresh();
        $this->assertTrue($user->canAccess('settings', 'view'));
        $this->assertFalse($user->canAccess('dashboard', 'view'));
    }

    public function test_admin_can_delete_users_but_not_super_admin_or_their_own_account(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.accounts'))
            ->assertOk()
            ->assertSee('data-open-delete', false)
            ->assertSee('Delete user?', false);

        $this->actingAs($admin)->delete(route('admin.accounts.destroy', $user))
            ->assertRedirect(route('admin.accounts'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        $this->delete(route('admin.accounts.destroy', $admin))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $otherSuperAdmin = User::factory()->superAdmin()->create();
        $this->delete(route('admin.accounts.destroy', $otherSuperAdmin))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $otherSuperAdmin->id]);
    }

    public function test_user_password_can_be_shorter_than_twelve_characters(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('admin.accounts.store'), [
            'name' => 'Short Password User',
            'email' => 'short-password@example.com',
            'password' => 'pass',
            'password_confirmation' => 'pass',
        ])->assertRedirect(route('admin.accounts'));

        $user = User::where('email', 'short-password@example.com')->firstOrFail();
        $this->assertTrue(password_verify('pass', $user->password));
    }

    public function test_super_admin_account_is_hidden_from_permission_controlled_admins(): void
    {
        User::factory()->superAdmin()->create(['email' => 'root-admin@example.com']);
        $manager = User::factory()->create();
        $manager->syncPermissions(['accounts' => ['view' => true]]);

        $this->actingAs($manager)->get(route('admin.accounts'))
            ->assertOk()
            ->assertDontSee('root-admin@example.com');
    }

    public function test_super_admin_can_see_super_admin_accounts(): void
    {
        $root = User::factory()->superAdmin()->create();
        User::factory()->superAdmin()->create(['email' => 'visible-root@example.com']);
        $manager = User::factory()->create();
        $manager->syncPermissions(['accounts' => ['view' => true]]);

        $this->actingAs($manager)->get(route('admin.accounts'))->assertDontSee('visible-root@example.com');
        $this->actingAs($root)->get(route('admin.accounts'))->assertSee('visible-root@example.com');
    }

    public function test_admin_can_create_roles_and_assign_them_to_users(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Settings Editor',
            'permissions' => ['settings' => ['view' => '1', 'edit' => '1']],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::where('name', 'Settings Editor')->firstOrFail();
        $this->post(route('admin.accounts.store'), [
            'name' => 'Settings User',
            'email' => 'settings-user@example.com',
            'password' => 'pass',
            'password_confirmation' => 'pass',
            'role_id' => $role->id,
        ])->assertRedirect(route('admin.accounts'));

        $user = User::where('email', 'settings-user@example.com')->firstOrFail();
        $this->assertSame($role->id, $user->role_id);
        $this->assertTrue($user->canAccess('settings', 'view'));
        $this->assertTrue($user->canAccess('settings', 'edit'));
        $this->assertFalse($user->canAccess('accounts', 'view'));

        $this->get(route('admin.roles.edit', $role))->assertOk();
        $this->put(route('admin.roles.update', $role), [
            'name' => 'Dashboard Viewer',
            'permissions' => ['dashboard' => ['view' => '1']],
        ])->assertRedirect(route('admin.roles.index'));

        $user->refresh();
        $this->assertTrue($user->canAccess('dashboard', 'view'));
        $this->assertFalse($user->canAccess('settings', 'view'));

        $this->delete(route('admin.roles.destroy', $role))->assertStatus(409);

        $unusedRole = Role::create(['name' => 'Unused Role', 'is_super_admin' => false]);
        $this->delete(route('admin.roles.destroy', $unusedRole))
            ->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $unusedRole->id]);
    }

    public function test_signed_in_admin_can_update_their_own_profile(): void
    {
        $user = User::factory()->superAdmin()->create([
            'name' => 'Old Name',
            'email' => 'profile@example.com',
        ]);

        $this->actingAs($user)->get(route('admin.profile.edit'))->assertOk();
        $this->put(route('admin.profile.update'), [
            'name' => 'New Name',
            'email' => 'new-profile@example.com',
            'password' => 'updated',
            'password_confirmation' => 'updated',
        ])->assertRedirect(route('admin.profile.edit'));

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new-profile@example.com', $user->email);
        $this->assertTrue(password_verify('updated', $user->password));
    }
}
