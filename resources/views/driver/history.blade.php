@extends('driver.layouts.app')

@section('content')
<h4 class="mb-4">Duty History</h4>

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        @forelse($logs as $log)
            <div class="list-group-item px-3 py-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold">{{ $log->duty_date->format('d M Y') }}</div>
                    <span class="badge bg-{{ $log->status == 'completed' ? 'success' : ($log->status == 'started' ? 'primary' : 'secondary') }}">
                        {{ ucfirst($log->status) }}
                    </span>
                </div>
                <div class="row small text-muted">
                    <div class="col-6">
                        Start: {{ $log->start_time ? \Carbon\Carbon::parse($log->start_time)->format('H:i') : '-' }}<br>
                        End: {{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->format('H:i') : '-' }}
                    </div>
                    <div class="col-6 text-end">
                        Total: {{ $log->total_km }} km<br>
                        {{ $log->monthlyDuty->vehicle->vehicle_number ?? '' }}
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-muted">
                No history available.
            </div>
        @endforelse
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-white pt-3">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
