@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Monthly Duty #{{ $monthlyDuty->id }}</h2>
    <a href="{{ route('admin.monthly-duties.show', $monthlyDuty) }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">

        {{-- Read-only summary --}}
        <div class="alert alert-info mb-4">
            <div class="row g-2">
                <div class="col-md-3"><strong>Group:</strong> {{ $monthlyDuty->group }}</div>
                <div class="col-md-3"><strong>Department:</strong> {{ $monthlyDuty->department_name }}</div>
                <div class="col-md-3"><strong>Vehicle:</strong> {{ $monthlyDuty->vehicle->vehicle_number }}</div>
                <div class="col-md-3"><strong>Period:</strong> {{ $monthlyDuty->start_date->format('d M Y') }} – {{ $monthlyDuty->end_date->format('d M Y') }}</div>
            </div>
        </div>

        <form action="{{ route('admin.monthly-duties.update', $monthlyDuty) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Officer Name *</label>
                    <input type="text" name="officer_name"
                        class="form-control @error('officer_name') is-invalid @enderror"
                        value="{{ old('officer_name', $monthlyDuty->officer_name) }}" required>
                    @error('officer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Expected Start Time *</label>
                    <input type="time" name="expected_start_time"
                        class="form-control @error('expected_start_time') is-invalid @enderror"
                        value="{{ old('expected_start_time', $monthlyDuty->expected_start_time) }}" required>
                    @error('expected_start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Expected End Time</label>
                    <input type="time" name="expected_end_time" class="form-control"
                        value="{{ old('expected_end_time', $monthlyDuty->expected_end_time) }}">
                </div>

                @include('partials.state-city-select', [
                    'selectedState'   => old('state',   $monthlyDuty->state),
                    'selectedCity'    => old('city',    $monthlyDuty->city),
                    'selectedPincode' => old('pincode', $monthlyDuty->pincode),
                ])

                <div class="col-12 mb-3">
                    <label class="form-label">Route / Remarks</label>
                    <textarea name="route_remarks" class="form-control" rows="2"
                        placeholder="Enter route details or any remarks...">{{ old('route_remarks', $monthlyDuty->route_remarks) }}</textarea>
                </div>

                <div class="col-12 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_recurring" id="is_recurring" value="1"
                            {{ old('is_recurring', $monthlyDuty->is_recurring) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_recurring">
                            <strong>Recurring Duty</strong>
                            <small class="text-muted d-block">When enabled, this duty will automatically renew every month on the last day of the month.</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                <a href="{{ route('admin.monthly-duties.show', $monthlyDuty) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
