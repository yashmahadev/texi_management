@extends('admin.layouts.app')

@section('content')
<div class="page-header mb-4">
    <h2>Monthly Duties</h2>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
        </a>
        <a href="{{ route('admin.monthly-duties.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i> Create New
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4 filter-card">
    <div class="card-body">
        <form action="{{ route('admin.monthly-duties.index') }}" method="GET" class="row g-3">
            <div class="col-md-9">
                <label class="form-label small fw-bold">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search Dept, Officer, Vehicle #..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
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
                    @include('partials.sort-th', ['col'=>'id',              'label'=>'ID',          'sort'=>$sort,'dir'=>$dir])
                    @include('partials.sort-th', ['col'=>'start_date',      'label'=>'Dates',       'sort'=>$sort,'dir'=>$dir])
                    <th>Vehicle</th>
                    <th>Primary Driver</th>
                    @include('partials.sort-th', ['col'=>'department_name', 'label'=>'Officer/Dept','sort'=>$sort,'dir'=>$dir])
                    @include('partials.sort-th', ['col'=>'created_at',      'label'=>'Created At',  'sort'=>$sort,'dir'=>$dir])
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
                        @if($duty->is_recurring)
                            <br><span class="badge bg-success mt-1"><i class="bi bi-arrow-repeat me-1"></i>Recurring</span>
                        @endif
                    </td>
                    <td>{{ $duty->created_at->toAppDate() }}</td>
                    <td>
                        <div class="btn-group">
                            <a href="{{ route('admin.monthly-duties.show', $duty->id) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i> View
                            </a>
                            @can('update', $duty)
                            <a href="{{ route('admin.monthly-duties.edit', $duty->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            @endcan
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
