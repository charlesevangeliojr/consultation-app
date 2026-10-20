<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApprovalBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_counts_unapproved_teachers_and_updates_after_approval(): void
    {
        $role = Role::firstOrCreate(['name' => 'Teacher']);
        $pending = User::factory()->create(['role_id' => $role->id]);
        $pending->teacher()->create(['consultation_approved' => false]);
        $approved = User::factory()->create(['role_id' => $role->id]);
        $approved->teacher()->create(['consultation_approved' => true]);
        $this->actingAs(User::factory()->superAdmin()->create());
        $response = $this->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(2, substr_count($response->getContent(), 'data-teacher-approval-count="1"'));
        $this->put(route('admin.accounts.school-profile', $pending), ['consultation_approved' => 1])->assertSessionHasNoErrors();
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('data-teacher-approval-count=', false);
        $this->put(route('admin.accounts.school-profile', $pending), ['consultation_approved' => 0])->assertSessionHasNoErrors();
        $this->get(route('admin.accounts'))->assertOk()->assertSee('data-teacher-approval-count="1"', false);
    }

    public function test_admin_without_accounts_access_does_not_receive_the_count(): void
    {
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $teacher->teacher()->create(['consultation_approved' => false]);
        $viewer = User::factory()->create();
        $viewer->syncPermissions(['dashboard' => ['view' => true]]);
        $this->actingAs($viewer)->get(route('admin.dashboard'))->assertOk()->assertDontSee('data-teacher-approval-count=', false);
    }

    public function test_account_groups_and_search_keep_pending_teachers_highlighted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $teacher = User::factory()->create(['name' => 'Pending Teacher', 'role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $teacher->teacher()->create(['consultation_approved' => false]);
        $approved = User::factory()->create(['role_id' => $teacher->role_id]);
        $approved->teacher()->create(['consultation_approved' => true]);
        $student = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
        $this->actingAs($admin);
        foreach (['admins' => [$admin->id], 'teachers' => [$teacher->id, $approved->id], 'students' => [$student->id], 'pending' => [$teacher->id]] as $group => $ids) {
            $response = $this->get(route('admin.accounts', ['group' => $group]))->assertOk();
            $this->assertEqualsCanonicalizing($ids, $response->viewData('users')->pluck('id')->all());
        }
        $this->get(route('admin.accounts', ['group' => 'pending', 'search' => 'Pending Teacher']))->assertOk()
            ->assertSee('class="account-needs-approval"', false)->assertSee('Needs scheduling approval')
            ->assertSee('name="group" value="pending"', false);
        $this->put(route('admin.accounts.school-profile', $teacher), ['consultation_approved' => 1])->assertSessionHasNoErrors();
        $response = $this->get(route('admin.accounts', ['group' => 'pending']))->assertOk();
        $this->assertSame(0, $response->viewData('users')->total());
    }
}
