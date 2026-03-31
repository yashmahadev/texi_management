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
// State → City dependent dropdown logic
// Driven entirely from the PHP config passed as inline JSON — no extra HTTP request needed.
(function () {
    const LOCATIONS = @json($locations);

    function initStateCityPair(stateEl, cityEl) {
        function populateCities(selectedCity) {
            const state = stateEl.value;
            cityEl.innerHTML = '';

            if (!state || !LOCATIONS[state]) {
                cityEl.innerHTML = '<option value="">Select State First</option>';
                return;
            }

            cityEl.innerHTML = '<option value="">Select City</option>';
            LOCATIONS[state].forEach(function (city) {
                const opt = document.createElement('option');
                opt.value = city;
                opt.textContent = city;
                if (city === selectedCity) opt.selected = true;
                cityEl.appendChild(opt);
            });
        }

        // On state change — reset city
        stateEl.addEventListener('change', function () {
            populateCities('');
        });

        // On first load — if state already selected, populate cities (keeping saved city)
        if (stateEl.value) {
            // Cities are already server-rendered for the saved state,
            // but re-run to ensure JS state is in sync if user navigates back.
            const currentCity = cityEl.value;
            populateCities(currentCity);
        }
    }

    // Wire up all state/city pairs on the page
    document.querySelectorAll('.state-select').forEach(function (stateEl) {
        const uid = stateEl.dataset.uid;
        const cityEl = document.getElementById('city_' + uid);
        if (cityEl) {
            initStateCityPair(stateEl, cityEl);
        }
    });
})();
</script>
@endpush
@endonce
