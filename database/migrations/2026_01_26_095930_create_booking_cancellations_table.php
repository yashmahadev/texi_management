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
        Schema::create('booking_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('direct_bookings')->cascadeOnDelete();
            $table->unsignedBigInteger('cancelled_by'); // User ID or Driver ID
            $table->string('cancelled_by_type'); // App\Models\User or App\Models\Driver
            $table->text('cancellation_reason');
            $table->timestamp('cancelled_at')->useCurrent(); // Immutable - no created_at or updated_at

            $table->index('cancelled_by_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_cancellations');
    }
};
