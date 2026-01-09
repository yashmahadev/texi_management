@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Create New Permission</h2>
    <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="{{ route('admin.permissions.store') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="form-label">Display Name *</label>
                <input type="text" name="display_name" class="form-control @error('display_name') is-invalid @enderror" value="{{ old('display_name') }}" placeholder="e.g. Edit User Profiles" required>
                @error('display_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label">Technical Name (Snake Case) *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. edit_users" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text mt-2">Use snake_case for permission names (e.g., manage_fleet).</div>
            </div>

            <div class="mb-4">
                <label class="form-label">Guard *</label>
                <select name="guard_name" class="form-select @error('guard_name') is-invalid @enderror" required>
                    <option value="web" {{ old('guard_name') == 'web' ? 'selected' : '' }}>Web (Admin Panel)</option>
                    <option value="driver" {{ old('guard_name') == 'driver' ? 'selected' : '' }}>Driver (App)</option>
                </select>
                @error('guard_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-2"></i> Create Permission
            </button>
        </form>
    </div>
</div>
@endsection
