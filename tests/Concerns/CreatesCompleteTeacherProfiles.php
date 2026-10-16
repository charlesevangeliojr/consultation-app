<?php

namespace Tests\Concerns;

use App\Models\Teacher;
use App\Models\User;
use App\Models\Subject;

trait CreatesCompleteTeacherProfiles
{
    protected function createCompleteTeacherProfile(User $user, array $attributes = []): Teacher
    {
        $profile = $user->teacher()->create($attributes + [
            'contact_number' => '+639123456789', 'phone_country' => 'PH', 'birthday' => '1990-05-10',
            'gender' => 'Female', 'address' => 'Fictional school town',
            'region' => 'National Capital Region (NCR)', 'city' => 'Quezon City', 'barangay' => 'Demo barangay',
            'consultation_offerings' => 'Academic support and exam preparation.',
            'profile_photo_path' => 'teacher-profiles/test-avatar.png',
        ]);
        $profile->subjects()->attach(Subject::firstOrCreate(['name' => 'General academic support']));
        return $profile;
    }

    protected function teacherProfileDetails(User $user): array
    {
        return ['name' => $user->name, 'email' => $user->email, 'phone_country' => 'PH', 'phone_number' => '9123456789',
            'birthday' => '1990-05-10', 'gender' => 'Female', 'region' => 'National Capital Region (NCR)',
            'province' => null, 'city' => 'Quezon City', 'barangay' => 'Demo barangay', 'house_number' => '12 Demo Street',
            'postal_code' => '1100', 'consultation_offerings' => 'Academic support and exam preparation.'];
    }
}
