@extends('admin.layouts.app')

@section('content')
<div class="mb-4">
    <div class="page-header mb-3">
        <h2>Daily Duty Logs</h2>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
        </a>
    </div>
    
    <div class="card border-0 shadow-sm mb-3 filter-card">
        <div class="card-body">
            <form action="{{ route('admin.daily-logs.index') }}" method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Department</label>
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>
                                {{ $dept }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Officer</label>
                    <select name="officer" class="form-select">
                        <option value="">All Officers</option>
                        @foreach($officers as $officer)
                            <option value="{{ $officer }}" {{ request('officer') == $officer ? 'selected' : '' }}>
                                {{ $officer }}
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
                        <option value="pending"   {{ request('status') == 'pending'   ? 'selected' : '' }}>Pending</option>
                        <option value="started"   {{ request('status') == 'started'   ? 'selected' : '' }}>Started</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="missing"   {{ request('status') == 'missing'   ? 'selected' : '' }}>Missing</option>
                        <option value="approved"  {{ request('status') == 'approved'  ? 'selected' : '' }}>Approved</option>
                        <option value="disputed"  {{ request('status') == 'disputed'  ? 'selected' : '' }}>Disputed</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
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
                    @include('partials.sort-th', ['col'=>'duty_date', 'label'=>'Date',     'sort'=>$sort,'dir'=>$dir])
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Dept</th>
                    @include('partials.sort-th', ['col'=>'status',    'label'=>'Status',   'sort'=>$sort,'dir'=>$dir])
                    <th>Times</th>
                    @include('partials.sort-th', ['col'=>'total_km',  'label'=>'Total KM', 'sort'=>$sort,'dir'=>$dir])
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->duty_date->toAppDate() }}</td>
                    <td>{{ $log->monthlyDuty->vehicle->vehicle_number ?? ($log->directBooking->vehicle->vehicle_number ?? '-') }}</td>
                    <td>{{ $log->monthlyDuty->primaryDriver->name ?? ($log->directBooking->driver->name ?? 'Unassigned') }}</td>
                    <td>{{ $log->monthlyDuty->department_name ?? ($log->directBooking->customer_name ?? '-') }}</td>
                    <td>
                        <span class="badge bg-{{ $log->status == 'completed' ? 'success' : ($log->status == 'started' ? 'primary' : ($log->status == 'pending' ? 'warning' : 'secondary')) }}">
                            {{ ucfirst($log->status) }}
                        </span>
                    </td>
                    <td>
                        <small>
                            S: {{ $log->start_time ? \Carbon\Carbon::parse($log->start_time)->format('H:i:s') : '-' }}<br>
                            E: {{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->format('H:i:s') : '-' }}
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
