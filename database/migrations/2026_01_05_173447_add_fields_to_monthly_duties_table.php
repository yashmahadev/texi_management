<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_duties', function (Blueprint $table) {
            $table->string('state')->nullable()->after('expected_start_time');
            $table->string('city')->nullable()->after('state');
            $table->string('pincode')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_duties', function (Blueprint $table) {
            $table->dropColumn(['state', 'city', 'pincode']);
        });
    }
};
