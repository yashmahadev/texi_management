@extends('driver.layouts.app')

@section('content')
<h4 class="mb-4">Duty History</h4>

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        @forelse($logs as $log)
            <div class="list-group-item px-3 py-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="fw-bold">{{ $log->duty_date->toAppDate() }}</div>
                    <span class="badge bg-{{ $log->status == 'completed' ? 'success' : ($log->status == 'started' ? 'primary' : 'secondary') }}">
                        {{ ucfirst($log->status) }}
                    </span>
                </div>
                <div class="mb-2">
                    <i class="bi bi-person text-muted small me-1"></i>
                    <span class="small fw-semibold">
                        @if($log->monthly_duty_id)
                            {{ $log->monthlyDuty->officer_name }} ({{ $log->monthlyDuty->department_name }})
                        @elseif($log->direct_booking_id)
                            {{ $log->directBooking->customer_name }}
                        @endif
                    </span>
                </div>
                <div class="row small text-muted">
                    <div class="col-6">
                        Start: {{ $log->start_time ? \Carbon\Carbon::parse($log->start_time)->toAppDateTime() : '-' }}<br>
                        End: {{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->toAppDateTime() : '-' }}
                    </div>
                    <div class="col-6 text-end">
                        Total: {{ $log->total_km }} km<br>
                        <span class="text-dark">
                            @if($log->monthly_duty_id)
                                {{ $log->monthlyDuty->vehicle->vehicle_number ?? '' }}
                                <small class="text-muted">({{ $log->monthlyDuty->vehicle->vehicle_type ?? '' }})</small>
                            @elseif($log->direct_booking_id)
                                {{ $log->directBooking->vehicle->vehicle_number ?? '' }}
                                <small class="text-muted">({{ $log->directBooking->vehicle->vehicle_type ?? '' }})</small>
                            @endif
                        </span>
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
