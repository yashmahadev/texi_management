@extends('driver.layouts.app')

@section('content')
<div class="mb-4 d-flex align-items-center">
    <a href="{{ url()->previous() }}" class="btn btn-link text-dark ps-0">
        <i class="bi bi-arrow-left fs-4"></i>
    </a>
    <h5 class="mb-0 fw-bold">Booking Details</h5>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4 small" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4 small" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="badge bg-primary px-3 py-2 fs-6">{{ $booking->status }}</span>
            <div class="fw-bold text-muted">{{ $booking->booking_number }}</div>
        </div>

        <div class="mb-4">
            <label class="text-muted small d-block">Customer</label>
            <div class="fw-bold fs-5">{{ $booking->customer_name }}</div>
            <a href="tel:{{ $booking->customer_mobile }}" class="btn btn-sm btn-outline-primary mt-2">
                <i class="bi bi-telephone-fill me-1"></i> Call Customer
            </a>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <label class="text-muted small d-block">Start Time</label>
                <div class="fw-bold">{{ $booking->booking_datetime->format('d M, h:i A') }}</div>
            </div>
            @if($booking->booking_end_datetime)
            <div class="col-6">
                <label class="text-muted small d-block">Expected End</label>
                <div class="fw-bold">{{ $booking->booking_end_datetime->format('d M, h:i A') }}</div>
            </div>
            @endif
        </div>

        <div class="mb-4">
            <div class="p-3 bg-light rounded shadow-sm border-start border-4 border-success mb-3">
                <label class="text-muted small d-block mb-1">Pickup Location</label>
                <div>{{ $booking->pickup_location }}</div>
            </div>
            <div class="p-3 bg-light rounded shadow-sm border-start border-4 border-danger">
                <label class="text-muted small d-block mb-1">Drop Location</label>
                <div>{{ $booking->drop_location }}</div>
            </div>
        </div>

        @if($booking->notes)
        <div class="mb-4">
            <label class="text-muted small d-block">Customer Notes</label>
            <div class="p-2 border rounded bg-light small">{{ $booking->notes }}</div>
        </div>
        @endif

        <div class="mb-4">
            <label class="text-muted small d-block">Vehicle Assigned</label>
            <div class="fw-bold">{{ $booking->activeAssignment->vehicle->vehicle_number ?? 'N/A' }}</div>
            <div class="small text-muted">{{ $booking->activeAssignment->vehicle->vehicle_type ?? '' }}</div>
        </div>

        <hr>

        <!-- Actions -->
        @if($booking->status == 'ASSIGNED')
            <div class="row g-2">
                <div class="col-8">
                    <form action="{{ route('driver.direct-bookings.accept', $booking) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 btn-lg shadow-sm">
                            <i class="bi bi-check-circle me-1"></i> Accept Trip
                        </button>
                    </form>
                </div>
                <div class="col-4">
                    <button type="button" class="btn btn-outline-danger w-100 btn-lg" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>
        @elseif($booking->status == 'ACCEPTED')
            <form action="{{ route('driver.direct-bookings.start', $booking) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success w-100 btn-lg shadow-sm py-3">
                    <i class="bi bi-play-circle me-2"></i> Start Trip
                </button>
            </form>
        @elseif($booking->status == 'STARTED')
            <button type="button" class="btn btn-danger w-100 btn-lg shadow-sm py-3" data-bs-toggle="modal" data-bs-target="#endTripModal">
                <i class="bi bi-stop-circle me-2"></i> End Trip & Capture KM
            </button>
        @elseif($booking->status == 'COMPLETED')
            <div class="alert alert-success text-center">
                <i class="bi bi-check-circle-fill fs-1 d-block mb-2"></i>
                <div class="fw-bold">Trip Completed</div>
                @if($booking->fare)
                    <div class="mt-2 fs-5">Amount: ₹{{ number_format($booking->fare->final_fare, 2) }}</div>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- History Modal / Status Logs -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 small fw-bold">Timeline</h6>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush small">
            @foreach($booking->statusLogs->reverse() as $log)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-light text-dark border">{{ $log->to_status }}</span>
                        <span class="text-muted">{{ $log->created_at->format('h:i A') }}</span>
                    </div>
                    @if($log->remarks)
                        <div class="mt-1 text-muted">{{ $log->remarks }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('driver.direct-bookings.reject', $booking) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject this trip? This will return the booking to admin for reassignment.</p>
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection *</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Enter rejection reason..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- End Trip Modal -->
<div class="modal fade" id="endTripModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('driver.direct-bookings.end', $booking) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Complete Trip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Actual KM Traveled *</label>
                        <input type="number" name="actual_km" class="form-control form-control-lg" value="{{ old('actual_km') }}" required min="1" step="0.1" placeholder="Enter final odometer KM">
                        <small class="text-muted">Total KM will be used to calculate final fare.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Closing Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes...">{{ old('remarks') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Complete & Finish</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
