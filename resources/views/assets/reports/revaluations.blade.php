@extends('layouts.main')

@section('title', 'Revaluation Report (IFRS)')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Revaluation Report', 'url' => '#', 'icon' => 'bx bx-trending-up']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Revaluation Report (IFRS Compliant)</h4>
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
                            <div class="col-md-4">
                                <label class="form-label">From Date</label>
                                <input type="date" id="from_date" name="from_date" class="form-control" value="{{ \Carbon\Carbon::now()->startOfYear()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">To Date</label>
                                <input type="date" id="to_date" name="to_date" class="form-control" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
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
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-trending-up me-2"></i>Asset Revaluation Report (IFRS)
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Summary Cards -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Revaluations</small>
                                        <h5 class="mb-0 text-primary" id="summary-count">0</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-success">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Increase</small>
                                        <h5 class="mb-0 text-success" id="summary-increase">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-danger">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Decrease</small>
                                        <h5 class="mb-0 text-danger" id="summary-decrease">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-info">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Net Movement</small>
                                        <h5 class="mb-0 text-info" id="summary-net">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover" id="revaluations-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Asset Code</th>
                                        <th class="text-end">Old Carrying Amount</th>
                                        <th class="text-end">Revalued Amount</th>
                                        <th class="text-end">Surplus/(Deficit)</th>
                                        <th class="text-end">Revaluation Reserve Movement</th>
                                        <th>Valuer</th>
                                        <th>Valuation Date</th>
                                    </tr>
                                </thead>
                                <tbody id="revaluations-tbody">
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

        if (!fromDate || !toDate) {
            Swal.fire('Error!', 'Please select both from and to dates.', 'error');
            return;
        }

        $.ajax({
            url: '{{ route('assets.reports.revaluation.data') }}',
            type: 'GET',
            data: {
                from_date: fromDate,
                to_date: toDate
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
        $('#summary-increase').text(formatCurrency(summary.total_increase));
        $('#summary-decrease').text(formatCurrency(summary.total_decrease));
        
        const netClass = summary.net_movement >= 0 ? 'text-success' : 'text-danger';
        $('#summary-net').removeClass('text-success text-danger text-info').addClass(netClass);
        $('#summary-net').text(formatCurrency(summary.net_movement));

        // Render revaluation rows
        data.forEach(function(rev) {
            const surplusClass = rev.surplus_deficit >= 0 ? 'text-success' : 'text-danger';
            
            html += '<tr>';
            html += '<td>' + (rev.asset_code || 'N/A') + '</td>';
            html += '<td class="text-end">' + formatCurrency(rev.old_carrying_amount) + '</td>';
            html += '<td class="text-end">' + formatCurrency(rev.revalued_amount) + '</td>';
            html += '<td class="text-end ' + surplusClass + '"><strong>' + formatCurrency(rev.surplus_deficit) + '</strong></td>';
            html += '<td class="text-end">' + formatCurrency(rev.revaluation_reserve_movement) + '</td>';
            html += '<td>' + (rev.valuer || 'N/A') + '</td>';
            html += '<td>' + rev.valuation_date + '</td>';
            html += '</tr>';
        });

        if (data.length === 0) {
            html = '<tr><td colspan="7" class="text-center text-muted">No revaluations found for the selected period.</td></tr>';
        } else {
            // Add totals row
            html += '<tr class="table-warning fw-bold">';
            html += '<td>TOTAL</td>';
            html += '<td class="text-end">' + formatCurrency(data.reduce((sum, r) => sum + r.old_carrying_amount, 0)) + '</td>';
            html += '<td class="text-end">' + formatCurrency(data.reduce((sum, r) => sum + r.revalued_amount, 0)) + '</td>';
            html += '<td class="text-end ' + netClass + '">' + formatCurrency(summary.net_movement) + '</td>';
            html += '<td colspan="3"></td>';
            html += '</tr>';
        }

        $('#revaluations-tbody').html(html);
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

        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.revaluation.export-excel') }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'from_date', 'value': fromDate }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'to_date', 'value': toDate }));

        $('body').append(form);
        form.submit();
        form.remove();
    }

    function exportToPdf() {
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();

        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.revaluation.export-pdf') }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'from_date', 'value': fromDate }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'to_date', 'value': toDate }));

        $('body').append(form);
        form.submit();
        form.remove();
    }
});
</script>
@endpush
