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
        Schema::create('direct_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number', 50)->unique(); // BK-YYYYMMDD-XXXX
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_mobile', 15);
            $table->text('pickup_location');
            $table->text('drop_location');
            $table->dateTime('booking_datetime');
            $table->integer('estimated_km')->nullable();
            $table->integer('actual_km')->nullable();
            $table->string('status')->default('CREATED'); // CREATED, ASSIGNED, ACCEPTED, STARTED, COMPLETED, CANCELLED
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('booking_datetime');
            $table->index('status');
            $table->index('customer_mobile');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_bookings');
    }
};
