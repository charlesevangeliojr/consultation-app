<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\Role;
use App\Models\User;
use App\Support\PortalNavigationBadges;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentConsultationsTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    private function student(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
    }

    private function booking(User $student, string $status = 'rejected', array $attributes = []): ConsultationAppointment
    {
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        return ConsultationAppointment::create(array_merge([
            'student_id' => $student->id, 'teacher_id' => $teacher->id, 'status' => $status,
            'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'topic' => 'Private consultation details', 'is_proposed' => true,
        ], $attributes));
    }

    public function test_consultations_page_is_private_and_read_receipts_only_clear_rejections(): void
    {
        $student = $this->student();
        $other = $this->student();
        $rejected = $this->booking($student);
        $pending = $this->booking($student, 'pending');
        $confirmed = $this->booking($student, 'confirmed', ['starts_at' => now()->subMinutes(5)]);
        $foreign = $this->booking($other);
        $this->actingAs($student)->get(route('consultations.student-consultations.index'))->assertOk()
            ->assertSee('data-unseen-rejection="'.$rejected->id.'"', false)->assertDontSee('data-unseen-rejection="'.$foreign->id.'"', false);
        $this->assertNull($rejected->fresh()->student_rejection_seen_at);
        $this->assertSame(3, PortalNavigationBadges::forUser($student)['users']['badge']);
        $this->postJson(route('consultations.student-consultations.seen'), ['ids' => [$rejected->id, $pending->id, $confirmed->id, $foreign->id]])
            ->assertOk()->assertJson(['count' => 2]);
        $this->assertNotNull($rejected->fresh()->student_rejection_seen_at);
        $this->assertNull($foreign->fresh()->student_rejection_seen_at);
        $this->assertNull($pending->fresh()->student_rejection_seen_at);
        $this->postJson(route('consultations.student-consultations.seen'), ['ids' => [$rejected->id]])->assertOk()->assertJson(['count' => 2]);
        $confirmed->update(['status' => 'completed']);
        $pending->update(['status' => 'cancelled']);
        $this->assertSame(0, PortalNavigationBadges::forUser($student)['users']['badge']);
    }

    public function test_finished_bookings_stop_counting_and_rejections_remain_until_seen(): void
    {
        $student = $this->student();
        $this->booking($student, 'confirmed', ['starts_at' => now()->subHours(2), 'ends_at' => now()->subHour()]);
        $this->booking($student, 'rejected', ['starts_at' => now()->subHours(2), 'ends_at' => now()->subHour()]);
        $this->assertSame(1, PortalNavigationBadges::forUser($student)['users']['badge']);
        $response = $this->actingAs($student)->get(route('consultations.browse'))->assertOk();
        $this->assertMatchesRegularExpression('/href="'.preg_quote(route('consultations.student-consultations.index'), '/').'"[^>]*>.*?data-navigation-count="1"/s', $response->getContent());
        $this->postJson(route('consultations.student-consultations.seen'), ['ids' => []])->assertUnprocessable();
    }

    public function test_teacher_cannot_access_student_list_or_read_receipts(): void
    {
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $this->createCompleteTeacherProfile($teacher);
        $this->actingAs($teacher)->get(route('consultations.student-consultations.index'))->assertForbidden();
        $this->postJson(route('consultations.student-consultations.seen'), ['ids' => [1]])->assertForbidden();
    }
}
