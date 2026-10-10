<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('contact_number', 30)->nullable();
            $table->date('birthday')->nullable();
            $table->string('gender', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('active');
            $table->string('lrn', 12)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['lrn']);
            $table->dropColumn(['contact_number', 'birthday', 'gender', 'address', 'status', 'lrn']);
        });
    }
};
