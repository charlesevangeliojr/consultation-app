<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\Role;
use App\Models\User;
use App\Support\PortalNavigationBadges;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalNavigationBadgesTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    public function test_badges_count_only_the_current_users_upcoming_active_records(): void
    {
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $this->createCompleteTeacherProfile($teacher, ['consultation_approved' => true]);
        $student = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
        $otherStudent = User::factory()->create(['role_id' => $student->role_id]);
        $period = ConsultationAvailability::create(['teacher_id' => $teacher->id, 'date' => today()->addDay()->toDateString(), 'starts_at' => '13:00', 'ends_at' => '17:00', 'slot_minutes' => 30]);
        foreach (['pending', 'confirmed', 'rejected', 'cancelled'] as $status) {
            ConsultationAppointment::create(['teacher_id' => $teacher->id, 'student_id' => $student->id, 'availability_id' => $period->id,
                'starts_at' => today()->addDay()->setTime(13, 0), 'ends_at' => today()->addDay()->setTime(13, 30), 'topic' => 'Private topic', 'status' => $status]);
        }
        ConsultationAppointment::create(['teacher_id' => $teacher->id, 'student_id' => $student->id, 'availability_id' => $period->id,
            'starts_at' => today()->subDay()->setTime(13, 0), 'ends_at' => today()->subDay()->setTime(13, 30), 'topic' => 'Past', 'status' => 'pending']);
        $badges = PortalNavigationBadges::forUser($teacher);
        $this->assertSame(1, $badges['users']['badge']);
        $this->assertSame(1, $badges['calendar']['badge']);
        $this->assertSame(1, $badges['plus']['badge']);
        $this->assertSame(0, $badges['profile']['badge']);
        $this->actingAs($teacher)->get(route('consultations.teacher.requests'))->assertOk()
            ->assertSee('1 consultation request awaiting your approval')->assertSee('1 upcoming confirmed consultation');
        $studentBadges = PortalNavigationBadges::forUser($student);
        $this->assertSame(3, $studentBadges['users']['badge']);
        $this->assertArrayNotHasKey('calendar', $studentBadges);
        $this->actingAs($student)->get(route('consultations.dashboard'))->assertOk()
            ->assertSee('3 unfinished consultations or unseen rejections');
        $this->assertSame(0, PortalNavigationBadges::forUser($otherStudent)['users']['badge']);
        $this->actingAs($otherStudent)->get(route('consultations.dashboard'))->assertOk()->assertDontSee('data-navigation-count=', false);
        ConsultationAppointment::where('status', 'pending')->update(['status' => 'rejected']);
        $this->assertSame(0, PortalNavigationBadges::forUser($teacher)['users']['badge']);
        $period->update(['cancelled_at' => now()]);
        $this->assertSame(0, PortalNavigationBadges::forUser($teacher)['plus']['badge']);
    }

    public function test_incomplete_teacher_profile_shows_remaining_fields_and_expired_availability_is_not_counted(): void
    {
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $teacher->teacher()->create();
        ConsultationAvailability::create(['teacher_id' => $teacher->id, 'date' => today()->subDay()->toDateString(), 'starts_at' => '13:00', 'ends_at' => '14:00', 'slot_minutes' => 30]);
        $badges = PortalNavigationBadges::forUser($teacher);
        $this->assertGreaterThan(0, $badges['profile']['badge']);
        $this->assertSame(0, $badges['plus']['badge']);
        $this->actingAs($teacher)->get(route('consultations.teacher-profile.edit'))->assertOk()->assertSee('required profile details missing');
    }
}
