<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DemoConsultationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoConsultationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_has_working_credentials_profiles_and_future_bookings(): void
    {
        $this->seed(DemoConsultationSeeder::class);
        $this->assertDatabaseCount('users', 8);
        $this->assertSame(6, GradeLevel::count());
        $this->assertSame(6, Section::count());
        $this->assertSame(5, Subject::count());
        $this->assertSame(7, ConsultationAvailability::count());
        $this->assertSame(5, ConsultationAppointment::count());
        foreach (User::all() as $user) {
            $this->assertTrue(Hash::check(DemoConsultationSeeder::PASSWORD, $user->password));
            if ($user->isStudent()) $this->assertNotNull($user->student->section);
            if ($user->isTeacher()) $this->assertNotEmpty($user->teacher->subjects);
        }
        $admin = User::where('email', 'demo.admin@example.com')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => DemoConsultationSeeder::PASSWORD])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->assertTrue(ConsultationAppointment::first()->starts_at->isFuture());
    }

    public function test_reseeding_does_not_duplicate_data_or_overwrite_edits(): void
    {
        $this->seed(DemoConsultationSeeder::class);
        $teacher = User::where('email', 'demo.teacher1@example.com')->firstOrFail();
        $teacher->update(['name' => 'Edited teacher', 'password' => 'ChangedPassword!2026']);
        $teacher->teacher->update(['slot_minutes' => 45, 'consultation_approved' => false]);
        $this->seed(DemoConsultationSeeder::class);
        $this->assertDatabaseCount('users', 8);
        $this->assertDatabaseCount('consultation_availabilities', 7);
        $this->assertDatabaseCount('consultation_appointments', 5);
        $this->assertSame('Edited teacher', $teacher->fresh()->name);
        $this->assertTrue(Hash::check('ChangedPassword!2026', $teacher->fresh()->password));
        $this->assertSame(45, $teacher->teacher->fresh()->slot_minutes);
        $this->assertFalse($teacher->teacher->fresh()->consultation_approved);
    }
}
