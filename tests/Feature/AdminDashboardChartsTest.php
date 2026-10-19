<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    private function booking(string $date, string $status): void
    {
        ConsultationAppointment::create([
            'teacher_id' => User::factory()->create()->id,
            'student_id' => User::factory()->create()->id,
            'starts_at' => $date.' 13:00:00', 'ends_at' => $date.' 13:30:00',
            'status' => $status, 'topic' => 'A private reason that must not appear in analytics',
        ]);
    }

    public function test_dashboard_shows_six_month_trend_and_selected_month_status_counts(): void
    {
        $this->booking('2026-10-01', 'confirmed');
        $this->booking('2026-10-15', 'completed');
        $this->booking('2026-10-31', 'cancelled');
        $this->booking('2026-10-31', 'rejected');
        $this->booking('2026-10-31', 'pending');
        $this->booking('2026-09-01', 'completed');
        $this->booking('2026-05-01', 'completed');
        $this->booking('2026-04-30', 'completed');
        $this->booking('2026-11-01', 'confirmed');
        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)->get(route('admin.dashboard', ['month' => '2026-10']))->assertOk()
            ->assertViewHas('total', 5)->assertViewHas('counts', fn ($counts) => $counts->count() === 5 && $counts['pending'] === 1)
            ->assertViewHas('trend', fn ($trend) => $trend->count() === 6 && $trend->first()['month']->format('Y-m') === '2026-05' && $trend->sum('total') === 7)
            ->assertSee('Monthly consultations')->assertSee('Consultation status')->assertSee('View monthly data')
            ->assertDontSee('A private reason that must not appear in analytics');
        $this->get(route('admin.dashboard', ['month' => '2026-09']))->assertOk()->assertViewHas('total', 1);
    }

    public function test_empty_dashboard_has_zero_totals_and_safe_accessible_charts(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('total', 0)->assertViewHas('maximum', 1)
            ->assertViewHas('trend', fn ($trend) => $trend->count() === 6 && $trend->sum('total') === 0)
            ->assertSee('No consultations in this period yet.')->assertSee('No bookings scheduled this month.')
            ->assertSee('role="img"', false)->assertDontSee('NaN')->assertDontSee('INF');
    }

    public function test_month_filter_supports_year_boundaries_and_validates_bad_input(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        $this->get(route('admin.dashboard', ['month' => '2027-01']))->assertOk()
            ->assertViewHas('trend', fn ($trend) => $trend->first()['month']->format('Y-m') === '2026-08' && $trend->last()['month']->format('Y-m') === '2027-01');
        foreach (['invalid', '2026-13', ['invalid']] as $month) {
            $this->get(route('admin.dashboard', ['month' => $month]))->assertSessionHasErrors('month');
        }
    }

    public function test_dashboard_charts_require_dashboard_permission(): void
    {
        $user = User::factory()->create();
        $user->syncPermissions(['accounts' => ['view' => true]]);
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $user->syncPermissions(['dashboard' => ['view' => true]]);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Monthly consultations');
    }
}
