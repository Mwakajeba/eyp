@extends('layouts.main')

@section('title', 'Leasehold Improvements Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Leasehold Improvements', 'url' => '#', 'icon' => 'bx bx-building']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Leasehold Improvements Report</h4>
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
                            <div class="col-md-5">
                                <label class="form-label">Category</label>
                                <select id="category_id" name="category_id" class="form-select select2-single">
                                    <option value="">All Leasehold Categories</option>
                                    @foreach($categories ?? [] as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="active" selected>Active</option>
                                    <option value="disposed">Disposed</option>
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
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-building me-2"></i>Leasehold Improvements Report
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Summary Cards -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Cost</small>
                                        <h5 class="mb-0 text-primary" id="summary-cost">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total NBV</small>
                                        <h5 class="mb-0 text-success" id="summary-nbv">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Note:</strong> Leasehold improvements are depreciated over the shorter of the asset's useful life or the remaining lease term.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover" id="leasehold-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Asset Code</th>
                                        <th>Lease Term</th>
                                        <th class="text-end">Improvement Cost</th>
                                        <th>Depreciation Method</th>
                                        <th>Remaining Lease Period</th>
                                        <th class="text-end">NBV</th>
                                    </tr>
                                </thead>
                                <tbody id="leasehold-tbody">
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

    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    function generateReport() {
        const categoryId = $('#category_id').val();
        const status = $('#status').val();

        $.ajax({
            url: '{{ route('assets.reports.leasehold-improvements.data') }}',
            type: 'GET',
            data: {
                category_id: categoryId,
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
        $('#summary-nbv').text(formatCurrency(summary.total_nbv));

        // Render leasehold rows
        data.forEach(function(item) {
            html += '<tr>';
            html += '<td>' + item.asset_code + '</td>';
            html += '<td>' + item.lease_term + '</td>';
            html += '<td class="text-end">' + formatCurrency(item.improvement_cost) + '</td>';
            html += '<td>' + item.depreciation_method + '</td>';
            html += '<td>' + item.remaining_lease_period + '</td>';
            html += '<td class="text-end"><strong>' + formatCurrency(item.nbv) + '</strong></td>';
            html += '</tr>';
        });

        // Add totals row
        html += '<tr class="table-warning fw-bold">';
        html += '<td colspan="2">TOTAL</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_cost) + '</td>';
        html += '<td colspan="2"></td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_nbv) + '</td>';
        html += '</tr>';

        if (data.length === 0) {
            html = '<tr><td colspan="6" class="text-center text-muted">No leasehold improvement assets found.</td></tr>';
        }

        $('#leasehold-tbody').html(html);
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

        const categoryId = $('#category_id').val();
        const status = $('#status').val();

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.leasehold-improvements.export-excel') }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        if (categoryId) form.append($('<input>', { 'type': 'hidden', 'name': 'category_id', 'value': categoryId }));
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

        const categoryId = $('#category_id').val();
        const status = $('#status').val();

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.leasehold-improvements.export-pdf') }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        if (categoryId) form.append($('<input>', { 'type': 'hidden', 'name': 'category_id', 'value': categoryId }));
        if (status) form.append($('<input>', { 'type': 'hidden', 'name': 'status', 'value': status }));

        $('body').append(form);
        form.submit();
        form.remove();
    }
});
</script>
@endpush
