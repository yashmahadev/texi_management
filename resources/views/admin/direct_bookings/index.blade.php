@extends('admin.layouts.app')

@section('content')

{{-- Page Header --}}
<div class="page-header mb-4">
    <h2 class="mb-0">Direct Bookings</h2>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
        </a>
        @can('create_direct_bookings')
        <a href="{{ route('admin.direct-bookings.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New Booking
        </a>
        @endcan
    </div>
</div>

{{-- Status Summary Badges --}}
@php
    $statusCounts = $bookings->getCollection()->groupBy('status')->map->count();
    $allStatuses = ['CREATED'=>'secondary','ASSIGNED'=>'info','ACCEPTED'=>'primary','STARTED'=>'warning','COMPLETED'=>'success','CANCELLED'=>'danger'];
@endphp
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="{{ route('admin.direct-bookings.index') }}" class="text-decoration-none">
        <span class="badge bg-dark fs-6 px-3 py-2">All ({{ $bookings->total() }})</span>
    </a>
    @foreach($allStatuses as $s => $color)
        <a href="{{ request()->fullUrlWithQuery(['status' => $s]) }}" class="text-decoration-none">
            <span class="badge bg-{{ $color }} fs-6 px-3 py-2">
                {{ ucfirst(strtolower($s)) }}
                @if(request('status') === $s) ✓ @endif
            </span>
        </a>
    @endforeach
</div>

{{-- Filters --}}
<div class="card shadow-sm mb-4 filter-card">
    <div class="card-body">
        <form action="{{ route('admin.direct-bookings.index') }}" method="GET" class="row g-2">
            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-bold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach($allStatuses as $s => $color)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst(strtolower($s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-bold">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Booking # / Customer name / Mobile" value="{{ request('search') }}">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label small fw-bold">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label small fw-bold">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-2 col-12 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                <a href="{{ route('admin.direct-bookings.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Desktop Table --}}
<div class="card shadow-sm d-none d-md-block">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    @include('partials.sort-th', ['col'=>'booking_number',   'label'=>'Booking #',   'sort'=>$sort,'dir'=>$dir])
                    @include('partials.sort-th', ['col'=>'customer_name',    'label'=>'Customer',    'sort'=>$sort,'dir'=>$dir])
                    <th>Route</th>
                    @include('partials.sort-th', ['col'=>'booking_datetime', 'label'=>'Date & Time', 'sort'=>$sort,'dir'=>$dir])
                    <th>Driver / Vehicle</th>
                    @include('partials.sort-th', ['col'=>'status',           'label'=>'Status',      'sort'=>$sort,'dir'=>$dir])
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                @php $color = $allStatuses[$booking->status] ?? 'secondary'; @endphp
                <tr>
                    <td>
                        <span class="fw-bold text-primary">{{ $booking->booking_number }}</span>
                        @if($booking->booking_end_datetime)
                            <br><small class="badge bg-light text-dark border">Multi-day</small>
                        @endif
                    </td>
                    <td>
                        <span class="fw-semibold">{{ $booking->customer_name }}</span><br>
                        <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $booking->customer_mobile }}</small>
                    </td>
                    <td style="max-width:180px;">
                        <small>
                            <i class="bi bi-circle-fill text-success me-1" style="font-size:.5rem;"></i>
                            {{ Str::limit($booking->pickup_location, 25) }}
                        </small><br>
                        <small>
                            <i class="bi bi-circle-fill text-danger me-1" style="font-size:.5rem;"></i>
                            {{ Str::limit($booking->drop_location, 25) }}
                        </small>
                    </td>
                    <td class="text-nowrap">
                        <i class="bi bi-calendar3 me-1 text-muted"></i>{{ $booking->booking_datetime->format('d M Y') }}<br>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i>{{ $booking->booking_datetime->format('h:i A') }}</small>
                    </td>
                    <td>
                        @if($booking->activeAssignment)
                            <i class="bi bi-person me-1 text-muted"></i>{{ $booking->activeAssignment->driver->name }}<br>
                            <small class="text-muted"><i class="bi bi-car-front me-1"></i>{{ $booking->activeAssignment->vehicle->vehicle_number }}</small>
                        @else
                            <span class="text-muted small">Not assigned</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $color }} px-2 py-1">{{ $booking->status }}</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.direct-bookings.show', $booking) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <i class="bi bi-calendar-x fs-1 text-muted d-block mb-2"></i>
                        No bookings found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">Showing {{ $bookings->firstItem() }}–{{ $bookings->lastItem() }} of {{ $bookings->total() }} bookings</small>
        {{ $bookings->links() }}
    </div>
</div>

{{-- Mobile Card List --}}
<div class="d-md-none">
    @forelse($bookings as $booking)
    @php $color = $allStatuses[$booking->status] ?? 'secondary'; @endphp
    <div class="card shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="fw-bold text-primary">{{ $booking->booking_number }}</span>
                    <span class="badge bg-{{ $color }} ms-2">{{ $booking->status }}</span>
                </div>
                <a href="{{ route('admin.direct-bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i>
                </a>
            </div>
            <div class="mb-1">
                <i class="bi bi-person me-1 text-muted"></i>
                <strong>{{ $booking->customer_name }}</strong>
                <span class="text-muted ms-2">{{ $booking->customer_mobile }}</span>
            </div>
            <div class="mb-1 small text-muted">
                <i class="bi bi-circle-fill text-success me-1" style="font-size:.45rem;"></i>{{ Str::limit($booking->pickup_location, 35) }}<br>
                <i class="bi bi-circle-fill text-danger me-1" style="font-size:.45rem;"></i>{{ Str::limit($booking->drop_location, 35) }}
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                <span><i class="bi bi-calendar3 me-1"></i>{{ $booking->booking_datetime->format('d M Y, h:i A') }}</span>
                @if($booking->activeAssignment)
                    <span><i class="bi bi-car-front me-1"></i>{{ $booking->activeAssignment->vehicle->vehicle_number }}</span>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5 text-muted">
        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
        No bookings found.
    </div>
    @endforelse

    {{-- Mobile pagination --}}
    <div class="d-flex justify-content-center mt-3">
        {{ $bookings->links() }}
    </div>
</div>

@endsection
