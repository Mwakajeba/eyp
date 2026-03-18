@extends('layouts.main')

@section('title', 'Assets by Category Summary')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Assets by Category', 'url' => '#', 'icon' => 'bx bx-category']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Assets by Category Summary</h4>
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
                            <div class="col-md-10">
                                <label class="form-label">Status Filter</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="active" selected>Active</option>
                                    <option value="disposed">Disposed</option>
                                    <option value="under_construction">Under Construction</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
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
                            <i class="bx bx-category me-2"></i>Assets by Category Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Summary Cards -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Cost</small>
                                        <h5 class="mb-0 text-primary" id="summary-cost">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-warning">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Depreciation</small>
                                        <h5 class="mb-0 text-warning" id="summary-depreciation">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-danger">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Impairment</small>
                                        <h5 class="mb-0 text-danger" id="summary-impairment">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-success">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Net Book Value</small>
                                        <h5 class="mb-0 text-success" id="summary-nbv">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover" id="by-category-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Total Cost</th>
                                        <th class="text-end">Total Accumulated Depreciation</th>
                                        <th class="text-end">Total Impairment</th>
                                        <th class="text-end">Net Book Value</th>
                                    </tr>
                                </thead>
                                <tbody id="by-category-tbody">
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
        const status = $('#status').val();

        $.ajax({
            url: '{{ route('assets.reports.by-category.data') }}',
            type: 'GET',
            data: {
                status: status
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
        $('#summary-cost').text(formatCurrency(summary.total_cost));
        $('#summary-depreciation').text(formatCurrency(summary.total_depreciation));
        $('#summary-impairment').text(formatCurrency(summary.total_impairment));
        $('#summary-nbv').text(formatCurrency(summary.total_nbv));

        // Render category rows
        data.forEach(function(cat) {
            html += '<tr>';
            html += '<td><strong>' + cat.category + '</strong></td>';
            html += '<td class="text-end">' + formatCurrency(cat.total_cost) + '</td>';
            html += '<td class="text-end">' + formatCurrency(cat.total_accumulated_depreciation) + '</td>';
            html += '<td class="text-end">' + formatCurrency(cat.total_impairment) + '</td>';
            html += '<td class="text-end"><strong>' + formatCurrency(cat.net_book_value) + '</strong></td>';
            html += '</tr>';
        });

        // Add totals row
        html += '<tr class="table-warning fw-bold">';
        html += '<td>TOTAL</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_cost) + '</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_depreciation) + '</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_impairment) + '</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_nbv) + '</td>';
        html += '</tr>';

        if (data.length === 0) {
            html = '<tr><td colspan="5" class="text-center text-muted">No assets found.</td></tr>';
        }

        $('#by-category-tbody').html(html);
    }

    function formatCurrency(amount) {
        return 'TZS ' + parseFloat(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function exportToExcel() {
        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        const status = $('#status').val();

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.by-category.export-excel') }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        if (status) form.append($('<input>', { 'type': 'hidden', 'name': 'status', 'value': status }));

        $('body').append(form);
        form.submit();
        form.remove();
    }

    function exportToPdf() {
        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        const status = $('#status').val();

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.by-category.export-pdf') }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        if (status) form.append($('<input>', { 'type': 'hidden', 'name': 'status', 'value': status }));

        $('body').append(form);
        form.submit();
        form.remove();
    }
});
</script>
@endpush
