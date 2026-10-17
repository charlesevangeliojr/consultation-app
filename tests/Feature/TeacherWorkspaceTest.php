<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherWorkspaceTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    private function teacher(bool $approved = true): User
    {
        $role = Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $this->createCompleteTeacherProfile($user, ['consultation_approved' => $approved]);
        return $user;
    }

    private function period(User $teacher, int $days = 1): ConsultationAvailability
    {
        return ConsultationAvailability::create([
            'teacher_id' => $teacher->id, 'date' => today()->addDays($days)->toDateString(),
            'starts_at' => '09:00', 'ends_at' => '11:00', 'slot_minutes' => 30, 'location' => 'Workspace room',
        ]);
    }

    private function booking(ConsultationAvailability $period, string $status = 'pending'): ConsultationAppointment
    {
        return ConsultationAppointment::create([
            'availability_id' => $period->id, 'teacher_id' => $period->teacher_id,
            'student_id' => User::factory()->create()->id,
            'starts_at' => $period->date->copy()->setTime(9, 0),
            'ends_at' => $period->date->copy()->setTime(9, 30),
            'status' => $status, 'topic' => 'Workspace request reason',
        ]);
    }

    public function test_workspace_pages_have_separate_content_and_one_active_navigation_destination(): void
    {
        $this->actingAs($this->teacher());
        $pages = [
            'teacher' => ['teacher-month-calendar', ['teacher-requests', 'teacher-settings', 'teacher-publish']],
            'teacher.requests' => ['teacher-requests', ['teacher-month-calendar', 'teacher-settings', 'teacher-publish']],
            'teacher.availability' => ['teacher-publish', ['teacher-month-calendar', 'teacher-requests', 'teacher-settings']],
            'teacher.preferences' => ['teacher-settings', ['teacher-month-calendar', 'teacher-requests', 'teacher-publish']],
        ];
        foreach ($pages as $route => [$expected, $absent]) {
            $response = $this->get(route('consultations.'.$route))->assertOk()->assertSee('id="'.$expected.'"', false);
            foreach ($absent as $id) $response->assertDontSee('id="'.$id.'"', false);
            foreach (['Teacher desktop navigation', 'Teacher mobile navigation'] as $label) {
                $this->assertSame(1, preg_match('/<nav\b[^>]*aria-label="'.$label.'"[^>]*>(.*?)<\/nav>/s', $response->getContent(), $matches));
                $this->assertSame(1, substr_count($matches[1], 'aria-current="page"'));
                $this->assertMatchesRegularExpression('/<a[^>]*href="'.preg_quote(route('consultations.'.$route), '/').'"[^>]*aria-current="page"/', $matches[1]);
            }
        }
    }

    public function test_request_filters_counts_and_pagination_are_scoped_to_signed_in_teacher(): void
    {
        $teacher = $this->teacher();
        $period = $this->period($teacher);
        for ($i = 0; $i < 13; $i++) $this->booking($period);
        $this->booking($period, 'confirmed');
        $this->booking($this->period($this->teacher()));
        $this->actingAs($teacher)->get(route('consultations.teacher.requests', ['status' => 'pending']))
            ->assertOk()->assertSee('status=pending', false)
            ->assertViewHas('statusCounts', fn ($counts) => $counts['pending'] === 13 && $counts['confirmed'] === 1)
            ->assertViewHas('appointments', fn ($rows) => $rows->total() === 13 && $rows->count() === 12 && $rows->every(fn ($row) => $row->teacher_id === $teacher->id && $row->status === 'pending'));
        $this->get(route('consultations.teacher.requests', ['status' => 'pending', 'page' => 2]))
            ->assertOk()->assertViewHas('appointments', fn ($rows) => $rows->count() === 1);
        $this->getJson(route('consultations.teacher.requests', ['status' => 'invalid']))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_availability_page_only_lists_own_uncancelled_upcoming_windows(): void
    {
        $teacher = $this->teacher();
        $own = $this->period($teacher);
        $this->period($teacher, -1);
        $this->period($teacher, 2)->update(['cancelled_at' => now()]);
        $this->period($this->teacher());
        $this->actingAs($teacher)->get(route('consultations.teacher.availability'))->assertOk()
            ->assertViewHas('availability', fn ($rows) => $rows->pluck('id')->all() === [$own->id]);
    }

    public function test_actions_return_to_their_own_workspace_pages(): void
    {
        $teacher = $this->teacher();
        $this->actingAs($teacher)->post(route('consultations.availability.store'), [
            'date' => today()->addDay()->toDateString(), 'starts_at' => '09:00', 'ends_at' => '11:00', 'slot_minutes' => 30,
        ])->assertRedirect(route('consultations.teacher.availability'));
        $period = ConsultationAvailability::firstOrFail();
        $booking = $this->booking($period);
        $this->patch(route('consultations.appointments.update', $booking), ['status' => 'confirmed'])
            ->assertRedirect(route('consultations.teacher.requests'));
        $this->put(route('consultations.teacher.settings'), ['max_consultation_minutes' => 30, 'auto_approve_bookings' => 1, 'show_student_names' => 0])
            ->assertRedirect(route('consultations.teacher.preferences'));
        $this->patch(route('consultations.availability.cancel', $period))
            ->assertRedirect(route('consultations.teacher.availability'));
        $this->assertSame('rejected', $booking->fresh()->status);
    }

    public function test_form_validation_returns_to_the_origin_page_and_preserves_input(): void
    {
        $this->actingAs($this->teacher());
        $this->from(route('consultations.teacher.availability'))->post(route('consultations.availability.store'), [
            'date' => today()->addDay()->toDateString(), 'starts_at' => '11:00', 'ends_at' => '09:00', 'location' => 'Keep my location',
        ])->assertRedirect(route('consultations.teacher.availability'))->assertSessionHasErrors('ends_at')
            ->assertSessionHasInput('location', 'Keep my location');
        $this->from(route('consultations.teacher.preferences'))->put(route('consultations.teacher.settings'), ['max_consultation_minutes' => 1])
            ->assertRedirect(route('consultations.teacher.preferences'))->assertSessionHasErrors('max_consultation_minutes');
    }

    public function test_non_teachers_cannot_access_workspace_pages_and_unapproved_teachers_cannot_publish(): void
    {
        foreach (['Student', 'Administrator'] as $name) {
            $role = Role::firstOrCreate(['name' => $name], ['is_super_admin' => false]);
            $this->actingAs(User::factory()->create(['role_id' => $role->id]));
            foreach (['teacher.requests', 'teacher.availability', 'teacher.preferences'] as $route) {
                $this->get(route('consultations.'.$route))->assertForbidden();
            }
        }
        $this->actingAs($this->teacher(false))->get(route('consultations.teacher.availability'))
            ->assertOk()->assertDontSee('id="teacher-publish"', false)->assertSee('cannot publish availability');
        $this->post(route('consultations.availability.store'), [
            'date' => today()->addDay()->toDateString(), 'starts_at' => '09:00', 'ends_at' => '11:00',
        ])->assertForbidden();
    }
}
