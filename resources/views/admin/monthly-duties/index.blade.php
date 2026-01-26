@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Monthly Duties</h2>
    <a href="{{ route('admin.monthly-duties.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i> Create New
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('admin.monthly-duties.index') }}" method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="form-label small fw-bold">Department</label>
                <input type="text" name="department" class="form-control" placeholder="Search Dept..." value="{{ request('department') }}">
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold">Officer</label>
                <input type="text" name="officer" class="form-control" placeholder="Search Officer..." value="{{ request('officer') }}">
            </div>
            <!-- <div class="col-md-2">
                <label class="form-label small fw-bold">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div> -->
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('admin.monthly-duties.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
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
                        {{ $duty->start_date->toAppDate() }} - {{ $duty->end_date->toAppDate() }}
                    </td>
                    <td>{{ $duty->vehicle->vehicle_number }}</td>
                    <td>{{ $duty->primaryDriver->name }}</td>
                    <td>
                        {{ $duty->officer_name }}<br>
                        <small class="text-muted">{{ $duty->department_name }}</small>
                    </td>
                    <td>{{ $duty->created_at->toAppDate() }}</td>
                    <td>
                        <div class="btn-group">
                            <a href="{{ route('admin.monthly-duties.show', $duty->id) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i> View
                            </a>
                            @can('delete', $duty)
                                <form action="{{ route('admin.monthly-duties.destroy', $duty->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this duty? All associated daily logs, replacements, and billing records will be permanently removed.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            @endcan
                        </div>
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
