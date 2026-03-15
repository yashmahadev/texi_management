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
        Schema::create('booking_fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('direct_bookings')->cascadeOnDelete();
            $table->decimal('base_fare', 10, 2);
            $table->decimal('per_km_rate', 10, 2);
            $table->integer('total_km');
            $table->decimal('calculated_fare', 10, 2); // base_fare + (total_km * per_km_rate)
            $table->decimal('admin_adjusted_fare', 10, 2)->nullable(); // Admin override
            $table->decimal('final_fare', 10, 2); // effective fare (adjusted or calculated)
            $table->text('adjustment_reason')->nullable();
            $table->boolean('is_locked')->default(false); // Locked after trip completion
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_locked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_fares');
    }
};
