@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Create Monthly Duty</h2>
    <a href="{{ route('admin.monthly-duties.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.monthly-duties.store') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department Name *</label>
                    <input type="text" name="department_name" class="form-control" value="{{ old('department_name') }}" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Officer Name *</label>
                    <input type="text" name="officer_name" class="form-control" value="{{ old('officer_name') }}" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Vehicle Type *</label>
                    <select id="vehicle_type" class="form-select" required>
                        <option value="">Select Vehicle Type</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Vehicle *</label>
                    <select name="vehicle_id" id="vehicle_id" class="form-select" required disabled>
                        <option value="">Select Vehicle Type First</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Primary Driver *</label>
                    <select name="primary_driver_id" class="form-select" required>
                        <option value="">Select Driver</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ old('primary_driver_id') == $driver->id ? 'selected' : '' }}>
                                {{ $driver->name }} ({{ $driver->mobile_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Start Date *</label>
                    <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">End Date *</label>
                    <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Expected Start Time *</label>
                    <input type="time" name="expected_start_time" class="form-control" value="{{ old('expected_start_time') }}" required>
                </div>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-primary px-4">Create Duty & Generate Logs</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('vehicle_type');
    const vehicleSelect = document.getElementById('vehicle_id');

    typeSelect.addEventListener('change', function() {
        const type = this.value;
        
        // Reset and disable vehicle select
        vehicleSelect.innerHTML = '<option value="">Loading...</option>';
        vehicleSelect.disabled = true;

        if (!type) {
            vehicleSelect.innerHTML = '<option value="">Select Vehicle Type First</option>';
            return;
        }

        fetch(`{{ route('admin.monthly-duties.vehicles-by-type') }}?type=${type}`)
            .then(response => response.json())
            .then(data => {
                vehicleSelect.innerHTML = '<option value="">Select Vehicle</option>';
                if (data.length === 0) {
                    vehicleSelect.innerHTML = '<option value="">No vehicles found for this type</option>';
                } else {
                    data.forEach(vehicle => {
                        const option = document.createElement('option');
                        option.value = vehicle.id;
                        option.textContent = vehicle.vehicle_number;
                        vehicleSelect.appendChild(option);
                    });
                    vehicleSelect.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error fetching vehicles:', error);
                vehicleSelect.innerHTML = '<option value="">Error loading vehicles</option>';
            });
    });
});
</script>
@endpush
