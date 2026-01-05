<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('driving_licence_number')->nullable()->after('mobile_number');
            $table->string('driving_licence_document')->nullable()->after('driving_licence_number');
            $table->string('aadhaar_number')->nullable()->after('driving_licence_document');
            $table->string('aadhaar_document')->nullable()->after('aadhaar_number');
            $table->string('alternate_contact_number')->nullable()->after('aadhaar_document');
            $table->string('relationship_with_alternate_contact')->nullable()->after('alternate_contact_number');
            $table->string('state')->nullable()->after('relationship_with_alternate_contact');
            $table->string('city')->nullable()->after('state');
            $table->string('pincode')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn([
                'driving_licence_number',
                'driving_licence_document',
                'aadhaar_number',
                'aadhaar_document',
                'alternate_contact_number',
                'relationship_with_alternate_contact',
                'state',
                'city',
                'pincode'
            ]);
        });
    }
};
