@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Daily Direct Trips Report</h2>
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

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-striped mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Booking #</th>
                    <th>Customer</th>
                    <th>Date & Time</th>
                    <th>Route</th>
                    <th>Vehicle & Driver</th>
                    <th>Status</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                <tr>
                    <td class="fw-bold">{{ $booking->booking_number }}</td>
                    <td>
                        {{ $booking->customer_name }}<br>
                        <small class="text-muted">{{ $booking->customer_mobile }}</small>
                    </td>
                    <td>
                        {{ $booking->booking_datetime->format('d M Y, h:i A') }}
                        @if($booking->booking_end_datetime)
                            <br><small class="text-muted">to {{ $booking->booking_end_datetime->format('d M Y, h:i A') }}</small>
                        @endif
                    </td>
                    <td>
                        <small>
                            <strong>P:</strong> {{ $booking->pickup_location }}<br>
                            <strong>D:</strong> {{ $booking->drop_location }}
                        </small>
                    </td>
                    <td>
                        @if($booking->activeAssignment)
                            {{ $booking->activeAssignment->vehicle->vehicle_number }}<br>
                            <small class="text-muted">{{ $booking->activeAssignment->driver->name }}</small>
                        @else
                            <span class="text-muted">No assignment</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $booking->status == 'COMPLETED' ? 'success' : ($booking->status == 'CANCELLED' ? 'danger' : 'info') }}">
                            {{ $booking->status }}
                        </span>
                    </td>
                    <td class="text-end">
                        @if($booking->fare)
                            ₹{{ number_format($booking->fare->final_fare, 2) }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4">No bookings found for this period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
