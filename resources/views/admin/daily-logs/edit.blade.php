@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Log #{{ $log->id }}</h2>
    <a href="{{ route('admin.daily-logs.show', $log->id) }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.daily-logs.update', $log->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Duty Date</label>
                    <input type="text" class="form-control" value="{{ $log->duty_date->format('d M Y') }}" disabled>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select" required>
                        @foreach(['pending', 'started', 'completed', 'missing', 'approved', 'disputed', 'replaced'] as $status)
                            <option value="{{ $status }}" {{ old('status', $log->status) == $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $log->start_time ? \Carbon\Carbon::parse($log->start_time)->format('H:i') : '') }}">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">End Time</label>
                    <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $log->end_time ? \Carbon\Carbon::parse($log->end_time)->format('H:i') : '') }}">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Start KM</label>
                    <input type="number" name="start_km" class="form-control" value="{{ old('start_km', $log->start_km) }}">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">End KM</label>
                    <input type="number" name="end_km" class="form-control" value="{{ old('end_km', $log->end_km) }}">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Total KM</label>
                    <input type="number" name="total_km" class="form-control" value="{{ old('total_km', $log->total_km) }}">
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary px-5">Update Log Record</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startKm = document.querySelector('input[name="start_km"]');
    const endKm = document.querySelector('input[name="end_km"]');
    const totalKm = document.querySelector('input[name="total_km"]');

    function calculateTotal() {
        const start = parseInt(startKm.value) || 0;
        const end = parseInt(endKm.value) || 0;
        if (end > 0) {
            totalKm.value = Math.max(0, end - start);
        }
    }

    startKm.addEventListener('input', calculateTotal);
    endKm.addEventListener('input', calculateTotal);
    
    // Set total_km to readonly to show it's auto-calculated
    totalKm.readOnly = true;
    totalKm.classList.add('bg-light');
});
</script>
@endpush
@endsection
