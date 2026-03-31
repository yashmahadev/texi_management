<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_duties', function (Blueprint $table) {
            $table->text('route_remarks')->nullable()->after('pincode');
        });

        Schema::table('direct_bookings', function (Blueprint $table) {
            $table->text('route_remarks')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_duties', function (Blueprint $table) {
            $table->dropColumn('route_remarks');
        });

        Schema::table('direct_bookings', function (Blueprint $table) {
            $table->dropColumn('route_remarks');
        });
    }
};
