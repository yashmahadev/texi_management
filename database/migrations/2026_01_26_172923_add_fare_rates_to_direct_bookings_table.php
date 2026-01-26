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
            $table->decimal('base_fare', 10, 2)->nullable()->after('drop_location');
            $table->decimal('per_km_rate', 10, 2)->nullable()->after('base_fare');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('direct_bookings', function (Blueprint $table) {
            $table->dropColumn(['base_fare', 'per_km_rate']);
        });
    }
};
