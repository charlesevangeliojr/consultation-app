<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_has_replay_button_and_student_only_tour_with_consultations_page(): void
    {
        $student = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
        foreach (['consultations.dashboard', 'consultations.browse', 'consultations.student-consultations.index', 'consultations.student-profile.edit'] as $route) {
            $response = $this->actingAs($student)->get(route($route))->assertOk()
                ->assertSee('portal-tour.css')->assertSee('portal-tour.js')->assertSee('Start navigation tour')
                ->assertSee('Student quick tour')->assertSee('Track your consultations')->assertDontSee('Teacher quick tour')
                ->assertSee('consultation-tour-v1-student-'.$student->id);
            preg_match('/<script type="application\/json" data-portal-tour-steps>(.*?)<\/script>/s', $response->getContent(), $matches);
            $steps = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
            $this->assertCount(4, $steps);
            $this->assertSame(route('consultations.student-consultations.index'), $steps[2]['url']);
        }
    }

    public function test_incomplete_teachers_can_take_tour_starting_with_profile_setup(): void
    {
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $teacher->teacher()->create();
        $response = $this->actingAs($teacher)->get(route('consultations.teacher-profile.edit'))->assertOk()
            ->assertSee('Teacher quick tour')->assertSee('Start with your profile')->assertSee('Review student requests')
            ->assertSee('consultation-tour-v1-teacher-'.$teacher->id)->assertDontSee('Student quick tour');
        preg_match('/<script type="application\/json" data-portal-tour-steps>(.*?)<\/script>/s', $response->getContent(), $matches);
        $steps = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(5, $steps);
        $this->assertSame(route('consultations.teacher-profile.edit'), $steps[0]['url']);
    }

    public function test_admin_and_public_pages_do_not_show_portal_tour(): void
    {
        $this->get(route('register'))->assertOk()->assertDontSee('data-portal-tour-start', false);
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.dashboard'))
            ->assertOk()->assertDontSee('data-portal-tour-start', false);
    }

    public function test_first_student_login_requires_tour_and_completion_is_saved_to_account(): void
    {
        $student = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id, 'password' => 'TourTest!2026']);
        $student->student()->create(['lrn' => '990000123456', 'status' => 'active']);
        $this->followingRedirects()->post(route('portal.login.store'), ['login' => '990000123456', 'password' => 'TourTest!2026'])
            ->assertOk()->assertSee('data-tour-required="true"', false);
        $this->postJson(route('consultations.tour.complete'))->assertNoContent();
        $this->assertNotNull($student->fresh()->portal_tour_seen_at);
        $this->get(route('consultations.dashboard'))->assertOk()->assertSee('data-tour-required="false"', false)
            ->assertSee('Start navigation tour');
    }

    public function test_first_incomplete_teacher_login_shows_tour_and_can_acknowledge_without_profile_completion(): void
    {
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id, 'password' => 'TourTest!2026']);
        $this->followingRedirects()->post(route('portal.login.store'), ['login' => $teacher->email, 'password' => 'TourTest!2026'])
            ->assertOk()->assertSee('Teacher quick tour')->assertSee('data-tour-required="true"', false);
        $this->postJson(route('consultations.tour.complete'))->assertNoContent();
        $this->assertNotNull($teacher->fresh()->portal_tour_seen_at);
        $this->get(route('consultations.teacher-profile.edit'))->assertOk()->assertSee('data-tour-required="false"', false);
    }
}
