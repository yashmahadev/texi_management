@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Create New Role</h2>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="{{ route('admin.roles.store') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="form-label">Role Name *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Manager" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <h5 class="mb-3">Assign Permissions</h5>
            <div class="row g-3 mb-4">
                @foreach($permissions as $permission)
                <div class="col-md-3">
                    <div class="form-check card p-2">
                        <input class="form-check-input ms-0" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm_{{ $permission->id }}" {{ is_array(old('permissions')) && in_array($permission->id, old('permissions')) ? 'checked' : '' }}>
                        <label class="form-check-label ps-2 mt-1" for="perm_{{ $permission->id }}">
                            {{ $permission->display_name ?? $permission->name }}
                        </label>
                    </div>
                </div>
                @endforeach
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-2"></i> Create Role
            </button>
        </form>
    </div>
</div>
@endsection
