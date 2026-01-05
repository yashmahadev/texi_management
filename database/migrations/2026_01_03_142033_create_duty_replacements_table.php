<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_duty_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_driver_id')->constrained('drivers');
            $table->foreignId('replacement_driver_id')->constrained('drivers');
            $table->string('reason');
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_replacements');
    }
};
