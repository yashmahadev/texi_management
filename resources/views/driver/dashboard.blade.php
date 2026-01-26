@extends('driver.layouts.app')

@section('content')
<div class="card border-0 shadow-sm mb-4 bg-primary text-white">
    <div class="card-body">
        <h5>Welcome, {{ Auth::guard('driver')->user()->name }}</h5>
        <p class="mb-0 small"><i class="bi bi-calendar-day"></i> {{ now()->format('l, d M Y') }}</p>
    </div>
</div>

@if($currentDuty)
    @php
        // Identify today's log
        $todayLog = $currentDuty->dailyLogs->first();
    @endphp

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Today's Duty</h6>
            <span class="badge bg-{{ $todayLog->status == 'completed' ? 'success' : ($todayLog->status == 'started' ? 'primary' : 'warning') }}">
                {{ ucfirst($todayLog->status) }}
            </span>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="text-muted small">Vehicle</label>
                <div class="fw-bold">{{ $currentDuty->vehicle->vehicle_number }}</div>
            </div>
            <div class="mb-3">
                <label class="text-muted small">Officer / Dept</label>
                <div class="fw-bold">{{ $currentDuty->officer_name }}</div>
                <div class="small">{{ $currentDuty->department_name }}</div>
            </div>
            <div class="mb-3">
                <label class="text-muted small">Expected Start</label>
                <div class="fw-bold">{{ \Carbon\Carbon::parse($currentDuty->expected_start_time)->format('h:i A') }}</div>
            </div>

            <hr>

            @if($todayLog->status == 'pending')
                <div class="d-grid">
                    <button class="btn btn-primary btn-lg" type="button" data-bs-toggle="collapse" data-bs-target="#startForm">
                        <i class="bi bi-play-circle me-2"></i> Start Duty
                    </button>
                    <div class="collapse mt-3" id="startForm">
                        <form action="{{ route('driver.duty.start', $todayLog->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Start KM *</label>
                                <input type="number" name="start_km" class="form-control form-control-lg" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label d-block">Odometer Photo</label>
                                <div class="photo-selection-container p-3 border rounded bg-light text-center">
                                    <div id="start-preview-container" class="mb-0 d-none text-center">
                                        <img id="start-photo-preview" src="#" alt="Preview" class="img-fluid rounded shadow-sm border mb-2" style="max-height: 200px;">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearPhoto('start')">
                                                <i class="bi bi-x-circle"></i> Change
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div id="start-actions">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <button type="button" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center justify-content-center" onclick="triggerPhoto('start', true)">
                                                    <i class="bi bi-camera fs-3 mb-1"></i>
                                                    <span class="small fw-bold">Take Photo</span>
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <button type="button" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center justify-content-center" onclick="triggerPhoto('start', false)">
                                                    <i class="bi bi-images fs-3 mb-1"></i>
                                                    <span class="small fw-bold">Gallery</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <input type="file" name="photo" id="start-photo-input" class="d-none" accept="image/*" onchange="previewPhoto(this, 'start')">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Confirm Start</button>
                        </form>
                    </div>
                </div>
            @elseif($todayLog->status == 'started')
                <div class="mb-3">
                    <label class="text-muted small">Start Time</label>
                    <div class="fw-bold">{{ \Carbon\Carbon::parse($todayLog->start_time)->format('h:i A') }}</div>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Start KM</label>
                    <div class="fw-bold">{{ $todayLog->start_km }}</div>
                </div>
                <div class="d-grid">
                    <button class="btn btn-danger btn-lg" type="button" data-bs-toggle="collapse" data-bs-target="#endForm">
                        <i class="bi bi-stop-circle me-2"></i> End Duty
                    </button>
                    <div class="collapse mt-3" id="endForm">
                        <form action="{{ route('driver.duty.end', $todayLog->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">End KM *</label>
                                <input type="number" name="end_km" class="form-control form-control-lg" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label d-block">Odometer Photo</label>
                                <div class="photo-selection-container p-3 border rounded bg-light text-center">
                                    <div id="end-preview-container" class="mb-0 d-none text-center">
                                        <img id="end-photo-preview" src="#" alt="Preview" class="img-fluid rounded shadow-sm border mb-2" style="max-height: 200px;">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearPhoto('end')">
                                                <i class="bi bi-x-circle"></i> Change
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div id="end-actions">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <button type="button" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center justify-content-center" onclick="triggerPhoto('end', true)">
                                                    <i class="bi bi-camera fs-3 mb-1"></i>
                                                    <span class="small fw-bold">Take Photo</span>
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <button type="button" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center justify-content-center" onclick="triggerPhoto('end', false)">
                                                    <i class="bi bi-images fs-3 mb-1"></i>
                                                    <span class="small fw-bold">Gallery</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <input type="file" name="photo" id="end-photo-input" class="d-none" accept="image/*" onchange="previewPhoto(this, 'end')">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger w-100">Confirm End</button>
                        </form>
                    </div>
                </div>
            @elseif($todayLog->status == 'completed')
                <div class="text-center text-success py-3">
                    <i class="bi bi-check-circle-fill display-4"></i>
                    <p class="mt-2 text-dark">Duty Completed</p>
                    <div class="row mt-3 text-start">
                        <div class="col-6">
                            <small class="text-muted">Start</small>
                            <div>{{ $todayLog->start_km }} km</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">End</small>
                            <div>{{ $todayLog->end_km }} km</div>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="alert alert-light border">
                                <strong>Total: {{ $todayLog->total_km }} km</strong>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@else
    <div class="alert alert-info border-0 shadow-sm">
        <i class="bi bi-info-circle me-2"></i> No active duty assigned for today.
    </div>
@endif

<!-- History Link (quick access) -->
<div class="d-grid">
    <a href="{{ route('driver.history') }}" class="btn btn-light btn-lg border shadow-sm text-start">
        <i class="bi bi-clock-history me-2 text-primary"></i> View Past Duties
    </a>
</div>

@push('scripts')
<script>
    function triggerPhoto(prefix, useCamera) {
        const input = document.getElementById(prefix + '-photo-input');
        if (useCamera) {
            input.setAttribute('capture', 'environment');
        } else {
            input.removeAttribute('capture');
        }
        input.click();
    }

    function previewPhoto(input, prefix) {
        const preview = document.getElementById(prefix + '-photo-preview');
        const container = document.getElementById(prefix + '-preview-container');
        const actions = document.getElementById(prefix + '-actions');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                container.classList.remove('d-none');
                actions.classList.add('d-none');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function clearPhoto(prefix) {
        const input = document.getElementById(prefix + '-photo-input');
        const preview = document.getElementById(prefix + '-photo-preview');
        const container = document.getElementById(prefix + '-preview-container');
        const actions = document.getElementById(prefix + '-actions');

        input.value = "";
        preview.src = "#";
        container.classList.add('d-none');
        actions.classList.remove('d-none');
    }
</script>
@endpush
@endsection
