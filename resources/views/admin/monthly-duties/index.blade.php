@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Monthly Duties</h2>
    <a href="{{ route('admin.monthly-duties.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i> Create New
    </a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Dates</th>
                    <th>Vehicle</th>
                    <th>Primary Driver</th>
                    <th>Officer/Dept</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($duties as $duty)
                <tr>
                    <td>#{{ $duty->id }}</td>
                    <td>
                        {{ $duty->start_date->format('d M') }} - {{ $duty->end_date->format('d M Y') }}
                    </td>
                    <td>{{ $duty->vehicle->vehicle_number }}</td>
                    <td>{{ $duty->primaryDriver->name }}</td>
                    <td>
                        {{ $duty->officer_name }}<br>
                        <small class="text-muted">{{ $duty->department_name }}</small>
                    </td>
                    <td>{{ $duty->created_at->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('admin.monthly-duties.show', $duty->id) }}" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4">No monthly duties found.</td>
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
