@extends('layouts.main')

@section('title', 'Impairment Report (IAS 36)')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Impairment Report', 'url' => '#', 'icon' => 'bx bx-error-circle']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Impairment Report (IAS 36)</h4>
                    <div class="page-title-right" id="export-buttons" style="display: none;">
                        <button type="button" id="export_pdf" class="btn btn-danger">
                            <i class="bx bxs-file-pdf me-1"></i>Export PDF
                        </button>
                        <button type="button" id="export_excel" class="btn btn-success ms-2">
                            <i class="bx bx-file me-1"></i>Export Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" id="from_date" name="from_date" class="form-control" value="{{ \Carbon\Carbon::now()->startOfYear()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" id="to_date" name="to_date" class="form-control" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Type</label>
                                <select id="type" name="type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="impairment">Impairment</option>
                                    <option value="reversal">Reversal</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" id="generate_report" class="btn btn-primary w-100">
                                    <i class="bx bx-search me-1"></i>Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Results -->
        <div class="row" id="report-results" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-error-circle me-2"></i>Impairment Report (IAS 36 Compliant)
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Summary Cards -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="card border-warning">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Impairments</small>
                                        <h5 class="mb-0 text-warning" id="summary-count">0</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-danger">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Impairment Loss</small>
                                        <h5 class="mb-0 text-danger" id="summary-loss">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-success">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Reversals</small>
                                        <h5 class="mb-0 text-success" id="summary-reversals">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Net Impact</small>
                                        <h5 class="mb-0 text-primary" id="summary-net">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover" id="impairments-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Asset Code</th>
                                        <th>CGU</th>
                                        <th class="text-end">Carrying Amount Before</th>
                                        <th class="text-end">Recoverable Amount</th>
                                        <th class="text-end">Impairment Loss</th>
                                        <th class="text-end">Reversal</th>
                                        <th class="text-end">Carrying Amount After</th>
                                    </tr>
                                </thead>
                                <tbody id="impairments-tbody">
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
    $('#generate_report').on('click', function() {
        generateReport();
    });

    $('#export_pdf').on('click', function() {
        exportToPdf();
    });

    $('#export_excel').on('click', function() {
        exportToExcel();
    });

    function generateReport() {
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const type = $('#type').val();

        if (!fromDate || !toDate) {
            Swal.fire('Error!', 'Please select both from and to dates.', 'error');
            return;
        }

        $.ajax({
            url: '{{ route('assets.reports.impairments.data') }}',
            type: 'GET',
            data: {
                from_date: fromDate,
                to_date: toDate,
                type: type
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'Generating Report...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                Swal.close();
                if (response.success && response.data) {
                    renderReport(response.data, response.summary);
                    $('#generation-date').text(new Date().toLocaleString());
                    $('#export-buttons').show();
                    $('#report-results').show();
                } else {
                    Swal.fire('Error!', 'Failed to generate report.', 'error');
                }
            },
            error: function(xhr) {
                Swal.close();
                Swal.fire('Error!', 'An error occurred while generating the report.', 'error');
            }
        });
    }

    function renderReport(data, summary) {
        let html = '';

        // Update summary
        $('#summary-count').text(summary.count);
        $('#summary-loss').text(formatCurrency(summary.total_loss));
        $('#summary-reversals').text(formatCurrency(summary.total_reversals));
        $('#summary-net').text(formatCurrency(summary.net_impact));

        // Render impairment rows
        data.forEach(function(imp) {
            html += '<tr>';
            html += '<td>' + (imp.asset_code || 'N/A') + '</td>';
            html += '<td>' + (imp.cgu || 'N/A') + '</td>';
            html += '<td class="text-end">' + formatCurrency(imp.carrying_amount_before) + '</td>';
            html += '<td class="text-end">' + formatCurrency(imp.recoverable_amount) + '</td>';
            html += '<td class="text-end text-danger">' + formatCurrency(imp.impairment_loss) + '</td>';
            html += '<td class="text-end text-success">' + formatCurrency(imp.reversal) + '</td>';
            html += '<td class="text-end"><strong>' + formatCurrency(imp.carrying_amount_after) + '</strong></td>';
            html += '</tr>';
        });

        // Add totals row
        html += '<tr class="table-warning fw-bold">';
        html += '<td colspan="4">TOTAL</td>';
        html += '<td class="text-end text-danger">' + formatCurrency(summary.total_loss) + '</td>';
        html += '<td class="text-end text-success">' + formatCurrency(summary.total_reversals) + '</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_carrying_after) + '</td>';
        html += '</tr>';

        $('#impairments-tbody').html(html);
    }

    function formatCurrency(amount) {
        return 'TZS ' + parseFloat(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function exportToExcel() {
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const type = $('#type').val();

        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.impairments.export-excel') }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'from_date', 'value': fromDate }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'to_date', 'value': toDate }));
        if (type) {
            form.append($('<input>', { 'type': 'hidden', 'name': 'type', 'value': type }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }

    function exportToPdf() {
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const type = $('#type').val();

        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.impairments.export-pdf') }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'from_date', 'value': fromDate }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'to_date', 'value': toDate }));
        if (type) {
            form.append($('<input>', { 'type': 'hidden', 'name': 'type', 'value': type }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }
});
</script>
@endpush
