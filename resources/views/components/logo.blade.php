<div class="d-flex align-items-center justify-content-center {{ $wrapperClass ?? '' }}">
    <img src="{{ asset('favicon.svg') }}" alt="Logo" class="{{ $iconClass ?? 'me-2' }}" style="height: {{ $imageHeight ?? '2rem' }}; width: auto; max-width: 100%; object-fit: contain;">
    <span class="{{ $textClass ?? 'fs-4 fw-bold' }}">{{ config('app.name', 'Taxi Management') }}</span>
</div>
