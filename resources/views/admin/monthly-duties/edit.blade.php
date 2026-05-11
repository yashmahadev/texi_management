@extends('admin.layouts.app')

@section('content')
<div class="page-header mb-4">
    <h2>Edit Monthly Duty #{{ $monthlyDuty->id }}</h2>
    <a href="{{ route('admin.monthly-duties.show', $monthlyDuty) }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.monthly-duties.update', $monthlyDuty) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">

                {{-- Group --}}
                <div class="col-md-6 mb-3">
                    <label class="form-label">Group *</label>
                    <select name="group" id="group_select" class="form-select @error('group') is-invalid @enderror" required>
                        <option value="">Select Group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group }}"
                                {{ old('group', $monthlyDuty->group) == $group ? 'selected' : '' }}>
                                {{ $group }}
                            </option>
                        @endforeach
                    </select>
                    @error('group') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Department --}}
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department *</label>
                    <div class="input-group">
                        <select name="department_id" id="department_id"
                            class="form-select @error('department_id') is-invalid @enderror" required>
                            {{-- Pre-populated with current department --}}
                            <option value="{{ $monthlyDuty->department_id }}" selected>
                                {{ $monthlyDuty->department_name }}
                            </option>
                        </select>
                        <button class="btn btn-outline-primary" type="button" id="add_dept_btn"
                            data-bs-toggle="modal" data-bs-target="#addDeptModal">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                    @error('department_id') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                {{-- Officer Name --}}
                <div class="col-md-6 mb-3">
                    <label class="form-label">Officer Name *</label>
                    <select name="officer_name" id="officer_name"
                        class="form-control select2-tags @error('officer_name') is-invalid @enderror" required>
                        <option value="">Select or Type New Officer</option>
                        @foreach($officers as $officer)
                            <option value="{{ $officer }}"
                                {{ old('officer_name', $monthlyDuty->officer_name) == $officer ? 'selected' : '' }}>
                                {{ $officer }}
                            </option>
                        @endforeach
                    </select>
                    @error('officer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Start Date --}}
                <div class="col-sm-6 col-md-3 mb-3">
                    <label class="form-label">Start Date *</label>
                    <input type="date" name="start_date" id="start_date"
                        class="form-control @error('start_date') is-invalid @enderror"
                        value="{{ old('start_date', $monthlyDuty->start_date->format('Y-m-d')) }}" required>
                    @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- End Date --}}
                <div class="col-sm-6 col-md-3 mb-3">
                    <label class="form-label">End Date *</label>
                    <input type="date" name="end_date" id="end_date"
                        class="form-control @error('end_date') is-invalid @enderror"
                        value="{{ old('end_date', $monthlyDuty->end_date->format('Y-m-d')) }}" required>
                    @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Vehicle Type --}}
                <div class="col-sm-6 col-md-3 mb-3">
                    <label class="form-label">Vehicle Type *</label>
                    <select name="vehicle_type" id="vehicle_type" class="form-select" required>
                        <option value="">Select Vehicle Type</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}"
                                {{ $monthlyDuty->vehicle->vehicle_type == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Vehicle --}}
                <div class="col-sm-6 col-md-3 mb-3">
                    <label class="form-label">Vehicle *</label>
                    <select name="vehicle_id" id="vehicle_id"
                        class="form-select @error('vehicle_id') is-invalid @enderror" required>
                        {{-- Pre-populated with current vehicle --}}
                        <option value="{{ $monthlyDuty->vehicle_id }}" selected>
                            {{ $monthlyDuty->vehicle->vehicle_number }}
                            ({{ $monthlyDuty->primaryDriver->name ?? 'No Driver' }})
                        </option>
                    </select>
                    <small class="text-muted">Select dates & type above to reload available vehicles.</small>
                    @error('vehicle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Expected Start Time --}}
                <div class="col-sm-6 col-md-3 mb-3">
                    <label class="form-label">Expected Start Time *</label>
                    <input type="time" name="expected_start_time"
                        class="form-control @error('expected_start_time') is-invalid @enderror"
                        value="{{ old('expected_start_time', \Carbon\Carbon::parse($monthlyDuty->expected_start_time)->format('H:i')) }}"
                        required>
                    @error('expected_start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Expected End Time --}}
                <div class="col-sm-6 col-md-3 mb-3">
                    <label class="form-label">Expected End Time</label>
                    <input type="time" name="expected_end_time" class="form-control"
                        value="{{ old('expected_end_time', $monthlyDuty->expected_end_time ? \Carbon\Carbon::parse($monthlyDuty->expected_end_time)->format('H:i') : '') }}">
                </div>

                {{-- State / City / Pincode --}}
                @include('partials.state-city-select', [
                    'selectedState'   => old('state',   $monthlyDuty->state),
                    'selectedCity'    => old('city',    $monthlyDuty->city),
                    'selectedPincode' => old('pincode', $monthlyDuty->pincode),
                ])

                {{-- Route / Remarks --}}
                <div class="col-12 mb-3">
                    <label class="form-label">Route / Remarks</label>
                    <textarea name="route_remarks" class="form-control" rows="2"
                        placeholder="Enter route details or any remarks...">{{ old('route_remarks', $monthlyDuty->route_remarks) }}</textarea>
                </div>

                {{-- Recurring --}}
                <div class="col-12 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_recurring"
                            id="is_recurring" value="1"
                            {{ old('is_recurring', $monthlyDuty->is_recurring) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_recurring">
                            <strong>Recurring Duty</strong>
                            <small class="text-muted d-block">When enabled, this duty will automatically renew every month on the last day of the month.</small>
                        </label>
                    </div>
                </div>

            </div>

            {{-- Date change warning --}}
            <div class="alert alert-warning d-none" id="date_change_warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Note:</strong> Changing the dates will regenerate daily logs.
                Existing <strong>pending</strong> logs will be removed and recreated.
                Completed and started logs will be preserved.
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-2"></i> Save Changes
                </button>
                <a href="{{ route('admin.monthly-duties.show', $monthlyDuty) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

{{-- Add Department Modal --}}
<div class="modal fade" id="addDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
document.addEventListener('DOMContentLoaded', function () {

    const groupSelect   = document.getElementById('group_select');
    const deptSelect    = document.getElementById('department_id');
    const addDeptBtn    = document.getElementById('add_dept_btn');
    const typeSelect    = document.getElementById('vehicle_type');
    const vehicleSelect = document.getElementById('vehicle_id');
    const startDateInput = document.getElementById('start_date');
    const endDateInput   = document.getElementById('end_date');
    const dateWarning    = document.getElementById('date_change_warning');

    const originalStart = '{{ $monthlyDuty->start_date->format('Y-m-d') }}';
    const originalEnd   = '{{ $monthlyDuty->end_date->format('Y-m-d') }}';

    // Helper: notify Select2 that a select's options have changed
    function refreshSelect2(el) {
        if (window.jQuery && $(el).hasClass('select2-hidden-accessible')) {
            $(el).trigger('change');
        }
    }

    // Show warning when dates change
    function checkDateChange() {
        if (startDateInput.value !== originalStart || endDateInput.value !== originalEnd) {
            dateWarning.classList.remove('d-none');
        } else {
            dateWarning.classList.add('d-none');
        }
    }
    startDateInput.addEventListener('change', checkDateChange);
    endDateInput.addEventListener('change', checkDateChange);

    // Group change → reload departments
    $(groupSelect).on('change', function () {
        const group = this.value;
        deptSelect.innerHTML = '<option value="">Loading...</option>';
        deptSelect.disabled = true;
        addDeptBtn.disabled = true;
        refreshSelect2(deptSelect);

        if (!group) {
            deptSelect.innerHTML = '<option value="">Select Group First</option>';
            refreshSelect2(deptSelect);
            return;
        }

        fetch(`{{ route('admin.departments.index') }}?group=${encodeURIComponent(group)}`)
            .then(r => {
                if (!r.ok) throw new Error(`Server error: ${r.status}`);
                return r.json();
            })
            .then(data => {
                deptSelect.innerHTML = '<option value="">Select Department</option>';
                data.forEach(dept => {
                    const opt = document.createElement('option');
                    opt.value = dept.id;
                    opt.textContent = dept.name;
                    deptSelect.appendChild(opt);
                });
                deptSelect.disabled = false;
                addDeptBtn.disabled = false;
                refreshSelect2(deptSelect);
            })
            .catch(err => {
                console.error('Error fetching departments:', err);
                deptSelect.innerHTML = '<option value="">Error loading departments</option>';
                deptSelect.disabled = false;
                addDeptBtn.disabled = false;
                refreshSelect2(deptSelect);
            });
    });

    // Save new department from modal
    document.getElementById('save_new_dept').addEventListener('click', function () {
        const name  = document.getElementById('new_dept_name').value.trim();
        const group = groupSelect.value;
        if (!name) { alert('Please enter a department name.'); return; }

        fetch(`{{ route('admin.departments.store') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ name, group })
        })
        .then(async r => {
            const data = await r.json();
            if (!r.ok) throw new Error(Object.values(data.errors ?? {})[0]?.[0] ?? data.message);
            return data;
        })
        .then(data => {
            const opt = document.createElement('option');
            opt.value = data.id;
            opt.textContent = data.name;
            opt.selected = true;
            deptSelect.appendChild(opt);
            refreshSelect2(deptSelect);
            bootstrap.Modal.getInstance(document.getElementById('addDeptModal')).hide();
            document.getElementById('new_dept_name').value = '';
        })
        .catch(e => alert(e.message));
    });

    // Vehicle type + date change → reload vehicles
    function fetchVehicles() {
        const type      = typeSelect.value;
        const startDate = startDateInput.value;
        const endDate   = endDateInput.value;

        if (!type || !startDate || !endDate) {
            vehicleSelect.innerHTML = '<option value="">Select Dates & Type First</option>';
            refreshSelect2(vehicleSelect);
            return;
        }

        vehicleSelect.innerHTML = '<option value="">Loading...</option>';
        refreshSelect2(vehicleSelect);

        fetch(`{{ route('admin.monthly-duties.vehicles-by-type') }}?type=${encodeURIComponent(type)}&start_date=${startDate}&end_date=${endDate}&exclude_duty_id={{ $monthlyDuty->id }}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(async r => {
            const data = await r.json();
            if (!r.ok) throw new Error(data.message ?? 'Error loading vehicles');
            return data;
        })
        .then(data => {
            vehicleSelect.innerHTML = '<option value="">Select Vehicle</option>';
            if (!data || !data.length) {
                vehicleSelect.innerHTML = '<option value="">No vehicles available for this period</option>';
            } else {
                data.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.id;
                    opt.textContent = `${v.vehicle_number} — ${v.driver_name} (${v.driver_mobile})`;
                    if (v.is_assigned) {
                        opt.disabled = true;
                        opt.textContent += ' [Already Assigned]';
                    }
                    if (v.id == {{ $monthlyDuty->vehicle_id }}) opt.selected = true;
                    vehicleSelect.appendChild(opt);
                });
            }
            refreshSelect2(vehicleSelect);
        })
        .catch(e => {
            console.error('Error fetching vehicles:', e);
            vehicleSelect.innerHTML = `<option value="">Error: ${e.message}</option>`;
            refreshSelect2(vehicleSelect);
        });
    }

    // Use jQuery .on() so Select2-triggered change events are also caught
    $(typeSelect).on('change', fetchVehicles);
    startDateInput.addEventListener('change', fetchVehicles);
    endDateInput.addEventListener('change', fetchVehicles);
});
</script>
@endpush
