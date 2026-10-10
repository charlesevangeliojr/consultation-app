<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
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
        $response = $this->post(route('register.store'), [
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
        ]);
        $this->assertDatabaseMissing('teachers', ['user_id' => $user->id]);
    }

    public function test_teacher_can_register_and_cannot_assign_admin_role(): void
    {
        $response = $this->post(route('register.store'), [
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
    }
}
