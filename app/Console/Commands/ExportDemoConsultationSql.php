<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DemoConsultationSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class ExportDemoConsultationSql extends Command
{
    protected $signature = 'demo:export-sql';

    protected $description = 'Export only fictional demo consultation data as a GitHub-safe MySQL data file';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Demo exports are disabled in production.');
            return self::FAILURE;
        }

        $emails = ['demo.admin@example.com', 'demo.teacher1@example.com', 'demo.teacher2@example.com', 'demo.teacher3@example.com', 'demo.student1@example.com', 'demo.student2@example.com', 'demo.student3@example.com', 'demo.student4@example.com'];
        $users = User::with('role')->whereIn('email', $emails)->orderBy('email')->get();
        if ($users->count() !== count($emails) || $users->contains(fn ($user) => ! Hash::check(DemoConsultationSeeder::PASSWORD, $user->password))) {
            $this->error('All eight demo accounts must exist with the documented demo password. No export was written.');
            return self::FAILURE;
        }

        $lines = [
            '-- ConSync fictional demo data only (MySQL / MariaDB).',
            '-- Apply Laravel migrations BEFORE importing. No database/schema is created here.',
            '-- Demo password: DemoConsult!2026. Public testing credentials, not production accounts.',
            '-- Existing matching records are preserved. IDs are resolved by email/name, not copied.',
            '-- Dates are a snapshot; use DemoConsultationSeeder for relative future dates on a fresh install.',
            '-- See docs/demo-data.md for credentials and import instructions.',
            '',
            'SET @DEMO_PREVIOUS_SQL_MODE = @@SESSION.sql_mode;',
            "SET SESSION sql_mode = 'NO_BACKSLASH_ESCAPES';",
            'START TRANSACTION;',
            '',
        ];
        $insert = function (string $table, array $values, string $existing) use (&$lines): void {
            $columns = implode(', ', array_map(fn ($column) => '`'.$column.'`', array_keys($values)));
            $lines[] = "INSERT INTO `{$table}` ({$columns}, `created_at`, `updated_at`)";
            $lines[] = 'SELECT '.implode(', ', $values).", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS ({$existing});";
            $lines[] = '';
        };
        $q = fn ($value) => $this->quote($value);
        $userId = fn ($email) => '(SELECT `id` FROM `users` WHERE `email` = '.$q($email).' LIMIT 1)';
        $gradeId = fn ($name) => '(SELECT `id` FROM `grade_levels` WHERE `name` = '.$q($name).' LIMIT 1)';
        $subjectId = fn ($name) => '(SELECT `id` FROM `subjects` WHERE `name` = '.$q($name).' LIMIT 1)';

        $insert('roles', ['name' => $q('Super Admin'), 'is_super_admin' => '1'], 'SELECT 1 FROM `roles` WHERE `is_super_admin` = 1');
        foreach (['Teacher', 'Student'] as $role) {
            $insert('roles', ['name' => $q($role), 'is_super_admin' => '0'], 'SELECT 1 FROM `roles` WHERE `name` = '.$q($role));
        }

        $grades = DB::table('grade_levels')->whereIn('name', ['Grade 10', 'Grade 11', 'Grade 12'])->get()->keyBy('id');
        foreach ($grades as $grade) {
            $insert('grade_levels', ['name' => $q($grade->name)], 'SELECT 1 FROM `grade_levels` WHERE `name` = '.$q($grade->name));
        }
        $sections = DB::table('sections')->whereIn('grade_level_id', $grades->keys())->whereIn('name', ['Newton', 'Galileo', 'Einstein', 'Curie', 'Tesla', 'Darwin'])->get()->keyBy('id');
        $sectionLookup = function ($section) use ($q, $gradeId, $grades) {
            return '`grade_level_id` = '.$gradeId($grades[$section->grade_level_id]->name).' AND `name` = '.$q($section->name);
        };
        foreach ($sections as $section) {
            $insert('sections', ['grade_level_id' => $gradeId($grades[$section->grade_level_id]->name), 'name' => $q($section->name)], 'SELECT 1 FROM `sections` WHERE '.$sectionLookup($section));
        }
        $subjects = DB::table('subjects')->whereIn('name', ['Mathematics', 'Science', 'English', 'Filipino', 'Computer Studies'])->get()->keyBy('id');
        foreach ($subjects as $subject) {
            $insert('subjects', ['name' => $q($subject->name)], 'SELECT 1 FROM `subjects` WHERE `name` = '.$q($subject->name));
        }

        foreach ($users as $user) {
            $role = $user->role->is_super_admin
                ? '(SELECT `id` FROM `roles` WHERE `is_super_admin` = 1 LIMIT 1)'
                : '(SELECT `id` FROM `roles` WHERE `name` = '.$q($user->role->name).' LIMIT 1)';
            $insert('users', ['name' => $q($user->name), 'email' => $q($user->email), 'password' => $q($user->password), 'role_id' => $role], 'SELECT 1 FROM `users` WHERE `email` = '.$q($user->email));
        }

        $teachers = DB::table('teachers')->whereIn('user_id', $users->pluck('id'))->get()->keyBy('id');
        foreach ($teachers as $teacher) {
            $id = $userId($users->firstWhere('id', $teacher->user_id)->email);
            $insert('teachers', [
                'user_id' => $id, 'consultation_approved' => $q($teacher->consultation_approved), 'slot_minutes' => $q($teacher->slot_minutes),
                'auto_approve_bookings' => $q($teacher->auto_approve_bookings), 'show_student_names' => $q($teacher->show_student_names),
            ], 'SELECT 1 FROM `teachers` WHERE `user_id` = '.$id);
        }
        foreach (DB::table('subject_teacher')->whereIn('teacher_id', $teachers->keys())->whereIn('subject_id', $subjects->keys())->get() as $assignment) {
            $teacherUser = $users->firstWhere('id', $teachers[$assignment->teacher_id]->user_id);
            $teacherId = '(SELECT `id` FROM `teachers` WHERE `user_id` = '.$userId($teacherUser->email).' LIMIT 1)';
            $subject = $subjectId($subjects[$assignment->subject_id]->name);
            $lines[] = "INSERT INTO `subject_teacher` (`teacher_id`, `subject_id`) SELECT {$teacherId}, {$subject} WHERE NOT EXISTS (SELECT 1 FROM `subject_teacher` WHERE `teacher_id` = {$teacherId} AND `subject_id` = {$subject});";
            $lines[] = '';
        }
        foreach (DB::table('students')->whereIn('user_id', $users->pluck('id'))->get() as $student) {
            if (! isset($grades[$student->grade_level_id], $sections[$student->section_id])) {
                $this->error('A demo student has a non-demo class assignment. No export was written.');
                return self::FAILURE;
            }
            $id = $userId($users->firstWhere('id', $student->user_id)->email);
            $values = ['user_id' => $id];
            foreach (['contact_number', 'birthday', 'gender', 'address', 'status', 'lrn'] as $column) $values[$column] = $q($student->$column);
            $values['grade_level_id'] = $gradeId($grades[$student->grade_level_id]->name);
            $values['section_id'] = '(SELECT `id` FROM `sections` WHERE '.$sectionLookup($sections[$student->section_id]).' LIMIT 1)';
            $insert('students', $values, 'SELECT 1 FROM `students` WHERE `user_id` = '.$id);
        }

        $periods = DB::table('consultation_availabilities')->whereIn('teacher_id', $teachers->pluck('user_id'))->orderBy('id')->get()->keyBy('id');
        $periodLookup = function ($period) use ($userId, $users, $q) {
            return '`teacher_id` = '.$userId($users->firstWhere('id', $period->teacher_id)->email).' AND `date` = '.$q($period->date).' AND `starts_at` = '.$q($period->starts_at).' AND `ends_at` = '.$q($period->ends_at);
        };
        foreach ($periods as $period) {
            $values = ['teacher_id' => $userId($users->firstWhere('id', $period->teacher_id)->email)];
            foreach (['date', 'starts_at', 'ends_at', 'slot_minutes', 'location', 'cancelled_at'] as $column) $values[$column] = $q($period->$column);
            $insert('consultation_availabilities', $values, 'SELECT 1 FROM `consultation_availabilities` WHERE '.$periodLookup($period));
        }
        foreach (DB::table('consultation_appointments')->whereIn('availability_id', $periods->keys())->whereIn('student_id', $users->pluck('id'))->orderBy('id')->get() as $booking) {
            $periodId = '(SELECT `id` FROM `consultation_availabilities` WHERE '.$periodLookup($periods[$booking->availability_id]).' LIMIT 1)';
            $studentId = $userId($users->firstWhere('id', $booking->student_id)->email);
            $values = ['availability_id' => $periodId, 'teacher_id' => $userId($users->firstWhere('id', $booking->teacher_id)->email), 'student_id' => $studentId];
            foreach (['starts_at', 'ends_at', 'topic', 'status'] as $column) $values[$column] = $q($booking->$column);
            $insert('consultation_appointments', $values, 'SELECT 1 FROM `consultation_appointments` WHERE `availability_id` = '.$periodId.' AND `student_id` = '.$studentId.' AND `starts_at` = '.$q($booking->starts_at));
        }

        $lines[] = 'COMMIT;';
        $lines[] = 'SET SESSION sql_mode = @DEMO_PREVIOUS_SQL_MODE;';
        $lines[] = '';
        $destination = database_path('sql/demo-consultation-data.sql');
        File::ensureDirectoryExists(dirname($destination));
        File::put($destination, implode("\n", $lines));
        $this->info('Demo-only SQL exported to database/sql/demo-consultation-data.sql');
        return self::SUCCESS;
    }

    private function quote(mixed $value): string
    {
        if ($value === null) return 'NULL';
        if (is_bool($value) || is_int($value) || is_float($value)) return (string) (int) $value;
        return "'".str_replace("'", "''", (string) $value)."'";
    }
}
