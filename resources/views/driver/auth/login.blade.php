@extends('driver.layouts.app')

@section('content')
<div class="text-center mt-5">
    <div class="mb-4 text-center">
        @include('components.logo', ['iconClass' => 'fs-1 text-primary', 'textClass' => 'fs-3 fw-bold text-dark'])
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <p class="text-muted mb-4">Enter your registered mobile number to receive OTP via WhatsApp.</p>
            
            <form action="{{ route('driver.login.send-otp') }}" method="POST">
                @csrf
                <div class="mb-3 text-start">
                    <label class="form-label">Mobile Number</label>
                    <input type="text" name="mobile_number" class="form-control form-control-lg" placeholder="e.g. 9876543210" required>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Send OTP</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
