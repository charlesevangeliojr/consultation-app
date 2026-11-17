<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('student_reminder_settings')) {
            Schema::create('student_reminder_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('minutes_before')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('consultation_reminders')) {
            Schema::create('consultation_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('appointment_id')->unique()->constrained('consultation_appointments')->cascadeOnDelete();
                $table->timestamp('sent_at');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_reminders');
        Schema::dropIfExists('student_reminder_settings');
    }
};
