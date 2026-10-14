<?php

namespace Database\Seeders;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoConsultationSeeder extends Seeder
{
    public const PASSWORD = 'DemoConsult!2026';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo accounts must not be seeded in production.');
        }

        DB::transaction(function () {
            $adminRole = Role::firstOrCreate(['is_super_admin' => true], ['name' => 'Super Admin']);
            $teacherRole = Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
            $studentRole = Role::firstOrCreate(['name' => 'Student'], ['is_super_admin' => false]);

            $sections = [];
            foreach (['Grade 10' => ['Newton', 'Galileo'], 'Grade 11' => ['Einstein', 'Curie'], 'Grade 12' => ['Tesla', 'Darwin']] as $gradeName => $names) {
                $grade = GradeLevel::firstOrCreate(['name' => $gradeName]);
                foreach ($names as $name) {
                    $sections[$name] = Section::firstOrCreate(['grade_level_id' => $grade->id, 'name' => $name]);
                }
            }
            $subjects = collect(['Mathematics', 'Science', 'English', 'Filipino', 'Computer Studies'])
                ->mapWithKeys(fn ($name) => [$name => Subject::firstOrCreate(['name' => $name])]);

            $this->account('demo.admin@example.com', 'Demo School Administrator', $adminRole);
            $teachers = [];
            foreach ([
                ['demo.teacher1@example.com', 'Maria Santos', true, false, false, 30, ['Mathematics', 'Science']],
                ['demo.teacher2@example.com', 'Daniel Reyes', true, true, true, 20, ['English', 'Filipino']],
                ['demo.teacher3@example.com', 'Angela Cruz', false, false, false, 45, ['Computer Studies']],
            ] as [$email, $name, $approved, $autoApprove, $showNames, $minutes, $handled]) {
                $user = $this->account($email, $name, $teacherRole);
                // Never alter an existing account, profile, preference, or subject assignment.
                if ($user->wasRecentlyCreated) {
                    $profile = $user->teacher()->create([
                        'consultation_approved' => $approved,
                        'auto_approve_bookings' => $autoApprove,
                        'show_student_names' => $showNames,
                        'slot_minutes' => $minutes,
                    ]);
                    $profile->subjects()->attach(collect($handled)->map(fn ($subject) => $subjects[$subject]->id));
                    $teachers[] = $user;
                } else {
                    $teachers[] = null;
                }
            }

            $students = [];
            foreach ([
                ['demo.student1@example.com', 'Alex Mendoza', 'Einstein', '990000000001', '9170000001', 'Male'],
                ['demo.student2@example.com', 'Bea Garcia', 'Curie', '990000000002', '9170000002', 'Female'],
                ['demo.student3@example.com', 'Carlo Ramos', 'Tesla', '990000000003', '9170000003', 'Male'],
                ['demo.student4@example.com', 'Diana Flores', 'Newton', '990000000004', '9170000004', 'Female'],
            ] as [$email, $name, $sectionName, $lrn, $phone, $gender]) {
                $user = $this->account($email, $name, $studentRole);
                if ($user->wasRecentlyCreated) {
                    $section = $sections[$sectionName];
                    $user->student()->create([
                        'grade_level_id' => $section->grade_level_id,
                        'section_id' => $section->id,
                        'lrn' => $lrn,
                        'contact_number' => '+63'.$phone,
                        'birthday' => '2008-05-15',
                        'gender' => $gender,
                        'address' => 'Demo address, Quezon City (fictional)',
                        'status' => 'active',
                    ]);
                    $students[] = $user;
                } else {
                    $students[] = null;
                }
            }

            foreach (array_slice($teachers, 0, 2) as $index => $teacher) {
                if (! $teacher) continue;
                for ($day = 1; $day <= 3; $day++) {
                    $date = today()->addDays($day);
                    $period = ConsultationAvailability::create([
                        'teacher_id' => $teacher->id,
                        'date' => $date->toDateString(),
                        'starts_at' => $index === 0 ? '09:00' : '13:00',
                        'ends_at' => $index === 0 ? '11:00' : '15:00',
                        'slot_minutes' => $teacher->teacher->slot_minutes,
                        'location' => $index === 0 ? 'Demo Room 201' : 'Demo Faculty Office',
                    ]);
                    if ($day === 1 && $index === 0) {
                        $this->booking($period, $students[0], '09:00', 'pending', 'Help understanding algebra');
                        $this->booking($period, $students[1], '09:30', 'confirmed', 'Science project feedback');
                    }
                    if ($day === 1 && $index === 1) {
                        $this->booking($period, $students[2], '13:00', 'confirmed', 'English essay review');
                        $this->booking($period, $students[3], '13:20', 'confirmed', 'Filipino presentation preparation');
                    }
                }
            }

            if ($teachers[0]) {
                $cancelled = ConsultationAvailability::create([
                    'teacher_id' => $teachers[0]->id,
                    'date' => today()->addDay()->toDateString(),
                    'starts_at' => '15:00', 'ends_at' => '16:00', 'slot_minutes' => 30,
                    'location' => 'Demo Room 201', 'cancelled_at' => now(),
                ]);
                $this->booking($cancelled, $students[0], '15:00', 'rejected', 'Demo: teacher cancelled this availability');
            }
        });

        $this->command?->info('Demo data added. Existing accounts and records were not changed.');
        $this->command?->info('New demo accounts use password: '.self::PASSWORD);
    }

    private function account(string $email, string $name, Role $role): User
    {
        return User::firstOrCreate(['email' => $email], [
            'name' => $name, 'role_id' => $role->id, 'password' => self::PASSWORD,
        ]);
    }

    private function booking(ConsultationAvailability $period, ?User $student, string $time, string $status, string $topic): void
    {
        if (! $student) return;
        $start = $period->date->copy()->setTimeFromTimeString($time);
        ConsultationAppointment::create([
            'availability_id' => $period->id,
            'teacher_id' => $period->teacher_id,
            'student_id' => $student->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes($period->slot_minutes),
            'status' => $status,
            'topic' => $topic,
        ]);
    }
}
