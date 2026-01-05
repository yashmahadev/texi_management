@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Drivers</h2>
    <a href="{{ route('admin.drivers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i> Add Driver
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Mobile Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drivers as $driver)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="fw-bold">{{ $driver->name }}</td>
                        <td>{{ $driver->mobile_number }}</td>
                        <td>
                            <span class="badge bg-{{ $driver->status == 'active' ? 'success' : ($driver->status == 'suspended' ? 'danger' : 'secondary') }}">
                                {{ ucfirst($driver->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.drivers.edit', $driver->id) }}" class="btn btn-sm btn-outline-secondary me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.drivers.destroy', $driver->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure? This driver cannot be deleted if associated with past duties.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">No drivers found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($drivers->hasPages())
    <div class="card-footer bg-white">
        {{ $drivers->links() }}
    </div>
    @endif
</div>
@endsection
