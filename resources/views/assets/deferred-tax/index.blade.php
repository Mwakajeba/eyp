@extends('layouts.main')

@section('title', 'Deferred Tax Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Deferred Tax Management', 'url' => '#', 'icon' => 'bx bx-calculator']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Deferred Tax Management</h4>
                    <div class="page-title-right">
                        <a href="{{ route('assets.deferred-tax.schedule') }}" class="btn btn-outline-info">
                            <i class="bx bx-file me-1"></i>View Schedule
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Process Deferred Tax Card -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-calculator me-2"></i>Calculate & Process Deferred Tax
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="process-deferred-tax-form">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Tax Year <span class="text-danger">*</span></label>
                                    <input type="number" name="tax_year" class="form-control" value="{{ now()->year }}" min="2000" max="2100" required>
                                    <small class="text-muted">Select the tax year for deferred tax calculation</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Post to GL</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="post_to_gl">
                                        <input type="hidden" name="post_to_gl" id="post_to_gl_hidden" value="0">
                                        <label class="form-check-label" for="post_to_gl">
                                            Automatically post deferred tax movements to General Ledger
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="bx bx-play me-1"></i>Process Deferred Tax
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Information Card -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-info-circle me-2"></i>Deferred Tax Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h6>Deferred Tax Calculation:</h6>
                                <ul>
                                    <li><strong>Temporary Difference:</strong> Book NBV - Tax WDV</li>
                                    <li><strong>Deferred Tax Liability (DTL):</strong> If Book NBV > Tax WDV, DTL = Temporary Difference × Tax Rate</li>
                                    <li><strong>Deferred Tax Asset (DTA):</strong> If Book NBV < Tax WDV, DTA = |Temporary Difference| × Tax Rate</li>
                                    <li><strong>Tax Rate:</strong> Corporate tax rate (default: 30%) - configurable in system settings</li>
                                </ul>
                                <hr>
                                <div class="alert alert-warning mb-0">
                                    <i class="bx bx-info-circle me-1"></i>
                                    <strong>Note:</strong> Deferred tax is calculated based on temporary differences between book depreciation (IFRS) and tax depreciation (TRA). The system automatically calculates and tracks deferred tax for all assets with assigned tax classes.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(function(){
    $('#post_to_gl').on('change', function(){
        $('#post_to_gl_hidden').val(this.checked ? '1' : '0');
    });

    $('#process-deferred-tax-form').on('submit', function(e){
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

        $.ajax({
            url: '{{ route('assets.deferred-tax.process') }}',
            type: 'POST',
            data: form.serialize(),
            success: function(response){
                if (response.success) {
                    let html = `<p>Successfully processed deferred tax for <strong>${response.data.total_processed}</strong> assets.</p>`;

                    if (response.data.total_errors > 0) {
                        html += `<p class="text-warning mt-2">${response.data.total_errors} errors occurred. Check logs for details.</p>`;
                    }

                    Swal.fire({
                        icon: response.data.total_errors > 0 ? 'warning' : 'success',
                        title: 'Deferred Tax Processed!',
                        html: html,
                        showConfirmButton: true,
                    });
                } else {
                    Swal.fire('Error!', response.message || 'Processing failed', 'error');
                }
            },
            error: function(xhr){
                let json = xhr.responseJSON;
                if (!json && xhr.responseText) {
                    try { json = JSON.parse(xhr.responseText); } catch(_) {}
                }
                Swal.fire('Error!', (json && json.message) ? json.message : 'An error occurred during processing.', 'error');
            },
            complete: function(){
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endpush

