<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TeacherCalendarTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    private function teacher(): User
    {
        $role = Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
        $teacher = User::factory()->create(['role_id' => $role->id]);
        $this->createCompleteTeacherProfile($teacher, ['consultation_approved' => true]);
        return $teacher;
    }

    private function period(User $teacher, string $date): ConsultationAvailability
    {
        return ConsultationAvailability::create([
            'teacher_id' => $teacher->id, 'date' => $date, 'starts_at' => '09:00',
            'ends_at' => '11:00', 'slot_minutes' => 30, 'location' => 'Calendar Room',
        ]);
    }

    private function booking(ConsultationAvailability $period, string $time, string $status): ConsultationAppointment
    {
        $student = User::factory()->create();
        $start = Carbon::parse($period->date->toDateString().' '.$time);
        return ConsultationAppointment::create([
            'availability_id' => $period->id, 'teacher_id' => $period->teacher_id,
            'student_id' => $student->id, 'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(30),
            'status' => $status, 'topic' => 'Calendar consultation topic',
        ]);
    }

    public function test_teacher_has_a_month_calendar_with_today_and_navigation(): void
    {
        $this->actingAs($this->teacher())->get(route('consultations.teacher'))
            ->assertOk()->assertSee('My schedule calendar')->assertSee(now()->format('F Y'))
            ->assertSee('Previous month')->assertSee('Next month')->assertSee('Today')
            ->assertSee('No availability published for this date.')
            ->assertSee('No consultations scheduled for this date.')
            ->assertViewHas('selectedDay', fn ($date) => $date->isToday());
    }

    public function test_teacher_can_select_a_day_and_see_time_ordered_bookings_and_availability(): void
    {
        $teacher = $this->teacher();
        $date = today()->addDay()->toDateString();
        $period = $this->period($teacher, $date);
        $late = $this->booking($period, '10:00', 'confirmed');
        $early = $this->booking($period, '09:00', 'pending');
        $this->actingAs($teacher)->get(route('consultations.teacher', ['day' => $date]))
            ->assertOk()->assertSee('Schedule for '.Carbon::parse($date)->format('l, F j, Y'))
            ->assertSee('Calendar Room')->assertSee('Calendar consultation topic')
            ->assertSeeInOrder([$early->student->name, $late->student->name])
            ->assertViewHas('calendarAvailability', fn ($days) => $days->get($date)->pluck('id')->all() === [$period->id])
            ->assertViewHas('calendarAppointments', fn ($days) => $days->get($date)->pluck('id')->all() === [$early->id, $late->id]);
    }

    public function test_teacher_can_browse_past_months_and_year_boundaries(): void
    {
        $teacher = $this->teacher();
        $period = $this->period($teacher, '2025-12-15');
        $booking = $this->booking($period, '09:00', 'completed');
        $this->actingAs($teacher)->get(route('consultations.teacher', ['month' => '2025-12', 'day' => '2025-12-15']))
            ->assertOk()->assertSee('December 2025')->assertSee('month=2026-01', false)
            ->assertSee('month=2025-11', false)->assertSee('completed')
            ->assertViewHas('calendarAppointments', fn ($days) => $days->get('2025-12-15')->first()->is($booking));
    }

    public function test_calendar_is_scoped_to_the_teacher_and_selected_month(): void
    {
        $teacher = $this->teacher();
        $otherTeacher = $this->teacher();
        $own = $this->period($teacher, '2027-02-10');
        $other = $this->period($otherTeacher, '2027-02-10');
        $this->booking($other, '09:00', 'confirmed');
        $this->period($teacher, '2027-03-10');
        $this->actingAs($teacher)->get(route('consultations.teacher', ['month' => '2027-02', 'teacher' => $otherTeacher->id]))
            ->assertOk()
            ->assertViewHas('calendarAvailability', fn ($days) => $days->flatten()->pluck('id')->all() === [$own->id])
            ->assertViewHas('calendarAppointments', fn ($days) => $days->isEmpty())
            ->assertViewHas('selectedDay', fn ($date) => $date->toDateString() === '2027-02-01');
    }

    public function test_cancelled_availability_and_cancelled_or_rejected_bookings_are_not_scheduled(): void
    {
        $teacher = $this->teacher();
        $date = today()->addDay()->toDateString();
        $period = $this->period($teacher, $date);
        $cancelled = $this->period($teacher, $date);
        $cancelled->update(['cancelled_at' => now()]);
        $this->booking($period, '09:00', 'cancelled');
        $this->booking($period, '09:30', 'rejected');
        $this->actingAs($teacher)->get(route('consultations.teacher', ['day' => $date]))
            ->assertOk()->assertViewHas('calendarAppointments', fn ($days) => $days->isEmpty())
            ->assertViewHas('calendarAvailability', fn ($days) => $days->get($date)->pluck('id')->all() === [$period->id]);
    }

    public function test_invalid_calendar_filters_are_rejected(): void
    {
        $this->actingAs($this->teacher());
        $this->getJson(route('consultations.teacher', ['month' => '2026-13']))->assertUnprocessable()->assertJsonValidationErrors('month');
        $this->getJson(route('consultations.teacher', ['day' => '2026-02-30']))->assertUnprocessable()->assertJsonValidationErrors('day');
        $this->getJson(route('consultations.teacher', ['month' => '2026-10', 'day' => '2026-11-01']))->assertUnprocessable()->assertJsonValidationErrors('day');
    }

    public function test_student_cannot_view_a_teachers_private_calendar(): void
    {
        $role = Role::firstOrCreate(['name' => 'Student'], ['is_super_admin' => false]);
        $student = User::factory()->create(['role_id' => $role->id]);
        $this->actingAs($student)->get(route('consultations.teacher'))->assertForbidden();
    }

    public function test_compact_calendar_contains_only_the_weeks_needed_including_leap_years(): void
    {
        $this->actingAs($this->teacher());
        foreach (['2021-02' => [28, 0], '2020-02' => [29, 6], '2026-11' => [30, 12]] as $month => [$days, $blanks]) {
            $response = $this->get(route('consultations.teacher', ['month' => $month]))->assertOk();
            $html = $response->getContent();
            $this->assertSame($days, substr_count($html, 'data-calendar-date='));
            $this->assertSame($blanks, substr_count($html, 'class="calendar-day-blank"'));
            $response->assertSee('calendar-agenda')->assertSee('calendar-event-dots')->assertSee('responsive-calendar.css');
        }
    }

    public function test_desktop_teacher_workspace_has_a_sidebar_and_preserves_the_selected_month(): void
    {
        $response = $this->actingAs($this->teacher())->get(route('consultations.teacher', ['month' => '2026-11']))
            ->assertOk()->assertSee('November 2026')
            ->assertSee('aria-label="Teacher desktop navigation"', false)
            ->assertSee('id="teacher-workspace"', false)
            ->assertSee('My calendar')
            ->assertSee('Teacher mobile navigation');
        $this->assertSame(1, preg_match('/<nav\b[^>]*aria-label="Teacher desktop navigation"[^>]*>(.*?)<\/nav>/s', $response->getContent(), $matches));
        $this->assertStringContainsString('month=2026-11', $matches[1]);
        $this->assertStringContainsString('aria-current="page"', $matches[1]);
        foreach (['teacher-requests', 'teacher-publish', 'teacher-settings'] as $section) {
            $this->assertStringNotContainsString('data-navigation-section="'.$section.'"', $matches[1]);
            $response->assertDontSee('id="'.$section.'"', false);
        }
        $response->assertDontSee('Save preferences')->assertDontSee('Publish times');
    }

    public function test_calendar_day_indicators_show_status_counts_and_ignore_cancelled_or_rejected_bookings(): void
    {
        $teacher = $this->teacher();
        $date = today()->addDay()->toDateString();
        $period = $this->period($teacher, $date);
        $this->booking($period, '09:00', 'pending');
        $this->booking($period, '09:30', 'confirmed');
        $this->booking($period, '10:00', 'completed');
        $this->booking($period, '10:30', 'pending');
        $this->booking($period, '09:00', 'cancelled');
        $this->booking($period, '09:00', 'rejected');
        $response = $this->actingAs($teacher)->get(route('consultations.teacher', ['day' => $date]))->assertOk();
        $this->assertSame(1, preg_match('/<a\b[^>]*data-calendar-date="'.$date.'"[^>]*>.*?<\/a>/s', $response->getContent(), $matches));
        foreach (['availability', 'pending', 'confirmed', 'completed'] as $indicator) {
            $this->assertStringContainsString('data-calendar-indicator="'.$indicator.'"', $matches[0]);
        }
        $this->assertStringContainsString('2 pending, 1 confirmed, 1 completed', $matches[0]);
        $this->assertStringNotContainsString('data-calendar-indicator="cancelled"', $matches[0]);
        $this->assertStringNotContainsString('data-calendar-indicator="rejected"', $matches[0]);
        $response->assertSee('aria-label="Calendar indicators"', false)->assertSee('Selected date')->assertSee('Published availability');
    }

    public function test_empty_month_explains_when_calendar_indicators_appear(): void
    {
        $this->actingAs($this->teacher())->get(route('consultations.teacher', ['month' => '2026-11']))
            ->assertOk()->assertSee('No availability or consultations in November 2026.')
            ->assertSee('Indicators appear on dates with schedules.');
    }
}
