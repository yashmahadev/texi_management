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
        $performerType = null;
        
        if (Auth::guard('web')->check()) {
            $performedBy = Auth::guard('web')->id();
            $performerType = \App\Models\User::class;
        } elseif (Auth::guard('driver')->check()) {
            $performedBy = Auth::guard('driver')->id(); 
            $performerType = \App\Models\Driver::class;
        }

        return AuditLog::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'performed_by' => $performedBy,
            'performer_type' => $performerType,
            'remarks' => $remarks,
        ]);
    }
}
