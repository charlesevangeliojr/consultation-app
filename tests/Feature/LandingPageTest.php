<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_explains_current_workflows_without_advertising_unimplemented_features(): void
    {
        SiteSetting::current()->update(['name' => 'Fictional Learning School']);
        $this->get('/')->assertOk()->assertSee('Fictional Learning School')
            ->assertSee('One school. One consultation system.')->assertSee('This installation serves one school')
            ->assertSee('There is no school-selection or multi-school management feature')
            ->assertSee('>Register →</a>', false)
            ->assertSee('Big questions.')->assertSee('Let’s make time.')
            ->assertSee('role="tablist"', false)->assertSee('Your role. Your next step.')
            ->assertSee('12-digit LRN and password')->assertSee('registered email and password')
            ->assertSee('Student email is not required')->assertSee('Grades 7–12')
            ->assertSee('Request another time')->assertSee('Proposed times always need teacher acceptance')
            ->assertSee('maximum consultation time in whole minutes')->assertSee('no student reschedule action')
            ->assertSee('Only teachers control individual booking decisions')
            ->assertSee('unseen rejections')->assertSee('Share, then Add to Home Screen')
            ->assertSee('not guaranteed free slots')->assertSee('Asia/Manila')
            ->assertSee('no email, SMS, or web push reminders')->assertSee('not cached for offline use')
            ->assertSee(route('register', ['role' => 'student']), false)
            ->assertSee(route('register', ['role' => 'teacher']), false)
            ->assertSee(route('admin.login'), false)
            ->assertDontSee('Faculty TV display')->assertDontSee('authorized display devices')
            ->assertDontSee('daily, weekly, and monthly views')->assertDontSee('Science department');
    }

    public function test_preview_only_lists_non_cancelled_unexpired_windows_from_approved_teachers_and_never_bookings(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        $teacher = User::factory()->create(['name' => 'Approved Public Teacher', 'role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $teacher->teacher()->create(['consultation_approved' => true]);
        $unapproved = User::factory()->create(['name' => 'Unapproved Hidden Teacher', 'role_id' => $teacher->role_id]);
        $unapproved->teacher()->create(['consultation_approved' => false]);
        $base = ['teacher_id' => $teacher->id, 'date' => today()->toDateString(), 'starts_at' => '13:00', 'ends_at' => '14:00', 'slot_minutes' => 30];
        $visible = ConsultationAvailability::create($base + ['location' => 'Visible room']);
        ConsultationAvailability::create(array_merge($base, ['starts_at' => '09:00', 'ends_at' => '10:00', 'location' => 'Elapsed hidden room']));
        ConsultationAvailability::create(array_merge($base, ['date' => today()->subDay()->toDateString(), 'location' => 'Past hidden room']));
        ConsultationAvailability::create(array_merge($base, ['cancelled_at' => now(), 'location' => 'Cancelled hidden room']));
        ConsultationAvailability::create(array_merge($base, ['teacher_id' => $unapproved->id, 'location' => 'Unapproved hidden room']));
        $student = User::factory()->create(['name' => 'Private Student Name', 'role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
        ConsultationAppointment::create(['teacher_id' => $teacher->id, 'student_id' => $student->id, 'availability_id' => $visible->id,
            'starts_at' => today()->setTime(13, 0), 'ends_at' => today()->setTime(14, 0), 'status' => 'confirmed', 'topic' => 'Private learning concern']);
        $this->get('/')->assertOk()->assertSee('Approved Public Teacher')->assertSee('Visible room')
            ->assertDontSee('Elapsed hidden room')->assertDontSee('Past hidden room')->assertDontSee('Cancelled hidden room')
            ->assertDontSee('Unapproved hidden room')->assertDontSee('Unapproved Hidden Teacher')
            ->assertDontSee('Private Student Name')->assertDontSee('Private learning concern');
    }

    public function test_empty_preview_is_honest_and_the_preview_is_limited_to_twelve_windows(): void
    {
        $this->get('/')->assertOk()->assertSee('No upcoming published hours to preview')->assertSee('you can still propose a future time');
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $teacher->teacher()->create(['consultation_approved' => true]);
        foreach (range(1, 13) as $day) {
            ConsultationAvailability::create(['teacher_id' => $teacher->id, 'date' => today()->addDays($day)->toDateString(), 'starts_at' => '13:00', 'ends_at' => '14:00', 'slot_minutes' => 30, 'location' => 'Preview location '.$day]);
        }
        $response = $this->get('/')->assertOk()->assertDontSee('Preview location 13');
        $this->assertCount(12, $response->viewData('publicAvailability'));
    }

    public function test_role_switcher_assets_are_versioned_so_old_hosted_scripts_are_not_reused(): void
    {
        $jsVersion = substr(hash_file('sha256', public_path('js/landing.js')), 0, 12);
        $cssVersion = substr(hash_file('sha256', public_path('css/landing-hero.css')), 0, 12);
        $this->get('/')->assertOk()
            ->assertSee('js/landing.js?v='.$jsVersion, false)
            ->assertSee('css/landing-hero.css?v='.$cssVersion, false)
            ->assertSee('data-turbo-eval="false" data-turbo-track="reload"', false)
            ->assertSee('aria-controls="hero-teacher-panel"', false);
    }
}
