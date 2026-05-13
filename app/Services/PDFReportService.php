<?php

namespace App\Services;

use App\Models\MonthlyDuty;
use App\Models\DailyDutyLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PDFReportService
{
    /**
     * One aggregated row per date is the correct billing view.
     * Multiple logs on the same date are merged: KM summed,
     * earliest start time, latest end time, worst-case status.
     */
    public function generateMonthlyReport(MonthlyDuty $monthlyDuty)
    {
        $monthlyDuty->load(['dailyLogs', 'vehicle', 'primaryDriver', 'creator']);

        // Aggregate: one row per calendar date
        $aggregatedRows = $this->aggregateLogsByDate($monthlyDuty->dailyLogs);

        $totalKm         = $aggregatedRows->sum('total_km');
        $totalDuties     = $aggregatedRows->count();
        $completedDuties = $aggregatedRows->where('status', 'completed')->count();
        $company         = $this->getCompanySettings();

        // Scale factor so content always fits one page regardless of row count
        $scale = $this->computeScale($aggregatedRows->count(), 'monthly');

        $pdf = Pdf::loadView('admin.reports.monthly-pdf', compact(
            'monthlyDuty', 'aggregatedRows', 'totalKm', 'totalDuties', 'completedDuties', 'company', 'scale'
        ))
        ->setPaper('a4', 'portrait')
        ->setOption('dpi', 96)
        ->setOption('isHtml5ParserEnabled', true)
        ->setOption('isRemoteEnabled', false);

        return $pdf;
    }

    public function generateBillProcessingReport(
        string $startDate,
        string $endDate,
        ?int $monthlyDutyId = null,
        $prefetchedLogs = null
    ) {
        if ($prefetchedLogs) {
            // $prefetchedLogs is already grouped by date (Collection of Collections)
            $rawLogs = $prefetchedLogs->flatten();
        } else {
            $query = DailyDutyLog::with(['monthlyDuty.vehicle', 'monthlyDuty.primaryDriver', 'billingLog'])
                ->whereBetween('duty_date', [$startDate, $endDate]);

            if ($monthlyDutyId) {
                $query->where('monthly_duty_id', $monthlyDutyId);
            }

            $rawLogs = $query->orderBy('duty_date')->get()
                ->map(function ($log) {
                    if ($log->billingLog) {
                        $log->start_time = $log->billingLog->start_time;
                        $log->end_time   = $log->billingLog->end_time;
                        $log->start_km   = $log->billingLog->start_km;
                        $log->end_km     = $log->billingLog->end_km;
                        $log->total_km   = $log->billingLog->total_km;
                        $log->status     = $log->billingLog->status;
                    }
                    return $log;
                });
        }

        // Aggregate to one row per date
        $aggregatedRows = $this->aggregateLogsByDate($rawLogs);

        $company = $this->getCompanySettings();
        $scale   = $this->computeScale($aggregatedRows->count(), 'bill');

        $pdf = Pdf::loadView('admin.reports.bill-processing-pdf', compact(
            'aggregatedRows', 'startDate', 'endDate', 'company', 'scale'
        ))
        ->setPaper('a4', 'portrait')
        ->setOption('dpi', 96)
        ->setOption('isHtml5ParserEnabled', true)
        ->setOption('isRemoteEnabled', false);

        return $pdf;
    }

    /**
     * Collapse multiple DailyDutyLog rows for the same date into one summary row.
     * Returns a Collection of plain objects, one per unique duty_date.
     */
    private function aggregateLogsByDate(Collection $logs): Collection
    {
        return $logs
            ->groupBy(fn ($log) => Carbon::parse($log->duty_date)->format('Y-m-d'))
            ->map(function ($dayLogs, $date) {
                $statusPriority = ['missing' => 0, 'disputed' => 1, 'pending' => 2,
                                   'started' => 3, 'replaced' => 4, 'completed' => 5];

                // Pick the "worst" status (lowest priority = most attention needed)
                $status = $dayLogs->sortBy(fn ($l) => $statusPriority[strtolower($l->status ?? 'pending')] ?? 2)
                                  ->first()->status ?? 'pending';

                // Earliest non-null start_time, latest non-null end_time
                $startTimes = $dayLogs->pluck('start_time')->filter()->sort()->values();
                $endTimes   = $dayLogs->pluck('end_time')->filter()->sortDesc()->values();

                // For KM: use first log's start_km and last log's end_km if available,
                // otherwise sum total_km across all logs for the day
                $firstLog = $dayLogs->sortBy('start_km')->first();
                $lastLog  = $dayLogs->sortByDesc('end_km')->first();

                $startKm = $firstLog->start_km ?? null;
                $endKm   = $lastLog->end_km ?? null;
                $totalKm = ($startKm !== null && $endKm !== null)
                    ? max(0, $endKm - $startKm)
                    : $dayLogs->sum('total_km');

                // Carry the first log's relationships for vehicle/driver/dept display
                $representative = $dayLogs->first();

                return (object) [
                    'duty_date'   => Carbon::parse($date),
                    'start_time'  => $startTimes->first(),
                    'end_time'    => $endTimes->first(),
                    'start_km'    => $startKm,
                    'end_km'      => $endKm,
                    'total_km'    => $totalKm,
                    'status'      => $status,
                    'trip_count'  => $dayLogs->count(),
                    'monthlyDuty' => $representative->monthlyDuty ?? null,
                ];
            })
            ->sortKeys()
            ->values();
    }

    /**
     * Compute CSS zoom so all rows fit on exactly one A4 page.
     *
     * Calibrated against real DomPDF output:
     *  - ≤15 rows  → 1.0  (plenty of room)
     *  - ≤20 rows  → 0.95
     *  - ≤25 rows  → 0.88
     *  - ≤31 rows  → 0.82  (full month, confirmed 1-page at this value)
     *  - ≤38 rows  → 0.74
     *  - >38 rows  → 0.68  (hard floor — still legible)
     */
    private function computeScale(int $rowCount, string $type): float
    {
        if ($rowCount <= 15) return 1.0;
        if ($rowCount <= 20) return 0.95;
        if ($rowCount <= 25) return 0.88;
        if ($rowCount <= 31) return 0.82;
        if ($rowCount <= 38) return 0.74;
        return 0.68;
    }

    private function getCompanySettings(): array
    {
        return [
            'name'    => \App\Models\Setting::get('company_name', 'Government Vehicle Log Book'),
            'address' => \App\Models\Setting::get('company_address'),
            'mobile'  => \App\Models\Setting::get('company_mobile'),
            'email'   => \App\Models\Setting::get('company_email'),
            'logo'    => \App\Models\Setting::get('company_logo'),
            'gst'     => \App\Models\Setting::get('company_gst'),
        ];
    }
}
