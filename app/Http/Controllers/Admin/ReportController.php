<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyDuty;
use App\Services\PDFReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $pdfService;
    protected $auditLogger;

    public function __construct(PDFReportService $pdfService, \App\Services\AuditLogService $auditLogger)
    {
        $this->pdfService = $pdfService;
        $this->auditLogger = $auditLogger;
    }

    public function index()
    {
        $duties = MonthlyDuty::latest()->paginate(20);
        return view('admin.reports.index', compact('duties'));
    }

    public function billProcessing(Request $request)
    {
        $duties = MonthlyDuty::with(['vehicle', 'primaryDriver'])->latest()->get();
        $logs = collect();

        if ($request->filled('monthly_duty_id')) {
            $duty = MonthlyDuty::findOrFail($request->monthly_duty_id);
            
            $query = \App\Models\DailyDutyLog::with(['monthlyDuty.vehicle', 'monthlyDuty.primaryDriver', 'billingLog'])
                ->where('monthly_duty_id', $duty->id)
                ->orderBy('duty_date');

            $logs = $query->get()
                ->groupBy(function($log) {
                    return $log->duty_date->format('Y-m-d');
                });
            
            // For the form hidden inputs
            $request->merge([
                'start_date' => $duty->start_date->toDateString(),
                'end_date' => $duty->end_date->toDateString()
            ]);
        }

        $recentReports = collect();

        return view('admin.reports.bill_processing', compact('logs', 'recentReports', 'duties'));
    }

    public function downloadBillProcessing(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'logs' => 'nullable|array',
            'logs.*.start_time' => 'nullable',
            'logs.*.end_time' => 'nullable',
            'logs.*.start_km' => 'nullable|numeric',
            'logs.*.end_km' => 'nullable|numeric',
            'logs.*.status' => 'required|string',
        ]);

        // Save to BillingLog table instead of original logs
        if ($request->has('logs')) {
            foreach ($request->logs as $id => $data) {
                \App\Models\BillingLog::updateOrCreate(
                    ['daily_duty_log_id' => $id],
                    [
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'start_km' => $data['start_km'],
                        'end_km' => $data['end_km'],
                        'total_km' => ($data['end_km'] && $data['start_km']) ? ($data['end_km'] - $data['start_km']) : 0,
                        'status' => $data['status'],
                        'created_by' => \Illuminate\Support\Facades\Auth::id(),
                    ]
                );
            }
        }

        $pdf = $this->pdfService->generateBillProcessingReport($request->start_date, $request->end_date, $request->monthly_duty_id);
        
        // Save PDF to system
        $filename = 'bill_processing_' . $request->start_date . '_to_' . $request->end_date . '_' . time() . '.pdf';
        $path = 'reports/' . $filename;
        
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $pdf->output());

        $this->auditLogger->log('Download Bill Report', 'reports', 0, "Downloaded bill processing report from {$request->start_date} to {$request->end_date}");

        return $pdf->download($filename);
    }

    public function previewBillProcessingPDF(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'logs' => 'nullable|array',
        ]);

        $groupedLogs = $this->getProcessedLogsCollection($request);
        $pdf = $this->pdfService->generateBillProcessingReport($request->start_date, $request->end_date, $request->monthly_duty_id, $groupedLogs);
        
        return $pdf->download('PREVIEW_bill_processing_' . $request->start_date . '_to_' . $request->end_date . '.pdf');
    }

    private function getProcessedLogsCollection(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $dutyId = $request->monthly_duty_id;

        if ($dutyId && (!$startDate || !$endDate)) {
            $duty = MonthlyDuty::find($dutyId);
            if ($duty) {
                $startDate = $duty->start_date->toDateString();
                $endDate = $duty->end_date->toDateString();
            }
        }

        $query = \App\Models\DailyDutyLog::with(['monthlyDuty.vehicle', 'monthlyDuty.primaryDriver', 'billingLog'])
            ->whereBetween('duty_date', [$startDate, $endDate]);

        if ($dutyId) {
            $query->where('monthly_duty_id', $dutyId);
        }

        $logsCollection = $query->orderBy('duty_date')->get();

        if ($request->has('logs')) {
            foreach ($request->logs as $id => $data) {
                $log = $logsCollection->where('id', $id)->first();
                if ($log) {
                    // Create an in-memory replica with overridden billing data
                    $log->start_time = $data['start_time'];
                    $log->end_time = $data['end_time'];
                    $log->start_km = $data['start_km'];
                    $log->end_km = $data['end_km'];
                    $log->total_km = ($data['end_km'] && $data['start_km']) ? ($data['end_km'] - $data['start_km']) : 0;
                    $log->status = $data['status'];
                }
            }
        } else {
            // If no form logs submitted, but we want to preview, use existing billing logs if any
            foreach ($logsCollection as $log) {
                if ($log->billingLog) {
                    $log->start_time = $log->billingLog->start_time;
                    $log->end_time = $log->billingLog->end_time;
                    $log->start_km = $log->billingLog->start_km;
                    $log->end_km = $log->billingLog->end_km;
                    $log->total_km = $log->billingLog->total_km;
                    $log->status = $log->billingLog->status;
                }
            }
        }

        return $logsCollection->groupBy(function($log) {
            return $log->duty_date->format('Y-m-d');
        });
    }

    public function download(MonthlyDuty $monthlyDuty)
    {
        $pdf = $this->pdfService->generateMonthlyReport($monthlyDuty);
        
        $this->auditLogger->log('Download Monthly Report', 'monthly_duties', $monthlyDuty->id, "Downloaded monthly report for {$monthlyDuty->department_name}");

        return $pdf->download('monthly_report_' . $monthlyDuty->id . '.pdf');
    }
}
