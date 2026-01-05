@extends('admin.layouts.app')

@section('content')
<h2>Reports</h2>

<div class="card shadow-sm mt-4">
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
