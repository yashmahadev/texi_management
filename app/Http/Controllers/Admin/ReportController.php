<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyDuty;
use App\Services\PDFReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $pdfService;

    public function __construct(PDFReportService $pdfService)
    {
        $this->pdfService = $pdfService;
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

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query = \App\Models\DailyDutyLog::with(['monthlyDuty.vehicle', 'monthlyDuty.primaryDriver'])
                ->whereBetween('duty_date', [$request->start_date, $request->end_date]);

            if ($request->filled('monthly_duty_id')) {
                $query->where('monthly_duty_id', $request->monthly_duty_id);
            }

            $logs = $query->orderBy('duty_date')
                ->get()
                ->groupBy(function($log) {
                    return $log->duty_date->format('Y-m-d');
                });
        }

        $recentReports = collect();
        // $recentReports = collect(\Illuminate\Support\Facades\Storage::disk('public')->files('reports'))
        //     ->map(function($file) {
        //         return [
        //             'name' => basename($file),
        //             'url' => \Illuminate\Support\Facades\Storage::url($file),
        //             'date' => \Illuminate\Support\Facades\Storage::disk('public')->lastModified($file)
        //         ];
        //     })
        //     ->sortByDesc('date')
        //     ->take(5);

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

        // Bulk Update if logs provided (Admin/Operator only)
        if ($request->has('logs')) {
            foreach ($request->logs as $id => $data) {
                $log = \App\Models\DailyDutyLog::find($id);
                if ($log) {
                    $log->update([
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'start_km' => $data['start_km'],
                        'end_km' => $data['end_km'],
                        'total_km' => ($data['end_km'] && $data['start_km']) ? ($data['end_km'] - $data['start_km']) : $log->total_km,
                        'status' => $data['status'],
                    ]);
                }
            }
        }

        $pdf = $this->pdfService->generateBillProcessingReport($request->start_date, $request->end_date, $request->monthly_duty_id);
        
        // Save PDF to system
        $filename = 'bill_processing_' . $request->start_date . '_to_' . $request->end_date . '_' . time() . '.pdf';
        $path = 'reports/' . $filename;
        
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $pdf->output());

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
        $query = \App\Models\DailyDutyLog::with(['monthlyDuty.vehicle', 'monthlyDuty.primaryDriver'])
            ->whereBetween('duty_date', [$request->start_date, $request->end_date]);

        if ($request->filled('monthly_duty_id')) {
            $query->where('monthly_duty_id', $request->monthly_duty_id);
        }

        $logsCollection = $query->orderBy('duty_date')->get();

        if ($request->has('logs')) {
            foreach ($request->logs as $id => $data) {
                $log = $logsCollection->where('id', $id)->first();
                if ($log) {
                    $log->start_time = $data['start_time'];
                    $log->end_time = $data['end_time'];
                    $log->start_km = $data['start_km'];
                    $log->end_km = $data['end_km'];
                    $log->total_km = ($data['end_km'] && $data['start_km']) ? ($data['end_km'] - $data['start_km']) : $log->total_km;
                    $log->status = $data['status'];
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
        return $pdf->download('monthly_report_' . $monthlyDuty->id . '.pdf');
    }
}
