<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('action');
            // $table->foreignId('performed_by')->nullable()->constrained('users'); // Removed in favor of unconstrained for flexibility
            // If driver performed action, they are not in users table. So generic ID?
            // "performed_by" (FK users). User prompt says: "Every admin action... performed_by remarks".
            // Driver actions (log duty) might also need audit?
            // "performed_by" usually refers to User ID. If Driver, maybe store string or separate ID?
            // "performed_by" column in prompt creates FK ambiguity if drivers are not users.
            // Prompt: "audit_logs: performed_by".
            // I'll make it nullable and NOT constrained for now, OR constrained to users if only admins trigger audit logs.
            // Prompt says "Every admin action... Must create audit_logs". Doesn't explicitly say driver actions.
            // But "Driver: Start duty" is critical.
            // I'll leave it as nullable unsinged big integer without constraint, or add `performer_type` + `performer_id`.
            // Prompt defines: "performed_by". I'll stick to that. FK to `users` likely means Admin.
            // I'll remove constraint to allow flexible logging or Add `performed_by_type`.
            // But strict prompt says: "audit_logs - performed_by". I'll use simple integer, no strict foreign key constraint to avoid issues with drivers.
            // Actually, I'll assume it refers to Admin Users ID as per "Every admin action".
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
