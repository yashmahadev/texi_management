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

    public function download(MonthlyDuty $monthlyDuty)
    {
        // Check permissions via policy if needed
        // $this->authorize('view', $monthlyDuty);

        $pdf = $this->pdfService->generateMonthlyReport($monthlyDuty);
        
        return $pdf->download('monthly_report_' . $monthlyDuty->id . '.pdf');
    }
}
