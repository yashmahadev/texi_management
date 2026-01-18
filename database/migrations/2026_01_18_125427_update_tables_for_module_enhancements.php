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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('fuel_type')->nullable()->after('vehicle_type'); // Petrol, Diesel, CNG
            $table->string('transmission_type')->nullable()->after('fuel_type'); // Automatic, Manual
            $table->string('color')->nullable()->after('transmission_type');
            $table->string('make_model')->nullable()->after('color');
            $table->string('vehicle_type_custom')->nullable()->after('make_model'); // For "Bus" text input
            $table->enum('pass_type', ['Private', 'Taxi'])->nullable()->after('vehicle_type_custom');
            $table->integer('challan_count')->default(0)->after('pass_type');
            $table->decimal('challan_amount', 10, 2)->default(0)->after('challan_count');
            $table->string('rc_book_path')->nullable()->after('challan_amount');
            $table->text('insurance_details')->nullable()->after('rc_book_path');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->integer('age')->nullable()->after('name');
            $table->date('dl_expiry')->nullable()->after('driving_licence_number');
            $table->text('address')->nullable()->after('pincode');
            $table->boolean('is_police_verified')->default(false)->after('address');
            $table->string('police_verification_document')->nullable()->after('is_police_verified');
        });

        Schema::table('monthly_duties', function (Blueprint $table) {
            $table->enum('group', ['Government', 'Corporate'])->nullable()->after('officer_name');
            $table->foreignId('department_id')->nullable()->after('group')->constrained()->nullOnDelete();
            // We'll keep department_name for now but department_id is preferred
        });
    }

    public function down(): void
    {
        Schema::table('monthly_duties', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['group', 'department_id']);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['age', 'dl_expiry', 'address', 'is_police_verified', 'police_verification_document']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn([
                'owner_id', 'fuel_type', 'transmission_type', 'color', 
                'make_model', 'vehicle_type_custom', 'pass_type', 
                'challan_count', 'challan_amount', 'rc_book_path', 'insurance_details'
            ]);
        });
    }
};
