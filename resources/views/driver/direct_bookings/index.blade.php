@extends('driver.layouts.app')

@section('content')
<div class="mb-4">
    <h5 class="fw-bold"><i class="bi bi-calendar-check me-2"></i>My Direct Bookings</h5>
    <p class="text-muted small">List of all your assigned and past trips</p>
</div>

@forelse($bookings as $booking)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="fw-bold">{{ $booking->booking_number }}</div>
                    <div class="text-muted small">{{ $booking->booking_datetime->format('d M Y, h:i A') }}</div>
                </div>
                @php
                    $statusColors = [
                        'ASSIGNED' => 'info', 'ACCEPTED' => 'primary', 'STARTED' => 'warning',
                        'COMPLETED' => 'success', 'CANCELLED' => 'danger'
                    ];
                @endphp
                <span class="badge bg-{{ $statusColors[$booking->status] ?? 'secondary' }}">
                    {{ $booking->status }}
                </span>
            </div>
            
            <div class="mb-2">
                <i class="bi bi-person text-primary me-1"></i> {{ $booking->customer_name }}
            </div>
            
            <div class="mb-3 small">
                <div class="text-truncate"><i class="bi bi-geo-alt text-success me-1"></i> {{ $booking->pickup_location }}</div>
                <div class="text-truncate"><i class="bi bi-geo-alt text-danger me-1"></i> {{ $booking->drop_location }}</div>
            </div>

            <a href="{{ route('driver.direct-bookings.show', $booking) }}" class="btn btn-outline-primary btn-sm w-100">
                View Details
            </a>
        </div>
    </div>
@empty
    <div class="text-center py-5">
        <i class="bi bi-journal-x display-1 text-muted"></i>
        <p class="mt-3">No bookings found.</p>
        <a href="{{ route('driver.dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
    </div>
@endforelse
@endsection
