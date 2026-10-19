<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\GradeLevel;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_account_type_selection_has_a_back_to_home_link(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Back to home')
            ->assertSee('href="'.url('/').'"', false);
    }
    use RefreshDatabase;

    public function test_registration_choice_and_role_forms_are_available(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Choose your account type');
        $this->get(route('register', ['role' => 'student']))->assertOk()->assertSee('Create your account')->assertSee('Contact number')->assertSee('LRN')->assertDontSee('name="email"', false)->assertSeeInOrder(['name="lrn"', 'data-form-step="2"'], false)->assertSee('list="region-options"', false)->assertSee('list="barangay-options"', false);
        $this->get(route('register', ['role' => 'teacher']))->assertOk()->assertSee('Create your account')->assertDontSee('name="subject_ids[]"', false);
    }

    public function test_student_can_register_and_is_given_a_student_profile(): void
    {
        $grade = GradeLevel::where('name', 'Grade 11')->firstOrFail();
        $section = Section::create(['name' => 'Einstein', 'grade_level_id' => $grade->id]);
        $response = $this->post(route('register.store'), [
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
            'first_name' => 'Sam',
            'middle_name' => 'A.',
            'last_name' => 'Student',
            'suffix' => 'Jr.',
            'role' => 'student',
            'contact_number' => '9171234567',
            'birthday' => '2005-04-10',
            'gender' => 'Female',
            'house_number' => '12',
            'region' => 'Sample Region',
            'barangay' => 'Barangay One',
            'city' => 'Sample City',
            'province' => 'Sample Province',
            'lrn' => '123456789012',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::whereHas('student', fn ($query) => $query->where('lrn', '123456789012'))->firstOrFail();
        $this->assertNull($user->email);

        $response->assertRedirect(route('consultations.home'));
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->isStudent());
        $this->assertSame('Sam A. Student Jr.', $user->name);
        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'contact_number' => '+639171234567',
            'birthday' => '2005-04-10 00:00:00',
            'gender' => 'Female',
            'address' => '12, Sample Region, Barangay One, Sample City, Sample Province',
            'house_number' => '12',
            'region' => 'Sample Region',
            'province' => 'Sample Province',
            'city' => 'Sample City',
            'barangay' => 'Barangay One',
            'lrn' => '123456789012',
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
        ]);
        $this->assertDatabaseMissing('teachers', ['user_id' => $user->id]);
        $this->get(route('consultations.student-profile.edit'))->assertOk()
            ->assertSee('value="Sample Region"', false)
            ->assertSee('value="Sample Province"', false)
            ->assertSee('value="Sample City"', false)
            ->assertSee('value="Barangay One"', false)
            ->assertSee('value="12"', false)
            ->assertDontSee('name="postal_code"', false);
    }

    public function test_registration_returns_to_the_earliest_step_with_errors(): void
    {
        $this->withSession([
            'errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag([
                'lrn' => ['The lrn must be 12 digits.'],
                'password' => ['The password must be at least 8 characters.'],
            ])),
        ])->get(route('register', ['role' => 'student']))
            ->assertOk()
            ->assertSee('data-initial-step="1"', false)
            ->assertSee('The lrn must be 12 digits.');
    }

    public function test_invalid_registration_does_not_create_an_account(): void
    {
        $this->from(route('register', ['role' => 'student']))->post(route('register.store'), [
            'first_name' => 'Sam',
            'last_name' => 'Student',
            'email' => 'not-an-email',
            'role' => 'student',
            'contact_number' => '123',
            'birthday' => today()->toDateString(),
            'gender' => 'Female',
            'lrn' => '123',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertRedirect(route('register', ['role' => 'student']))
            ->assertSessionHasErrors(['contact_number', 'birthday', 'lrn', 'password']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('students', 0);
    }

    public function test_teacher_can_register_and_cannot_assign_admin_role(): void
    {
        $response = $this->post(route('register.store'), [
            'consultation_approved' => true,
            'first_name' => 'Taylor',
            'last_name' => 'Teacher',
            'email' => 'taylor@example.com',
            'role' => 'teacher',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $user = User::where('email', 'taylor@example.com')->firstOrFail();

        $response->assertRedirect(route('consultations.home'));
        $this->assertTrue($user->isTeacher());
        $this->assertDatabaseHas('teachers', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('students', ['user_id' => $user->id]);
        $this->assertFalse(Role::find($user->role_id)->is_super_admin);
        $this->assertFalse($user->teacher->consultation_approved);
        $this->assertFalse($user->teacher->subjects()->exists());
    }

    public function test_registration_requires_school_information_and_matching_section(): void
    {
        $grade = GradeLevel::where('name', 'Grade 10')->firstOrFail();
        $otherGrade = GradeLevel::where('name', 'Grade 12')->firstOrFail();
        $section = Section::create(['name' => 'Newton', 'grade_level_id' => $otherGrade->id]);
        $this->post(route('register.store'), [
            'role' => 'student',
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
        ])->assertSessionHasErrors('section_id');

        $this->post(route('register.store'), ['role' => 'teacher'])->assertSessionHasErrors(['email', 'password'])->assertSessionDoesntHaveErrors('subject_ids');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_created_options_appear_on_registration_forms(): void
    {
        $grade = GradeLevel::where('name', 'Grade 11')->firstOrFail();
        Section::create(['name' => 'Section Einstein', 'grade_level_id' => $grade->id]);
        $this->get(route('register', ['role' => 'student']))->assertSee('Grade 11')->assertSee('Section Einstein');
        $this->get(route('register', ['role' => 'teacher']))->assertDontSee('Subjects you handle');
    }
}
