@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Direct Booking Reports</h2>
    <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i> Back to Main Reports
    </a>
</div>

<div class="row">
    <!-- Daily Trips Report -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary text-white rounded p-3 me-3">
                        <i class="bi bi-calendar-range fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Daily Direct Trips</h5>
                        <p class="text-muted small mb-0">View all direct customer bookings for a specific period.</p>
                    </div>
                </div>
                <form action="{{ route('admin.reports.direct-bookings.daily-trips') }}" method="GET">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small">From Date</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">To Date</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Generate Report</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Revenue Summary Report -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-success text-white rounded p-3 me-3">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Revenue Summary</h5>
                        <p class="text-muted small mb-0">Summary of revenue generated from completed direct bookings.</p>
                    </div>
                </div>
                <form action="{{ route('admin.reports.direct-bookings.revenue') }}" method="GET">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small">From Date</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">To Date</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ date('Y-m-t') }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">Generate Report</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
