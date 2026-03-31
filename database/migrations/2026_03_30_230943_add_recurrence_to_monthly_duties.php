<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_duties', function (Blueprint $table) {
            $table->string('recurrence_type')->default('none')->after('route_remarks'); // none, weekly, monthly
            $table->date('recurrence_end_date')->nullable()->after('recurrence_type');
            $table->unsignedBigInteger('parent_duty_id')->nullable()->after('recurrence_end_date');
            $table->foreign('parent_duty_id')->references('id')->on('monthly_duties')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('monthly_duties', function (Blueprint $table) {
            $table->dropForeign(['parent_duty_id']);
            $table->dropColumn(['recurrence_type', 'recurrence_end_date', 'parent_duty_id']);
        });
    }
};
