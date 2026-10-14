<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_level_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->timestamps();
            $table->unique(['grade_level_id', 'name']);
        });
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('grade_level_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::table('teachers', function (Blueprint $table) {
            $table->boolean('consultation_approved')->default(false);
            $table->unsignedSmallInteger('slot_minutes')->default(30);
            $table->boolean('auto_approve_bookings')->default(false);
            $table->boolean('show_student_names')->default(false);
        });
        Schema::create('subject_teacher', function (Blueprint $table) {
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->primary(['subject_id', 'teacher_id']);
        });
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('queue_name_visibility')->default('teacher_choice');
        });
        Schema::table('consultation_availabilities', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('consultation_availabilities', fn (Blueprint $table) => $table->dropColumn('cancelled_at'));
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('queue_name_visibility'));
        Schema::dropIfExists('subject_teacher');
        Schema::table('teachers', fn (Blueprint $table) => $table->dropColumn(['consultation_approved', 'slot_minutes', 'auto_approve_bookings', 'show_student_names']));
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
            $table->dropConstrainedForeignId('grade_level_id');
        });
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('grade_levels');
    }
};
