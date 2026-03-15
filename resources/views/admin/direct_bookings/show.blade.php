@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Booking Details - {{ $booking->booking_number }}</h2>
    <div>
        @if($booking->status === 'CREATED' || $booking->status === 'ASSIGNED')
            @can('cancel_bookings')
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-x-circle me-2"></i> Cancel Booking
                </button>
            @endcan
        @endif
        <a href="{{ route('admin.direct-bookings.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back to List
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Booking Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        @php
                            $statusColors = [
                                'CREATED' => 'secondary', 'ASSIGNED' => 'info', 'ACCEPTED' => 'primary',
                                'STARTED' => 'warning', 'COMPLETED' => 'success', 'CANCELLED' => 'danger'
                            ];
                        @endphp
                        <span class="badge bg-{{ $statusColors[$booking->status] ?? 'secondary' }} fs-6">{{ $booking->status }}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Booking Start:</strong><br>
                        {{ $booking->booking_datetime->format('d M Y, h:i A') }}
                    </div>
                    <div class="col-md-6">
                        <strong>Booking End:</strong><br>
                        {{ $booking->booking_end_datetime ? $booking->booking_end_datetime->format('d M Y, h:i A') : '—' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Customer Name:</strong><br>
                        {{ $booking->customer_name }}
                    </div>
                    <div class="col-md-6">
                        <strong>Customer Mobile:</strong><br>
                        {{ $booking->customer_mobile }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong><i class="bi bi-geo-alt-fill text-success"></i> Pickup Location:</strong><br>
                        {{ $booking->pickup_location }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong><i class="bi bi-geo-alt-fill text-danger"></i> Drop Location:</strong><br>
                        {{ $booking->drop_location }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Estimated KM:</strong><br>
                        {{ $booking->estimated_km ?? 'N/A' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Actual KM:</strong><br>
                        {{ $booking->actual_km ?? 'N/A' }}
                    </div>
                </div>

                @if($booking->notes)
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Customer Notes:</strong><br>
                        {{ $booking->notes }}
                    </div>
                </div>
                @endif

                @if($booking->admin_notes)
                <div class="row">
                    <div class="col-md-12">
                        <strong>Admin Notes:</strong><br>
                        {{ $booking->admin_notes }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if($booking->fare)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Fare Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Base Fare:</strong> ₹{{ number_format($booking->fare->base_fare, 2) }}<br>
                        <strong>Per KM Rate:</strong> ₹{{ number_format($booking->fare->per_km_rate, 2) }}<br>
                        <strong>Total KM:</strong> {{ $booking->fare->total_km }} KM<br>
                        <strong>Calculated Fare:</strong> ₹{{ number_format($booking->fare->calculated_fare, 2) }}
                    </div>
                    <div class="col-md-6">
                        @if($booking->fare->admin_adjusted_fare)
                            <strong class="text-warning">Adjusted Fare:</strong> ₹{{ number_format($booking->fare->admin_adjusted_fare, 2) }}<br>
                            <strong>Reason:</strong> {{ $booking->fare->adjustment_reason }}<br>
                        @endif
                        <strong class="fs-5">Final Fare:</strong> <span class="text-success fs-4">₹{{ number_format($booking->fare->final_fare, 2) }}</span><br>
                        <span class="badge bg-{{ $booking->fare->is_locked ? 'danger' : 'warning' }}">
                            {{ $booking->fare->is_locked ? 'Locked' : 'Unlocked' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        @if(in_array($booking->status, ['CREATED', 'ASSIGNED']) && !empty($availableDrivers) && $availableDrivers->count() > 0)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Assign Driver</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.direct-bookings.assign', $booking) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Driver</label>
                        <select name="driver_id" class="form-select" required>
                            <option value="">Select Driver</option>
                            @foreach($availableDrivers as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->name }} - {{ $driver->mobile_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vehicle</label>
                        <select name="vehicle_id" class="form-select" required>
                            <option value="">Select Vehicle</option>
                            @foreach($availableVehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_number }} ({{ $vehicle->vehicle_type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Assign Driver</button>
                </form>
            </div>
        </div>
        @elseif($booking->activeAssignment)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Assigned Driver & Vehicle</h5>
            </div>
            <div class="card-body">
                <strong>Driver:</strong> {{ $booking->activeAssignment->driver->name }}<br>
                <strong>Mobile:</strong> {{ $booking->activeAssignment->driver->mobile_number }}<br>
                <strong>Vehicle:</strong> {{ $booking->activeAssignment->vehicle->vehicle_number }}<br>
                <strong>Assigned At:</strong> {{ $booking->activeAssignment->assigned_at->format('d M Y, h:i A') }}
            </div>
        </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Status Timeline</h5>
            </div>
            <div class="card-body">
                @foreach($booking->statusLogs as $log)
                    <div class="mb-3 pb-3 border-bottom">
                        <span class="badge bg-secondary">{{ $log->to_status }}</span><br>
                        <small>{{ $log->created_at->format('d M Y, h:i A') }}</small><br>
                        @if($log->changed_by_type)
                            <small class="text-muted">By: {{ class_basename($log->changed_by_type) }} #{{ $log->changed_by }}</small>
                        @endif
                        @if($log->remarks)
                            <br><small>{{ $log->remarks }}</small>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.direct-bookings.cancel', $booking) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                    <textarea name="cancellation_reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
