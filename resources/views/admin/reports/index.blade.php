@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Reports</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reports.bill-processing') }}" class="btn btn-primary">
            <i class="bi bi-calculator"></i> Bill Processing Report
        </a>
        <a href="{{ route('admin.reports.direct-bookings.index') }}" class="btn btn-primary">
            <i class="bi bi-calendar-check"></i> Direct Booking Reports
        </a>
    </div>
</div>

{{-- The original instruction's code edit was malformed, attempting to insert divs inside an <a> tag.
    I've interpreted the intent as adding a new link for Direct Booking Reports in the header,
    and then adding a new section for "Recent Monthly Duties" which seems to be a re-labeling
    of the existing table, or a new section entirely.
    Given the structure, I'll assume the "Recent Monthly Duties" is a new header for the existing table.
    The card structure for "Direct Booking Reports" from the instruction is also not directly applicable
    to the header, so I've converted it to a simple button link like the Bill Processing Report.
    If a card layout is desired, the structure would need to be significantly altered,
    likely by wrapping the entire header in a row/column system.
--}}

<div class="card shadow-sm mt-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">Recent Monthly Duties</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Month/Period</th>
                    <th>Vehicle</th>
                    <th>Department</th>
                    <th>Total KM</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($duties as $duty)
                <tr>
                    <td>{{ $duty->start_date->format('M Y') }}</td>
                    <td>{{ $duty->vehicle->vehicle_number }}</td>
                    <td>{{ $duty->department_name }}</td>
                    <td>{{ $duty->dailyLogs->sum('total_km') }}</td>
                    <td>Active</td> 
                    <td>
                        <a href="{{ route('admin.reports.download', $duty->id) }}" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-file-pdf"></i> Download PDF
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4">No data available.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
     <div class="card-footer bg-white">
        {{ $duties->links() }}
    </div>
</div>
@endsection
