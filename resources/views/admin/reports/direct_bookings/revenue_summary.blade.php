@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Revenue Summary (Direct Bookings)</h2>
        <p class="text-muted mb-0">Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</p>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-outline-primary d-print-none me-2">
            <i class="bi bi-printer me-2"></i> Print
        </button>
        <a href="{{ route('admin.reports.direct-bookings.index') }}" class="btn btn-outline-secondary d-print-none">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm bg-primary text-white">
            <div class="card-body py-4">
                <h6 class="text-uppercase mb-2 opacity-75">Total Revenue</h6>
                <h2 class="mb-0">₹{{ number_format($totalRevenue, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm bg-success text-white">
            <div class="card-body py-4">
                <h6 class="text-uppercase mb-2 opacity-75">Completed Trips</h6>
                <h2 class="mb-0">{{ $bookings->count() }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm bg-info text-white">
            <div class="card-body py-4">
                <h6 class="text-uppercase mb-2 opacity-75">Avg. Revenue / Trip</h6>
                <h2 class="mb-0">₹{{ $bookings->count() > 0 ? number_format($totalRevenue / $bookings->count(), 2) : '0.00' }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Completed Trips Breakdown</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Booking #</th>
                    <th>Customer</th>
                    <th>KM</th>
                    <th class="text-end">Base Fare</th>
                    <th class="text-end">KM Fare</th>
                    <th class="text-end">Final Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                <tr>
                    <td>{{ $booking->booking_datetime->format('d M Y') }}</td>
                    <td class="fw-bold">{{ $booking->booking_number }}</td>
                    <td>{{ $booking->customer_name }}</td>
                    <td>{{ $booking->fare ? $booking->fare->total_km : '-' }} KM</td>
                    <td class="text-end">₹{{ $booking->fare ? number_format($booking->fare->base_fare, 2) : '0.00' }}</td>
                    <td class="text-end">₹{{ $booking->fare ? number_format($booking->fare->total_km * $booking->fare->per_km_rate, 2) : '0.00' }}</td>
                    <td class="text-end fw-bold">₹{{ $booking->fare ? number_format($booking->fare->final_fare, 2) : '0.00' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4">No completed bookings found for this period.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="6" class="text-end">GRAND TOTAL</th>
                    <th class="text-end text-primary">₹{{ number_format($totalRevenue, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
