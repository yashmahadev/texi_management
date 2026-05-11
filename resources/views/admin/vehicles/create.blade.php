@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Add Owner, Vehicles & Drivers</h2>
    <a href="{{ route('admin.vehicles.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i> Back
    </a>
</div>

<form action="{{ route('admin.vehicles.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    
    <!-- Owner Section -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Owner Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Owner/Vendor Name *</label>
                    <input type="text" name="owner_name" class="form-control @error('owner_name') is-invalid @enderror" value="{{ old('owner_name') }}" required>
                    @error('owner_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Owner Mobile *</label>
                    <input type="text" name="owner_mobile" class="form-control @error('owner_mobile') is-invalid @enderror" value="{{ old('owner_mobile') }}" required maxlength="10">
                    @error('owner_mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-4 col-6 mb-3">
                    <label class="form-label">Owner Aadhaar Number</label>
                    <input type="text" name="owner_aadhaar_number" class="form-control @error('owner_aadhaar_number') is-invalid @enderror" value="{{ old('owner_aadhaar_number') }}">
                </div>
                <div class="col-sm-4 col-6 mb-3">
                    <label class="form-label">Owner PAN Card Number</label>
                    <input type="text" name="owner_pancard_number" class="form-control @error('owner_pancard_number') is-invalid @enderror" value="{{ old('owner_pancard_number') }}">
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Owner Address</label>
                    <textarea name="owner_address" class="form-control @error('owner_address') is-invalid @enderror" rows="1">{{ old('owner_address') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicles & Drivers Container -->
    <div id="vehicle-driver-container">
        <!-- Initial Section -->
        <div class="vehicle-driver-block card shadow-sm border-0 mb-4" data-index="0">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Vehicle & Driver #1</h5>
                <button type="button" class="btn btn-danger btn-sm remove-block d-none">
                    <i class="bi bi-trash me-1"></i> Remove
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Vehicle Details -->
                    <div class="col-md-12 mb-2">
                        <h6 class="text-primary border-bottom pb-2">Vehicle Details</h6>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Vehicle Number *</label>
                        <input type="text" name="vehicles[0][vehicle_number]" class="form-control" value="{{ old('vehicles.0.vehicle_number') }}" required placeholder="GJ01AB1234">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Make & Model *</label>
                        <input type="text" name="vehicles[0][make_model]" class="form-control" value="{{ old('vehicles.0.make_model') }}" required placeholder="e.g. 2024-Swift Dzire">
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <label class="form-label">Fuel Type *</label>
                        <select name="vehicles[0][fuel_type]" class="form-select" required>
                            @foreach(['Petrol','Diesel','CNG'] as $fuel)
                                <option value="{{ $fuel }}" {{ old('vehicles.0.fuel_type', 'Petrol') == $fuel ? 'selected' : '' }}>{{ $fuel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <label class="form-label">Transmission *</label>
                        <select name="vehicles[0][transmission_type]" class="form-select" required>
                            @foreach(['Manual','Automatic'] as $trans)
                                <option value="{{ $trans }}" {{ old('vehicles.0.transmission_type', 'Manual') == $trans ? 'selected' : '' }}>{{ $trans }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <label class="form-label">Color</label>
                        <input type="text" name="vehicles[0][color]" class="form-control" value="{{ old('vehicles.0.color') }}" placeholder="e.g. White">
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <label class="form-label">Category *</label>
                        <select name="vehicles[0][vehicle_type]" class="form-select vehicle-type-select" required>
                            @foreach($types as $type)
                                <option value="{{ $type }}" {{ old('vehicles.0.vehicle_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                            <option value="Bus" {{ old('vehicles.0.vehicle_type') == 'Bus' ? 'selected' : '' }}>Bus</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 d-none custom-type-input">
                        <label class="form-label">Specify Type *</label>
                        <input type="text" name="vehicles[0][vehicle_type_custom]" class="form-control" value="{{ old('vehicles.0.vehicle_type_custom') }}">
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <label class="form-label">Passing Type *</label>
                        <select name="vehicles[0][pass_type]" class="form-select" required>
                            <option value="Private" {{ old('vehicles.0.pass_type', 'Private') == 'Private' ? 'selected' : '' }}>Private Pass</option>
                            <option value="Taxi" {{ old('vehicles.0.pass_type', 'Private') == 'Taxi' ? 'selected' : '' }}>Taxi Pass</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <label class="form-label">PUC Expiry</label>
                        <input type="date" name="vehicles[0][puc_expiry_date]" class="form-control" value="{{ old('vehicles.0.puc_expiry_date') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">RC Book (Upload)</label>
                        <input type="file" name="vehicles[0][rc_book]" class="form-control">
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <label class="form-label">Challan Count</label>
                        <input type="number" name="vehicles[0][challan_count]" class="form-control" value="{{ old('vehicles.0.challan_count', 0) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Total Challan Amt</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="vehicles[0][challan_amount]" class="form-control" value="{{ old('vehicles.0.challan_amount', 0) }}">
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Insurance Details</label>
                        <textarea name="vehicles[0][insurance_details]" class="form-control" rows="1">{{ old('vehicles.0.insurance_details') }}</textarea>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Insurance Expiry Date</label>
                        <input type="date" name="vehicles[0][insurance_expiry_date]" class="form-control" value="{{ old('vehicles.0.insurance_expiry_date') }}">
                    </div>

                    <!-- Driver Details -->
                    <div class="col-md-12 mb-2 mt-2">
                        <h6 class="text-primary border-bottom pb-2">Driver Details</h6>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Driver Name *</label>
                        <input type="text" name="vehicles[0][driver_name]" class="form-control" value="{{ old('vehicles.0.driver_name') }}" required>
                    </div>
                    <div class="col-6 col-sm-2 mb-3">
                        <label class="form-label">Mobile *</label>
                        <input type="text" name="vehicles[0][driver_mobile]" class="form-control" value="{{ old('vehicles.0.driver_mobile') }}" required maxlength="10">
                    </div>
                    <div class="col-6 col-sm-1 mb-3">
                        <label class="form-label">Age</label>
                        <input type="number" name="vehicles[0][driver_age]" class="form-control" value="{{ old('vehicles.0.driver_age') }}">
                    </div>
                    <div class="col-sm-3 col-12 mb-3">
                        <label class="form-label">DL Number</label>
                        <input type="text" name="vehicles[0][driver_dl_number]" class="form-control" value="{{ old('vehicles.0.driver_dl_number') }}">
                    </div>
                    <div class="col-6 col-sm-3 mb-3">
                        <label class="form-label">DL Expiry</label>
                        <input type="date" name="vehicles[0][driver_dl_expiry]" class="form-control" value="{{ old('vehicles.0.driver_dl_expiry') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Driver Address</label>
                        <input type="text" name="vehicles[0][driver_address]" class="form-control" value="{{ old('vehicles.0.driver_address') }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Police Verified?</label>
                        <select name="vehicles[0][is_police_verified]" class="form-select police-verified-select">
                            <option value="0" {{ old('vehicles.0.is_police_verified', '0') == '0' ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('vehicles.0.is_police_verified') == '1' ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 d-none police-doc-input">
                        <label class="form-label">PV Document *</label>
                        <input type="file" name="vehicles[0][police_verification_document]" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">DL (Upload)</label>
                        <input type="file" name="vehicles[0][driver_dl_document]" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="button" id="add-vehicle" class="btn btn-outline-primary px-4">
            <i class="bi bi-plus-circle me-2"></i> Add Another Vehicle & Driver
        </button>
    </div>

    <div class="d-flex justify-content-end mb-5">
        <button type="submit" class="btn btn-primary btn-lg px-5 shadow">
            <i class="bi bi-save me-2"></i> Save All Details
        </button>
    </div>
</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let blockIndex = 1;
        const container = document.getElementById('vehicle-driver-container');
        const addButton = document.getElementById('add-vehicle');

        // Function to handle conditional inputs
        function initBlockEvents(block) {
            const vehicleTypeSelect = block.querySelector('.vehicle-type-select');
            const customTypeInput = block.querySelector('.custom-type-input');
            const policeVerifiedSelect = block.querySelector('.police-verified-select');
            const policeDocInput = block.querySelector('.police-doc-input');

            vehicleTypeSelect.addEventListener('change', function() {
                if (this.value === 'Bus') {
                    customTypeInput.classList.remove('d-none');
                    customTypeInput.querySelector('input').setAttribute('required', 'required');
                } else {
                    customTypeInput.classList.add('d-none');
                    customTypeInput.querySelector('input').removeAttribute('required');
                }
            });

            policeVerifiedSelect.addEventListener('change', function() {
                if (this.value === '1') {
                    policeDocInput.classList.remove('d-none');
                    policeDocInput.querySelector('input').setAttribute('required', 'required');
                } else {
                    policeDocInput.classList.add('d-none');
                    policeDocInput.querySelector('input').removeAttribute('required');
                }
            });

            const removeBtn = block.querySelector('.remove-block');
            removeBtn.addEventListener('click', function() {
                block.remove();
                updateBlockCounters();
            });
        }

        function updateBlockCounters() {
            const blocks = container.querySelectorAll('.vehicle-driver-block');
            blocks.forEach((block, idx) => {
                block.querySelector('h5').innerText = `Vehicle & Driver #${idx + 1}`;
                const removeBtn = block.querySelector('.remove-block');
                if (blocks.length > 1) {
                    removeBtn.classList.remove('d-none');
                } else {
                    removeBtn.classList.add('d-none');
                }
            });
        }

        // Initialize first block
        initBlockEvents(container.querySelector('.vehicle-driver-block'));

        // Restore conditional field visibility from old() on validation failure
        const firstBlock = container.querySelector('.vehicle-driver-block');
        const firstVehicleType = firstBlock.querySelector('.vehicle-type-select');
        if (firstVehicleType && firstVehicleType.value === 'Bus') {
            firstBlock.querySelector('.custom-type-input').classList.remove('d-none');
            firstBlock.querySelector('.custom-type-input input').setAttribute('required', 'required');
        }
        const firstPoliceSelect = firstBlock.querySelector('.police-verified-select');
        if (firstPoliceSelect && firstPoliceSelect.value === '1') {
            firstBlock.querySelector('.police-doc-input').classList.remove('d-none');
            firstBlock.querySelector('.police-doc-input input').setAttribute('required', 'required');
        }

        addButton.addEventListener('click', function() {
            const firstBlock = container.querySelector('.vehicle-driver-block');
            const newBlock = firstBlock.cloneNode(true);
            
            // Update names to use new index
            const inputs = newBlock.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace(/\[\d+\]/, `[${blockIndex}]`));
                }
                if (input.type !== 'radio' && input.type !== 'checkbox') {
                    input.value = '';
                }
                if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                }
                // Remove required if it was added conditionally
                if (input.closest('.d-none')) {
                    input.removeAttribute('required');
                }
            });

            // Reset visibility for conditional fields
            newBlock.querySelectorAll('.custom-type-input, .police-doc-input').forEach(el => el.classList.add('d-none'));

            container.appendChild(newBlock);
            
            // Clean up cloned Select2 artifacts and re-initialize
            $(newBlock).find('.select2-container').remove();
            $(newBlock).find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').removeAttr('aria-hidden').show();
            initSelect2(newBlock);

            initBlockEvents(newBlock);
            blockIndex++;
            updateBlockCounters();
        });
    });
</script>
@endpush
@endsection
