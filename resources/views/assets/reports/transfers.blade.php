@extends('layouts.main')

@section('title', 'Asset Transfer Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Asset Transfers', 'url' => '#', 'icon' => 'bx bx-transfer']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Asset Transfer Report</h4>
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
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-transfer me-2"></i>Asset Transfers Report
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Summary Cards -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="card border-info">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Transfers</small>
                                        <h5 class="mb-0 text-info" id="summary-count">0</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover" id="transfers-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Asset Code</th>
                                        <th>From Location</th>
                                        <th>To Location</th>
                                        <th>Transfer Date</th>
                                        <th>Approved By</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="transfers-tbody">
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
            url: '{{ route('assets.reports.transfers.data') }}',
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

        // Render transfer rows
        data.forEach(function(transfer) {
            html += '<tr>';
            html += '<td>' + (transfer.asset_code || 'N/A') + '</td>';
            html += '<td>' + (transfer.from_location || 'N/A') + '</td>';
            html += '<td>' + (transfer.to_location || 'N/A') + '</td>';
            html += '<td>' + transfer.transfer_date + '</td>';
            html += '<td>' + (transfer.approved_by || 'N/A') + '</td>';
            html += '<td>' + (transfer.remarks || '') + '</td>';
            html += '</tr>';
        });

        if (data.length === 0) {
            html = '<tr><td colspan="6" class="text-center text-muted">No transfers found for the selected period.</td></tr>';
        }

        $('#transfers-tbody').html(html);
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
            'action': '{{ route('assets.reports.transfers.export-excel') }}'
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
            'action': '{{ route('assets.reports.transfers.export-pdf') }}'
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
