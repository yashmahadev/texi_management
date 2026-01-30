@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Vehicle & Driver</h2>
    <a href="{{ route('admin.vehicles.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i> Back
    </a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Owner Details</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Owner Name</label>
                <input type="text" class="form-control" value="{{ $vehicle->owner->name }}" readonly>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Owner Mobile</label>
                <input type="text" class="form-control" value="{{ $vehicle->owner->mobile }}" readonly>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Owner Aadhaar</label>
                <input type="text" class="form-control" value="{{ $vehicle->owner->aadhaar_number }}" readonly>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="{{ route('admin.vehicles.update', $vehicle->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <h5 class="mb-3 text-primary">Vehicle Details</h5>
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Vehicle Number *</label>
                    <input type="text" name="vehicle_number" class="form-control @error('vehicle_number') is-invalid @enderror" value="{{ old('vehicle_number', $vehicle->vehicle_number) }}" required>
                    @error('vehicle_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Make & Model *</label>
                    <input type="text" name="make_model" class="form-control" value="{{ old('make_model', $vehicle->make_model) }}" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Color</label>
                    <input type="text" name="color" class="form-control" value="{{ old('color', $vehicle->color) }}">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Vehicle Type *</label>
                    <select name="vehicle_type" class="form-select" required>
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ old('vehicle_type', $vehicle->vehicle_type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Passing Type *</label>
                    <select name="pass_type" class="form-select" required>
                        <option value="Private" {{ old('pass_type', $vehicle->pass_type) == 'Private' ? 'selected' : '' }}>Private</option>
                        <option value="Taxi" {{ old('pass_type', $vehicle->pass_type) == 'Taxi' ? 'selected' : '' }}>Taxi</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Fuel Type *</label>
                    <select name="fuel_type" class="form-select" required>
                        <option value="CNG" {{ old('fuel_type', $vehicle->fuel_type) == 'CNG' ? 'selected' : '' }}>CNG</option>
                        <option value="Diesel" {{ old('fuel_type', $vehicle->fuel_type) == 'Diesel' ? 'selected' : '' }}>Diesel</option>
                        <option value="Petrol" {{ old('fuel_type', $vehicle->fuel_type) == 'Petrol' ? 'selected' : '' }}>Petrol</option>
                        <option value="Electric" {{ old('fuel_type', $vehicle->fuel_type) == 'Electric' ? 'selected' : '' }}>Electric</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Transmission *</label>
                    <select name="transmission_type" class="form-select" required>
                        <option value="Manual" {{ old('transmission_type', $vehicle->transmission_type) == 'Manual' ? 'selected' : '' }}>Manual</option>
                        <option value="Automatic" {{ old('transmission_type', $vehicle->transmission_type) == 'Automatic' ? 'selected' : '' }}>Automatic</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">PUC Expiry Date</label>
                    <input type="date" name="puc_expiry_date" class="form-control" value="{{ old('puc_expiry_date', $vehicle->puc_expiry_date ? $vehicle->puc_expiry_date->format('Y-m-d') : '') }}">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Insurance Expiry Date</label>
                    <input type="date" name="insurance_expiry_date" class="form-control" value="{{ old('insurance_expiry_date', $vehicle->insurance_expiry_date ? $vehicle->insurance_expiry_date->format('Y-m-d') : '') }}">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select" required>
                        <option value="active" {{ old('status', $vehicle->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="maintenance" {{ old('status', $vehicle->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="inactive" {{ old('status', $vehicle->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">RC Book (Upload to change)</label>
                    <input type="file" name="rc_book" class="form-control">
                    @if($vehicle->rc_book_path)
                        <small><a href="{{ Storage::url($vehicle->rc_book_path) }}" target="_blank">View Current RC</a></small>
                    @endif
                </div>
            </div>

            <hr>

            <h5 class="mb-3 text-primary">Driver Details</h5>
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Driver Name *</label>
                    <input type="text" name="driver_name" class="form-control" value="{{ old('driver_name', $vehicle->driver->name ?? '') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Mobile Number *</label>
                    <input type="text" name="driver_mobile" class="form-control" value="{{ old('driver_mobile', $vehicle->driver->mobile_number ?? '') }}" required maxlength="10">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Age</label>
                    <input type="number" name="driver_age" class="form-control" value="{{ old('driver_age', $vehicle->driver->age ?? '') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Driving Licence Number</label>
                    <input type="text" name="driver_dl_number" class="form-control" value="{{ old('driver_dl_number', $vehicle->driver->driving_licence_number ?? '') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">DL Document (Upload to Change)</label>
                    <input type="file" name="driver_dl_document" class="form-control">
                    @if($vehicle->driver && $vehicle->driver->driving_licence_document)
                        <small><a href="{{ Storage::url($vehicle->driver->driving_licence_document) }}" target="_blank">View Current DL</a></small>
                    @endif
                </div>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="driver_address" class="form-control" rows="2">{{ old('driver_address', $vehicle->driver->address ?? '') }}</textarea>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="is_police_verified" id="pv_toggle" value="1" {{ old('is_police_verified', $vehicle->driver->is_police_verified ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="pv_toggle">Police Verified</label>
                    </div>
                </div>

                <div class="col-md-6 mb-3" id="pv_doc_section">
                    <label class="form-label">PV Document (Upload to Change)</label>
                    <input type="file" name="police_verification_document" class="form-control">
                    @if($vehicle->driver && $vehicle->driver->police_verification_document)
                        <small><a href="{{ Storage::url($vehicle->driver->police_verification_document) }}" target="_blank">View Current PV Doc</a></small>
                    @endif
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="bi bi-save me-2"></i> Update Vehicle & Driver
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
