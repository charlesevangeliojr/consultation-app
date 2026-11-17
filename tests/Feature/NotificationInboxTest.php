<?php

namespace Tests\Feature;

use App\Models\AccountNotification;
use App\Models\ConsultationAppointment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    private function student(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
    }

    private function notice(User $user, string $body = 'Your consultation was updated.'): AccountNotification
    {
        return AccountNotification::create(['user_id' => $user->id, 'title' => 'Consultation update', 'body' => $body, 'url' => '/consultations/student/consultations']);
    }

    public function test_students_and_teachers_see_their_own_workspace_in_notification_pages(): void
    {
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        foreach ([[$this->student(), 'student-workspace', 'Student sidebar'], [$teacher, 'teacher-workspace', 'Teacher sidebar']] as [$user, $workspace, $sidebar]) {
            foreach (['notifications.index', 'notifications.settings'] as $route) {
                $this->actingAs($user)->get(route($route))->assertOk()
                    ->assertSee('id="'.$workspace.'"', false)
                    ->assertSee($sidebar)
                    ->assertDontSee('admin-workspace');
            }
        }
    }

    public function test_notification_destinations_cannot_send_students_or_teachers_to_admin_pages(): void
    {
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        foreach ([$this->student(), $teacher] as $user) {
            $notice = $this->notice($user);
            $notice->update(['url' => '/admin/accounts']);
            $this->actingAs($user)->post(route('notifications.read', $notice->id))->assertRedirect('/notifications');
        }
        $notice = $this->notice($teacher);
        $notice->update(['url' => '/consultations/teacher/requests']);
        $this->actingAs($teacher)->post(route('notifications.read', $notice->id))->assertRedirect('/consultations/teacher/requests');
    }

    public function test_inbox_badge_filters_and_read_actions_are_scoped_to_the_signed_in_user(): void
    {
        $student = $this->student();
        $other = $this->student();
        $mine = $this->notice($student, 'My private account update');
        $foreign = $this->notice($other, 'Another account update');
        $this->actingAs($student)->get(route('notifications.index'))->assertOk()->assertSee('My private account update')
            ->assertDontSee('Another account update')->assertSee('data-notification-count="1"', false)->assertSee('Unread');
        $this->getJson(route('notifications.summary'))->assertOk()->assertJson(['unread' => 1]);
        $this->post(route('notifications.read', $foreign->id))->assertNotFound();
        $this->assertNull($foreign->fresh()->read_at);
        $this->post(route('notifications.read', $mine->id))->assertRedirect('/consultations/student/consultations');
        $this->assertNotNull($mine->fresh()->read_at);
        $this->getJson(route('notifications.summary'))->assertOk()->assertJson(['unread' => 0]);
        $this->get(route('notifications.index', ['filter' => 'unread']))->assertOk()->assertDontSee('My private account update');
        $this->notice($student);
        $this->post(route('notifications.read-all'))->assertSessionHas('status');
        $this->assertSame(0, AccountNotification::where('user_id', $student->id)->whereNull('read_at')->count());
        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_notifications_are_saved_even_when_browser_push_is_disabled(): void
    {
        config(['webpush.enabled' => false]);
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $student = $this->student();
        $booking = ConsultationAppointment::create(['teacher_id' => $teacher->id, 'student_id' => $student->id,
            'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'status' => 'pending', 'is_proposed' => true, 'topic' => 'PRIVATE_REASON']);
        $this->assertSame(1, AccountNotification::where('user_id', $teacher->id)->count());
        $booking->update(['status' => 'confirmed']);
        $this->assertSame(1, AccountNotification::where('user_id', $student->id)->count());
        $this->assertStringNotContainsString('PRIVATE_REASON', AccountNotification::where('user_id', $student->id)->first()->body);
        $this->actingAs($student)->get(route('consultations.dashboard'))->assertOk()->assertSee('data-notification-bell', false)
            ->assertSee('data-notification-count="1"', false);
    }

    public function test_opening_inbox_clears_only_the_existing_snapshot_not_newer_or_other_users_notifications(): void
    {
        $student = $this->student();
        $old = $this->notice($student);
        $other = $this->notice($this->student());
        $opened = $this->actingAs($student)->get(route('notifications.index'))->assertOk();
        $through = $opened->viewData('openedThrough');
        $this->assertSame($old->id, $through);
        $new = $this->notice($student, 'Arrived after opening');
        $this->postJson(route('notifications.read-all'), ['through' => $through])->assertOk()->assertJson(['unread' => 1]);
        $this->assertNotNull($old->fresh()->read_at);
        $this->assertNull($new->fresh()->read_at);
        $this->assertNull($other->fresh()->read_at);
        $this->postJson(route('notifications.read-all'), ['through' => $new->id])->assertOk()->assertJson(['unread' => 0]);
        $this->postJson(route('notifications.read-all'), ['through' => -1])->assertUnprocessable();
    }

    public function test_admins_get_approval_inbox_alerts_without_a_push_subscription(): void
    {
        config(['webpush.enabled' => false]);
        $admin = User::factory()->superAdmin()->create();
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $teacher->teacher()->create(['consultation_approved' => false]);
        $this->actingAs($admin)->get(route('notifications.index'))->assertOk()->assertSee('Teacher scheduling approval')
            ->assertSee('data-notification-count="1"', false);
        $this->post(route('notifications.read', AccountNotification::where('user_id', $admin->id)->first()->id))->assertRedirect('/admin/accounts');
    }

    public function test_missing_inbox_table_shows_setup_guidance_and_unsafe_destinations_never_redirect_offsite(): void
    {
        $student = $this->student();
        $notice = $this->notice($student);
        $notice->update(['url' => 'https://evil.example']);
        $this->actingAs($student)->post(route('notifications.read', $notice->id))->assertRedirect('/notifications');
        \Illuminate\Support\Facades\Schema::drop('account_notifications');
        $this->get(route('notifications.index'))->assertOk()->assertSee('Notification database setup is missing')->assertDontSee('SQLSTATE');
        $this->getJson(route('notifications.summary'))->assertOk()->assertJson(['unread' => 0]);
    }

    public function test_scheduling_permission_notifications_redirect_when_approved_and_hide_view_update_when_no_redirection(): void
    {
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $teacher->teacher()->create(['consultation_approved' => false]);

        $teacher->teacher->update(['consultation_approved' => true]);
        $incompleteNotice = AccountNotification::where('user_id', $teacher->id)->latest('id')->first();
        $this->assertSame('/consultations/teacher/profile', $incompleteNotice->url);
        $this->actingAs($teacher)->get(route('notifications.index'))->assertOk()->assertSee('View update →');
        $this->actingAs($teacher)->post(route('notifications.read', $incompleteNotice->id))->assertRedirect('/consultations/teacher/profile');

        $teacher->teacher->delete();
        $this->createCompleteTeacherProfile($teacher, ['consultation_approved' => false]);
        $teacher->refresh();
        $teacher->teacher->update(['consultation_approved' => true]);
        $approvedNotice = AccountNotification::where('user_id', $teacher->id)->latest('id')->first();
        $this->assertSame('/consultations/teacher/availability', $approvedNotice->url);
        $this->actingAs($teacher)->post(route('notifications.read', $approvedNotice->id))->assertRedirect('/consultations/teacher/availability');
        $this->actingAs($teacher)->post(route('notifications.read', $incompleteNotice->id))->assertRedirect('/consultations/teacher/availability');

        $teacher->teacher->fresh()->update(['consultation_approved' => false]);
        $revokedNotice = AccountNotification::where('user_id', $teacher->id)->latest('id')->first();
        AccountNotification::where('user_id', $teacher->id)->where('id', '!=', $revokedNotice->id)->delete();
        $this->assertSame('', $revokedNotice->url);
        $this->actingAs($teacher)->get(route('notifications.index'))->assertOk()
            ->assertDontSee('View update →')
            ->assertSee('Mark as read');
        $this->actingAs($teacher)->post(route('notifications.read', $revokedNotice->id))->assertRedirect('/notifications');
        $this->assertNotNull($revokedNotice->fresh()->read_at);
        $this->actingAs($teacher)->get(route('notifications.index'))->assertOk()
            ->assertDontSee('View update →')
            ->assertDontSee('Mark as read');
    }
}
