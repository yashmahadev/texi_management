<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_duties', function (Blueprint $table) {
            $table->id();
            $table->string('department_name');
            $table->string('officer_name');
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('primary_driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->time('expected_start_time');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_duties');
    }
};
