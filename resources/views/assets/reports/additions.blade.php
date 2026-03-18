@extends('layouts.main')

@section('title', 'Asset Addition Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Asset Additions', 'url' => '#', 'icon' => 'bx bx-plus-circle']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Asset Addition Report</h4>
                    <div class="page-title-right" id="export-buttons" style="display: none;">
                        <button type="button" id="export_pdf" class="btn btn-danger">
                            <i class="bx bxs-file-pdf me-1"></i>Export PDF
                        </button>
                        <button type="button" id="export_excel" class="btn btn-success ms-2">
                            <i class="bx bx-file me-1"></i>Export Excel
                        </button>
                        <button type="button" class="btn btn-secondary ms-2" onclick="window.print()">
                            <i class="bx bx-printer me-1"></i>Print
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
                                <label class="form-label">Category</label>
                                <select id="category_id" name="category_id" class="form-select select2-single">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
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
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-plus-circle me-2"></i>Asset Additions Report
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Summary Cards -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card border-success">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Additions</small>
                                        <h5 class="mb-0 text-success" id="summary-count">0</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-primary">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Capitalized</small>
                                        <h5 class="mb-0 text-primary" id="summary-amount">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-info">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Average Value</small>
                                        <h5 class="mb-0 text-info" id="summary-average">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover" id="additions-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Asset Code</th>
                                        <th>Asset Name</th>
                                        <th>Category</th>
                                        <th>Invoice No</th>
                                        <th>Vendor</th>
                                        <th>Purchase Date</th>
                                        <th>Capitalized Date</th>
                                        <th class="text-end">Amount</th>
                                        <th>Approved By</th>
                                    </tr>
                                </thead>
                                <tbody id="additions-tbody">
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

    $('#export_pdf').on('click', function() {
        exportToPdf();
    });

    $('#export_excel').on('click', function() {
        exportToExcel();
    });

    function generateReport() {
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const categoryId = $('#category_id').val();

        if (!fromDate || !toDate) {
            Swal.fire('Error!', 'Please select both from and to dates.', 'error');
            return;
        }

        $.ajax({
            url: '{{ route('assets.reports.additions.data') }}',
            type: 'GET',
            data: {
                from_date: fromDate,
                to_date: toDate,
                category_id: categoryId
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
        $('#summary-amount').text(formatCurrency(summary.total_amount));
        $('#summary-average').text(formatCurrency(summary.average_amount));

        // Render addition rows
        data.forEach(function(addition) {
            html += '<tr>';
            html += '<td>' + (addition.asset_code || 'N/A') + '</td>';
            html += '<td>' + (addition.asset_name || 'N/A') + '</td>';
            html += '<td>' + addition.category + '</td>';
            html += '<td>' + (addition.invoice_no || 'N/A') + '</td>';
            html += '<td>' + (addition.vendor || 'N/A') + '</td>';
            html += '<td>' + addition.purchase_date + '</td>';
            html += '<td>' + addition.capitalized_date + '</td>';
            html += '<td class="text-end">' + formatCurrency(addition.amount) + '</td>';
            html += '<td>' + (addition.approved_by || 'N/A') + '</td>';
            html += '</tr>';
        });

        // Add totals row
        html += '<tr class="table-warning fw-bold">';
        html += '<td colspan="7">TOTAL</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_amount) + '</td>';
        html += '<td></td>';
        html += '</tr>';

        $('#additions-tbody').html(html);
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
        const categoryId = $('#category_id').val();

        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.additions.export-excel') }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'from_date', 'value': fromDate }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'to_date', 'value': toDate }));
        if (categoryId) {
            form.append($('<input>', { 'type': 'hidden', 'name': 'category_id', 'value': categoryId }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }

    function exportToPdf() {
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const categoryId = $('#category_id').val();

        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.additions.export-pdf') }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'from_date', 'value': fromDate }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'to_date', 'value': toDate }));
        if (categoryId) {
            form.append($('<input>', { 'type': 'hidden', 'name': 'category_id', 'value': categoryId }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }
});
</script>
@endpush
