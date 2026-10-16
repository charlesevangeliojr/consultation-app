<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeacherProfileCompletionTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    private function teacher(): User
    {
        $role = Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $user->teacher()->create();
        return $user;
    }

    private function details(User $user): array
    {
        return $this->teacherProfileDetails($user);
    }

    public function test_incomplete_teacher_is_redirected_to_profile_from_every_workspace_page(): void
    {
        $teacher = $this->teacher();
        $this->assertFalse($teacher->teacher->hasCompleteProfile());
        $this->assertCount(6, $teacher->teacher->missingProfileFields());
        $this->actingAs($teacher);
        foreach (['home', 'teacher', 'teacher.requests', 'teacher.availability', 'teacher.preferences'] as $page) {
            $this->get(route('consultations.'.$page))->assertRedirect(route('consultations.teacher-profile.edit'))
                ->assertSessionHas('profile_required');
        }
        $this->get(route('consultations.teacher-profile.edit'))->assertOk()
            ->assertSee('Setup required')->assertSee('Profile checklist')->assertSee('25%')
            ->assertSee('teacher-profile.css')->assertSee('teacher-profile.js')
            ->assertDontSee('Back to calendar');
    }

    public function test_direct_requests_cannot_bypass_profile_completion(): void
    {
        $teacher = $this->teacher();
        $teacher->teacher->update(['consultation_approved' => true]);
        $this->actingAs($teacher)->postJson(route('consultations.availability.store'), [
            'date' => today()->addDay()->toDateString(), 'starts_at' => '09:00', 'ends_at' => '11:00',
            'profile_complete' => true,
        ])->assertForbidden()->assertJsonPath('profile_url', route('consultations.teacher-profile.edit'))
            ->assertJsonStructure(['missing_fields' => ['contact_number', 'birthday', 'gender', 'address', 'profile_photo']]);
        $this->putJson(route('consultations.teacher.settings'), ['slot_minutes' => 20, 'auto_approve_bookings' => true, 'show_student_names' => true])->assertForbidden();
        $this->assertDatabaseCount('consultation_availabilities', 0);
        $this->assertFalse($teacher->teacher->fresh()->auto_approve_bookings);
    }

    public function test_every_personal_field_and_initial_photo_are_required(): void
    {
        $teacher = $this->teacher();
        $this->actingAs($teacher)->from(route('consultations.teacher-profile.edit'))
            ->put(route('consultations.teacher-profile.update'), ['name' => $teacher->name, 'email' => $teacher->email])
            ->assertRedirect(route('consultations.teacher-profile.edit'))
            ->assertSessionHasErrors(['phone_country', 'phone_number', 'birthday', 'gender', 'region', 'city', 'barangay', 'profile_photo', 'consultation_offerings']);
        $this->put(route('consultations.teacher-profile.update'), $this->details($teacher))
            ->assertSessionHasErrors('profile_photo');
        $this->assertFalse($teacher->teacher->fresh()->hasCompleteProfile());
    }

    public function test_valid_profile_unlocks_workspace_without_granting_admin_approval(): void
    {
        Storage::fake('public');
        $teacher = $this->teacher();
        $this->actingAs($teacher)->put(route('consultations.teacher-profile.update'), $this->details($teacher) + [
            'profile_photo' => UploadedFile::fake()->image('portrait.png'), 'consultation_approved' => true,
        ])->assertSessionHasNoErrors()->assertRedirect(route('consultations.teacher-profile.edit'));
        $profile = $teacher->teacher->fresh();
        $this->assertTrue($profile->hasCompleteProfile());
        $this->assertFalse($profile->consultation_approved);
        Storage::disk('public')->assertExists($profile->profile_photo_path);
        $this->get(route('consultations.teacher'))->assertOk();
        $this->get(route('consultations.teacher-profile.edit'))->assertOk()->assertSee('Profile complete')->assertSee('100%');
        // Later edits do not require re-uploading the saved photo, but cannot erase a required detail.
        $this->put(route('consultations.teacher-profile.update'), $this->details($teacher))->assertSessionHasNoErrors();
        $this->put(route('consultations.teacher-profile.update'), array_replace($this->details($teacher), ['barangay' => '   ']))->assertSessionHasErrors('barangay');
        $this->assertTrue($profile->fresh()->hasCompleteProfile());
    }

    public function test_invalid_phone_and_future_birthday_do_not_count_as_complete(): void
    {
        $teacher = $this->teacher();
        $teacher->teacher->update(collect($this->details($teacher))->except(['name', 'email', 'phone_number', 'subject_ids'])->all() + ['profile_photo_path' => 'teacher-profiles/test.png']);
        $teacher->teacher->update(['contact_number' => 'abcdefg', 'birthday' => today()->addDay()]);
        $missing = $teacher->teacher->fresh()->missingProfileFields();
        $this->assertArrayHasKey('contact_number', $missing);
        $this->assertArrayHasKey('birthday', $missing);
        $this->actingAs($teacher)->get(route('consultations.teacher'))->assertRedirect(route('consultations.teacher-profile.edit'));
    }

    public function test_incomplete_teacher_can_still_cancel_availability_reject_requests_and_sign_out(): void
    {
        $teacher = $this->teacher();
        $period = ConsultationAvailability::create(['teacher_id' => $teacher->id, 'date' => today()->addDay()->toDateString(), 'starts_at' => '09:00', 'ends_at' => '11:00', 'slot_minutes' => 30]);
        $booking = ConsultationAppointment::create(['teacher_id' => $teacher->id, 'student_id' => User::factory()->create()->id,
            'availability_id' => $period->id, 'starts_at' => today()->addDay()->setTime(9, 0), 'ends_at' => today()->addDay()->setTime(9, 30), 'status' => 'pending']);
        $this->actingAs($teacher)->patchJson(route('consultations.appointments.update', $booking), ['status' => 'confirmed'])->assertForbidden();
        $this->patch(route('consultations.appointments.update', $booking), ['status' => 'rejected'])->assertRedirect();
        $this->assertSame('rejected', $booking->fresh()->status);
        $this->patch(route('consultations.availability.cancel', $period))->assertRedirect();
        $this->assertNotNull($period->fresh()->cancelled_at);
        $this->post(route('consultations.logout'))->assertRedirect(route('portal.login'));
        $this->assertGuest();
    }
}
