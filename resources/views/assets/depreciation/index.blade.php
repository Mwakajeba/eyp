@extends('layouts.main')

@section('title', 'Depreciation Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Depreciation Management', 'url' => '#', 'icon' => 'bx bx-calculator']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Depreciation Management</h4>
                    <div class="page-title-right">
                        <a href="{{ route('assets.depreciation.history') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-history me-1"></i>View History
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Process Depreciation Card -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-calculator me-2"></i>Process Depreciation
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="process-depreciation-form">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Period Date <span class="text-danger">*</span></label>
                                    <input type="date" name="period_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                                    <small class="text-muted">Select the month/year for depreciation processing</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Post to GL</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="post_to_gl" checked>
                                        <input type="hidden" name="post_to_gl" id="post_to_gl_hidden" value="1">
                                        <label class="form-check-label" for="post_to_gl">
                                            Automatically post depreciation entries to General Ledger
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bx bx-play me-1"></i>Process Depreciation
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
                            <i class="bx bx-info-circle me-2"></i>Depreciation Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Supported Depreciation Methods:</h6>
                                <ul>
                                    <li><strong>Straight Line:</strong> Equal depreciation over useful life</li>
                                    <li><strong>Declining Balance:</strong> Accelerated depreciation method</li>
                                    <li><strong>Sum of Years' Digits:</strong> Accelerated method based on remaining useful life</li>
                                    <li><strong>Units of Production:</strong> Based on usage (requires units tracking)</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Depreciation Conventions:</h6>
                                <ul>
                                    <li><strong>Monthly Prorata:</strong> Prorated based on actual days in month</li>
                                    <li><strong>Mid-Month:</strong> Full month if capitalized before 15th, half month otherwise</li>
                                    <li><strong>Full Month:</strong> Full month depreciation regardless of capitalization date</li>
                                </ul>
                            </div>
                        </div>
                        <hr>
                        <div class="alert alert-warning mb-0">
                            <i class="bx bx-info-circle me-1"></i>
                            <strong>Note:</strong> The system will automatically skip assets that:
                            <ul class="mb-0 mt-2">
                                <li>Are already depreciated for the selected period</li>
                                <li>Have status of "disposed" or "retired"</li>
                                <li>Have zero or negative purchase cost</li>
                            </ul>
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
    // Handle checkbox to hidden input
    $('#post_to_gl').on('change', function(){
        $('#post_to_gl_hidden').val(this.checked ? '1' : '0');
    });

    $('#process-depreciation-form').on('submit', function(e){
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
        
        $.ajax({
            url: '{{ route('assets.depreciation.process') }}',
            type: 'POST',
            data: form.serialize(),
            success: function(response){
                if (response.success) {
                    let html = `<p>Successfully processed <strong>${response.data.total_processed}</strong> assets.</p>`;
                    
                    if (response.data.total_errors > 0 && response.data.errors && response.data.errors.length > 0) {
                        html += `<div class="mt-3"><strong class="text-danger">Errors (${response.data.total_errors}):</strong><ul class="text-start mt-2" style="max-height: 300px; overflow-y: auto;">`;
                        response.data.errors.forEach(function(error) {
                            html += `<li><strong>${error.asset_name || 'Asset #' + error.asset_id}</strong>: ${error.error || 'Unknown error'}</li>`;
                        });
                        html += `</ul></div>`;
                    } else if (response.data.total_errors > 0) {
                        html += `<p class="text-warning mt-2">${response.data.total_errors} errors occurred. Check logs for details.</p>`;
                    }
                    
                    Swal.fire({
                        icon: response.data.total_errors > 0 ? 'warning' : 'success',
                        title: 'Depreciation Processed!',
                        html: html,
                        showConfirmButton: true,
                        width: '600px',
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

