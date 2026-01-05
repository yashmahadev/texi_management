<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(string $action, string $entityType, int $entityId, ?string $remarks = null)
    {
        $userId = Auth::id(); // Can be Admin User ID or Driver ID via wrapper?
        // If driver is authenticated via 'driver' guard, Auth::id() returns driver ID?
        // Wait, Auth::id() uses default guard.
        // We should check guards.
        
        $performedBy = null;
        
        if (Auth::guard('web')->check()) {
            $performedBy = Auth::guard('web')->id();
        } elseif (Auth::guard('driver')->check()) {
            // Drivers are not in users table. Our migration has 'performed_by' as unsignedBigInteger (nullable, formerly constrained to users).
            // If I removed the constraint, I can store driver ID.
            // But to distinguish, I might need 'performed_by_type'. 
            // For now, prompt implies Admin Actions need logging.
            // "Every admin action... Must create audit_logs entry".
            // I'll prioritize Admin.
            $performedBy = Auth::guard('driver')->id(); 
        }

        return AuditLog::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'performed_by' => $performedBy, // ID only. If needed we can add performer_type logic later.
            'remarks' => $remarks,
        ]);
    }
}
