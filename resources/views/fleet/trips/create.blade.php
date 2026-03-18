@extends('layouts.main')

@section('title', 'Create New Trip - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Trip Planning', 'url' => route('fleet.trips.index'), 'icon' => 'bx bx-trip'],
            ['label' => 'Create New Trip', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Create Trip</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.trips.store') }}" id="trip-form">
                    @csrf

                    @include('fleet.trips.form', ['trip' => null, 'vehicles' => $vehicles, 'drivers' => $drivers, 'customers' => $customers])

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.trips.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bx bx-save me-1"></i>Create Trip
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const form = $('#trip-form');
    const submitBtn = form.find('button[type="submit"]');
    
    // Reset form submitted state on page load (in case of validation errors)
    form.removeAttr('data-submitted');
    
    // Ensure button is clickable - remove any disabled state on load
    submitBtn.prop('disabled', false);
    
    // Auto-select assigned driver when vehicle is selected
    $('#vehicle_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const driverId = selectedOption.data('driver-id');
        const driverName = selectedOption.data('driver-name');
        
        if (driverId) {
            // Set the driver dropdown to the assigned driver
            $('#driver_id').val(driverId).trigger('change');
            
            // Show notification
            if (typeof toastr !== 'undefined') {
                toastr.info('Assigned driver "' + driverName + '" selected automatically');
            }
        }
    });

    // Form submission handler
    form.on('submit', function(e) {
        // Only handle invalid forms - let valid forms submit normally
        if (!this.checkValidity()) {
            // Form is invalid - reset state so user can try again
            form.removeAttr('data-submitted');
            submitBtn.prop('disabled', false);
            // Let browser show validation errors
            return true;
        }
        // Form is valid - let it submit normally
        // The global handler will set data-submitted, which is fine
    });
    
    // Direct button click handler for debugging
    submitBtn.on('click', function(e) {
        console.log('Create Trip button clicked');
        // Ensure form is not in submitted state
        form.removeAttr('data-submitted');
        // Ensure button is enabled
        $(this).prop('disabled', false);
        // Don't prevent default - let the form submit normally
        // e.preventDefault() is NOT called here intentionally
    });
    
    // Handle validation errors - reset form state when fields change
    form.find('input, select, textarea').on('input change', function() {
        // Remove submitted state when user makes changes
        if (form.attr('data-submitted') === 'true') {
            form.removeAttr('data-submitted');
            submitBtn.prop('disabled', false);
        }
    });
});
</script>
@endpush
