@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Add Vehicle & Driver</h2>
    <a href="{{ route('admin.vehicles.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i> Back
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="{{ route('admin.vehicles.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <h5 class="mb-3 text-primary">Vehicle Details</h5>
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Vehicle Number *</label>
                    <input type="text" name="vehicle_number" class="form-control @error('vehicle_number') is-invalid @enderror" value="{{ old('vehicle_number') }}" required placeholder="e.g. GJ01AB1234">
                    @error('vehicle_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Type *</label>
                    <select name="vehicle_type" class="form-select @error('vehicle_type') is-invalid @enderror" required>
                        <option value="">Select Type</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ old('vehicle_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('vehicle_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">PUC Expiry Date</label>
                    <input type="date" name="puc_expiry_date" class="form-control @error('puc_expiry_date') is-invalid @enderror" value="{{ old('puc_expiry_date') }}">
                    @error('puc_expiry_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr>

            <h5 class="mb-3 text-primary">Driver Details (Mandatory)</h5>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Driver Name *</label>
                    <input type="text" name="driver_name" class="form-control @error('driver_name') is-invalid @enderror" value="{{ old('driver_name') }}" required>
                    @error('driver_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Mobile Number *</label>
                    <input type="text" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror" value="{{ old('mobile_number') }}" required maxlength="10">
                    @error('mobile_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Driving Licence Number</label>
                    <input type="text" name="driving_licence_number" class="form-control @error('driving_licence_number') is-invalid @enderror" value="{{ old('driving_licence_number') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">DL Document</label>
                    <input type="file" name="driving_licence_document" class="form-control @error('driving_licence_document') is-invalid @enderror">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Aadhaar Number</label>
                    <input type="text" name="aadhaar_number" class="form-control @error('aadhaar_number') is-invalid @enderror" value="{{ old('aadhaar_number') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Aadhaar Document</label>
                    <input type="file" name="aadhaar_document" class="form-control @error('aadhaar_document') is-invalid @enderror">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Alternate Contact</label>
                    <input type="text" name="alternate_contact_number" class="form-control" value="{{ old('alternate_contact_number') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Relation</label>
                    <select name="relationship_with_alternate_contact" class="form-select">
                        <option value="">Select Relation</option>
                        @foreach(['Father', 'Mother', 'Brother', 'Sister', 'Wife', 'Friend', 'Other'] as $relation)
                            <option value="{{ $relation }}" {{ old('relationship_with_alternate_contact') == $relation ? 'selected' : '' }}>{{ $relation }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" value="{{ old('state') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Pincode</label>
                    <input type="text" name="pincode" class="form-control" value="{{ old('pincode') }}">
                </div>
            </div>

            <hr>

            <h5 class="mb-3 text-primary">Ownership Info</h5>
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="is_driver_owner" id="is_driver_owner" value="1" {{ old('is_driver_owner', '1') == '1' ? 'checked' : '' }}>
                <label class="form-check-label fw-bold" for="is_driver_owner">Driver is the Owner</label>
            </div>

            <div id="owner_details_section" class="{{ old('is_driver_owner', '1') == '1' ? 'd-none' : '' }}">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Owner/Vendor Name *</label>
                        <input type="text" name="owner_name" class="form-control @error('owner_name') is-invalid @enderror" value="{{ old('owner_name') }}">
                        @error('owner_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Owner Mobile *</label>
                        <input type="text" name="owner_mobile" class="form-control @error('owner_mobile') is-invalid @enderror" value="{{ old('owner_mobile') }}" maxlength="10">
                        @error('owner_mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Owner Aadhaar Number</label>
                        <input type="text" name="owner_aadhaar_number" class="form-control" value="{{ old('owner_aadhaar_number') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Owner PAN Card Number</label>
                        <input type="text" name="owner_pancard_number" class="form-control" value="{{ old('owner_pancard_number') }}">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="bi bi-save me-2"></i> Save Vehicle & Driver
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('is_driver_owner');
        const section = document.getElementById('owner_details_section');
        toggle.addEventListener('change', function() {
            section.classList.toggle('d-none', this.checked);
        });
    });
</script>
@endpush
@endsection
