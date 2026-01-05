<?php

namespace App\Services;

use App\Models\MonthlyDuty;
use Barryvdh\DomPDF\Facade\Pdf;

class PDFReportService
{
    public function generateMonthlyReport(MonthlyDuty $monthlyDuty)
    {
        $monthlyDuty->load(['dailyLogs', 'vehicle', 'primaryDriver', 'creator']);
        
        // Calculate totals
        $totalKm = $monthlyDuty->dailyLogs->sum('total_km');
        $totalDuties = $monthlyDuty->dailyLogs->count();
        $completedDuties = $monthlyDuty->dailyLogs->where('status', 'completed')->count();

        $pdf = Pdf::loadView('admin.reports.monthly-pdf', compact('monthlyDuty', 'totalKm', 'totalDuties', 'completedDuties'));
        
        return $pdf;
    }
}
