<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposedConsultationRequestTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    private function teacher(bool $automatic = false): User
    {
        $role = Role::firstOrCreate(['name' => 'Teacher']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $this->createCompleteTeacherProfile($user, ['consultation_approved' => true, 'max_consultation_minutes' => 60, 'auto_approve_bookings' => $automatic]);
        return $user;
    }

    private function student(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
    }

    private function propose(User $student, User $teacher, array $overrides = [])
    {
        return $this->actingAs($student)->post(route('consultations.request-time', $teacher), $overrides + [
            'date' => today()->addDay()->toDateString(), 'starts_at' => '13:00', 'ends_at' => '14:00', 'topic' => 'Private proposed reason',
        ]);
    }

    public function test_requests_without_availability_always_need_manual_approval_and_render_safely(): void
    {
        $teacher = $this->teacher(true);
        $student = $this->student();
        $this->actingAs($student)->get(route('consultations.browse'))->assertOk()->assertSee('Request another time')->assertSee('Send time request');
        $this->propose($student, $teacher)->assertSessionHasNoErrors()->assertRedirect(route('consultations.dashboard'));
        $booking = ConsultationAppointment::firstOrFail();
        $this->assertTrue($booking->is_proposed);
        $this->assertNull($booking->availability_id);
        $this->assertSame('pending', $booking->status);
        $this->assertDatabaseCount('consultation_availabilities', 0);
        $this->get(route('consultations.dashboard'))->assertOk()->assertSee('Awaiting teacher approval');
        $this->actingAs($teacher)->get(route('consultations.teacher.requests'))->assertOk()->assertSee('Student-proposed time');
        $this->get(route('consultations.teacher', ['month' => $booking->starts_at->format('Y-m')]))->assertOk();
        $this->patch(route('consultations.appointments.update', $booking), ['status' => 'confirmed'])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_accepting_one_proposal_rejects_other_overlapping_proposals_but_not_adjacent_ones(): void
    {
        $teacher = $this->teacher();
        $this->propose($this->student(), $teacher)->assertSessionHasNoErrors();
        $first = ConsultationAppointment::firstOrFail();
        $this->propose($this->student(), $teacher, ['starts_at' => '13:30', 'ends_at' => '14:30'])->assertSessionHasNoErrors();
        $second = ConsultationAppointment::latest('id')->firstOrFail();
        $this->propose($this->student(), $teacher, ['starts_at' => '14:00', 'ends_at' => '14:30'])->assertSessionHasNoErrors();
        $third = ConsultationAppointment::latest('id')->firstOrFail();
        $this->actingAs($teacher)->patch(route('consultations.appointments.update', $first), ['status' => 'confirmed'])->assertSessionHasNoErrors();
        $this->assertSame('time_taken', $second->fresh()->rejection_reason);
        $this->assertSame('rejected', $second->fresh()->status);
        $this->assertSame('pending', $third->fresh()->status);
        $this->actingAs($second->student)->get(route('consultations.dashboard'))->assertOk()->assertSee('another accepted consultation took this time');
    }

    public function test_start_time_expiry_runs_in_command_and_on_portal_access_and_does_not_reject_confirmed_bookings(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $this->propose($student, $teacher)->assertSessionHasNoErrors();
        $booking = ConsultationAppointment::firstOrFail();
        $this->travelTo($booking->starts_at);
        $this->artisan('consultations:reject-stale-requests')->assertSuccessful();
        $this->assertSame('expired', $booking->fresh()->rejection_reason);
        $this->actingAs($teacher)->patch(route('consultations.appointments.update', $booking), ['status' => 'confirmed'])->assertSessionHasErrors('status');
        $this->travelBack();
        $this->propose($student, $teacher)->assertSessionHasNoErrors();
        $next = ConsultationAppointment::latest('id')->firstOrFail();
        $this->travelTo($next->starts_at);
        $this->get(route('consultations.dashboard'))->assertOk()->assertSee('start time arrived before teacher approval');
        $this->assertSame('rejected', $next->fresh()->status);
        $this->travelBack();
        $this->propose($student, $teacher)->assertSessionHasNoErrors();
        $confirmed = ConsultationAppointment::latest('id')->firstOrFail();
        $confirmed->update(['status' => 'confirmed']);
        $this->travelTo($confirmed->starts_at);
        $this->artisan('consultations:reject-stale-requests')->assertSuccessful();
        $this->assertSame('confirmed', $confirmed->fresh()->status);
    }

    public function test_students_cannot_overlap_requests_across_teachers_and_invalid_ranges_are_rejected(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        foreach ([['ends_at' => '14:01'], ['ends_at' => '12:00'], ['date' => today()->subDay()->toDateString()], ['starts_at' => '13:00:30'], ['topic' => '']] as $invalid) {
            $this->propose($student, $teacher, $invalid)->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('consultation_appointments', 0);
        $this->propose($student, $teacher)->assertSessionHasNoErrors();
        $otherTeacher = $this->teacher();
        $this->propose($student, $otherTeacher)->assertSessionHasErrors('starts_at');
        $this->propose($student, $otherTeacher, ['starts_at' => '14:00', 'ends_at' => '14:30'])->assertSessionHasNoErrors();
        $teacher->teacher->update(['consultation_approved' => false]);
        $this->propose($this->student(), $teacher)->assertForbidden();
        $this->propose($otherTeacher, $otherTeacher)->assertForbidden();
    }

    public function test_automatic_published_booking_rejects_overlapping_proposal_and_blocks_new_proposals(): void
    {
        $teacher = $this->teacher(true);
        $this->propose($this->student(), $teacher)->assertSessionHasNoErrors();
        $proposal = ConsultationAppointment::firstOrFail();
        $period = ConsultationAvailability::create(['teacher_id' => $teacher->id, 'date' => today()->addDay(), 'starts_at' => '13:00', 'ends_at' => '17:00', 'slot_minutes' => 30]);
        $this->actingAs($this->student())->post(route('consultations.book', $period), ['starts_at' => '13:00', 'ends_at' => '13:30', 'topic' => 'Published booking'])->assertSessionHasNoErrors();
        $this->assertSame('time_taken', $proposal->fresh()->rejection_reason);
        $this->propose($this->student(), $teacher)->assertSessionHasErrors('starts_at');
    }

    public function test_student_can_cancel_proposal_and_only_owning_teacher_can_reject_it(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $this->propose($student, $teacher)->assertSessionHasNoErrors();
        $booking = ConsultationAppointment::firstOrFail();
        $this->actingAs($this->teacher())->patch(route('consultations.appointments.update', $booking), ['status' => 'confirmed'])->assertNotFound();
        $this->actingAs($student)->patch(route('consultations.appointments.cancel', $booking))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->propose($student, $teacher)->assertSessionHasNoErrors();
        $booking = ConsultationAppointment::latest('id')->firstOrFail();
        $this->actingAs($teacher)->patch(route('consultations.appointments.update', $booking), ['status' => 'rejected'])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $booking->fresh()->status);
    }

    public function test_accepted_proposal_blocks_published_booking_and_is_subtracted_from_free_ranges(): void
    {
        $teacher = $this->teacher();
        $this->propose($this->student(), $teacher)->assertSessionHasNoErrors();
        $booking = ConsultationAppointment::firstOrFail();
        $this->actingAs($teacher)->patch(route('consultations.appointments.update', $booking), ['status' => 'confirmed'])->assertSessionHasNoErrors();
        $period = ConsultationAvailability::create(['teacher_id' => $teacher->id, 'date' => today()->addDay(), 'starts_at' => '13:00', 'ends_at' => '17:00', 'slot_minutes' => 30]);
        $viewer = $this->student();
        $this->actingAs($viewer)->get(route('consultations.browse'))->assertOk()->assertSee('2:00 PM–5:00 PM')->assertDontSee('Private proposed reason');
        $this->post(route('consultations.book', $period), ['starts_at' => '13:30', 'ends_at' => '14:00', 'topic' => 'Conflict'])->assertSessionHasErrors('starts_at');
        $this->post(route('consultations.book', $period), ['starts_at' => '14:00', 'ends_at' => '14:30', 'topic' => 'Adjacent'])->assertSessionHasNoErrors();
    }

    public function test_manual_published_confirmation_and_command_reconciliation_reject_competing_proposals(): void
    {
        $teacher = $this->teacher();
        $this->propose($this->student(), $teacher)->assertSessionHasNoErrors();
        $proposal = ConsultationAppointment::firstOrFail();
        $period = ConsultationAvailability::create(['teacher_id' => $teacher->id, 'date' => today()->addDay(), 'starts_at' => '13:00', 'ends_at' => '17:00', 'slot_minutes' => 30]);
        $this->actingAs($this->student())->post(route('consultations.book', $period), ['starts_at' => '13:00', 'ends_at' => '13:30', 'topic' => 'Manual booking'])->assertSessionHasNoErrors();
        $published = ConsultationAppointment::latest('id')->firstOrFail();
        $this->assertSame('pending', $proposal->fresh()->status);
        $this->actingAs($teacher)->patch(route('consultations.appointments.update', $published), ['status' => 'confirmed'])->assertSessionHasNoErrors();
        $this->assertSame('time_taken', $proposal->fresh()->rejection_reason);
        // Recover stale state from imports or a missed reconciliation.
        $proposal->update(['status' => 'pending', 'rejection_reason' => null]);
        $this->artisan('consultations:reject-stale-requests')->assertSuccessful();
        $this->assertSame('time_taken', $proposal->fresh()->rejection_reason);
        $this->assertSame('confirmed', $published->fresh()->status);
    }
}
