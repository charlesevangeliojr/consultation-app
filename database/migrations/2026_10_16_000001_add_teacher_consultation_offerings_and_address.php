<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('phone_country', 2)->nullable();
            $table->string('house_number', 100)->nullable();
            $table->string('region', 150)->nullable();
            $table->string('province', 150)->nullable();
            $table->string('city', 150)->nullable();
            $table->string('barangay', 150)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('consultation_offerings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['phone_country', 'house_number', 'region', 'province', 'city', 'barangay', 'postal_code', 'consultation_offerings']);
        });
    }
};
