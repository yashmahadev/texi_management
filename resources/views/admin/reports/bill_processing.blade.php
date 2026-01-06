@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Bill Processing Report</h2>
    <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">Back to Reports</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.reports.bill-processing') }}" method="GET">
            <div class="row align-items-end">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Monthly Duty</label>
                    <select name="monthly_duty_id" class="form-select">
                        <option value="">All Duties</option>
                        @foreach($duties as $duty)
                            <option value="{{ $duty->id }}" {{ request('monthly_duty_id') == $duty->id ? 'selected' : '' }}>
                                {{ $duty->department_name }} - {{ $duty->vehicle->vehicle_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Start Date *</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date', date('Y-m-01')) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">End Date *</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Preview Logs
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- @if($recentReports->isNotEmpty())
<div class="card shadow-sm mt-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">Recent Generated Reports</h5>
    </div>
    <div class="list-group list-group-flush">
        @foreach($recentReports as $report)
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                    {{ $report['name'] }}
                    <small class="text-muted ms-2">({{ date('d M Y, H:i', $report['date']) }})</small>
                </div>
                <a href="{{ $report['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download"></i> View / Download
                </a>
            </div>
        @endforeach
    </div>
</div>
@endif -->

@if(request()->filled('start_date') && request()->filled('end_date'))
<div class="card shadow-sm mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Logs for Preview</h5>
        <div class="btn-group">
            <button type="button" onclick="submitBillForm('{{ route('admin.reports.bill-processing.preview') }}')" class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-pdf"></i> Download Preview (No Save)
            </button>
            <button type="button" onclick="submitBillForm('{{ route('admin.reports.bill-processing.download') }}')" class="btn btn-primary">
                <i class="bi bi-cloud-download"></i> Save Changes & Download PDF
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <form id="bulk-download-form" action="{{ route('admin.reports.bill-processing.download') }}" method="POST">
            @csrf
            <input type="hidden" name="start_date" value="{{ request('start_date') }}">
            <input type="hidden" name="end_date" value="{{ request('end_date') }}">
            <input type="hidden" name="monthly_duty_id" value="{{ request('monthly_duty_id') }}">
            
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                @forelse($logs as $date => $dayLogs)
                    <thead class="table-light">
                        <tr>
                            <th colspan="7" class="bg-light fw-bold">Date: {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</th>
                        </tr>
                        <tr class="small text-muted">
                            <th width="150">Vehicle</th>
                            <th width="150">Driver</th>
                            <th>Dept</th>
                            <th width="240">Times (Start / End)</th>
                            <th width="240">KM (Start / End)</th>
                            <th width="100">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dayLogs as $log)
                        <tr>
                            <td>{{ $log->monthlyDuty->vehicle->vehicle_number }}</td>
                            <td>{{ $log->monthlyDuty->primaryDriver->name }}</td>
                            <td>{{ $log->monthlyDuty->department_name }}</td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="time" name="logs[{{ $log->id }}][start_time]" class="form-control" value="{{ $log->start_time }}">
                                    <input type="time" name="logs[{{ $log->id }}][end_time]" class="form-control" value="{{ $log->end_time }}">
                                </div>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="logs[{{ $log->id }}][start_km]" class="form-control" placeholder="Start" value="{{ $log->start_km }}">
                                    <input type="number" name="logs[{{ $log->id }}][end_km]" class="form-control" placeholder="End" value="{{ $log->end_km }}">
                                </div>
                            </td>
                            <td>
                                <select name="logs[{{ $log->id }}][status]" class="form-select form-select-sm">
                                    <option value="pending" {{ $log->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="started" {{ $log->status === 'started' ? 'selected' : '' }}>Started</option>
                                    <option value="completed" {{ $log->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="missing" {{ $log->status === 'missing' ? 'selected' : '' }}>Missing</option>
                                    <option value="approved" {{ $log->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="disputed" {{ $log->status === 'disputed' ? 'selected' : '' }}>Disputed</option>
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center py-5">No logs found for the selected period.</td>
                        </tr>
                    </tbody>
                @endforelse
                </table>
            </div>
        </form>
    </div>
</div>
@endif
@push('scripts')
<script>
    function submitBillForm(action) {
        const form = document.getElementById('bulk-download-form');
        form.action = action;
        form.submit();
    }
</script>
@endpush
@endsection
