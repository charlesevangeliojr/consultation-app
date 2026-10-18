<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\AccountActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountActivityTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        $role = Role::firstOrCreate(['name' => 'Student'], ['is_super_admin' => false]);
        $user = User::factory()->create(['role_id' => $role->id, 'password' => 'password123']);
        $user->student()->create(['lrn' => str_pad((string) $user->id, 12, '0', STR_PAD_LEFT)]);

        return $user;
    }

    public function test_login_and_authenticated_heartbeat_record_activity_but_guests_cannot_ping(): void
    {
        $user = $this->student();
        $this->post(route('account.activity'))->assertRedirect(route('portal.login'));
        $this->assertNull($user->fresh()->last_seen_at);

        $this->post(route('portal.login.store'), ['login' => $user->student->lrn, 'password' => 'password123'])
            ->assertRedirect(route('consultations.home'));
        $this->assertTrue($user->fresh()->isOnline());

        $this->travel(2)->minutes();
        $this->post(route('account.activity'))->assertNoContent();
        $this->assertTrue($user->fresh()->last_seen_at->equalTo(now()->startOfSecond()));
    }

    public function test_online_presence_expires_after_five_minutes_and_logout_sets_offline(): void
    {
        $user = $this->student();
        $this->actingAs($user)->post(route('account.activity'))->assertNoContent();
        $this->assertTrue($user->fresh()->isOnline());
        $this->travel(5)->minutes();
        $this->assertFalse($user->fresh()->isOnline());
        $this->post(route('account.activity'))->assertNoContent();
        $this->assertTrue($user->fresh()->isOnline());
        $this->post(route('consultations.logout'))->assertRedirect(route('portal.login'));
        $this->assertFalse($user->fresh()->isOnline());
        $this->assertNotNull($user->fresh()->last_seen_at);
        $this->assertNotNull($user->fresh()->last_logged_out_at);
    }

    public function test_two_year_rule_uses_last_seen_and_monitoring_baseline(): void
    {
        $this->travelTo(now()->startOfSecond());
        $dormant = $this->student();
        $dormant->forceFill(['inactivity_reference_at' => now()->subYears(3), 'last_seen_at' => now()->subYears(2)])->save();
        $recent = $this->student();
        $recent->forceFill(['inactivity_reference_at' => now()->subYears(3), 'last_seen_at' => now()->subYears(2)->addSecond()])->save();
        $neverSeen = $this->student();
        $neverSeen->forceFill(['inactivity_reference_at' => now()->subYears(2), 'last_seen_at' => null])->save();
        $newBaseline = $this->student();
        $newBaseline->forceFill(['created_at' => now()->subYears(4), 'inactivity_reference_at' => now(), 'last_seen_at' => null])->save();
        $teacherRole = Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
        $teacher = User::factory()->create(['role_id' => $teacherRole->id]);
        $teacher->forceFill(['inactivity_reference_at' => now()->subYears(3)])->save();
        $teacher->student()->create(['status' => 'active']);

        $this->artisan('accounts:deactivate-inactive')->expectsOutput('Marked 2 student accounts inactive.')->assertSuccessful();
        $this->assertSame('inactive', $dormant->student->fresh()->status);
        $this->assertSame('inactive', $neverSeen->student->fresh()->status);
        $this->assertSame('active', $recent->student->fresh()->status);
        $this->assertSame('active', $newBaseline->student->fresh()->status);
        $this->assertSame('active', $teacher->student->fresh()->status);
    }

    public function test_admin_can_monitor_and_change_status_without_school_profile_fields(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = $this->student();
        $user->forceFill(['last_seen_at' => now()])->save();
        $this->actingAs($admin)->get(route('admin.accounts'))->assertOk()
            ->assertSee('Account status')->assertSee('Online status')->assertSee('Last seen')
            ->assertSee('Online')->assertSee('2 years')->assertSee($user->student->lrn);
        $this->get(route('admin.accounts.edit', $user))->assertOk()
            ->assertSee('Account activity &amp; status', false)->assertSee('Save account status');
        $this->patch(route('admin.accounts.status', $user), ['status' => 'inactive'])
            ->assertSessionHasNoErrors();
        $this->assertSame('inactive', $user->student->fresh()->status);
        $this->patch(route('admin.accounts.status', $user), ['status' => 'invalid'])
            ->assertSessionHasErrors('status');
    }

    public function test_reactivating_a_dormant_account_resets_its_inactivity_period(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = $this->student();
        $user->forceFill(['inactivity_reference_at' => now()->subYears(4), 'last_seen_at' => now()->subYears(3)])->save();
        AccountActivity::deactivateDormantStudents();
        $this->assertSame('inactive', $user->student->fresh()->status);
        $this->actingAs($admin)->patch(route('admin.accounts.status', $user), ['status' => 'active'])
            ->assertSessionHasNoErrors();
        AccountActivity::deactivateDormantStudents();
        $this->assertSame('active', $user->student->fresh()->status);
        $this->assertTrue($user->fresh()->inactivity_reference_at->isToday());
    }

    public function test_students_and_view_only_admins_cannot_change_status(): void
    {
        $user = $this->student();
        $this->actingAs($user)->patch(route('admin.accounts.status', $user), ['status' => 'inactive'])
            ->assertForbidden();
        $viewer = User::factory()->create();
        $viewer->syncPermissions(['accounts' => ['view' => true]]);
        $this->actingAs($viewer)->patch(route('admin.accounts.status', $user), ['status' => 'inactive'])
            ->assertForbidden();
        $this->assertSame('active', $user->student->fresh()->status);
    }

    public function test_manual_inactive_status_is_preserved_when_student_returns_online(): void
    {
        $user = $this->student();
        $user->student->update(['status' => 'inactive']);
        $this->actingAs($user)->post(route('account.activity'))->assertNoContent();
        $this->assertTrue($user->fresh()->isOnline());
        $this->assertSame('inactive', $user->student->fresh()->status);
    }
}
