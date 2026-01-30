@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Dashboard</h2>
    <span>{{ now()->toAppDate() }}</span>
</div>

<div class="row mb-4 g-3">
    <div class="col-6 col-lg-3">
        <div class="card bg-warning text-dark border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ $pendingDuties }}</h3>
                        <p class="mb-0 small fw-bold">Pending Today</p>
                    </div>
                    <i class="bi bi-clock-history fs-1 opacity-25 d-none d-sm-block"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card bg-success text-white border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ $completedDuties }}</h3>
                        <p class="mb-0 small fw-bold">Completed Today</p>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-25 d-none d-sm-block"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card bg-info text-white border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ number_format($monthlyKm) }}</h3>
                        <p class="mb-0 small fw-bold">KM This Month</p>
                    </div>
                    <i class="bi bi-speedometer fs-1 opacity-25 d-none d-sm-block"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card bg-danger text-white border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ $missingDuties }}</h3>
                        <p class="mb-0 small fw-bold">Missing Duties</p>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 opacity-25 d-none d-sm-block"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4 g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 bg-primary text-white rounded p-3 me-3">
                    <i class="bi bi-car-front fs-4"></i>
                </div>
                <div>
                    <h5 class="mb-0">{{ $activeVehicles }}</h5>
                    <p class="mb-0 text-muted small">Active Vehicles</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 bg-dark text-white rounded p-3 me-3">
                    <i class="bi bi-person-badge fs-4"></i>
                </div>
                <div>
                    <h5 class="mb-0">{{ $activeDrivers }}</h5>
                    <p class="mb-0 text-muted small">Active Drivers</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="row align-items-center">
            <div class="col-md-4">
                <ul class="nav nav-pills" id="dutyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="today-tab" data-bs-toggle="tab" data-bs-target="#today" type="button" role="tab" aria-controls="today" aria-selected="true">
                            Today ({{ $todaysDuties->count() }})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tomorrow-tab" data-bs-toggle="tab" data-bs-target="#tomorrow" type="button" role="tab" aria-controls="tomorrow" aria-selected="false">
                            Tomorrow ({{ $tomorrowsDuties->count() }})
                        </button>
                    </li>
                </ul>
            </div>
            <div class="col-md-8">
                <form action="{{ route('admin.dashboard') }}" method="GET" class="d-flex gap-2 justify-content-md-end mt-2 mt-md-0">
                    <select name="status" class="form-select form-select-sm" style="width: 150px;">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="started" {{ request('status') == 'started' ? 'selected' : '' }}>Started</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search vehicle, driver..." value="{{ request('search') }}" style="width: 200px;">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    @if(request()->hasAny(['status', 'search']))
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
        </div>
    </div>
    
    <div class="card-body p-0">
        <div class="tab-content" id="dutyTabsContent">
            <!-- Today's Duties -->
            <div class="tab-pane fade show active" id="today" role="tabpanel" aria-labelledby="today-tab">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Officer/Dept</th>
                                <th>Expected Start</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todaysDuties as $log)
                            <tr>
                                <td>{{ $log->monthlyDuty?->vehicle?->vehicle_number ?? 'N/A' }}</td>
                                <td>{{ $log->monthlyDuty?->primaryDriver?->name ?? 'N/A' }}</td>
                                <td>
                                    {{ $log->monthlyDuty?->officer_name ?? 'N/A' }}<br>
                                    <small class="text-muted">{{ $log->monthlyDuty?->department_name ?? 'N/A' }}</small>
                                </td>
                                <td>{{ $log->monthlyDuty?->expected_start_time ? \Carbon\Carbon::parse($log->monthlyDuty->expected_start_time)->format('H:i') : 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $log->status == 'completed' ? 'success' : ($log->status == 'started' ? 'primary' : ($log->status == 'pending' ? 'warning' : 'secondary')) }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.daily-logs.show', $log->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No duties found for today matching criteria.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tomorrow's Duties -->
            <div class="tab-pane fade" id="tomorrow" role="tabpanel" aria-labelledby="tomorrow-tab">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Officer/Dept</th>
                                <th>Expected Start</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tomorrowsDuties as $log)
                            <tr>
                                <td>{{ $log->monthlyDuty?->vehicle?->vehicle_number ?? 'N/A' }}</td>
                                <td>{{ $log->monthlyDuty?->primaryDriver?->name ?? 'N/A' }}</td>
                                <td>
                                    {{ $log->monthlyDuty?->officer_name ?? 'N/A' }}<br>
                                    <small class="text-muted">{{ $log->monthlyDuty?->department_name ?? 'N/A' }}</small>
                                </td>
                                <td>{{ $log->monthlyDuty?->expected_start_time ? \Carbon\Carbon::parse($log->monthlyDuty->expected_start_time)->format('H:i') : 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $log->status == 'completed' ? 'success' : ($log->status == 'started' ? 'primary' : ($log->status == 'pending' ? 'warning' : 'secondary')) }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.daily-logs.show', $log->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No duties scheduled for tomorrow matching criteria.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
