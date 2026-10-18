<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Section;
use App\Models\SiteSetting;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolConsultationWorkflowTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    private function account(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['is_super_admin' => $roleName === 'Super Admin']);
        $user = User::factory()->create(['role_id' => $role->id]);
        if ($user->isTeacher()) $this->createCompleteTeacherProfile($user);
        if ($user->isStudent()) $user->student()->create();
        return $user;
    }

    private function period(User $teacher, string $from = '10:00', string $until = '11:00'): ConsultationAvailability
    {
        return ConsultationAvailability::create([
            'teacher_id' => $teacher->id,
            'date' => today()->addDay()->toDateString(),
            'starts_at' => $from,
            'ends_at' => $until,
            'slot_minutes' => 30,
        ]);
    }

    private function book(User $student, ConsultationAvailability $period, string $time = '10:00')
    {
        return $this->actingAs($student)->post(route('consultations.book', $period), [
            'starts_at' => $period->date->toDateString().' '.$time,
            'ends_at' => \Illuminate\Support\Carbon::parse($period->date->toDateString().' '.$time)->addMinutes(30)->toDateTimeString(),
            'topic' => 'Private course discussion',
        ]);
    }

    public function test_unapproved_teacher_cannot_publish_or_receive_bookings(): void
    {
        $teacher = $this->account('Teacher');
        $student = $this->account('Student');
        $this->actingAs($teacher)->post(route('consultations.availability.store'), [
            'date' => today()->addDay()->toDateString(), 'starts_at' => '10:00', 'ends_at' => '11:00',
        ])->assertForbidden();
        $this->assertDatabaseCount('consultation_availabilities', 0);
        $period = $this->period($teacher);
        $this->book($student, $period)->assertForbidden();
        $this->actingAs($student)->get(route('consultations.browse'))->assertDontSee($teacher->name);
        $this->assertDatabaseCount('consultation_appointments', 0);
    }

    public function test_admin_approves_teacher_without_subjects_and_preserves_existing_assignments(): void
    {
        $admin = $this->account('Super Admin');
        $teacher = $this->account('Teacher');
        $subject = Subject::create(['name' => 'Physics']);
        $teacher->teacher->subjects()->attach($subject);
        $existingSubjects = $teacher->teacher->subjects()->orderBy('subjects.id')->pluck('subjects.id')->all();
        $this->actingAs($admin)->get(route('admin.accounts.edit', $teacher))->assertOk()->assertSee('Consultation permission')->assertDontSee('name="subject_ids[]"', false);
        $this->put(route('admin.accounts.school-profile', $teacher), [
            'consultation_approved' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue($teacher->teacher->fresh()->consultation_approved);
        $this->assertSame($existingSubjects, $teacher->teacher->subjects()->orderBy('subjects.id')->pluck('subjects.id')->all());
        $this->actingAs($teacher)->post(route('consultations.availability.store'), [
            'date' => today()->addDay()->toDateString(), 'starts_at' => '10:00', 'ends_at' => '11:00',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('consultation_availabilities', 1);
    }

    public function test_teacher_settings_cannot_self_approve_and_preserve_historical_availability(): void
    {
        $teacher = $this->account('Teacher');
        $this->actingAs($teacher)->put(route('consultations.teacher.settings'), [
            'max_consultation_minutes' => 30, 'auto_approve_bookings' => 1, 'show_student_names' => 1, 'consultation_approved' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertFalse($teacher->teacher->fresh()->consultation_approved);
        $teacher->teacher->update(['consultation_approved' => true]);
        $this->post(route('consultations.availability.store'), [
            'date' => today()->addDay()->toDateString(), 'starts_at' => '10:00', 'ends_at' => '11:00',
        ])->assertSessionHasNoErrors();
        $period = ConsultationAvailability::firstOrFail();
        $this->assertSame(30, $period->slot_minutes);
        $this->put(route('consultations.teacher.settings'), [
            'max_consultation_minutes' => 60, 'auto_approve_bookings' => 0, 'show_student_names' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertSame(30, $period->fresh()->slot_minutes);
        $this->assertSame(60, $teacher->teacher->fresh()->max_consultation_minutes);
    }

    public function test_auto_approval_and_manual_approval_are_teacher_controlled(): void
    {
        $teacher = $this->account('Teacher');
        $teacher->teacher->update(['consultation_approved' => true, 'auto_approve_bookings' => true]);
        $period = $this->period($teacher);
        $student = $this->account('Student');
        $this->book($student, $period)->assertSessionHasNoErrors();
        $this->assertSame('confirmed', ConsultationAppointment::firstOrFail()->status);
        $teacher->teacher->update(['auto_approve_bookings' => false]);
        $this->book($student, $period, '10:30')->assertSessionHasNoErrors();
        $pending = ConsultationAppointment::latest('id')->firstOrFail();
        $this->assertSame('pending', $pending->status);
        $admin = $this->account('Super Admin');
        $this->actingAs($admin)->patch(route('consultations.appointments.update', $pending), ['status' => 'confirmed'])->assertForbidden();
        $this->actingAs($teacher)->patch(route('consultations.appointments.update', $pending), ['status' => 'confirmed'])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $pending->fresh()->status);
    }

    public function test_cancelling_availability_rejects_active_queue_and_preserves_history(): void
    {
        $teacher = $this->account('Teacher');
        $teacher->teacher->update(['consultation_approved' => true]);
        $period = $this->period($teacher, '10:00', '12:00');
        $student = $this->account('Student');
        $this->book($student, $period)->assertSessionHasNoErrors();
        $this->book($student, $period, '10:30')->assertSessionHasNoErrors();
        $this->book($student, $period, '11:00')->assertSessionHasNoErrors();
        $bookings = ConsultationAppointment::orderBy('id')->get();
        $bookings[1]->update(['status' => 'confirmed']);
        $bookings[2]->update(['status' => 'completed']);
        $this->actingAs($teacher)->patch(route('consultations.availability.cancel', $period))->assertSessionHasNoErrors();
        $this->assertNotNull($period->fresh()->cancelled_at);
        $this->assertSame(['rejected', 'rejected', 'completed'], ConsultationAppointment::orderBy('id')->pluck('status')->all());
        $this->book($student, $period, '11:30')->assertStatus(422);
        $this->actingAs($student)->get(route('consultations.dashboard'))->assertOk()->assertSee('automatically rejected');
        $this->actingAs($teacher)->patch(route('consultations.appointments.update', $bookings[0]), ['status' => 'confirmed'])->assertSessionHasErrors('status');
    }

    public function test_student_can_cancel_and_book_again_but_not_cancel_another_students_booking(): void
    {
        $teacher = $this->account('Teacher');
        $teacher->teacher->update(['consultation_approved' => true]);
        $student = $this->account('Student');
        $other = $this->account('Student');
        $period = $this->period($teacher);
        $this->book($student, $period)->assertSessionHasNoErrors();
        $booking = ConsultationAppointment::firstOrFail();
        $this->actingAs($other)->patch(route('consultations.appointments.cancel', $booking))->assertNotFound();
        $this->actingAs($student)->patch(route('consultations.appointments.cancel', $booking))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->book($student, $period)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('consultation_appointments', 2);
    }

    public function test_queue_privacy_obeys_teacher_preference_and_school_override(): void
    {
        $teacher = $this->account('Teacher');
        $teacher->teacher->update(['consultation_approved' => true]);
        $student = $this->account('Student');
        $student->update(['name' => 'Private Student Name']);
        $viewer = $this->account('Student');
        $period = $this->period($teacher);
        $this->book($student, $period)->assertSessionHasNoErrors();
        $this->actingAs($viewer)->get(route('consultations.browse'))->assertOk()->assertDontSee('Private Student Name')->assertDontSee('Private course discussion')->assertSee('Student (name hidden)');
        $teacher->teacher->update(['show_student_names' => true]);
        $this->get(route('consultations.browse'))->assertSee('Private Student Name')->assertDontSee('Private course discussion');
        SiteSetting::current()->update(['queue_name_visibility' => 'hide_all']);
        $this->get(route('consultations.browse'))->assertDontSee('Private Student Name');
        $teacher->teacher->update(['show_student_names' => false]);
        SiteSetting::current()->update(['queue_name_visibility' => 'show_all']);
        $this->get(route('consultations.browse'))->assertSee('Private Student Name');
        SiteSetting::current()->update(['queue_name_visibility' => 'hide_all']);
        $this->actingAs($student)->get(route('consultations.browse'))->assertSee('You');
    }

    public function test_queue_is_ordered_by_time_not_booking_order(): void
    {
        $teacher = $this->account('Teacher');
        $teacher->teacher->update(['consultation_approved' => true, 'show_student_names' => true]);
        $late = $this->account('Student');
        $late->update(['name' => 'Later Student']);
        $early = $this->account('Student');
        $early->update(['name' => 'Earlier Student']);
        $viewer = $this->account('Student');
        $period = $this->period($teacher);
        $this->book($late, $period, '10:30')->assertSessionHasNoErrors();
        $this->book($early, $period)->assertSessionHasNoErrors();
        $this->actingAs($viewer)->get(route('consultations.browse'))->assertSeeInOrder(['Earlier Student', 'Later Student']);
    }

    public function test_student_cannot_book_overlapping_consultations_with_different_teachers(): void
    {
        $teacher = $this->account('Teacher');
        $other = $this->account('Teacher');
        $teacher->teacher->update(['consultation_approved' => true]);
        $other->teacher->update(['consultation_approved' => true]);
        $student = $this->account('Student');
        $this->book($student, $this->period($teacher))->assertSessionHasNoErrors();
        $this->book($student, $this->period($other))->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('consultation_appointments', 1);
    }

    public function test_invalid_slot_missing_reason_and_past_slot_are_rejected(): void
    {
        $teacher = $this->account('Teacher');
        $teacher->teacher->update(['consultation_approved' => true]);
        $student = $this->account('Student');
        $period = $this->period($teacher);
        $this->book($student, $period, '09:50')->assertSessionHasErrors('starts_at');
        $this->actingAs($student)->post(route('consultations.book', $period), ['starts_at' => $period->date->toDateString().' 10:00'])->assertSessionHasErrors('topic');
        $this->post(route('consultations.book', $period), ['starts_at' => now()->subHour()->toDateTimeString(), 'topic' => 'Discussion'])->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('consultation_appointments', 0);
    }

    public function test_revoking_permission_blocks_confirmation_and_booking(): void
    {
        $teacher = $this->account('Teacher');
        $teacher->teacher->update(['consultation_approved' => true]);
        $student = $this->account('Student');
        $period = $this->period($teacher);
        $this->book($student, $period)->assertSessionHasNoErrors();
        $booking = ConsultationAppointment::firstOrFail();
        $teacher->teacher->update(['consultation_approved' => false]);
        $this->actingAs($teacher)->patch(route('consultations.appointments.update', $booking), ['status' => 'confirmed'])->assertForbidden();
        $this->book($student, $period, '10:30')->assertForbidden();
    }

    public function test_admin_manages_school_lists_and_cannot_delete_used_options(): void
    {
        $admin = $this->account('Super Admin');
        $this->actingAs($admin)->get(route('admin.school'))->assertOk();
        $this->post(route('admin.school.store', 'grade-levels'), ['name' => 'Grade 11'])->assertSessionHasNoErrors();
        $grade = GradeLevel::firstOrFail();
        $this->post(route('admin.school.store', 'sections'), ['name' => 'Einstein', 'grade_level_id' => $grade->id])->assertSessionHasNoErrors();
        $section = Section::firstOrFail();
        $student = $this->account('Student');
        $this->actingAs($admin)->put(route('admin.accounts.school-profile', $student), [
            'grade_level_id' => $grade->id, 'section_id' => $section->id, 'status' => 'active', 'lrn' => '123456789012',
        ])->assertSessionHasNoErrors();
        $this->assertSame($section->id, $student->student->fresh()->section_id);
        $this->get(route('admin.accounts.edit', $student))->assertOk()->assertSee('Student school profile');
        $this->delete(route('admin.school.destroy', ['sections', $section->id]))->assertSessionHasErrors('catalog');
        $this->delete(route('admin.school.destroy', ['grade-levels', $grade->id]))->assertSessionHasErrors('catalog');
        $this->put(route('admin.school.update', ['sections', $section->id]), ['name' => 'Curie'])->assertSessionHasNoErrors();
        $unused = GradeLevel::create(['name' => 'Unused grade']);
        $this->delete(route('admin.school.destroy', ['grade-levels', $unused->id]))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('grade_levels', ['id' => $unused->id]);
    }

    public function test_admin_subject_management_is_removed(): void
    {
        $admin = $this->account('Super Admin');
        $subject = Subject::create(['name' => 'Legacy subject']);
        $teacher = $this->account('Teacher');
        $teacher->teacher->subjects()->attach($subject);
        $existingSubjects = $teacher->teacher->subjects()->orderBy('subjects.id')->pluck('subjects.id')->all();
        $this->actingAs($admin)->get(route('admin.school'))->assertOk()->assertDontSee('Subjects')->assertDontSee('Legacy subject');
        $this->get(route('admin.consultations'))->assertOk()->assertDontSee('Legacy subject')->assertDontSee('No subjects assigned');
        $this->post(route('admin.school.store', 'subjects'), ['name' => 'New subject'])->assertNotFound();
        $this->put(route('admin.school.update', ['subjects', $subject->id]), ['name' => 'Changed subject'])->assertNotFound();
        $this->delete(route('admin.school.destroy', ['subjects', $subject->id]))->assertNotFound();
        $this->put(route('admin.accounts.school-profile', $teacher), ['consultation_approved' => 1, 'subject_ids' => []])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'name' => 'Legacy subject']);
        $this->assertSame($existingSubjects, $teacher->teacher->subjects()->orderBy('subjects.id')->pluck('subjects.id')->all());
    }

    public function test_students_and_teachers_cannot_use_admin_management_routes(): void
    {
        foreach (['Student', 'Teacher'] as $role) {
            $user = $this->account($role);
            $this->actingAs($user)->get(route('admin.school'))->assertForbidden();
            $this->post(route('admin.school.store', 'subjects'), ['name' => 'Unauthorized'])->assertForbidden();
            $this->get(route('admin.consultations'))->assertForbidden();
        }
    }

    public function test_admin_monitoring_is_read_only_and_school_privacy_is_editable(): void
    {
        $admin = $this->account('Super Admin');
        $this->actingAs($admin)->get(route('admin.consultations'))->assertOk()->assertSee('Read-only');
        $this->put(route('admin.settings.update'), ['name' => 'School', 'queue_name_visibility' => 'hide_all'])->assertSessionHasNoErrors();
        $this->assertSame('hide_all', SiteSetting::current()->queue_name_visibility);
    }

    public function test_other_teacher_cannot_cancel_availability_or_change_booking(): void
    {
        $teacher = $this->account('Teacher');
        $teacher->teacher->update(['consultation_approved' => true]);
        $other = $this->account('Teacher');
        $student = $this->account('Student');
        $period = $this->period($teacher);
        $this->book($student, $period)->assertSessionHasNoErrors();
        $this->actingAs($other)->patch(route('consultations.availability.cancel', $period))->assertNotFound();
        $this->patch(route('consultations.appointments.update', ConsultationAppointment::firstOrFail()), ['status' => 'confirmed'])->assertNotFound();
    }
}
