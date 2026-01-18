@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Vehicles</h2>
    <a href="{{ route('admin.vehicles.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i> Add Vehicle
    </a>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form action="{{ route('admin.vehicles.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Vehicle #, Driver, Owner" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Vehicle Type</label>
                <select name="vehicle_type" class="form-select">
                    <option value="">All Types</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}" {{ request('vehicle_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Fuel Type</label>
                <select name="fuel_type" class="form-select">
                    <option value="">All Fuels</option>
                    <option value="Petrol" {{ request('fuel_type') == 'Petrol' ? 'selected' : '' }}>Petrol</option>
                    <option value="Diesel" {{ request('fuel_type') == 'Diesel' ? 'selected' : '' }}>Diesel</option>
                    <option value="CNG" {{ request('fuel_type') == 'CNG' ? 'selected' : '' }}>CNG</option>
                    <option value="Electric" {{ request('fuel_type') == 'Electric' ? 'selected' : '' }}>Electric</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="{{ route('admin.vehicles.index') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Vehicle Details</th>
                        <th>Type & Configuration</th>
                        <th>Driver Details</th>
                        <th>Owner/Vendor</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                    <tr>
                        <td>{{ ($vehicles->currentPage() - 1) * $vehicles->perPage() + $loop->iteration }}</td>
                        <td>
                            <div class="fw-bold fs-6 text-primary">{{ $vehicle->vehicle_number }}</div>
                            <small class="text-muted d-block">{{ $vehicle->make_model }}</small>
                            <span class="badge bg-light text-dark border small mt-1">{{ $vehicle->pass_type }}</span>
                        </td>
                        <td>
                            <div>{{ $vehicle->vehicle_type }}</div>
                            <small class="text-muted">
                                {{ $vehicle->fuel_type }} | {{ $vehicle->transmission_type }}
                            </small>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $vehicle->driver->name ?? 'N/A' }}</div>
                            <small class="text-muted d-block">
                                <i class="bi bi-phone small"></i> {{ $vehicle->driver->mobile_number ?? 'No Mobile' }}
                            </small>
                            @if($vehicle->driver && $vehicle->driver->is_police_verified)
                                <span class="badge bg-success-subtle text-success small" style="font-size: 0.7rem;">
                                    <i class="bi bi-patch-check"></i> Police Verified
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-medium">{{ $vehicle->owner->name ?? $vehicle->owner_name ?? 'N/A' }}</div>
                            <small class="text-muted">
                                <i class="bi bi-phone small"></i> {{ $vehicle->owner->mobile ?? $vehicle->owner_mobile ?? 'N/A' }}
                            </small>
                        </td>
                        <td>
                            <span class="badge rounded-pill bg-{{ $vehicle->status == 'active' ? 'success' : ($vehicle->status == 'maintenance' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($vehicle->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.vehicles.edit', $vehicle->id) }}" class="btn btn-outline-secondary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.vehicles.destroy', $vehicle->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this vehicle?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                            No vehicles found matching your criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($vehicles->hasPages())
    <div class="card-footer bg-white py-3">
        {{ $vehicles->links() }}
    </div>
    @endif
</div>
@endsection
