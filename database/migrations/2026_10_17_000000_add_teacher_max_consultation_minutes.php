<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_consultation_minutes')->default(30);
        });
    }

    public function down(): void
    {
        Schema::table('teachers', fn (Blueprint $table) => $table->dropColumn('max_consultation_minutes'));
    }
};
