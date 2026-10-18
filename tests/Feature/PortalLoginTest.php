<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_sign_in_with_lrn_and_password_without_email(): void
    {
        $user = $this->student();

        $this->post(route('portal.login.store'), ['login' => '012345678901', 'password' => 'password123'])
            ->assertRedirect(route('consultations.home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_student_cannot_sign_in_with_email_or_wrong_password_or_unknown_lrn(): void
    {
        $user = $this->student();
        $user->update(['email' => 'student@example.com']);

        foreach ([['student@example.com', 'password123'], ['012345678901', 'incorrect'], ['999999999999', 'password123']] as [$login, $password]) {
            $this->post(route('portal.login.store'), compact('login', 'password'))
                ->assertSessionHasErrors('login');
            $this->assertGuest();
        }
    }

    public function test_teacher_can_still_sign_in_with_email_and_password(): void
    {
        $role = Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
        $user = User::factory()->create(['email' => 'teacher@example.com', 'role_id' => $role->id, 'password' => 'password123']);

        $this->post(route('portal.login.store'), ['login' => 'teacher@example.com', 'password' => 'password123'])
            ->assertRedirect(route('consultations.home'));
        $this->assertAuthenticatedAs($user);
    }

    private function student(): User
    {
        $role = Role::firstOrCreate(['name' => 'Student'], ['is_super_admin' => false]);
        $user = User::factory()->create(['email' => null, 'role_id' => $role->id, 'password' => 'password123']);
        Student::create(['user_id' => $user->id, 'lrn' => '012345678901']);

        return $user;
    }
}
