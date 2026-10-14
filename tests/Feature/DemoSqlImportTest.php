<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\GradeLevel;
use App\Models\User;
use Database\Seeders\DemoConsultationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoSqlImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sql_snapshot_imports_demo_data_without_fixed_ids_or_duplicates(): void
    {
        $existing = User::factory()->create(['email' => 'existing@example.com']);
        GradeLevel::create(['name' => 'Existing grade']);
        // SQLite runs the portable INSERT statements; MySQL session settings and
        // transaction wrappers are separately checked against the local MySQL DB.
        $sql = file_get_contents(database_path('sql/demo-consultation-data.sql'));
        $dataStatements = preg_replace('/^(SET .*;|START TRANSACTION;|COMMIT;)\r?$/m', '', $sql);
        DB::unprepared($dataStatements);

        $this->assertDatabaseCount('users', 9);
        $this->assertDatabaseCount('students', 4);
        $this->assertDatabaseCount('teachers', 3);
        $this->assertSame(7, ConsultationAvailability::count());
        $this->assertSame(5, ConsultationAppointment::count());
        $this->assertSame($existing->name, $existing->fresh()->name);
        foreach (User::where('email', 'like', 'demo.%@example.com')->get() as $user) {
            $this->assertTrue(Hash::check(DemoConsultationSeeder::PASSWORD, $user->password));
        }
        $student = User::where('email', 'demo.student1@example.com')->firstOrFail();
        $this->assertSame('Grade 11', $student->student->gradeLevel->name);
        $this->assertSame('Einstein', $student->student->section->name);

        $teacher = User::where('email', 'demo.teacher1@example.com')->firstOrFail();
        $teacher->update(['name' => 'Edited teacher']);
        DB::unprepared($dataStatements);
        $this->assertDatabaseCount('users', 9);
        $this->assertDatabaseCount('consultation_availabilities', 7);
        $this->assertDatabaseCount('consultation_appointments', 5);
        $this->assertSame('Edited teacher', $teacher->fresh()->name);
    }
}
