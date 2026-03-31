<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_duties', function (Blueprint $table) {
            // Drop old complex recurrence columns
            if (Schema::hasColumn('monthly_duties', 'parent_duty_id')) {
                $table->dropForeign(['parent_duty_id']);
                $table->dropColumn('parent_duty_id');
            }
            if (Schema::hasColumn('monthly_duties', 'recurrence_end_date')) {
                $table->dropColumn('recurrence_end_date');
            }
            if (Schema::hasColumn('monthly_duties', 'recurrence_type')) {
                $table->dropColumn('recurrence_type');
            }

            // Add simple recurring checkbox flag
            $table->boolean('is_recurring')->default(false)->after('route_remarks');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_duties', function (Blueprint $table) {
            $table->dropColumn('is_recurring');
            $table->string('recurrence_type')->default('none');
            $table->date('recurrence_end_date')->nullable();
            $table->unsignedBigInteger('parent_duty_id')->nullable();
            $table->foreign('parent_duty_id')->references('id')->on('monthly_duties')->nullOnDelete();
        });
    }
};
