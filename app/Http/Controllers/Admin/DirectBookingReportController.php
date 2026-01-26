<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DirectBooking;
use App\Services\DirectBookingService;
use App\Services\PDFReportService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DirectBookingReportController extends Controller
{
    protected $bookingService;
    protected $pdfService;

    public function __construct(DirectBookingService $bookingService, PDFReportService $pdfService)
    {
        $this->bookingService = $bookingService;
        $this->pdfService = $pdfService;
    }

    public function index()
    {
        return view('admin.reports.direct_bookings.index');
    }

    public function dailyTrips(Request $request)
    {
        $fromDate = $request->get('from_date', Carbon::today()->toDateString());
        $toDate = $request->get('to_date', Carbon::today()->toDateString());

        $bookings = $this->bookingService->getBookingsList([
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'per_page' => 100,
        ]);

        return view('admin.reports.direct_bookings.daily_trips', compact('bookings', 'fromDate', 'toDate'));
    }

    public function revenueSummary(Request $request)
    {
        $fromDate = $request->get('from_date', Carbon::now()->startOfMonth()->toDateString());
        $toDate = $request->get('to_date', Carbon::now()->endOfMonth()->toDateString());

        $bookings = DirectBooking::whereIn('status', ['COMPLETED'])
            ->where(function($q) use ($fromDate, $toDate) {
                $q->whereDate('booking_datetime', '>=', $fromDate)
                  ->whereDate('booking_datetime', '<=', $toDate);
            })
            ->with(['fare', 'customer'])
            ->get();

        $totalRevenue = $bookings->sum(function($booking) {
            return $booking->fare ? $booking->fare->final_fare : 0;
        });

        return view('admin.reports.direct_bookings.revenue_summary', compact('bookings', 'totalRevenue', 'fromDate', 'toDate'));
    }
}
