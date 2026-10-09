<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('slot_minutes')->default(30);
            $table->string('location')->nullable();
            $table->timestamps();
            $table->index(['teacher_id', 'date']);
        });

        Schema::create('consultation_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('availability_id')->constrained('consultation_availabilities')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('topic', 500)->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->index(['teacher_id', 'starts_at', 'status']);
            $table->index(['student_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_appointments');
        Schema::dropIfExists('consultation_availabilities');
    }
};
