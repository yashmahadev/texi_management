@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Create New Booking</h2>
    <a href="{{ route('admin.direct-bookings.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i> Back to List
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.direct-bookings.store') }}" method="POST" id="bookingForm">
            @csrf

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Customer (Optional)</label>
                    <select name="customer_id" class="form-select" id="customer_id">
                        <option value="">Walk-in / New Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" 
                                    data-name="{{ $customer->name }}" 
                                    data-mobile="{{ $customer->mobile }}"
                                    {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }} - {{ $customer->mobile }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Customer Name <span class="text-danger">*</span></label>
                    <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror" 
                           value="{{ old('customer_name') }}" required id="customer_name">
                    @error('customer_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Customer Mobile <span class="text-danger">*</span></label>
                    <input type="text" name="customer_mobile" class="form-control @error('customer_mobile') is-invalid @enderror" 
                           value="{{ old('customer_mobile') }}" required id="customer_mobile">
                    @error('customer_mobile')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Pickup Location <span class="text-danger">*</span></label>
                    <textarea name="pickup_location" class="form-control @error('pickup_location') is-invalid @enderror" 
                              rows="2" required>{{ old('pickup_location') }}</textarea>
                    @error('pickup_location')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Drop Location <span class="text-danger">*</span></label>
                    <textarea name="drop_location" class="form-control @error('drop_location') is-invalid @enderror" 
                              rows="2" required>{{ old('drop_location') }}</textarea>
                    @error('drop_location')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Booking Start Date & Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="booking_datetime" 
                           class="form-control @error('booking_datetime') is-invalid @enderror" 
                           value="{{ old('booking_datetime') }}" required id="booking_datetime">
                    @error('booking_datetime')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Booking End Date & Time</label>
                    <input type="datetime-local" name="booking_end_datetime" 
                           class="form-control @error('booking_end_datetime') is-invalid @enderror" 
                           value="{{ old('booking_end_datetime') }}" id="booking_end_datetime">
                    @error('booking_end_datetime')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Optional: For multi-day bookings</small>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Estimated KM</label>
                    <input type="number" name="estimated_km" class="form-control @error('estimated_km') is-invalid @enderror" 
                           value="{{ old('estimated_km') }}" min="1">
                    @error('estimated_km')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Base Fare (₹) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="base_fare" class="form-control @error('base_fare') is-invalid @enderror" 
                               value="{{ old('base_fare', $defaults['base_fare']) }}" step="0.01" min="0" required>
                    </div>
                    @error('base_fare')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Default: ₹{{ $defaults['base_fare'] }}</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Per KM Rate (₹/km) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="per_km_rate" class="form-control @error('per_km_rate') is-invalid @enderror" 
                               value="{{ old('per_km_rate', $defaults['per_km_rate']) }}" step="0.01" min="0" required>
                    </div>
                    @error('per_km_rate')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Default: ₹{{ $defaults['per_km_rate'] }}</small>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Optional:</strong> You can assign a driver and vehicle now, or do it later from the booking details page.
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        Assign Driver (Optional)
                        <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="checkAvailability" disabled>
                            <i class="bi bi-search me-1"></i> Check Availability
                        </button>
                    </label>
                    <select name="driver_id" class="form-select" id="driver_id" disabled>
                        <option value="">Select booking date/time first</option>
                    </select>
                    <small class="text-muted" id="driver_status"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Assign Vehicle (Optional)</label>
                    <select name="vehicle_id" class="form-select" id="vehicle_id" disabled>
                        <option value="">Select booking date/time first</option>
                    </select>
                    <small class="text-muted" id="vehicle_status"></small>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Customer Notes</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" 
                              rows="2">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Admin Notes (Internal)</label>
                    <textarea name="admin_notes" class="form-control @error('admin_notes') is-invalid @enderror" 
                              rows="2">{{ old('admin_notes') }}</textarea>
                    @error('admin_notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-2"></i> Create Booking
                </button>
                <a href="{{ route('admin.direct-bookings.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customer_id');
    const customerNameInput = document.getElementById('customer_name');
    const customerMobileInput = document.getElementById('customer_mobile');
    const bookingDateTimeInput = document.getElementById('booking_datetime');
    const checkAvailabilityBtn = document.getElementById('checkAvailability');
    const driverSelect = document.getElementById('driver_id');
    const vehicleSelect = document.getElementById('vehicle_id');
    const driverStatus = document.getElementById('driver_status');
    const vehicleStatus = document.getElementById('vehicle_status');

    // Auto-fill customer details when selected
    customerSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (selected.value) {
            customerNameInput.value = selected.dataset.name;
            customerMobileInput.value = selected.dataset.mobile;
        } else {
            customerNameInput.value = '';
            customerMobileInput.value = '';
        }
    });

    // Enable check availability button when datetime is selected
    bookingDateTimeInput.addEventListener('change', function() {
        if (this.value) {
            checkAvailabilityBtn.disabled = false;
            driverSelect.innerHTML = '<option value="">Click "Check Availability" to load drivers</option>';
            vehicleSelect.innerHTML = '<option value="">Click "Check Availability" to load vehicles</option>';
            driverSelect.disabled = true;
            vehicleSelect.disabled = true;
        } else {
            checkAvailabilityBtn.disabled = true;
            driverSelect.disabled = true;
            vehicleSelect.disabled = true;
        }
    });

    // Fetch available drivers and vehicles
    checkAvailabilityBtn.addEventListener('click', function() {
        const bookingDateTime = bookingDateTimeInput.value;
        const bookingEndDateTime = document.getElementById('booking_end_datetime').value;
        
        if (!bookingDateTime) {
            alert('Please select booking start date and time first');
            return;
        }

        // Disable button and show loading
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Checking...';
        driverSelect.innerHTML = '<option value="">Loading available drivers...</option>';
        vehicleSelect.innerHTML = '<option value="">Loading available vehicles...</option>';
        driverStatus.textContent = '';
        vehicleStatus.textContent = '';

        let url = `{{ route('admin.direct-bookings.available-resources') }}?booking_datetime=${bookingDateTime}`;
        if (bookingEndDateTime) {
            url += `&booking_end_datetime=${bookingEndDateTime}`;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                // Populate drivers
                driverSelect.innerHTML = '<option value="">No driver (assign later)</option>';
                if (data.drivers && data.drivers.length > 0) {
                    data.drivers.forEach(driver => {
                        const option = document.createElement('option');
                        option.value = driver.id;
                        option.textContent = `${driver.name} - ${driver.mobile}${driver.vehicle_number ? ' (' + driver.vehicle_number + ')' : ''}`;
                        driverSelect.appendChild(option);
                    });
                    driverSelect.disabled = false;
                    driverStatus.textContent = `${data.drivers.length} driver(s) available`;
                    driverStatus.className = 'text-success';
                } else {
                    driverSelect.innerHTML = '<option value="">No drivers available at this time</option>';
                    driverStatus.textContent = 'All drivers busy or on monthly duty';
                    driverStatus.className = 'text-warning';
                }

                // Populate vehicles
                vehicleSelect.innerHTML = '<option value="">No vehicle (assign later)</option>';
                if (data.vehicles && data.vehicles.length > 0) {
                    data.vehicles.forEach(vehicle => {
                        const option = document.createElement('option');
                        option.value = vehicle.id;
                        option.textContent = `${vehicle.vehicle_number} (${vehicle.vehicle_type})${vehicle.driver_name ? ' - ' + vehicle.driver_name : ''}`;
                        vehicleSelect.appendChild(option);
                    });
                    vehicleSelect.disabled = false;
                    vehicleStatus.textContent = `${data.vehicles.length} vehicle(s) available`;
                    vehicleStatus.className = 'text-success';
                } else {
                    vehicleSelect.innerHTML = '<option value="">No vehicles available at this time</option>';
                    vehicleStatus.textContent = 'All vehicles busy';
                    vehicleStatus.className = 'text-warning';
                }

                // Re-enable button
                checkAvailabilityBtn.disabled = false;
                checkAvailabilityBtn.innerHTML = '<i class="bi bi-search me-1"></i> Check Availability';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error checking availability. Please try again.');
                checkAvailabilityBtn.disabled = false;
                checkAvailabilityBtn.innerHTML = '<i class="bi bi-search me-1"></i> Check Availability';
            });
    });

    // Auto-select vehicle when driver is selected (if driver has a vehicle)
    driverSelect.addEventListener('change', function() {
        const selectedDriver = this.options[this.selectedIndex];
        // Note: Additional logic can be added here if needed
    });
});
</script>
@endpush
@endsection
