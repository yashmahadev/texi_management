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
        Schema::table('monthly_duties', function (Blueprint $table) {
            $table->index('start_date');
            $table->index('end_date');
            $table->index('officer_name');
            $table->index('department_name');
        });

        Schema::table('daily_duty_logs', function (Blueprint $table) {
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_duties', function (Blueprint $table) {
            $table->dropIndex(['start_date']);
            $table->dropIndex(['end_date']);
            $table->dropIndex(['officer_name']);
            $table->dropIndex(['department_name']);
        });

        Schema::table('daily_duty_logs', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
