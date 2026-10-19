<?php

namespace Tests\Feature;

use App\Models\ConsultationAvailability;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTeacherApprovalTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    public function test_admin_approves_and_revokes_teacher_scheduling_from_account_controls(): void
    {
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $this->createCompleteTeacherProfile($teacher, ['consultation_approved' => false]);
        $admin = User::factory()->superAdmin()->create();
        $window = ['date' => today()->addDay()->toDateString(), 'starts_at' => '13:00', 'ends_at' => '14:00'];
        $this->actingAs($teacher)->post(route('consultations.availability.store'), $window)->assertForbidden();
        $this->assertDatabaseCount('consultation_availabilities', 0);
        $this->actingAs($admin)->get(route('admin.accounts', ['search' => $teacher->email]))->assertOk()
            ->assertSee('Approve scheduling')->assertSee('Not approved');
        $this->get(route('admin.consultations'))->assertOk()->assertSee('Approve scheduling');
        $this->from(route('admin.accounts'))->put(route('admin.accounts.school-profile', $teacher), ['consultation_approved' => 1])
            ->assertRedirect(route('admin.accounts'))->assertSessionHas('status', 'Teacher scheduling approved. They can publish availability after completing their profile.');
        $this->actingAs($teacher)->post(route('consultations.availability.store'), $window)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('consultation_availabilities', 1);
        $this->actingAs($admin)->get(route('admin.accounts', ['search' => $teacher->email]))->assertOk()->assertSee('Revoke scheduling');
        $this->put(route('admin.accounts.school-profile', $teacher), ['consultation_approved' => 0])->assertSessionHasNoErrors();
        $this->actingAs($teacher)->post(route('consultations.availability.store'), $window + ['location' => 'New room'])->assertForbidden();
        $this->assertDatabaseCount('consultation_availabilities', 1);
        $this->assertNull(ConsultationAvailability::first()->cancelled_at);
    }

    public function test_read_only_admins_and_teachers_cannot_grant_scheduling_permission(): void
    {
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $this->createCompleteTeacherProfile($teacher, ['consultation_approved' => false]);
        $manager = User::factory()->create();
        $manager->syncPermissions(['accounts' => ['view' => true], 'dashboard' => ['view' => true]]);
        $this->actingAs($manager)->get(route('admin.accounts', ['search' => $teacher->email]))->assertOk()
            ->assertSee('Scheduling permission')->assertDontSee('Approve scheduling');
        $this->put(route('admin.accounts.school-profile', $teacher), ['consultation_approved' => 1])->assertForbidden();
        $this->actingAs($teacher)->put(route('admin.accounts.school-profile', $teacher), ['consultation_approved' => 1])->assertForbidden();
        $this->assertFalse($teacher->teacher->fresh()->consultation_approved);
    }
}
