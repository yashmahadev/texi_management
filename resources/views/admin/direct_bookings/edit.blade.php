@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Booking - {{ $booking->booking_number }}</h2>
    <a href="{{ route('admin.direct-bookings.show', $booking) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i> Back to Details
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.direct-bookings.update', $booking) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Customer</label>
                    <select name="customer_id" class="form-select" id="customer_id">
                        <option value="">Walk-in / New Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" 
                                    data-name="{{ $customer->name }}" 
                                    data-mobile="{{ $customer->mobile }}"
                                    {{ old('customer_id', $booking->customer_id) == $customer->id ? 'selected' : '' }}>
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
                           value="{{ old('customer_name', $booking->customer_name) }}" required id="customer_name">
                    @error('customer_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Customer Mobile <span class="text-danger">*</span></label>
                    <input type="text" name="customer_mobile" class="form-control @error('customer_mobile') is-invalid @enderror" 
                           value="{{ old('customer_mobile', $booking->customer_mobile) }}" required id="customer_mobile">
                    @error('customer_mobile')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Pickup Location <span class="text-danger">*</span></label>
                    <textarea name="pickup_location" class="form-control @error('pickup_location') is-invalid @enderror" 
                              rows="2" required>{{ old('pickup_location', $booking->pickup_location) }}</textarea>
                    @error('pickup_location')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Drop Location <span class="text-danger">*</span></label>
                    <textarea name="drop_location" class="form-control @error('drop_location') is-invalid @enderror" 
                              rows="2" required>{{ old('drop_location', $booking->drop_location) }}</textarea>
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
                           value="{{ old('booking_datetime', $booking->booking_datetime->format('Y-m-d\TH:i')) }}" required>
                    @error('booking_datetime')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Booking End Date & Time</label>
                    <input type="datetime-local" name="booking_end_datetime" 
                           class="form-control @error('booking_end_datetime') is-invalid @enderror" 
                           value="{{ old('booking_end_datetime', $booking->booking_end_datetime ? $booking->booking_end_datetime->format('Y-m-d\TH:i') : '') }}">
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
                           value="{{ old('estimated_km', $booking->estimated_km) }}" min="1">
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
                               value="{{ old('base_fare', $booking->base_fare) }}" step="0.01" min="0" required>
                    </div>
                    @error('base_fare')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Per KM Rate (₹/km) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="per_km_rate" class="form-control @error('per_km_rate') is-invalid @enderror" 
                               value="{{ old('per_km_rate', $booking->per_km_rate) }}" step="0.01" min="0" required>
                    </div>
                    @error('per_km_rate')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Customer Notes</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" 
                              rows="2">{{ old('notes', $booking->notes) }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Admin Notes (Internal)</label>
                    <textarea name="admin_notes" class="form-control @error('admin_notes') is-invalid @enderror" 
                              rows="2">{{ old('admin_notes', $booking->admin_notes) }}</textarea>
                    @error('admin_notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i> Update Booking
                </button>
                <a href="{{ route('admin.direct-bookings.show', $booking) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('customer_id').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (selected.value) {
            document.getElementById('customer_name').value = selected.dataset.name;
            document.getElementById('customer_mobile').value = selected.dataset.mobile;
        }
    });
</script>
@endpush
@endsection
