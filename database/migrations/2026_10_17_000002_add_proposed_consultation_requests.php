<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('availability_id')->nullable()->change();
            $table->boolean('is_proposed')->default(false);
            $table->string('rejection_reason', 40)->nullable();
            $table->index(['is_proposed', 'status', 'starts_at'], 'proposed_requests_expiry_index');
        });
    }

    public function down(): void
    {
        if (DB::table('consultation_appointments')->whereNull('availability_id')->exists()) {
            throw new RuntimeException('Cannot remove proposed request support while requests without availability exist. No records were deleted.');
        }
        Schema::table('consultation_appointments', function (Blueprint $table) {
            $table->dropIndex('proposed_requests_expiry_index');
            $table->dropColumn(['is_proposed', 'rejection_reason']);
            $table->unsignedBigInteger('availability_id')->nullable(false)->change();
        });
    }
};
