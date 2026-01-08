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
                    <select name="department_name" id="department_name" class="form-control select2-tags" required>
                        <option value="">Select or Type New Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept }}" {{ old('department_name') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Officer Name *</label>
                    <select name="officer_name" id="officer_name" class="form-control select2-tags" required>
                        <option value="">Select or Type New Officer</option>
                        @foreach($officers as $officer)
                            <option value="{{ $officer }}" {{ old('officer_name') == $officer ? 'selected' : '' }}>{{ $officer }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Start Date *</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date') }}" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">End Date *</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date') }}" required>
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
                        <option value="">Select Dates & Type First</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Expected Start Time *</label>
                    <input type="time" name="expected_start_time" class="form-control" value="{{ old('expected_start_time') }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Expected End Time</label>
                    <input type="time" name="expected_end_time" class="form-control" value="{{ old('expected_end_time') }}">
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
            
            <div class="mt-3 d-flex gap-2">
                <button type="submit" name="action" value="save" class="btn btn-primary px-4">Create Duty & Generate Logs</button>
                <button type="submit" name="action" value="save_and_create" class="btn btn-outline-primary px-4">Save & Create Another</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2-tags').select2({
        tags: true,
        placeholder: 'Select or Type New',
        allowClear: true,
        width: '100%'
    });

    const typeSelect = document.getElementById('vehicle_type');
    const vehicleSelect = document.getElementById('vehicle_id');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    function fetchVehicles() {
        const type = typeSelect.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        if (!type || !startDate || !endDate) {
            vehicleSelect.innerHTML = '<option value="">Select Dates & Type First</option>';
            vehicleSelect.disabled = true;
            return;
        }

        vehicleSelect.innerHTML = '<option value="">Loading...</option>';
        vehicleSelect.disabled = true;

        fetch(`{{ route('admin.monthly-duties.vehicles-by-type') }}?type=${type}&start_date=${startDate}&end_date=${endDate}`)
            .then(response => response.json())
            .then(data => {
                vehicleSelect.innerHTML = '<option value="">Select Vehicle</option>';
                if (data.length === 0) {
                    vehicleSelect.innerHTML = '<option value="">No vehicles found for this type</option>';
                } else {
                    data.forEach(vehicle => {
                        const option = document.createElement('option');
                        option.value = vehicle.id;
                        let label = `${vehicle.vehicle_number} - ${vehicle.driver_name} (${vehicle.driver_mobile})`;
                        
                        if (vehicle.is_assigned) {
                            option.disabled = true;
                            label += ' [Already Assigned]';
                        }
                        
                        option.textContent = label;
                        vehicleSelect.appendChild(option);
                    });
                    vehicleSelect.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error fetching vehicles:', error);
                vehicleSelect.innerHTML = '<option value="">Error loading vehicles</option>';
            });
    }

    typeSelect.addEventListener('change', fetchVehicles);
    startDateInput.addEventListener('change', fetchVehicles);
    endDateInput.addEventListener('change', fetchVehicles);
});
</script>
@endpush
