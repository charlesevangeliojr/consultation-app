<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_levels', fn (Blueprint $table) => $table->boolean('registration_enabled')->default(true));
        foreach (range(7, 12) as $grade) {
            DB::table('grade_levels')->insertOrIgnore([
                'name' => 'Grade '.$grade, 'registration_enabled' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Keep grade records and student/section assignments intact on rollback.
        Schema::table('grade_levels', fn (Blueprint $table) => $table->dropColumn('registration_enabled'));
    }
};
