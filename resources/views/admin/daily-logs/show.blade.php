@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Log Details #{{ $log->id }}</h2>
    <a href="{{ route('admin.daily-logs.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Duty Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6 border-end">
                        <h6>Vehicle Information</h6>
                        @php
                            $vehicle = $log->monthlyDuty->vehicle ?? $log->directBooking->vehicle ?? null;
                        @endphp
                        @if($vehicle)
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-2 rounded me-3">
                                    <i class="bi bi-truck fs-3"></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-5">{{ $vehicle->vehicle_number }}</div>
                                    <div class="text-muted small">{{ $vehicle->vehicle_type }} | {{ $vehicle->make_model }}</div>
                                </div>
                            </div>
                        @else
                            <div class="text-danger">Vehicle Info Not Found</div>
                        @endif
                    </div>
                    <div class="col-md-6 ps-md-4">
                        <h6>Driver Information</h6>
                        @php
                            $driver = $log->monthlyDuty->primaryDriver ?? $log->directBooking->driver ?? null;
                        @endphp
                        @if($driver)
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-2 rounded me-3">
                                    <i class="bi bi-person fs-3"></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-5">{{ $driver->name }}</div>
                                    <div class="text-muted small">{{ $driver->mobile_number }}</div>
                                </div>
                            </div>
                        @else
                            <div class="text-danger">Driver Not Assigned</div>
                        @endif
                    </div>
                </div>
                
                <hr class="mb-4">
                
                <h6 class="mb-3">Log Details</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Date</label>
                        <div class="fw-bold">{{ $log->duty_date->format('l, d F Y') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Status</label>
                        <div>
                            <span class="badge bg-{{ $log->status == 'completed' ? 'success' : ($log->status == 'started' ? 'primary' : 'warning') }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </div>
                    </div>
                </div>
                <!-- More details -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Start Time</label>
                        <div>{{ $log->start_time ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">End Time</label>
                        <div>{{ $log->end_time ?? '-' }}</div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="text-muted small">Start KM</label>
                        <div>{{ $log->start_km ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">End KM</label>
                        <div>{{ $log->end_km ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">Total KM</label>
                        <div class="fw-bold">{{ $log->total_km }}</div>
                    </div>
                </div>

                @if($log->start_photo_path)
                <div class="mb-3">
                    <label class="text-muted small">Start Photo</label>
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $log->start_photo_path) }}" 
                             alt="Start" 
                             class="img-fluid rounded cursor-pointer" 
                             style="max-height: 200px; cursor: zoom-in;"
                             data-bs-toggle="modal" 
                             data-bs-target="#photoModal" 
                             data-photo-url="{{ asset('storage/' . $log->start_photo_path) }}"
                             data-photo-title="Start KM Photo">
                    </div>
                </div>
                @endif
                
                @if($log->end_photo_path)
                <div class="mb-3">
                    <label class="text-muted small">End Photo</label>
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $log->end_photo_path) }}" 
                             alt="End" 
                             class="img-fluid rounded cursor-pointer" 
                             style="max-height: 200px; cursor: zoom-in;"
                             data-bs-toggle="modal" 
                             data-bs-target="#photoModal" 
                             data-photo-url="{{ asset('storage/' . $log->end_photo_path) }}"
                             data-photo-title="End KM Photo">
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if($log->replacements->count() > 0)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Replacements</h5>
            </div>
            <div class="card-body">
                @foreach($log->replacements as $replacement)
                    <div class="alert alert-warning">
                        <strong>Replaced:</strong> {{ $replacement->originalDriver->name }} 
                        <i class="bi bi-arrow-right mx-2"></i> 
                        {{ $replacement->replacementDriver->name }}<br>
                        <small>Reason: {{ $replacement->reason }} (by {{ $replacement->assigner->name }})</small>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Actions</h5>
            </div>
            <div class="card-body d-grid gap-2">
                @if($log->status == 'completed' || $log->status == 'disputed')
                    <form action="{{ route('admin.daily-logs.update-status', $log->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <button class="btn btn-success w-100 mb-2">Approve Duty</button>
                    </form>
                    
                    <button class="btn btn-warning w-100" type="button" data-bs-toggle="modal" data-bs-target="#disputeModal">
                        Mark Disputed
                    </button>
                @endif

                <a href="{{ route('admin.daily-logs.edit', $log->id) }}" class="btn btn-primary w-100 mb-2">
                    Edit Log Entry
                </a>

                <a href="{{ route('admin.replacements.create', $log->id) }}" class="btn btn-outline-dark">
                    Assign Replacement
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Dispute Modal -->
<div class="modal fade" id="disputeModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.daily-logs.update-status', $log->id) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dispute Duty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="status" value="disputed">
                    <div class="mb-3">
                        <label class="form-label">Remarks *</label>
                        <textarea name="remarks" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Confirm Dispute</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalTitle">Odometer Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="photoModalImage" class="img-fluid rounded" alt="Full size">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const photoModal = document.getElementById('photoModal');
    if (photoModal) {
        photoModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const url = button.getAttribute('data-photo-url');
            const title = button.getAttribute('data-photo-title');
            
            document.getElementById('photoModalImage').src = url;
            document.getElementById('photoModalTitle').innerText = title;
        });
    }
});
</script>
@endpush
