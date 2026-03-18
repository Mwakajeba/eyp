@extends('layouts.main')

@section('title', 'Tax Depreciation Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Tax Depreciation Management', 'url' => '#', 'icon' => 'bx bx-calculator']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Tax Depreciation Management (TRA Compliant)</h4>
                    <div class="page-title-right">
                        <a href="{{ route('assets.tax-depreciation.history') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-history me-1"></i>View History
                        </a>
                        <a href="{{ route('assets.tax-depreciation.reports.tra-schedule') }}" class="btn btn-outline-info ms-2">
                            <i class="bx bx-file me-1"></i>TRA Schedule Report
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Process Tax Depreciation Card -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-calculator me-2"></i>Process Tax Depreciation (TRA)
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="process-tax-depreciation-form">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Period Date <span class="text-danger">*</span></label>
                                    <input type="date" name="period_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                                    <small class="text-muted">Select the month/year for tax depreciation processing</small>
                                </div>
                                <div class="col-md-8 d-flex align-items-end">
                                    <button type="submit" class="btn btn-info w-100">
                                        <i class="bx bx-play me-1"></i>Process Tax Depreciation
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- TRA Tax Classes Information Card -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-info-circle me-2"></i>TRA Tax Depreciation Classes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Class</th>
                                                <th>Description</th>
                                                <th>Rate</th>
                                                <th>Method</th>
                                                <th>Special Conditions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>Class 1</strong></td>
                                                <td>Computers, small vehicles (&lt;30 seats), construction & earth-moving equipment</td>
                                                <td>37.5%</td>
                                                <td>Reducing Balance</td>
                                                <td>-</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Class 2</strong></td>
                                                <td>Heavy vehicles (≥30 seats), aircraft, vessels, manufacturing/agricultural machinery</td>
                                                <td>25%</td>
                                                <td>Reducing Balance</td>
                                                <td>50% allowance (first two years) if used in manufacturing/tourism/fish farming</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Class 3</strong></td>
                                                <td>Office furniture, fixtures, and equipment; any asset not in another class</td>
                                                <td>12.5%</td>
                                                <td>Reducing Balance</td>
                                                <td>-</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Class 5</strong></td>
                                                <td>Agricultural permanent structures (dams, fences, reservoirs, etc.)</td>
                                                <td>20%</td>
                                                <td>Straight Line</td>
                                                <td>5 years write-off</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Class 6</strong></td>
                                                <td>Other buildings & permanent structures</td>
                                                <td>5%</td>
                                                <td>Straight Line</td>
                                                <td>-</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Class 7</strong></td>
                                                <td>Intangible assets</td>
                                                <td>N/A</td>
                                                <td>Useful Life</td>
                                                <td>1 ÷ useful life (round down to nearest half year)</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Class 8</strong></td>
                                                <td>Agricultural plant & machinery, EFDs for non-VAT traders</td>
                                                <td>100%</td>
                                                <td>Immediate Write-Off</td>
                                                <td>Immediate write-off in first year</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="alert alert-info mb-0">
                            <i class="bx bx-info-circle me-1"></i>
                            <strong>Note:</strong> Tax depreciation is calculated separately from book depreciation and is NOT posted to the General Ledger. It is used for tax computation purposes only. Deferred tax is calculated based on the difference between book and tax values.
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
    $('#process-tax-depreciation-form').on('submit', function(e){
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

        $.ajax({
            url: '{{ route('assets.tax-depreciation.process') }}',
            type: 'POST',
            data: form.serialize(),
            success: function(response){
                if (response.success) {
                    let html = `<p>Successfully processed tax depreciation for <strong>${response.data.total_processed}</strong> assets.</p>`;

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
                        title: 'Tax Depreciation Processed!',
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

