@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Duty Details #{{ $monthlyDuty->id }}</h2>
    <div>
        <a href="{{ route('admin.reports.download', $monthlyDuty->id) }}" class="btn btn-outline-danger me-2">
            <i class="bi bi-file-pdf"></i> Download PDF
        </a>
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
                <p class="mb-1"><strong>Vehicle:</strong> {{ $monthlyDuty->vehicle->vehicle_number }}</p>
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
                <p class="mb-0"><strong>Time:</strong> {{ \Carbon\Carbon::parse($monthlyDuty->expected_start_time)->format('h:i A') }}</p>
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
