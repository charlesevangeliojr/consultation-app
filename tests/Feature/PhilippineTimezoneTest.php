<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PhilippineTimezoneTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    public function test_runtime_calendar_and_dashboard_use_philippine_date_after_utc_midnight_boundary(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $this->assertSame('Asia/Manila', date_default_timezone_get());
        $this->travelTo(Carbon::parse('2026-10-31 16:30:00', 'UTC'));
        $this->assertSame('2026-11-01 00:30', now()->format('Y-m-d H:i'));
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.consultations'))->assertOk()
            ->assertViewHas('selectedDay', fn ($day) => $day->toDateString() === '2026-11-01')
            ->assertSee('November 2026')->assertSee('Asia/Manila');
        $this->get(route('admin.dashboard'))->assertOk()->assertViewHas('month', fn ($month) => $month->format('Y-m') === '2026-11');
    }

    public function test_bookings_keep_entered_philippine_times_and_proposals_expire_at_the_philippine_start_time(): void
    {
        $this->travelTo(Carbon::parse('2026-10-19 16:30:00', 'UTC'));
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $this->createCompleteTeacherProfile($teacher, ['consultation_approved' => true, 'max_consultation_minutes' => 60]);
        $student = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
        $period = ConsultationAvailability::create(['teacher_id' => $teacher->id, 'date' => '2026-10-20', 'starts_at' => '13:00', 'ends_at' => '17:00', 'slot_minutes' => 30]);
        $this->actingAs($student)->post(route('consultations.book', $period), ['starts_at' => '13:00', 'ends_at' => '13:30', 'topic' => 'Philippine-time booking'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('consultation_appointments', ['starts_at' => '2026-10-20 13:00:00', 'ends_at' => '2026-10-20 13:30:00']);
        $this->post(route('consultations.request-time', $teacher), ['date' => '2026-10-20', 'starts_at' => '12:45', 'ends_at' => '13:00', 'topic' => 'Philippine-time proposal'])->assertSessionHasNoErrors();
        $proposal = ConsultationAppointment::where('is_proposed', true)->firstOrFail();
        $this->travelTo(Carbon::parse('2026-10-20 04:44:00', 'UTC'));
        $this->artisan('consultations:reject-stale-requests')->assertSuccessful();
        $this->assertSame('pending', $proposal->fresh()->status);
        $this->travelTo(Carbon::parse('2026-10-20 04:45:00', 'UTC'));
        $this->artisan('consultations:reject-stale-requests')->assertSuccessful();
        $this->assertSame('rejected', $proposal->fresh()->status);
        $this->assertSame('expired', $proposal->fresh()->rejection_reason);
    }
}
