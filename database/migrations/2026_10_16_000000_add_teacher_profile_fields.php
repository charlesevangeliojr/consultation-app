<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('contact_number', 30)->nullable();
            $table->date('birthday')->nullable();
            $table->string('gender', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('profile_photo_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['contact_number', 'birthday', 'gender', 'address', 'profile_photo_path']);
        });
    }
};
