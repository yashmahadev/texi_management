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
        Schema::table('direct_bookings', function (Blueprint $table) {
            // Add end datetime for multi-day bookings
            $table->dateTime('booking_end_datetime')->nullable()->after('booking_datetime');
            // Add index for better query performance
            $table->index('booking_end_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('direct_bookings', function (Blueprint $table) {
            $table->dropIndex(['booking_end_datetime']);
            $table->dropColumn('booking_end_datetime');
        });
    }
};
