<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'favicon_path')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->string('favicon_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'favicon_path')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('favicon_path');
            });
        }
    }
};
