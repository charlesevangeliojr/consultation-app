<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('site_settings', 'show_super_admins_to_admins')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('show_super_admins_to_admins');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('site_settings', 'show_super_admins_to_admins')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->boolean('show_super_admins_to_admins')->default(false);
            });
        }
    }
};
