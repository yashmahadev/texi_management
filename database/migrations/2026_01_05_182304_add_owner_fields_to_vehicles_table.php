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
            $table->boolean('is_driver_owner')->default(true)->after('driver_id');
            $table->string('owner_name')->nullable()->after('is_driver_owner');
            $table->string('owner_mobile')->nullable()->after('owner_name');
            $table->string('owner_aadhaar_number')->nullable()->after('owner_mobile');
            $table->string('owner_pancard_number')->nullable()->after('owner_aadhaar_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'is_driver_owner',
                'owner_name',
                'owner_mobile',
                'owner_aadhaar_number',
                'owner_pancard_number'
            ]);
        });
    }
};
