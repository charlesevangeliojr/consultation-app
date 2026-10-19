<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(GradeLevelSeeder::class);
        Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
        Role::firstOrCreate(['name' => 'Student'], ['is_super_admin' => false]);

        $superAdminRole = Role::firstOrCreate(
            ['is_super_admin' => true],
            ['name' => 'Super Admin']
        );

        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrator',
                'role_id' => $superAdminRole->id,
                'password' => '*',
            ]
        );
    }
}
