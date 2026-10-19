<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminConsultationCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_counts_the_entire_month_and_lists_only_the_selected_day(): void
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        foreach (['2026-10-05', '2026-10-06', '2026-11-01'] as $day) {
            ConsultationAppointment::create(['teacher_id' => $teacher->id, 'student_id' => $student->id, 'starts_at' => $day.' 13:00', 'ends_at' => $day.' 13:30', 'status' => 'confirmed', 'topic' => 'Private reason '.$day]);
            ConsultationAvailability::create(['teacher_id' => $teacher->id, 'date' => $day, 'starts_at' => '13:00', 'ends_at' => '17:00', 'slot_minutes' => 30]);
        }
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.consultations', ['month' => '2026-10', 'day' => '2026-10-05']))->assertOk()
            ->assertViewHas('appointments', fn ($items) => $items->total() === 1 && $items->first()->starts_at->toDateString() === '2026-10-05')
            ->assertViewHas('availability', fn ($items) => $items->total() === 1 && $items->first()->date->toDateString() === '2026-10-05')
            ->assertViewHas('bookingDates', fn ($dates) => $dates->count() === 2)
            ->assertSee('2 bookings this month')->assertSee('Private reason 2026-10-05')
            ->assertDontSee('Private reason 2026-10-06')->assertDontSee('Private reason 2026-11-01');
    }

    public function test_calendar_defaults_to_today_and_supports_past_months_leap_years_and_empty_dates(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        $this->get(route('admin.consultations'))->assertOk()->assertViewHas('selectedDay', fn ($date) => $date->isToday());
        $this->get(route('admin.consultations', ['month' => '2024-02', 'day' => '2024-02-29']))->assertOk()
            ->assertSee('February 2024')->assertSee('data-monitor-date="2024-02-29"', false)->assertDontSee('data-monitor-date="2024-02-30"', false)
            ->assertSee('No availability published for this date.')->assertSee('No bookings yet.');
        $this->get(route('admin.consultations', ['month' => '2027-01']))->assertOk()
            ->assertViewHas('selectedDay', fn ($date) => $date->toDateString() === '2027-01-01');
    }

    public function test_calendar_rejects_invalid_or_mismatched_dates_and_remains_permission_protected(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        foreach ([['month' => 'invalid'], ['day' => '2026-02-30'], ['month' => '2026-10', 'day' => '2026-11-01']] as $query) {
            $this->get(route('admin.consultations', $query))->assertSessionHasErrors();
        }
        $this->actingAs(User::factory()->create())->get(route('admin.consultations'))->assertForbidden();
    }
}
