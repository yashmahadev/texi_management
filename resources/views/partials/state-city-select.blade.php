{{--
    Reusable State → City dependent dropdown partial.
    Data source: config/india_locations.php (single source of truth).

    Usage:
        @include('partials.state-city-select', [
            'selectedState' => old('state', $model->state ?? ''),
            'selectedCity'  => old('city',  $model->city  ?? ''),
            'stateCol'      => 'col-sm-4 col-6',   // optional, default col-sm-4 col-6
            'cityCol'       => 'col-sm-4 col-6',   // optional
            'pincodeCol'    => 'col-sm-4 col-12',  // optional
            'selectedPincode' => old('pincode', $model->pincode ?? ''),
        ])
--}}

@php
    $locations     = config('india_locations');          // ['State' => ['City1', ...]]
    $selectedState = $selectedState ?? '';
    $selectedCity  = $selectedCity  ?? '';
    $selectedPincode = $selectedPincode ?? '';
    $stateCol      = $stateCol   ?? 'col-sm-4 col-6';
    $cityCol       = $cityCol    ?? 'col-sm-4 col-6';
    $pincodeCol    = $pincodeCol ?? 'col-sm-4 col-12';
    // Unique suffix so multiple instances on the same page don't clash
    $uid = 'sc_' . uniqid();
@endphp

<div class="{{ $stateCol }} mb-3">
    <label class="form-label">State</label>
    <select name="state" id="state_{{ $uid }}" class="form-select state-select" data-uid="{{ $uid }}">
        <option value="">Select State</option>
        @foreach(array_keys($locations) as $state)
            <option value="{{ $state }}" {{ $selectedState === $state ? 'selected' : '' }}>
                {{ $state }}
            </option>
        @endforeach
    </select>
</div>

<div class="{{ $cityCol }} mb-3">
    <label class="form-label">City</label>
    <select name="city" id="city_{{ $uid }}" class="form-select city-select" data-uid="{{ $uid }}">
        <option value="">Select State First</option>
        {{-- Pre-populate cities for the saved state so the page loads correctly --}}
        @if($selectedState && isset($locations[$selectedState]))
            @foreach($locations[$selectedState] as $city)
                <option value="{{ $city }}" {{ $selectedCity === $city ? 'selected' : '' }}>
                    {{ $city }}
                </option>
            @endforeach
        @endif
    </select>
</div>

<div class="{{ $pincodeCol }} mb-3">
    <label class="form-label">Pincode</label>
    <input type="text" name="pincode" class="form-control"
        value="{{ $selectedPincode }}" maxlength="10" placeholder="Enter pincode">
</div>

@once
@push('scripts')
<script>
$(document).ready(function() {
    const LOCATIONS = @json($locations);

    function populateCities(stateEl, cityEl, selectedCity) {
        const state = stateEl.value;
        const $citySelect = $(cityEl);
        
        $citySelect.empty();

        if (!state || !LOCATIONS[state]) {
            $citySelect.append('<option value="">Select State First</option>');
            $citySelect.trigger('change');
            return;
        }

        $citySelect.append('<option value="">Select City</option>');
        LOCATIONS[state].forEach(function (city) {
            const isSelected = (city === selectedCity) ? 'selected' : '';
            $citySelect.append(`<option value="${city}" ${isSelected}>${city}</option>`);
        });
        
        $citySelect.trigger('change');
    }

    // Delegate change event for all state-selects
    $(document).on('change', '.state-select', function() {
        const uid = $(this).data('uid');
        const cityEl = document.getElementById('city_' + uid);
        if (cityEl) {
            populateCities(this, cityEl, '');
        }
    });

    // Handle initial state for all existing pairs on load
    $('.state-select').each(function() {
        const uid = $(this).data('uid');
        const cityEl = document.getElementById('city_' + uid);
        if (cityEl && this.value) {
            const currentCity = $(cityEl).val();
            populateCities(this, cityEl, currentCity);
        }
    });
});
</script>
@endpush
@endonce
