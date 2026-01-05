@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>System Audit Logs</h2>
</div>

<div class="card shadow-sm">
    <div class="card-body border-bottom">
        <form action="{{ route('admin.audit-logs.index') }}" method="GET" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="entity_type" class="form-control" placeholder="Entity Type (e.g. daily_duty_logs)" value="{{ request('entity_type') }}">
            </div>
            <div class="col-md-3">
                <input type="text" name="action" class="form-control" placeholder="Action Search..." value="{{ request('action') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Time</th>
                    <th>Performer</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d M H:i:s') }}</td>
                    <td>
                        @if($log->performer)
                            {{ $log->performer->name }}
                        @elseif($log->performed_by)
                            <span class="text-muted">ID: {{ $log->performed_by }}</span>
                        @else
                            <span class="badge bg-secondary">System</span>
                        @endif
                    </td>
                    <td><span class="badge bg-info text-dark">{{ $log->action }}</span></td>
                    <td>{{ $log->entity_type }} #{{ $log->entity_id }}</td>
                    <td><small>{{ $log->remarks ?? '-' }}</small></td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4">No audit logs found.</td>
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
