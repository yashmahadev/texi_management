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
        Schema::create('billing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_duty_log_id')->constrained('daily_duty_logs')->onDelete('cascade');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('start_km', 10, 2)->nullable();
            $table->decimal('end_km', 10, 2)->nullable();
            $table->decimal('total_km', 10, 2)->nullable();
            $table->string('status')->default('completed');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_logs');
    }
};
