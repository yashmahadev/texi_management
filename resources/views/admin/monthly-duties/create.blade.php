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
                    <label class="form-label">Group *</label>
                    <select name="group" id="group_select" class="form-select" required>
                        <option value="">Select Group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group }}" {{ old('group') == $group ? 'selected' : '' }}>{{ $group }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Department *</label>
                    <div class="input-group">
                        <select name="department_id" id="department_id" class="form-select" required disabled>
                            <option value="">Select Group First</option>
                        </select>
                        <button class="btn btn-outline-primary" type="button" id="add_dept_btn" disabled data-bs-toggle="modal" data-bs-target="#addDeptModal">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                    @error('department_id') <small class="text-danger">{{ $message }}</small> @enderror
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

<!-- Add Department Modal -->
<div class="modal fade" id="addDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Department Name</label>
                    <input type="text" id="new_dept_name" class="form-control" placeholder="Enter department name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="save_new_dept">Save Department</button>
            </div>
        </div>
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

    const groupSelect = document.getElementById('group_select');
    const deptSelect = document.getElementById('department_id');
    const addDeptBtn = document.getElementById('add_dept_btn');
    const typeSelect = document.getElementById('vehicle_type');
    const vehicleSelect = document.getElementById('vehicle_id');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    // Group change - fetch departments
    groupSelect.addEventListener('change', function() {
        const group = this.value;
        deptSelect.innerHTML = '<option value="">Loading...</option>';
        deptSelect.disabled = true;
        addDeptBtn.disabled = true;

        if (!group) {
            deptSelect.innerHTML = '<option value="">Select Group First</option>';
            return;
        }

        fetch(`{{ route('admin.departments.index') }}?group=${group}`)
            .then(response => response.json())
            .then(data => {
                deptSelect.innerHTML = '<option value="">Select Department</option>';
                data.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = dept.name;
                    deptSelect.appendChild(option);
                });
                deptSelect.disabled = false;
                addDeptBtn.disabled = false;
            });
    });

    // Save New Department from Modal
    document.getElementById('save_new_dept').addEventListener('click', function() {
        const name = document.getElementById('new_dept_name').value;
        const group = groupSelect.value;

        if (!name) {
            alert('Please enter department name');
            return;
        }

        fetch(`{{ route('admin.departments.store') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ name, group })
        })
        .then(response => response.json())
        .then(data => {
            const option = document.createElement('option');
            option.value = data.id;
            option.textContent = data.name;
            option.selected = true;
            deptSelect.appendChild(option);
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addDeptModal'));
            modal.hide();
            document.getElementById('new_dept_name').value = '';
        })
        .catch(error => alert('Error saving department'));
    });

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
