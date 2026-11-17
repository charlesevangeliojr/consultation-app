<?php

namespace Tests\Feature;

use App\Models\AccountNotification;
use App\Models\ConsultationAppointment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsultationReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_set_and_disable_reminders_and_other_roles_cannot(): void
    {
        $student = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $this->actingAs($student)->put(route('notifications.reminder.update'), ['minutes_before' => 30])->assertRedirect(route('notifications.settings'));
        $this->assertDatabaseHas('student_reminder_settings', ['user_id' => $student->id, 'minutes_before' => 30]);
        $this->get(route('notifications.settings'))->assertOk()->assertSee('Consultation reminder')->assertSee('value="30"', false);
        $this->put(route('notifications.reminder.update'), ['minutes_before' => 1441])->assertSessionHasErrors('minutes_before');
        $this->put(route('notifications.reminder.update'), ['minutes_before' => ''])->assertRedirect();
        $this->assertDatabaseHas('student_reminder_settings', ['user_id' => $student->id, 'minutes_before' => null]);
        $this->actingAs($teacher)->put(route('notifications.reminder.update'), ['minutes_before' => 10])->assertForbidden();
    }

    public function test_due_confirmed_reminders_are_sent_once_and_other_bookings_are_skipped(): void
    {
        $this->travelTo(now()->startOfMinute());
        config(['webpush.enabled' => false]);
        $student = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        DB::table('student_reminder_settings')->insert(['user_id' => $student->id, 'minutes_before' => 30]);
        $make = fn ($minutes, $status) => ConsultationAppointment::create([
            'teacher_id' => $teacher->id, 'student_id' => $student->id,
            'starts_at' => now()->addMinutes($minutes), 'ends_at' => now()->addMinutes($minutes + 30),
            'status' => $status, 'is_proposed' => true, 'topic' => 'Private topic',
        ]);
        $due = $make(30, 'confirmed');
        $later = $make(31, 'confirmed');
        $make(15, 'pending');
        $make(10, 'cancelled');
        $make(-1, 'confirmed');
        AccountNotification::query()->delete();
        $this->artisan('consultations:send-reminders')->assertSuccessful();
        $this->artisan('consultations:send-reminders')->assertSuccessful();
        $this->assertDatabaseHas('consultation_reminders', ['appointment_id' => $due->id]);
        $this->assertDatabaseMissing('consultation_reminders', ['appointment_id' => $later->id]);
        $this->assertSame(1, AccountNotification::where('user_id', $student->id)->count());
        $this->assertStringNotContainsString('Private topic', AccountNotification::where('user_id', $student->id)->first()->body);
        $this->travel(1)->minutes();
        $this->artisan('consultations:send-reminders')->assertSuccessful();
        $this->assertDatabaseHas('consultation_reminders', ['appointment_id' => $later->id]);
    }

    public function test_reminder_saved_before_any_consultation_automatically_applies_to_future_confirmed_bookings(): void
    {
        $this->travelTo(now()->startOfMinute());
        config(['webpush.enabled' => false]);
        $student = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);

        // Student has zero consultations yet and saves reminder preference on Settings.
        $this->assertSame(0, ConsultationAppointment::where('student_id', $student->id)->count());
        $this->actingAs($student)->put(route('notifications.reminder.update'), [
            'minutes_before' => 30,
            'return_to' => 'consultations.student.settings',
        ])->assertRedirect(route('consultations.student.settings'));

        $this->get(route('consultations.student.settings'))
            ->assertOk()
            ->assertSee('Active: 30 min before');

        // Later, a consultation is confirmed starting in 25 minutes.
        $appointment = ConsultationAppointment::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'starts_at' => now()->addMinutes(25),
            'ends_at' => now()->addMinutes(55),
            'status' => 'confirmed',
            'is_proposed' => true,
            'topic' => 'Pre-saved reminder test',
        ]);
        AccountNotification::query()->delete();

        $this->artisan('consultations:send-reminders')->assertSuccessful();
        $this->assertDatabaseHas('consultation_reminders', ['appointment_id' => $appointment->id]);
        $this->assertSame(1, AccountNotification::where('user_id', $student->id)->count());
    }
}
