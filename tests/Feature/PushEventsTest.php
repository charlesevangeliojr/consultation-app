<?php

namespace Tests\Feature;

use App\Jobs\SendBrowserPush;
use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\PushSubscription;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PushEventsTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    private function accounts(): array
    {
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $this->createCompleteTeacherProfile($teacher, ['consultation_approved' => true]);
        $student = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
        return [$teacher, $student];
    }

    private function enable(): void
    {
        Bus::fake();
        config(['webpush.enabled' => true, 'webpush.public_key' => 'test-public', 'webpush.private_key' => 'test-private', 'webpush.subject' => 'https://school.example']);
    }

    private function booking(User $teacher, User $student, array $extra = []): ConsultationAppointment
    {
        return ConsultationAppointment::create(array_merge(['teacher_id' => $teacher->id, 'student_id' => $student->id,
            'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'status' => 'pending', 'is_proposed' => true, 'topic' => 'PRIVATE_REASON_DO_NOT_SEND'], $extra));
    }

    public function test_requests_send_only_after_commit_and_never_include_private_details(): void
    {
        [$teacher, $student] = $this->accounts();
        $this->enable();
        DB::beginTransaction();
        $this->booking($teacher, $student);
        Bus::assertNothingDispatched();
        DB::rollBack();
        Bus::assertNothingDispatched();
        DB::transaction(fn () => $this->booking($teacher, $student));
        Bus::assertDispatchedSync(SendBrowserPush::class, fn ($job) => $job->userId === $teacher->id
            && !str_contains($job->body, 'PRIVATE_REASON') && $job->url === '/consultations/teacher/requests');
    }

    public function test_confirmation_rejection_and_student_cancellation_notify_the_correct_accounts(): void
    {
        [$teacher, $student] = $this->accounts();
        $booking = $this->booking($teacher, $student);
        $this->enable();
        $booking->update(['status' => 'confirmed']);
        Bus::assertDispatchedSync(SendBrowserPush::class, fn ($job) => $job->userId === $student->id && str_contains($job->body, 'confirmed'));
        $this->actingAs($student);
        $booking->update(['status' => 'cancelled']);
        Bus::assertDispatchedSync(SendBrowserPush::class, fn ($job) => $job->userId === $teacher->id && str_contains($job->body, 'cancelled'));
    }

    public function test_cancelling_availability_and_automatic_rejection_emit_push_events(): void
    {
        [$teacher, $student] = $this->accounts();
        $period = ConsultationAvailability::create(['teacher_id' => $teacher->id, 'date' => today()->addDay()->toDateString(), 'starts_at' => '13:00', 'ends_at' => '15:00', 'slot_minutes' => 30]);
        $booking = $this->booking($teacher, $student, ['availability_id' => $period->id, 'is_proposed' => false]);
        $this->enable();
        $this->actingAs($teacher)->patch(route('consultations.availability.cancel', $period))->assertSessionHasNoErrors();
        $this->assertSame('rejected', $booking->fresh()->status);
        Bus::assertDispatchedSync(SendBrowserPush::class, fn ($job) => $job->userId === $student->id && str_contains($job->body, 'rejected'));
        Bus::fake();
        $proposed = $this->booking($teacher, $student, ['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        Bus::fake();
        \App\Support\ProposedConsultationRequests::reconcile();
        $this->assertSame('rejected', $proposed->fresh()->status);
        Bus::assertDispatchedSync(SendBrowserPush::class, fn ($job) => $job->userId === $student->id && str_contains($job->body, 'rejected'));
    }

    public function test_subscribed_admins_receive_teacher_approval_requests_and_teachers_receive_decisions(): void
    {
        $admin = User::factory()->superAdmin()->create();
        PushSubscription::create(['user_id' => $admin->id, 'endpoint_hash' => hash('sha256', 'example'), 'endpoint' => 'https://fcm.googleapis.com/fcm/send/example', 'public_key' => 'test', 'auth_token' => 'test', 'vapid_key_hash' => hash('sha256', 'test-public')]);
        $this->enable();
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $teacher->teacher()->create(['consultation_approved' => false]);
        Bus::assertDispatchedSync(SendBrowserPush::class, fn ($job) => $job->userId === $admin->id && $job->url === '/admin/accounts');
        $teacher->teacher->update(['consultation_approved' => true]);
        Bus::assertDispatchedSync(SendBrowserPush::class, fn ($job) => $job->userId === $teacher->id && str_contains($job->body, 'approved'));
    }
}
