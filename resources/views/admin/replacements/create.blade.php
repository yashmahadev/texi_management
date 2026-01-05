@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Assign Replacement</h2>
    <a href="{{ route('admin.daily-logs.show', $log->id) }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="alert alert-info">
            Replacing driver for date: <strong>{{ $log->duty_date->format('l, d F Y') }}</strong><br>
            Current Driver: <strong>{{ $log->monthlyDuty->primaryDriver->name }}</strong>
        </div>

        <form action="{{ route('admin.replacements.store', $log->id) }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label class="form-label">Replacement Driver *</label>
                <select name="replacement_driver_id" class="form-select" required>
                    <option value="">Select Driver</option>
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->name }} ({{ $driver->mobile_number }})</option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Reason *</label>
                <textarea name="reason" class="form-control" rows="3" required></textarea>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-dark">Assign Replacement</button>
            </div>
        </form>
    </div>
</div>
@endsection
