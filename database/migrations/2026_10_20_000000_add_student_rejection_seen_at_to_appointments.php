<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_appointments', fn (Blueprint $table) => $table->timestamp('student_rejection_seen_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('consultation_appointments', fn (Blueprint $table) => $table->dropColumn('student_rejection_seen_at'));
    }
};
