<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use Illuminate\Database\Seeder;

class GradeLevelSeeder extends Seeder
{
    public function run(): void
    {
        foreach (GradeLevel::STANDARD_NAMES as $name) {
            GradeLevel::firstOrCreate(['name' => $name], ['registration_enabled' => true]);
        }
    }
}
