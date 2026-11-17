<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    public function test_student_has_settings_page_in_navigation_and_can_save_reminders(): void
    {
        $student = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);

        $response = $this->actingAs($student)->get(route('consultations.student.settings'))
            ->assertOk()
            ->assertSee('id="student-settings"', false)
            ->assertSee('Consultation reminder')
            ->assertSee('Notifications on this device')
            ->assertSee('Change password')
            ->assertSee(route('consultations.student.settings'));

        $this->assertMatchesRegularExpression(
            '/<a[^>]*href="'.preg_quote(route('consultations.student.settings'), '/').'"[^>]*aria-current="page"/',
            $response->getContent()
        );

        $this->put(route('notifications.reminder.update'), [
            'minutes_before' => 45,
            'return_to' => 'consultations.student.settings',
        ])->assertRedirect(route('consultations.student.settings'));

        $this->assertDatabaseHas('student_reminder_settings', ['user_id' => $student->id, 'minutes_before' => 45]);
    }

    public function test_student_and_teacher_can_update_their_passwords_from_settings(): void
    {
        $student = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'Student'])->id,
            'password' => 'OldPass!123',
        ]);

        $this->actingAs($student)->put(route('consultations.settings.password'), [
            'current_password' => 'WrongPass!123',
            'password' => 'NewPass!456',
            'password_confirmation' => 'NewPass!456',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($student)->put(route('consultations.settings.password'), [
            'current_password' => 'OldPass!123',
            'password' => 'NewPass!456',
            'password_confirmation' => 'NewPass!456',
        ])->assertRedirect(route('consultations.student.settings'))->assertSessionHas('status', 'Password updated successfully.');

        $this->assertTrue(Hash::check('NewPass!456', $student->fresh()->password));

        $teacher = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id,
            'password' => 'TeachOld!123',
        ]);
        $this->createCompleteTeacherProfile($teacher, ['consultation_approved' => true]);

        $this->actingAs($teacher)->get(route('consultations.teacher.preferences'))
            ->assertOk()
            ->assertSee('Change password')
            ->assertSee('Notifications on this device');

        $this->actingAs($teacher)->put(route('consultations.settings.password'), [
            'current_password' => 'TeachOld!123',
            'password' => 'TeachNew!789',
            'password_confirmation' => 'TeachNew!789',
        ])->assertRedirect(route('consultations.teacher.preferences'))->assertSessionHas('status', 'Password updated successfully.');

        $this->assertTrue(Hash::check('TeachNew!789', $teacher->fresh()->password));
    }

    public function test_roles_cannot_cross_access_settings_pages(): void
    {
        $student = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $this->createCompleteTeacherProfile($teacher, ['consultation_approved' => true]);
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($teacher)->get(route('consultations.student.settings'))->assertForbidden();
        $this->actingAs($student)->get(route('consultations.teacher.preferences'))->assertForbidden();
        $this->actingAs($admin)->put(route('consultations.settings.password'), [
            'current_password' => 'password',
            'password' => 'NewAdmin!123',
            'password_confirmation' => 'NewAdmin!123',
        ])->assertForbidden();
    }
}
