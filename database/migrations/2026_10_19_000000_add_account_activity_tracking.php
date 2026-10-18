<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('last_logged_out_at')->nullable();
            // Existing accounts receive a fresh baseline: past activity was not tracked.
            $table->timestamp('inactivity_reference_at')->nullable()->index();
        });
        DB::table('users')->update(['inactivity_reference_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_seen_at']);
            $table->dropIndex(['inactivity_reference_at']);
            $table->dropColumn(['last_seen_at', 'last_logged_out_at', 'inactivity_reference_at']);
        });
    }
};
