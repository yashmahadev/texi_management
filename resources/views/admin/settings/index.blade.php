@extends('admin.layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary">
                    <i class="bi bi-building me-2"></i> Company Profile Settings
                </h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <!-- Logo Section -->
                        <div class="col-md-4 mb-4 text-center">
                            <label class="form-label d-block text-start">Company Logo</label>
                            <div class="mb-3">
                                @if($settings['company_logo'])
                                    <img src="{{ asset('storage/' . $settings['company_logo']) }}" 
                                         alt="Company Logo" 
                                         class="img-thumbnail shadow-sm mb-2" 
                                         style="max-height: 150px;">
                                @else
                                    <div class="bg-light border rounded d-flex align-items-center justify-content-center mx-auto mb-2" 
                                         style="height: 150px; width: 150px;">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                @endif
                            </div>
                            <input type="file" name="company_logo" class="form-control form-control-sm @error('company_logo') is-invalid @enderror">
                            <div class="form-text mt-1 small">SVG, PNG, JPG (Max 2MB)</div>
                            @error('company_logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Basic Info Section -->
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" 
                                       class="form-control @error('company_name') is-invalid @enderror" 
                                       value="{{ old('company_name', $settings['company_name']) }}" 
                                       placeholder="e.g. Acme Taxi Services">
                                @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Mobile</label>
                                    <input type="text" name="company_mobile" 
                                           class="form-control @error('company_mobile') is-invalid @enderror" 
                                           value="{{ old('company_mobile', $settings['company_mobile']) }}" 
                                           placeholder="+91 9876543210">
                                    @error('company_mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Email</label>
                                    <input type="email" name="company_email" 
                                           class="form-control @error('company_email') is-invalid @enderror" 
                                           value="{{ old('company_email', $settings['company_email']) }}" 
                                           placeholder="contact@acme.com">
                                    @error('company_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Company Address</label>
                            <textarea name="company_address" 
                                      class="form-control @error('company_address') is-invalid @enderror" 
                                      rows="3" 
                                      placeholder="Full business address...">{{ old('company_address', $settings['company_address']) }}</textarea>
                            @error('company_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">GST Number</label>
                            <input type="text" name="company_gst"
                                   class="form-control @error('company_gst') is-invalid @enderror"
                                   value="{{ old('company_gst', $settings['company_gst']) }}"
                                   placeholder="e.g. 22AAAAA0000A1Z5" maxlength="20">
                            <div class="form-text small">15-digit GST Identification Number (GSTIN). Printed on invoices and PDFs.</div>
                            @error('company_gst') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label d-block">Notification Sound</label>
                            <div class="d-flex align-items-center gap-3">
                                <input type="file" name="notification_sound" class="form-control @error('notification_sound') is-invalid @enderror">
                                @if($settings['notification_sound'] && $settings['notification_sound'] !== 'default')
                                    <div class="d-flex align-items-center">
                                        <audio controls class="me-2" style="height: 30px;">
                                            <source src="{{ asset('storage/' . $settings['notification_sound']) }}" type="audio/mpeg">
                                            Your browser does not support the audio element.
                                        </audio>
                                    </div>
                                @else
                                    <span class="badge bg-secondary">Using Default System Sound</span>
                                @endif
                            </div>
                            <div class="form-text mt-1 small">MP3, WAV, OGG (Max 2MB). Used for push notifications.</div>
                            @error('notification_sound') <div class="invalid-feedback text-danger d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <hr class="my-4 opacity-50">

                    <h6 class="mb-3 text-primary">
                        <i class="bi bi-cash-coin me-2"></i> Direct Booking Fare Settings
                    </h6>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Base Fare (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="booking_base_fare" 
                                       class="form-control @error('booking_base_fare') is-invalid @enderror" 
                                       value="{{ old('booking_base_fare', $settings['booking_base_fare']) }}" 
                                       step="0.01" min="0">
                            </div>
                            @error('booking_base_fare') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <div class="form-text small">Initial charge applied to every booking.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Per KM Rate (₹/km)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="booking_per_km_rate" 
                                       class="form-control @error('booking_per_km_rate') is-invalid @enderror" 
                                       value="{{ old('booking_per_km_rate', $settings['booking_per_km_rate']) }}" 
                                       step="0.01" min="0">
                            </div>
                            @error('booking_per_km_rate') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <div class="form-text small">Rate charged per kilometer traveled.</div>
                        </div>
                    </div>

                    <hr class="my-4 opacity-50">

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
