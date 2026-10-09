<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->json('permissions');
            $table->timestamps();
        });

        if (! Schema::hasColumn('users', 'access_role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('access_role_id')->nullable()->constrained('access_roles')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'access_role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('access_role_id');
            });
        }

        Schema::dropIfExists('access_roles');
    }
};
