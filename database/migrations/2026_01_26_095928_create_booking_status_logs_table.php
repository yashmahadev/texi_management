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
        Schema::create('booking_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('direct_bookings')->cascadeOnDelete();
            $table->string('from_status')->nullable(); // null for initial CREATED status
            $table->string('to_status');
            $table->unsignedBigInteger('changed_by')->nullable(); // User ID or Driver ID
            $table->string('changed_by_type')->nullable(); // App\Models\User or App\Models\Driver
            $table->text('remarks')->nullable();
            $table->json('metadata')->nullable(); // Additional data like KM, timestamps, location
            $table->timestamp('created_at')->useCurrent(); // Immutable - no updated_at

            $table->index(['booking_id', 'created_at']);
            $table->index('to_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_status_logs');
    }
};
