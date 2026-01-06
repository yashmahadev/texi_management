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

    public function generateBillProcessingReport($startDate, $endDate, $monthlyDutyId = null, $prefetchedLogs = null)
    {
        if ($prefetchedLogs) {
            $logs = $prefetchedLogs;
        } else {
            $query = \App\Models\DailyDutyLog::with(['monthlyDuty.vehicle', 'monthlyDuty.primaryDriver'])
                ->whereBetween('duty_date', [$startDate, $endDate]);

            if ($monthlyDutyId) {
                $query->where('monthly_duty_id', $monthlyDutyId);
            }

            $logs = $query->orderBy('duty_date')
                ->get()
                ->groupBy(function($log) {
                    return $log->duty_date->format('Y-m-d');
                });
        }

        $pdf = Pdf::loadView('admin.reports.bill-processing-pdf', compact('logs', 'startDate', 'endDate'));
        
        return $pdf;
    }
}
