<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_duty_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_duty_id')->constrained()->cascadeOnDelete();
            $table->date('duty_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('start_km')->nullable();
            $table->integer('end_km')->nullable();
            $table->integer('total_km')->default(0);
            $table->string('status')->default('pending'); // pending, started, completed, missing, disputed, replaced
            $table->string('start_photo_path')->nullable();
            $table->string('end_photo_path')->nullable();
            $table->timestamps();

            $table->index(['monthly_duty_id', 'duty_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_duty_logs');
    }
};
