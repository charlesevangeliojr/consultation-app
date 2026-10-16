<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ConsultationSchedulingTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    private function account(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['is_super_admin' => false]);
        $user = User::factory()->create(['email' => $email, 'role_id' => $role->id]);
        if ($user->isTeacher()) $this->createCompleteTeacherProfile($user, ['consultation_approved' => true]);
        return $user;
    }

    public function test_teacher_can_publish_availability_and_student_can_request_a_slot(): void
    {
        $teacher = $this->account('Teacher', 'teacher@example.com');
        $student = $this->account('Student', 'student@example.com');
        $date = today()->addDay()->toDateString();

        $this->actingAs($teacher)->post(route('consultations.availability.store'), [
            'date' => $date,
            'starts_at' => '10:00',
            'ends_at' => '11:00',
            'slot_minutes' => 30,
            'location' => 'Room 204',
        ])->assertRedirect(route('consultations.teacher.availability'));

        $availability = ConsultationAvailability::firstOrFail();
        $this->actingAs($student)->get(route('consultations.browse'))
            ->assertOk()->assertSee($teacher->name)->assertSee('10:00 AM');

        $this->post(route('consultations.book', $availability), [
            'starts_at' => Carbon::parse($date.' 10:00')->format('Y-m-d H:i:s'),
            'topic' => 'Course planning',
        ])->assertRedirect();

        $appointment = ConsultationAppointment::firstOrFail();
        $this->assertSame('pending', $appointment->status);
        $this->assertSame($teacher->id, $appointment->teacher_id);
        $this->assertSame($student->id, $appointment->student_id);

        $this->actingAs($student)->get(route('consultations.dashboard'))
            ->assertOk()->assertSee('Dashboard')->assertSee($teacher->name)->assertSee('Course planning');

        $this->actingAs($teacher)->get(route('consultations.teacher.requests'))
            ->assertOk()->assertSee($student->name)->assertSee('Course planning');
    }

    public function test_a_time_cannot_be_booked_twice(): void
    {
        $teacher = $this->account('Faculty Teacher', 'faculty@example.com');
        $student = $this->account('Student', 'student-one@example.com');
        $otherStudent = $this->account('Student', 'student-two@example.com');
        $date = today()->addDay()->toDateString();
        $availability = ConsultationAvailability::create([
            'teacher_id' => $teacher->id,
            'date' => $date,
            'starts_at' => '10:00',
            'ends_at' => '11:00',
            'slot_minutes' => 30,
        ]);
        $slot = Carbon::parse($date.' 10:00')->format('Y-m-d H:i:s');

        $this->actingAs($student)->post(route('consultations.book', $availability), ['starts_at' => $slot, 'topic' => 'Course planning'])->assertRedirect();
        $this->actingAs($otherStudent)->post(route('consultations.book', $availability), ['starts_at' => $slot, 'topic' => 'Course planning'])->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('consultation_appointments', 1);
    }
}
