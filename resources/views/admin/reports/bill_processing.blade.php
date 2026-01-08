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
                <div class="col-md-9 mb-3">
                    <label class="form-label">Select Monthly Duty to Process</label>
                    <select name="monthly_duty_id" class="form-select form-select-lg">
                        <option value="">-- Choose Duty (Dept / Vehicle) --</option>
                        @foreach($duties as $duty)
                            <option value="{{ $duty->id }}" {{ request('monthly_duty_id') == $duty->id ? 'selected' : '' }}>
                                {{ $duty->department_name }} ({{ $duty->officer_name }}) - {{ $duty->vehicle->vehicle_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-search"></i> Get Duty Logs
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

@if(request()->filled('monthly_duty_id'))
<div class="card shadow-sm mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Logs Preview: {{ \Carbon\Carbon::parse(request('start_date'))->format('d M') }} to {{ \Carbon\Carbon::parse(request('end_date'))->format('d M Y') }}</h5>
        <div class="btn-group">
            <button type="button" onclick="submitBillForm('{{ route('admin.reports.bill-processing.preview') }}')" class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-pdf"></i> Download Preview (No Save)
            </button>
            <button type="button" onclick="submitBillForm('{{ route('admin.reports.bill-processing.download') }}')" class="btn btn-primary">
                <i class="bi bi-cloud-download"></i> Save Changes & Download PDF
            </button>
        </div>
    </div>
    <div class="card-body">
        @php 
            $firstLog = $logs->flatten()->first();
            $duty = $firstLog ? $firstLog->monthlyDuty : null;
        @endphp

        @if($duty)
        <div class="bg-light p-3 border-bottom mb-0">
            <div class="row">
                <div class="col-md-4">
                    <small class="text-uppercase text-muted fw-bold">Vehicle Details</small>
                    <div class="fw-bold">{{ $duty->vehicle->vehicle_number }} ({{ $duty->vehicle->vehicle_type }})</div>
                    <small>{{ $duty->primaryDriver->name }} - {{ $duty->primaryDriver->mobile_number }}</small>
                </div>
                <div class="col-md-4 border-start">
                    <small class="text-uppercase text-muted fw-bold">Department / Officer</small>
                    <div class="fw-bold">{{ $duty->department_name }}</div>
                    <small>Attn: {{ $duty->officer_name }}</small>
                </div>
                <div class="col-md-4 border-start text-end">
                    <small class="text-uppercase text-muted fw-bold">Duty Period</small>
                    <div class="fw-bold">{{ $duty->start_date->format('d M Y') }} to {{ $duty->end_date->format('d M Y') }}</div>
                    <small class="text-primary fw-bold">ID: #{{ $duty->id }}</small>
                </div>
            </div>
        </div>
        @endif

        <form id="bulk-download-form" action="{{ route('admin.reports.bill-processing.download') }}" method="POST">
            @csrf
            <input type="hidden" name="start_date" value="{{ request('start_date') }}">
            <input type="hidden" name="end_date" value="{{ request('end_date') }}">
            <input type="hidden" name="monthly_duty_id" value="{{ request('monthly_duty_id') }}">
            
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase fw-bold">
                            <th width="120">Date</th>
                            <th width="240">Times (Start / End)</th>
                            <th width="240">KM (Start / End)</th>
                            <th width="80" class="text-center">Total KM</th>
                            <th width="150">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $date => $dayLogs)
                            @foreach($dayLogs as $log)
                            @php 
                                $displayLog = $log->billingLog ?? $log;
                            @endphp
                            <tr>
                                <td class="fw-bold align-middle">{{ \Carbon\Carbon::parse($date)->format('d-M (D)') }}</td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="time" name="logs[{{ $log->id }}][start_time]" class="form-control" value="{{ $displayLog->start_time ? \Carbon\Carbon::parse($displayLog->start_time)->format('H:i') : '' }}">
                                        <input type="time" name="logs[{{ $log->id }}][end_time]" class="form-control" value="{{ $displayLog->end_time ? \Carbon\Carbon::parse($displayLog->end_time)->format('H:i') : '' }}">
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="logs[{{ $log->id }}][start_km]" class="form-control" placeholder="Start" value="{{ $displayLog->start_km }}">
                                        <input type="number" name="logs[{{ $log->id }}][end_km]" class="form-control" placeholder="End" value="{{ $displayLog->end_km }}">
                                    </div>
                                </td>
                                <td class="text-center align-middle fw-bold bg-light">
                                    {{ $displayLog->total_km }}
                                </td>
                                <td>
                                    <select name="logs[{{ $log->id }}][status]" class="form-select form-select-sm">
                                        <option value="pending" {{ $displayLog->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="started" {{ $displayLog->status === 'started' ? 'selected' : '' }}>Started</option>
                                        <option value="completed" {{ $displayLog->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="missing" {{ $displayLog->status === 'missing' ? 'selected' : '' }}>Missing</option>
                                        <option value="approved" {{ $displayLog->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="disputed" {{ $displayLog->status === 'disputed' ? 'selected' : '' }}>Disputed</option>
                                    </select>
                                </td>
                            </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No logs found for the selected period.</td>
                            </tr>
                        @endforelse
                    </tbody>
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
