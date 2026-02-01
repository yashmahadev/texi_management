@extends('driver.layouts.app')

@section('content')
<div class="text-center mt-5">
    <h3 class="mb-4">Verify OTP</h3>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <p class="text-muted mb-4">Enter the OTP sent to <strong>{{ $mobile_number }}</strong></p>
            
            <form action="{{ route('driver.login.verify.post') }}" method="POST">
                @csrf
                <div class="mb-3 text-start">
                    <label class="form-label">OTP (123456)</label>
                    <input type="text" name="otp" class="form-control form-control-lg text-center letter-spacing-2" placeholder="XXXXXX" maxlength="6" required style="letter-spacing: 5px;">
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Verify & Login</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
