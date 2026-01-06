@extends('admin.layouts.app')

@section('content')
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Daily Duty Logs</h2>
    </div>
    
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form action="{{ route('admin.daily-logs.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Duty (Dept/Officer)</label>
                    <select name="monthly_duty_id" class="form-select">
                        <option value="">All Duties</option>
                        @foreach($duties as $duty)
                            <option value="{{ $duty->id }}" {{ request('monthly_duty_id') == $duty->id ? 'selected' : '' }}>
                                {{ $duty->department_name }} ({{ $duty->officer_name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="started" {{ request('status') == 'started' ? 'selected' : '' }}>Started</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="missing" {{ request('status') == 'missing' ? 'selected' : '' }}>Missing</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="disputed" {{ request('status') == 'disputed' ? 'selected' : '' }}>Disputed</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="{{ route('admin.daily-logs.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Dept</th>
                    <th>Status</th>
                    <th>Times</th>
                    <th>Total KM</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->duty_date->format('d M') }}</td>
                    <td>{{ $log->monthlyDuty->vehicle->vehicle_number }}</td>
                    <td>{{ $log->monthlyDuty->primaryDriver->name }}</td>
                    <td>{{ $log->monthlyDuty->department_name }}</td>
                    <td>
                        <span class="badge bg-{{ $log->status == 'completed' ? 'success' : ($log->status == 'started' ? 'primary' : ($log->status == 'pending' ? 'warning' : 'secondary')) }}">
                            {{ ucfirst($log->status) }}
                        </span>
                    </td>
                    <td>
                        <small>
                            S: {{ $log->start_time ? \Carbon\Carbon::parse($log->start_time)->format('H:i') : '-' }}<br>
                            E: {{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->format('H:i') : '-' }}
                        </small>
                    </td>
                    <td>{{ $log->total_km }}</td>
                    <td>
                        <a href="{{ route('admin.daily-logs.show', $log->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4">No logs found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">
        {{ $logs->links() }}
    </div>
</div>
@endsection
