@extends('admin.layouts.app')

@section('content')

@php
    $statusColors = [
        'CREATED'   => 'secondary',
        'ASSIGNED'  => 'info',
        'ACCEPTED'  => 'primary',
        'STARTED'   => 'warning',
        'COMPLETED' => 'success',
        'CANCELLED' => 'danger',
    ];
    $color = $statusColors[$booking->status] ?? 'secondary';
@endphp

{{-- Page Header --}}
<div class="page-header mb-4">
    <div>
        <h2 class="mb-0">{{ $booking->booking_number }}</h2>
        <span class="badge bg-{{ $color }} fs-6 mt-1">{{ $booking->status }}</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if(in_array($booking->status, ['CREATED', 'ASSIGNED']))
            @can('edit_direct_bookings')
            <a href="{{ route('admin.direct-bookings.edit', $booking) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            @endcan
        @endif
        @if(in_array($booking->status, ['CREATED', 'ASSIGNED', 'ACCEPTED']))
            @can('cancel_bookings')
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                <i class="bi bi-x-circle me-1"></i> Cancel
            </button>
            @endcan
        @endif
        <a href="{{ route('admin.direct-bookings.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-4">

    {{-- LEFT: Main Info --}}
    <div class="col-lg-8">

        {{-- Booking Details --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-info-circle me-2 text-primary"></i>Booking Details
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="text-muted small">Customer Name</label>
                        <div class="fw-semibold">{{ $booking->customer_name }}</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Customer Mobile</label>
                        <div><a href="tel:{{ $booking->customer_mobile }}">{{ $booking->customer_mobile }}</a></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Booking Start</label>
                        <div>{{ $booking->booking_datetime->format('d M Y, h:i A') }}</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Booking End</label>
                        <div>{{ $booking->booking_end_datetime ? $booking->booking_end_datetime->format('d M Y, h:i A') : '—' }}</div>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small"><i class="bi bi-circle-fill text-success me-1" style="font-size:.5rem;"></i>Pickup Location</label>
                        <div>{{ $booking->pickup_location }}</div>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small"><i class="bi bi-circle-fill text-danger me-1" style="font-size:.5rem;"></i>Drop Location</label>
                        <div>{{ $booking->drop_location }}</div>
                    </div>
                    @if($booking->route_remarks)
                    <div class="col-12">
                        <label class="text-muted small">Route / Remarks</label>
                        <div>{{ $booking->route_remarks }}</div>
                    </div>
                    @endif
                    <div class="col-sm-6">
                        <label class="text-muted small">Estimated KM</label>
                        <div>{{ $booking->estimated_km ? $booking->estimated_km . ' km' : '—' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Actual KM</label>
                        <div>{{ $booking->actual_km ? $booking->actual_km . ' km' : '—' }}</div>
                    </div>
                    @if($booking->notes)
                    <div class="col-12">
                        <label class="text-muted small">Customer Notes</label>
                        <div class="text-muted">{{ $booking->notes }}</div>
                    </div>
                    @endif
                    @if($booking->admin_notes)
                    <div class="col-12">
                        <label class="text-muted small">Admin Notes (Internal)</label>
                        <div class="text-muted fst-italic">{{ $booking->admin_notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Fare Details --}}
        @if($booking->fare)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cash-coin me-2 text-success"></i>Fare Details</span>
                @if(!$booking->fare->is_locked && $booking->status === 'COMPLETED')
                    @can('adjust_booking_fares')
                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#adjustFareModal">
                        <i class="bi bi-pencil me-1"></i> Adjust
                    </button>
                    @endcan
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-sm-3">
                        <label class="text-muted small">Base Fare</label>
                        <div class="fw-semibold">₹{{ number_format($booking->fare->base_fare, 2) }}</div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <label class="text-muted small">Per KM Rate</label>
                        <div class="fw-semibold">₹{{ number_format($booking->fare->per_km_rate, 2) }}</div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <label class="text-muted small">Total KM</label>
                        <div class="fw-semibold">{{ $booking->fare->total_km }} km</div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <label class="text-muted small">Calculated Fare</label>
                        <div class="fw-semibold">₹{{ number_format($booking->fare->calculated_fare, 2) }}</div>
                    </div>
                    @if($booking->fare->admin_adjusted_fare)
                    <div class="col-sm-6">
                        <label class="text-muted small">Admin Adjusted</label>
                        <div class="text-warning fw-semibold">₹{{ number_format($booking->fare->admin_adjusted_fare, 2) }}</div>
                        <small class="text-muted">{{ $booking->fare->adjustment_reason }}</small>
                    </div>
                    @endif
                    <div class="col-sm-6">
                        <label class="text-muted small">Final Fare</label>
                        <div class="fs-4 fw-bold text-success">₹{{ number_format($booking->fare->final_fare, 2) }}</div>
                        <span class="badge bg-{{ $booking->fare->is_locked ? 'danger' : 'warning' }}">
                            <i class="bi bi-{{ $booking->fare->is_locked ? 'lock' : 'unlock' }} me-1"></i>
                            {{ $booking->fare->is_locked ? 'Locked' : 'Unlocked' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Assignment History --}}
        @if($booking->assignments->count() > 1)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-clock-history me-2 text-secondary"></i>Assignment History
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Driver</th><th>Vehicle</th><th>Assigned At</th><th>By</th><th>Active</th></tr>
                    </thead>
                    <tbody>
                        @foreach($booking->assignments->sortByDesc('assigned_at') as $assignment)
                        <tr class="{{ $assignment->is_active ? 'table-success' : '' }}">
                            <td>{{ $assignment->driver->name }}</td>
                            <td>{{ $assignment->vehicle->vehicle_number }}</td>
                            <td>{{ $assignment->assigned_at->format('d M Y, h:i A') }}</td>
                            <td>{{ $assignment->assignedBy->name ?? '—' }}</td>
                            <td>
                                @if($assignment->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Replaced</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

    {{-- RIGHT: Sidebar --}}
    <div class="col-lg-4">

        {{-- Assign Driver --}}
        @if(in_array($booking->status, ['CREATED', 'ASSIGNED']))
            @can('assign_drivers_to_bookings')
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-person-check me-2 text-info"></i>
                    {{ $booking->status === 'ASSIGNED' ? 'Reassign Driver' : 'Assign Driver' }}
                </div>
                <div class="card-body">
                    @if(empty($availableDrivers) || count($availableDrivers) === 0)
                        <div class="alert alert-warning mb-0 small">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            No drivers available for this booking period.
                        </div>
                    @else
                    <form action="{{ route('admin.direct-bookings.assign', $booking) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Driver</label>
                            <select name="driver_id" class="form-select form-select-sm" required>
                                <option value="">Select Driver</option>
                                @foreach($availableDrivers as $driver)
                                    <option value="{{ $driver->id }}"
                                        {{ $booking->activeAssignment?->driver_id == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->name }} — {{ $driver->mobile_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Vehicle</label>
                            <select name="vehicle_id" class="form-select form-select-sm" required>
                                <option value="">Select Vehicle</option>
                                @foreach($availableVehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}"
                                        {{ $booking->activeAssignment?->vehicle_id == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->vehicle_number }} ({{ $vehicle->vehicle_type }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-check-lg me-1"></i> Assign
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endcan
        @elseif($booking->activeAssignment)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-person-badge me-2 text-secondary"></i>Assigned Driver
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                         style="width:44px;height:44px;font-size:1.2rem;">
                        <i class="bi bi-person"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">{{ $booking->activeAssignment->driver->name }}</div>
                        <div class="text-muted small">{{ $booking->activeAssignment->driver->mobile_number }}</div>
                    </div>
                </div>
                <div class="small">
                    <div class="mb-1"><i class="bi bi-car-front me-2 text-muted"></i>{{ $booking->activeAssignment->vehicle->vehicle_number }}
                        <span class="text-muted">({{ $booking->activeAssignment->vehicle->vehicle_type }})</span>
                    </div>
                    <div class="text-muted"><i class="bi bi-clock me-2"></i>Assigned {{ $booking->activeAssignment->assigned_at->format('d M Y, h:i A') }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- Status Timeline --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-activity me-2 text-primary"></i>Status Timeline
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($booking->statusLogs->sortByDesc('created_at') as $log)
                    @php $logColor = $statusColors[$log->to_status] ?? 'secondary'; @endphp
                    <li class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="badge bg-{{ $logColor }}">{{ $log->to_status }}</span>
                            <small class="text-muted">{{ $log->created_at->format('d M, h:i A') }}</small>
                        </div>
                        @if($log->remarks)
                            <div class="small text-muted mt-1">{{ $log->remarks }}</div>
                        @endif
                        @if($log->changed_by_type)
                            <div class="small text-muted">
                                <i class="bi bi-person me-1"></i>{{ class_basename($log->changed_by_type) }} #{{ $log->changed_by }}
                            </div>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

    </div>
</div>

{{-- Cancel Modal --}}
@if(in_array($booking->status, ['CREATED', 'ASSIGNED', 'ACCEPTED']))
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.direct-bookings.cancel', $booking) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking — {{ $booking->booking_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-bold">Cancellation Reason <span class="text-danger">*</span></label>
                    <textarea name="cancellation_reason" class="form-control" rows="3"
                        placeholder="Please provide a reason for cancellation..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i> Confirm Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Adjust Fare Modal --}}
@if($booking->fare && !$booking->fare->is_locked && $booking->status === 'COMPLETED')
<div class="modal fade" id="adjustFareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.direct-bookings.adjust-fare', $booking) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Fare</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Final Fare</label>
                        <div class="fs-5 text-success fw-bold">₹{{ number_format($booking->fare->final_fare, 2) }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">New Fare (₹) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="adjusted_fare" class="form-control"
                                value="{{ $booking->fare->final_fare }}" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason <span class="text-danger">*</span></label>
                        <textarea name="adjustment_reason" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-1"></i> Save Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
