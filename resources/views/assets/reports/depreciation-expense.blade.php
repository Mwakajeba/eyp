@extends('layouts.main')

@section('title', 'Depreciation Expense Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Depreciation Expense', 'url' => '#', 'icon' => 'bx bx-calculator']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Depreciation Expense Report</h4>
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
                                <label class="form-label">From Date <span class="text-danger">*</span></label>
                                <input type="date" id="from_date" name="from_date" class="form-control" value="{{ \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date <span class="text-danger">*</span></label>
                                <input type="date" id="to_date" name="to_date" class="form-control" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Asset Category</label>
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
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-calculator me-2"></i>Depreciation Expense Report - <span id="report-period"></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-1"></i>
                            <strong>P&L Verification:</strong> This report shows depreciation charges to be recognized in the Profit & Loss statement for the selected period.
                        </div>

                        <!-- Summary Cards -->
                        <div class="row mb-4" id="summary-section">
                            <div class="col-md-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Total Depreciation</h6>
                                        <h4 class="mb-0 text-primary" id="total-depreciation">TZS 0.00</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Assets Depreciated</h6>
                                        <h4 class="mb-0 text-success" id="total-assets">0</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Avg Per Asset</h6>
                                        <h4 class="mb-0 text-warning" id="avg-depreciation">TZS 0.00</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Monthly Avg</h6>
                                        <h4 class="mb-0 text-info" id="monthly-avg">TZS 0.00</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover" id="depreciation-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Asset Code</th>
                                        <th>Asset Name</th>
                                        <th>Category</th>
                                        <th class="text-end">Cost</th>
                                        <th class="text-end">Opening NBV</th>
                                        <th class="text-center">Dep. Rate</th>
                                        <th class="text-end">Depreciation This Period</th>
                                        <th class="text-end">Accumulated Depreciation</th>
                                        <th class="text-end">Closing NBV</th>
                                    </tr>
                                </thead>
                                <tbody id="depreciation-tbody">
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
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const categoryId = $('#category_id').val();

        if (!fromDate || !toDate) {
            Swal.fire('Error!', 'Please select both from and to dates.', 'error');
            return;
        }

        $.ajax({
            url: '{{ route('assets.reports.depreciation-expense.data') }}',
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
                    renderReport(response.data, response.summary, fromDate, toDate);
                    $('#report-period').text(fromDate + ' to ' + toDate);
                    $('#generation-date').text(new Date().toLocaleString());
                    $('#export-section').show();
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

    function renderReport(data, summary, fromDate, toDate) {
        let html = '';

        // Update summary cards
        $('#total-depreciation').text(formatCurrency(summary.total_depreciation));
        $('#total-assets').text(summary.asset_count);
        $('#avg-depreciation').text(formatCurrency(summary.avg_per_asset));
        $('#monthly-avg').text(formatCurrency(summary.monthly_avg));

        data.forEach(function(item) {
            html += '<tr>';
            html += '<td>' + (item.asset_code || 'N/A') + '</td>';
            html += '<td>' + (item.asset_name || 'N/A') + '</td>';
            html += '<td>' + (item.category_name || 'N/A') + '</td>';
            html += '<td class="text-end">' + formatCurrency(item.cost) + '</td>';
            html += '<td class="text-end">' + formatCurrency(item.opening_nbv) + '</td>';
            html += '<td class="text-center">' + (item.depreciation_rate || '0') + '%</td>';
            html += '<td class="text-end text-danger fw-bold">' + formatCurrency(item.period_depreciation) + '</td>';
            html += '<td class="text-end">' + formatCurrency(item.accumulated_depreciation) + '</td>';
            html += '<td class="text-end fw-bold">' + formatCurrency(item.closing_nbv) + '</td>';
            html += '</tr>';
        });

        // Add totals row
        html += '<tr class="table-warning fw-bold">';
        html += '<td colspan="3">TOTAL</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_cost) + '</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_opening_nbv) + '</td>';
        html += '<td></td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_depreciation) + '</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_accumulated) + '</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_closing_nbv) + '</td>';
        html += '</tr>';

        $('#depreciation-tbody').html(html);
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

        if (!fromDate || !toDate) {
            Swal.fire('Error!', 'Please select dates and generate the report first.', 'error');
            return;
        }

        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.depreciation-expense.export-excel') }}'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'from_date',
            'value': fromDate
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'to_date',
            'value': toDate
        }));

        if (categoryId) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'category_id',
                'value': categoryId
            }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }

    function exportToPdf() {
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const categoryId = $('#category_id').val();

        if (!fromDate || !toDate) {
            Swal.fire('Error!', 'Please select dates and generate the report first.', 'error');
            return;
        }

        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.depreciation-expense.export-pdf') }}'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'from_date',
            'value': fromDate
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'to_date',
            'value': toDate
        }));

        if (categoryId) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'category_id',
                'value': categoryId
            }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }
});
</script>
@endpush
