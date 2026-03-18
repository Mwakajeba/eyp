@extends('layouts.main')

@section('title', 'Depreciation Schedule')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Depreciation Schedule', 'url' => '#', 'icon' => 'bx bx-calendar']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Depreciation Schedule (Full Life Schedule)</h4>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Select Asset <span class="text-danger">*</span></label>
                                <select id="asset_id" name="asset_id" class="form-select select2-single" required>
                                    <option value="">-- Select Asset --</option>
                                    @foreach($assets as $asset)
                                        <option value="{{ $asset->id }}">
                                            {{ $asset->code }} - {{ $asset->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" id="from_date" name="from_date" class="form-control" value="{{ \Carbon\Carbon::now()->startOfYear()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" id="to_date" name="to_date" class="form-control" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" id="generate_report" class="btn btn-primary w-100">
                                    <i class="bx bx-search me-1"></i>Generate
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="row mb-4" id="export-section" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            <button type="button" id="export_pdf" class="btn btn-danger">
                                <i class="bx bx-file-pdf me-1"></i>Export PDF
                            </button>
                            <button type="button" id="export_excel" class="btn btn-success">
                                <i class="bx bx-file me-1"></i>Export Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Results -->
        <div class="row" id="report-results" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-calendar me-2"></i>Depreciation Schedule - <span id="asset-name"></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Asset Details Card -->
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Asset Code:</strong> <span id="detail-code"></span><br>
                                        <strong>Category:</strong> <span id="detail-category"></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Purchase Cost:</strong> <span id="detail-cost"></span><br>
                                        <strong>Salvage Value:</strong> <span id="detail-salvage"></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Useful Life:</strong> <span id="detail-life"></span><br>
                                        <strong>Depreciation Method:</strong> <span id="detail-method"></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Purchase Date:</strong> <span id="detail-purchase-date"></span><br>
                                        <strong>Capitalization Date:</strong> <span id="detail-cap-date"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Metrics -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Depreciation</small>
                                        <h5 class="mb-0 text-primary" id="summary-total-dep">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-success">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Current NBV</small>
                                        <h5 class="mb-0 text-success" id="summary-nbv">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-warning">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Periods Shown</small>
                                        <h5 class="mb-0 text-warning" id="summary-periods">0</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-info">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Remaining Life</small>
                                        <h5 class="mb-0 text-info" id="summary-remaining">0 months</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover" id="schedule-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period</th>
                                        <th>Date</th>
                                        <th class="text-end">Opening NBV</th>
                                        <th class="text-end">Depreciation</th>
                                        <th class="text-end">Revaluation Adj.</th>
                                        <th class="text-end">Impairment</th>
                                        <th class="text-end">Closing NBV</th>
                                    </tr>
                                </thead>
                                <tbody id="schedule-tbody">
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Report Footer -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <strong>Prepared By:</strong> {{ Auth::user()->name }}<br>
                                    <strong>Generated:</strong> <span id="generation-date"></span>
                                </small>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">
                                    <strong>Company:</strong> {{ Auth::user()->company->name ?? 'N/A' }}<br>
                                    <strong>Branch:</strong> {{ Auth::user()->branch->name ?? 'All Branches' }}
                                </small>
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
$(document).ready(function() {
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    $('#generate_report').on('click', function() {
        generateReport();
    });

    $('#export_excel').on('click', function() {
        exportToExcel();
    });

    $('#export_pdf').on('click', function() {
        exportToPdf();
    });

    function generateReport() {
        const assetId = $('#asset_id').val();
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();

        if (!assetId) {
            Swal.fire('Error!', 'Please select an asset.', 'error');
            return;
        }

        $.ajax({
            url: '{{ route('assets.reports.depreciation-schedule.data') }}',
            type: 'GET',
            data: {
                asset_id: assetId,
                from_date: fromDate,
                to_date: toDate
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'Generating Schedule...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                Swal.close();
                if (response.success && response.data) {
                    renderReport(response.data, response.asset_details, response.summary);
                    $('#generation-date').text(new Date().toLocaleString());
                    $('#export-section').show();
                    $('#report-results').show();
                } else {
                    Swal.fire('Error!', 'Failed to generate schedule.', 'error');
                }
            },
            error: function(xhr) {
                Swal.close();
                Swal.fire('Error!', 'An error occurred while generating the schedule.', 'error');
            }
        });
    }

    function renderReport(data, assetDetails, summary) {
        let html = '';

        // Update asset details
        $('#asset-name').text(assetDetails.name);
        $('#detail-code').text(assetDetails.code);
        $('#detail-category').text(assetDetails.category);
        $('#detail-cost').text(formatCurrency(assetDetails.cost));
        $('#detail-salvage').text(formatCurrency(assetDetails.salvage_value));
        $('#detail-life').text(assetDetails.useful_life + ' years');
        $('#detail-method').text(assetDetails.depreciation_method);
        $('#detail-purchase-date').text(assetDetails.purchase_date);
        $('#detail-cap-date').text(assetDetails.capitalization_date);

        // Update summary
        $('#summary-total-dep').text(formatCurrency(summary.total_depreciation));
        $('#summary-nbv').text(formatCurrency(summary.current_nbv));
        $('#summary-periods').text(summary.period_count);
        $('#summary-remaining').text(summary.remaining_months + ' months');

        // Render schedule rows
        data.forEach(function(period, index) {
            html += '<tr>';
            html += '<td>' + (index + 1) + '</td>';
            html += '<td>' + period.date + '</td>';
            html += '<td class="text-end">' + formatCurrency(period.opening_nbv) + '</td>';
            html += '<td class="text-end text-danger">' + formatCurrency(period.depreciation) + '</td>';
            html += '<td class="text-end">' + formatCurrency(period.revaluation) + '</td>';
            html += '<td class="text-end">' + formatCurrency(period.impairment) + '</td>';
            html += '<td class="text-end fw-bold">' + formatCurrency(period.closing_nbv) + '</td>';
            html += '</tr>';
        });

        // Add totals row
        html += '<tr class="table-warning fw-bold">';
        html += '<td colspan="3">TOTAL</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_depreciation) + '</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_revaluation) + '</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_impairment) + '</td>';
        html += '<td></td>';
        html += '</tr>';

        $('#schedule-tbody').html(html);
    }

    function formatCurrency(amount) {
        return 'TZS ' + parseFloat(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function exportToExcel() {
        const assetId = $('#asset_id').val();
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();

        if (!assetId) {
            Swal.fire('Error!', 'Please select an asset and generate the schedule first.', 'error');
            return;
        }

        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the schedule first before exporting.', 'error');
            return;
        }

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.depreciation-schedule.export-excel') }}'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'asset_id',
            'value': assetId
        }));

        if (fromDate) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'from_date',
                'value': fromDate
            }));
        }

        if (toDate) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'to_date',
                'value': toDate
            }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }

    function exportToPdf() {
        const assetId = $('#asset_id').val();
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();

        if (!assetId) {
            Swal.fire('Error!', 'Please select an asset and generate the schedule first.', 'error');
            return;
        }

        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the schedule first before exporting.', 'error');
            return;
        }

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.depreciation-schedule.export-pdf') }}'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'asset_id',
            'value': assetId
        }));

        if (fromDate) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'from_date',
                'value': fromDate
            }));
        }

        if (toDate) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'to_date',
                'value': toDate
            }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }
});
</script>
@endpush
