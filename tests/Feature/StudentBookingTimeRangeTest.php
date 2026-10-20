<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentBookingTimeRangeTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    private function teacher(int $maximum = 60): User
    {
        $role = Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
        $teacher = User::factory()->create(['role_id' => $role->id]);
        $this->createCompleteTeacherProfile($teacher, ['consultation_approved' => true, 'max_consultation_minutes' => $maximum]);
        return $teacher;
    }

    private function student(): User
    {
        $role = Role::firstOrCreate(['name' => 'Student'], ['is_super_admin' => false]);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function period(User $teacher): ConsultationAvailability
    {
        return ConsultationAvailability::create(['teacher_id' => $teacher->id, 'date' => today()->addDay()->toDateString(), 'starts_at' => '13:00', 'ends_at' => '17:00', 'slot_minutes' => 20]);
    }

    private function book(User $student, ConsultationAvailability $period, string $start, string $end)
    {
        return $this->actingAs($student)->post(route('consultations.book', $period), ['starts_at' => $start, 'ends_at' => $end, 'topic' => 'Help with exam preparation']);
    }

    public function test_student_can_choose_one_hour_or_shorter_unaligned_ranges_within_availability(): void
    {
        $period = $this->period($this->teacher());
        $student = $this->student();
        $this->book($student, $period, '13:00', '14:00')->assertSessionHasNoErrors()->assertRedirect();
        $this->book($student, $period, '14:07', '14:32')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('consultation_appointments', 2);
        $booking = ConsultationAppointment::firstOrFail();
        $this->assertSame('13:00', $booking->starts_at->format('H:i'));
        $this->assertSame('14:00', $booking->ends_at->format('H:i'));
        $this->assertSame('pending', $booking->status);
    }

    public function test_thirty_minute_limit_rejects_longer_requests_and_honors_automatic_confirmation(): void
    {
        $teacher = $this->teacher(30);
        $teacher->teacher->update(['auto_approve_bookings' => true]);
        $period = $this->period($teacher);
        $student = $this->student();
        $this->book($student, $period, '13:00', '14:00')->assertSessionHasErrors('ends_at');
        $this->book($student, $period, '13:00', '13:31')->assertSessionHasErrors('ends_at');
        $this->book($student, $period, '13:00', '13:30')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('consultation_appointments', 1);
        $this->assertSame('confirmed', ConsultationAppointment::firstOrFail()->status);
    }

    public function test_invalid_ranges_dates_precision_and_missing_end_are_rejected(): void
    {
        $period = $this->period($this->teacher());
        $student = $this->student();
        foreach ([['12:59', '13:15'], ['16:30', '17:01'], ['13:00', '13:00'], ['14:00', '13:30'], ['13:00', '14:01'], [$period->date->toDateString().' 13:00:30', $period->date->toDateString().' 13:30:30'], [$period->date->copy()->addDay()->format('Y-m-d').' 13:00', $period->date->copy()->addDay()->format('Y-m-d').' 13:30']] as [$start, $end]) {
            $this->book($student, $period, $start, $end)->assertSessionHasErrors();
        }
        $this->actingAs($student)->post(route('consultations.book', $period), ['starts_at' => '13:00', 'topic' => 'Discussion'])->assertSessionHasErrors('ends_at');
        $this->assertDatabaseCount('consultation_appointments', 0);
    }

    public function test_partial_overlaps_for_teacher_or_student_across_teachers_are_blocked_but_adjacent_bookings_are_allowed(): void
    {
        $first = $this->period($this->teacher());
        $second = $this->period($this->teacher());
        $student = $this->student();
        $viewer = $this->student();
        $this->book($student, $first, '13:00', '14:00')->assertSessionHasNoErrors();
        $this->book($viewer, $first, '13:45', '14:15')->assertSessionHasErrors('starts_at');
        $this->book($student, $second, '13:45', '14:15')->assertSessionHasErrors('starts_at');
        $this->book($student, $second, '14:00', '14:30')->assertSessionHasNoErrors();
        $this->book($viewer, $first, '14:00', '14:30')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('consultation_appointments', 3);
    }

    public function test_cancelled_and_rejected_ranges_are_reusable(): void
    {
        $period = $this->period($this->teacher());
        $student = $this->student();
        $this->book($student, $period, '13:00', '14:00')->assertSessionHasNoErrors();
        ConsultationAppointment::firstOrFail()->update(['status' => 'cancelled']);
        $this->book($student, $period, '13:00', '14:00')->assertSessionHasNoErrors();
        ConsultationAppointment::latest('id')->firstOrFail()->update(['status' => 'rejected']);
        $this->book($student, $period, '13:00', '14:00')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('consultation_appointments', 3);
    }

    public function test_teacher_limit_changes_affect_new_requests_without_changing_existing_times(): void
    {
        $teacher = $this->teacher();
        $period = $this->period($teacher);
        $student = $this->student();
        $this->book($student, $period, '13:00', '14:00')->assertSessionHasNoErrors();
        $booking = ConsultationAppointment::firstOrFail();
        $this->actingAs($teacher)->put(route('consultations.teacher.settings'), ['max_consultation_minutes' => 30, 'auto_approve_bookings' => 0, 'show_student_names' => 0])->assertSessionHasNoErrors();
        $this->book($student, $period, '14:00', '15:00')->assertSessionHasErrors('ends_at');
        $this->assertSame('14:00', $booking->fresh()->ends_at->format('H:i'));
        foreach ([0, -1, 1441, 2.5, 'invalid'] as $maximum) {
            $this->actingAs($teacher)->put(route('consultations.teacher.settings'), ['max_consultation_minutes' => $maximum, 'auto_approve_bookings' => 0, 'show_student_names' => 0])->assertSessionHasErrors('max_consultation_minutes');
        }
    }

    public function test_teacher_custom_limit_is_saved_and_enforced_for_student_bookings(): void
    {
        $teacher = $this->teacher();
        $period = $this->period($teacher);
        foreach ([1, 20, 45, 90, 1440] as $maximum) {
            $this->actingAs($teacher)->put(route('consultations.teacher.settings'), ['max_consultation_minutes' => $maximum, 'auto_approve_bookings' => 0, 'show_student_names' => 0])->assertSessionHasNoErrors();
            $this->assertSame($maximum, $teacher->teacher->fresh()->max_consultation_minutes);
        }
        $this->put(route('consultations.teacher.settings'), ['max_consultation_minutes' => 45, 'auto_approve_bookings' => 0, 'show_student_names' => 0])->assertSessionHasNoErrors();
        $student = $this->student();
        $this->book($student, $period, '13:00', '13:46')->assertSessionHasErrors('ends_at');
        $this->book($student, $period, '13:00', '13:45')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('consultation_appointments', 1);
    }

    public function test_browse_shows_time_range_form_and_free_ranges_exclude_student_bookings_with_other_teachers(): void
    {
        $first = $this->period($this->teacher());
        $second = $this->period($this->teacher());
        $student = $this->student();
        $this->book($student, $first, '13:00', '14:00')->assertSessionHasNoErrors();
        $this->actingAs($student)->get(route('consultations.browse', ['teacher' => $second->teacher_id]))->assertOk()
            ->assertSee('Start time')->assertSee('End time')->assertSee('Maximum 60 minutes')
            ->assertSee('data-booking-range', false)->assertSee('2:00 PM–5:00 PM')->assertDontSee('Help with exam preparation');
    }

    public function test_availability_no_longer_needs_to_fit_fixed_slots_or_the_maximum_duration(): void
    {
        $teacher = $this->teacher();
        $this->actingAs($teacher)->post(route('consultations.availability.store'), ['date' => today()->addDay()->toDateString(), 'starts_at' => '13:05', 'ends_at' => '13:22', 'slot_minutes' => 240])->assertSessionHasNoErrors();
        $period = ConsultationAvailability::firstOrFail();
        $this->assertSame(60, $period->slot_minutes);
        $this->book($this->student(), $period, '13:05', '13:22')->assertSessionHasNoErrors();
    }
}
