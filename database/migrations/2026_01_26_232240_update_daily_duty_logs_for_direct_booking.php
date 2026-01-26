<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('daily_duty_logs', function (Blueprint $table) {
            $table->foreignId('monthly_duty_id')->nullable()->change();
            $table->foreignId('direct_booking_id')->nullable()->after('monthly_duty_id')->constrained('direct_bookings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_duty_logs', function (Blueprint $table) {
            $table->dropForeign(['direct_booking_id']);
            $table->dropColumn('direct_booking_id');
            $table->foreignId('monthly_duty_id')->nullable(false)->change();
        });
    }
};
