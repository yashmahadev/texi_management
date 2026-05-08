@extends('admin.layouts.app')

@section('content')
<div class="page-header mb-4">
    <h2>Vehicles</h2>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
        </a>
        <a href="{{ route('admin.vehicles.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i> Add Vehicle
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 filter-card">
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
                        <th class="text-nowrap">Driver ID</th>
                        <th class="text-nowrap">Owner Name</th>
                        @include('partials.sort-th', ['col'=>'vehicle_number','label'=>'Driver Name',    'sort'=>$sort,'dir'=>$dir])
                        <th class="text-nowrap">Mobile</th>
                        @include('partials.sort-th', ['col'=>'vehicle_type',  'label'=>'Vehicle Type',   'sort'=>$sort,'dir'=>$dir])
                        @include('partials.sort-th', ['col'=>'vehicle_number','label'=>'Vehicle Number', 'sort'=>$sort,'dir'=>$dir])
                        <th class="text-nowrap">Commission %</th>
                        @include('partials.sort-th', ['col'=>'status',        'label'=>'Status',         'sort'=>$sort,'dir'=>$dir])
                        <th class="text-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                    <tr>
                        <td class="text-nowrap">
                            @if($vehicle->driver)
                                DR-{{ str_pad($vehicle->driver->id, 3, '0', STR_PAD_LEFT) }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $vehicle->owner->name ?? 'N/A' }}</td>
                        <td class="text-nowrap">{{ $vehicle->driver->name ?? 'N/A' }}</td>
                        <td class="text-nowrap">{{ $vehicle->driver->mobile_number ?? 'N/A' }}</td>
                        <td class="text-nowrap">{{ $vehicle->vehicle_type ?? 'N/A' }}</td>
                        <td class="text-nowrap fw-bold text-primary">{{ $vehicle->vehicle_number ?? 'N/A' }}</td>
                        <td class="text-nowrap">{{ $vehicle->commission_percentage ?? '-' }}</td>
                        <td class="text-nowrap">
                            <span class="badge rounded-pill bg-{{ $vehicle->status == 'active' ? 'success' : ($vehicle->status == 'maintenance' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($vehicle->status) }}
                            </span>
                        </td>
                        <td class="text-nowrap">
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
                        <td colspan="10" class="text-center py-5">
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
