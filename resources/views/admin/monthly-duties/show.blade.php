@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Duty Details #{{ $monthlyDuty->id }}</h2>
    <div>
        <a href="{{ route('admin.monthly-duties.edit', $monthlyDuty) }}" class="btn btn-outline-primary me-2">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('admin.reports.download', $monthlyDuty->id) }}" class="btn btn-outline-danger me-2">
            <i class="bi bi-file-pdf"></i> Download PDF
        </a>
        @can('delete', $monthlyDuty)
            <form action="{{ route('admin.monthly-duties.destroy', $monthlyDuty->id) }}" method="POST" class="d-inline me-2" onsubmit="return confirm('Are you sure you want to delete this duty? All associated daily logs, replacements, and billing records will be permanently removed.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-trash"></i> Delete Duty
                </button>
            </form>
        @endcan
        <a href="{{ route('admin.monthly-duties.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Assignment</h6>
                <p class="mb-1"><strong>Dept:</strong> {{ $monthlyDuty->department_name }}</p>
                <p class="mb-1"><strong>Officer:</strong> {{ $monthlyDuty->officer_name }}</p>
                <p class="mb-1"><strong>Vehicle:</strong> <a href="{{ route('admin.vehicles.edit', $monthlyDuty->vehicle->id) }}"> {{ $monthlyDuty->vehicle->vehicle_number }} ({{ $monthlyDuty->vehicle->owner_mobile }}) </a></p>
                <p class="mb-0"><strong>Driver:</strong> {{ $monthlyDuty->primaryDriver->name }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted">Schedule</h6>
                <p class="mb-1"><strong>Start:</strong> {{ $monthlyDuty->start_date->format('d M Y') }}</p>
                <p class="mb-1"><strong>End:</strong> {{ $monthlyDuty->end_date->format('d M Y') }}</p>
                <p class="mb-1"><strong>Start Time:</strong> {{ \Carbon\Carbon::parse($monthlyDuty->expected_start_time)->format('h:i A') }}</p>
                @if($monthlyDuty->expected_end_time)
                <p class="mb-1"><strong>End Time:</strong> {{ \Carbon\Carbon::parse($monthlyDuty->expected_end_time)->format('h:i A') }}</p>
                @endif
                @if($monthlyDuty->state || $monthlyDuty->city)
                <p class="mb-1"><strong>Location:</strong> {{ implode(', ', array_filter([$monthlyDuty->city, $monthlyDuty->state, $monthlyDuty->pincode])) }}</p>
                @endif
                @if($monthlyDuty->route_remarks)
                <p class="mb-1"><strong>Route/Remarks:</strong> {{ $monthlyDuty->route_remarks }}</p>
                @endif
                @if($monthlyDuty->is_recurring)
                <p class="mb-0 mt-2">
                    <span class="badge bg-success">
                        <i class="bi bi-arrow-repeat me-1"></i> Recurring — auto-renews every month
                    </span>
                </p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body bg-light">
                <h6 class="text-muted">Summary</h6>
                @php
                    $completed = $monthlyDuty->dailyLogs->where('status', 'completed')->count();
                    $total = $monthlyDuty->dailyLogs->count();
                    $totalKm = $monthlyDuty->dailyLogs->sum('total_km');
                @endphp
                <p class="mb-1"><strong>Progress:</strong> {{ $completed }} / {{ $total }} Days</p>
                <p class="mb-0"><strong>Total Usage:</strong> {{ $totalKm }} KM</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5>Daily Logs</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Start Time</th>
                    <th>Total KM</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyDuty->dailyLogs as $log)
                <tr>
                    <td>{{ $log->duty_date->format('d M Y') }}</td>
                    <td>
                        <span class="badge bg-{{ $log->status == 'completed' ? 'success' : ($log->status == 'started' ? 'primary' : ($log->status == 'pending' ? 'warning' : 'secondary')) }}">
                            {{ ucfirst($log->status) }}
                        </span>
                    </td>
                    <td>{{ $log->start_time ? \Carbon\Carbon::parse($log->start_time)->format('h:i A') : '-' }}</td>
                    <td>{{ $log->total_km }}</td>
                    <td>
                        <a href="{{ route('admin.daily-logs.show', $log->id) }}" class="btn btn-sm btn-outline-primary">Details</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
