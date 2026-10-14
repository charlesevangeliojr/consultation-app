<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_choice_and_role_forms_are_available(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Choose your account type');
        $this->get(route('register', ['role' => 'student']))->assertOk()->assertSee('Create your account')->assertSee('Contact number')->assertSee('LRN')->assertSee('list="region-options"', false)->assertSee('list="barangay-options"', false);
        $this->get(route('register', ['role' => 'teacher']))->assertOk()->assertSee('Create your account');
    }

    public function test_student_can_register_and_is_given_a_student_profile(): void
    {
        $grade = GradeLevel::create(['name' => 'Grade 11']);
        $section = Section::create(['name' => 'Einstein', 'grade_level_id' => $grade->id]);
        $response = $this->post(route('register.store'), [
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
            'first_name' => 'Sam',
            'middle_name' => 'A.',
            'last_name' => 'Student',
            'suffix' => 'Jr.',
            'email' => 'sam@example.com',
            'role' => 'student',
            'contact_number' => '9171234567',
            'birthday' => '2005-04-10',
            'gender' => 'Female',
            'house_number' => '12',
            'barangay' => 'Barangay One',
            'city' => 'Sample City',
            'province' => 'Sample Province',
            'postal_code' => '1234',
            'lrn' => '123456789012',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'sam@example.com')->firstOrFail();

        $response->assertRedirect(route('consultations.home'));
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->isStudent());
        $this->assertSame('Sam A. Student Jr.', $user->name);
        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'contact_number' => '+639171234567',
            'birthday' => '2005-04-10 00:00:00',
            'gender' => 'Female',
            'address' => '12, Barangay One, Sample City, Sample Province, 1234',
            'lrn' => '123456789012',
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
        ]);
        $this->assertDatabaseMissing('teachers', ['user_id' => $user->id]);
    }

    public function test_registration_returns_to_the_earliest_step_with_errors(): void
    {
        $this->withSession([
            'errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag([
                'email' => ['The email has already been taken.'],
                'lrn' => ['The lrn must be 12 digits.'],
                'password' => ['The password must be at least 8 characters.'],
            ])),
        ])->get(route('register', ['role' => 'student']))
            ->assertOk()
            ->assertSee('data-initial-step="1"', false)
            ->assertSee('The email has already been taken.');
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
            ->assertSessionHasErrors(['email', 'contact_number', 'birthday', 'lrn', 'password']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('students', 0);
    }

    public function test_teacher_can_register_and_cannot_assign_admin_role(): void
    {
        $subject = Subject::create(['name' => 'Mathematics']);
        $response = $this->post(route('register.store'), [
            'subject_ids' => [$subject->id],
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
        $this->assertSame([$subject->id], $user->teacher->subjects->pluck('id')->all());
    }

    public function test_registration_requires_school_information_and_matching_section(): void
    {
        $grade = GradeLevel::create(['name' => 'Grade 10']);
        $otherGrade = GradeLevel::create(['name' => 'Grade 12']);
        $section = Section::create(['name' => 'Newton', 'grade_level_id' => $otherGrade->id]);
        $this->post(route('register.store'), [
            'role' => 'student',
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
        ])->assertSessionHasErrors('section_id');

        $this->post(route('register.store'), ['role' => 'teacher'])->assertSessionHasErrors('subject_ids');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_created_options_appear_on_registration_forms(): void
    {
        $grade = GradeLevel::create(['name' => 'Grade Eleven']);
        Section::create(['name' => 'Section Einstein', 'grade_level_id' => $grade->id]);
        Subject::create(['name' => 'Applied Mathematics']);
        $this->get(route('register', ['role' => 'student']))->assertSee('Grade Eleven')->assertSee('Section Einstein');
        $this->get(route('register', ['role' => 'teacher']))->assertSee('Applied Mathematics');
    }
}
