@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Permissions</h2>
    <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i> Add Permission
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Display Name</th>
                    <th>Technical Name</th>
                    <th>Guard</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permissions as $permission)
                <tr>
                    <td><span class="fw-bold">{{ $permission->display_name ?? '-' }}</span></td>
                    <td><code>{{ $permission->name }}</code></td>
                    <td><span class="badge bg-light text-dark">{{ $permission->guard_name }}</span></td>
                    <td>
                        <form action="{{ route('admin.permissions.destroy', $permission->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center py-4 text-muted">No permissions found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">
        {{ $permissions->links() }}
    </div>
</div>
@endsection
