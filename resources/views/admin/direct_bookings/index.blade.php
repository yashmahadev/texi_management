@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Direct Bookings</h2>
    <a href="{{ route('admin.direct-bookings.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i> Create New Booking
    </a>
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

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('admin.direct-bookings.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="CREATED" {{ request('status') == 'CREATED' ? 'selected' : '' }}>Created</option>
                    <option value="ASSIGNED" {{ request('status') == 'ASSIGNED' ? 'selected' : '' }}>Assigned</option>
                    <option value="ACCEPTED" {{ request('status') == 'ACCEPTED' ? 'selected' : '' }}>Accepted</option>
                    <option value="STARTED" {{ request('status') == 'STARTED' ? 'selected' : '' }}>Started</option>
                    <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                    <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Booking# / Customer..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">From Date</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('admin.direct-bookings.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Booking #</th>
                    <th>Customer</th>
                    <th>Route</th>
                    <th>Date & Time</th>
                    <th>Driver/Vehicle</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                <tr>
                    <td>
                        <strong>{{ $booking->booking_number }}</strong>
                    </td>
                    <td>
                        {{ $booking->customer_name }}<br>
                        <small class="text-muted">{{ $booking->customer_mobile }}</small>
                    </td>
                    <td>
                        <small>
                            <i class="bi bi-geo-alt-fill text-success"></i> {{ Str::limit($booking->pickup_location, 20) }}<br>
                            <i class="bi bi-geo-alt-fill text-danger"></i> {{ Str::limit($booking->drop_location, 20) }}
                        </small>
                    </td>
                    <td>
                        {{ $booking->booking_datetime->format('d M Y, h:i A') }}
                        @if($booking->booking_end_datetime)
                            <br><small class="text-muted">to {{ $booking->booking_end_datetime->format('d M Y, h:i A') }}</small>
                        @endif
                    </td>
                    <td>
                        @if($booking->activeAssignment)
                            {{ $booking->activeAssignment->driver->name }}<br>
                            <small class="text-muted">{{ $booking->activeAssignment->vehicle->vehicle_number }}</small>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $statusColors = [
                                'CREATED' => 'secondary',
                                'ASSIGNED' => 'info',
                                'ACCEPTED' => 'primary',
                                'STARTED' => 'warning',
                                'COMPLETED' => 'success',
                                'CANCELLED' => 'danger'
                            ];
                            $color = $statusColors[$booking->status] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $color }}">{{ $booking->status }}</span>
                    </td>
                    <td>
                        <a href="{{ route('admin.direct-bookings.show', $booking) }}" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4">No bookings found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">
        {{ $bookings->links() }}
    </div>
</div>
@endsection
