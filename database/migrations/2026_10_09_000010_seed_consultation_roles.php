<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['Teacher', 'Student'] as $name) {
            DB::table('roles')->updateOrInsert(
                ['name' => $name],
                ['is_super_admin' => false, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    public function down(): void
    {
        DB::table('roles')->whereIn('name', ['Teacher', 'Student'])
            ->whereNotIn('id', DB::table('users')->whereNotNull('role_id')->select('role_id'))
            ->delete();
    }
};
