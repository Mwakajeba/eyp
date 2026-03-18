@extends('layouts.main')

@section('title', 'Asset Movement Schedule')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Asset Movement Schedule', 'url' => '#', 'icon' => 'bx bx-transfer-alt']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Asset Movement Schedule (Roll-forward)</h4>
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
                                <input type="date" id="from_date" name="from_date" class="form-control" value="{{ \Carbon\Carbon::now()->startOfYear()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date <span class="text-danger">*</span></label>
                                <input type="date" id="to_date" name="to_date" class="form-control" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Asset Category</label>
                                <select id="asset_category_id" name="asset_category_id" class="form-select select2-single">
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
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-transfer-alt me-2"></i>Asset Movement Schedule - <span id="report-period"></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-1"></i>
                            <strong>IFRSCompliance:</strong> This report satisfies IAS 16 reconciliation requirements.
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="movement-table">
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="2">Asset Category</th>
                                        <th colspan="6" class="text-center bg-primary text-white">COST / VALUATION</th>
                                        <th colspan="5" class="text-center bg-danger text-white">ACCUMULATED DEPRECIATION</th>
                                        <th rowspan="2" class="text-center bg-success text-white">Closing<br>NBV</th>
                                    </tr>
                                    <tr>
                                        <th class="text-end">Opening</th>
                                        <th class="text-end">Additions</th>
                                        <th class="text-end">Disposals</th>
                                        <th class="text-end">Transfers</th>
                                        <th class="text-end">Revaluation</th>
                                        <th class="text-end">Closing</th>
                                        <th class="text-end">Opening</th>
                                        <th class="text-end">Charge</th>
                                        <th class="text-end">Disposal Removed</th>
                                        <th class="text-end">Impairment</th>
                                        <th class="text-end">Closing</th>
                                    </tr>
                                </thead>
                                <tbody id="movement-tbody">
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Report Footer -->
                        <div class="row mt-3">
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
        const categoryId = $('#asset_category_id').val();

        if (!fromDate || !toDate) {
            Swal.fire('Error!', 'Please select both from and to dates.', 'error');
            return;
        }

        $.ajax({
            url: '{{ route('assets.reports.movement-schedule.data') }}',
            type: 'GET',
            data: {
                from_date: fromDate,
                to_date: toDate,
                asset_category_id: categoryId
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
                    renderReport(response.data);
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

    function renderReport(data) {
        let html = '';

        data.forEach(function(category) {
            html += '<tr>';
            html += '<td class="fw-bold">' + category.category_name + '</td>';
            // Cost columns
            html += '<td class="text-end">' + formatCurrency(category.opening_cost) + '</td>';
            html += '<td class="text-end text-success">' + formatCurrency(category.additions) + '</td>';
            html += '<td class="text-end text-danger">' + formatCurrency(category.disposals) + '</td>';
            html += '<td class="text-end">' + formatCurrency(category.transfers) + '</td>';
            html += '<td class="text-end">' + formatCurrency(category.revaluation) + '</td>';
            html += '<td class="text-end fw-bold">' + formatCurrency(category.closing_cost) + '</td>';
            // Depreciation columns
            html += '<td class="text-end">' + formatCurrency(category.opening_accum_dep) + '</td>';
            html += '<td class="text-end text-danger">' + formatCurrency(category.depreciation_charge) + '</td>';
            html += '<td class="text-end text-success">' + formatCurrency(category.disposal_dep_removed) + '</td>';
            html += '<td class="text-end text-danger">' + formatCurrency(category.impairment) + '</td>';
            html += '<td class="text-end fw-bold">' + formatCurrency(category.closing_accum_dep) + '</td>';
            // Closing NBV
            html += '<td class="text-end fw-bold text-success">' + formatCurrency(category.closing_nbv) + '</td>';
            html += '</tr>';
        });

        // Add grand total row if there's data
        if (data.length > 0) {
            const totals = calculateTotals(data);
            html += '<tr class="table-warning fw-bold">';
            html += '<td>GRAND TOTAL</td>';
            html += '<td class="text-end">' + formatCurrency(totals.opening_cost) + '</td>';
            html += '<td class="text-end">' + formatCurrency(totals.additions) + '</td>';
            html += '<td class="text-end">' + formatCurrency(totals.disposals) + '</td>';
            html += '<td class="text-end">' + formatCurrency(totals.transfers) + '</td>';
            html += '<td class="text-end">' + formatCurrency(totals.revaluation) + '</td>';
            html += '<td class="text-end">' + formatCurrency(totals.closing_cost) + '</td>';
            html += '<td class="text-end">' + formatCurrency(totals.opening_accum_dep) + '</td>';
            html += '<td class="text-end">' + formatCurrency(totals.depreciation_charge) + '</td>';
            html += '<td class="text-end">' + formatCurrency(totals.disposal_dep_removed) + '</td>';
            html += '<td class="text-end">' + formatCurrency(totals.impairment) + '</td>';
            html += '<td class="text-end">' + formatCurrency(totals.closing_accum_dep) + '</td>';
            html += '<td class="text-end">' + formatCurrency(totals.closing_nbv) + '</td>';
            html += '</tr>';
        }

        $('#movement-tbody').html(html);
    }

    function calculateTotals(data) {
        const totals = {
            opening_cost: 0,
            additions: 0,
            disposals: 0,
            transfers: 0,
            revaluation: 0,
            closing_cost: 0,
            opening_accum_dep: 0,
            depreciation_charge: 0,
            disposal_dep_removed: 0,
            impairment: 0,
            closing_accum_dep: 0,
            closing_nbv: 0
        };

        data.forEach(function(category) {
            totals.opening_cost += parseFloat(category.opening_cost);
            totals.additions += parseFloat(category.additions);
            totals.disposals += parseFloat(category.disposals);
            totals.transfers += parseFloat(category.transfers);
            totals.revaluation += parseFloat(category.revaluation);
            totals.closing_cost += parseFloat(category.closing_cost);
            totals.opening_accum_dep += parseFloat(category.opening_accum_dep);
            totals.depreciation_charge += parseFloat(category.depreciation_charge);
            totals.disposal_dep_removed += parseFloat(category.disposal_dep_removed);
            totals.impairment += parseFloat(category.impairment);
            totals.closing_accum_dep += parseFloat(category.closing_accum_dep);
            totals.closing_nbv += parseFloat(category.closing_nbv);
        });

        return totals;
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
        const categoryId = $('#asset_category_id').val();

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
            'action': '{{ route('assets.reports.movement-schedule.export-excel') }}'
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
                'name': 'asset_category_id',
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
        const categoryId = $('#asset_category_id').val();

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
            'action': '{{ route('assets.reports.movement-schedule.export-pdf') }}'
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
                'name': 'asset_category_id',
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
