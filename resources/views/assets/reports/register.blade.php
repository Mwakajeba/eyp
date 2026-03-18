@extends('layouts.main')

@section('title', 'Fixed Asset Register')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Fixed Asset Register', 'url' => '#', 'icon' => 'bx bx-book']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Fixed Asset Register (Master Report)</h4>
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
                                <label class="form-label">As of Date <span class="text-danger">*</span></label>
                                <input type="date" id="as_of_date" name="as_of_date" class="form-control" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
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
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="active" selected>Active</option>
                                    <option value="disposed">Disposed</option>
                                    <option value="under_repair">Under Repair</option>
                                    <option value="idle">Idle</option>
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
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-book me-2"></i>Fixed Asset Register - As Of <span id="report-date"></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-1"></i>
                            <strong>Audit Document:</strong> This report must reconcile to General Ledger accounts.
                        </div>
                        
                        <!-- Summary Totals -->
                        <div class="row mb-3" id="summary-section">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <small class="text-muted">Total Cost</small>
                                        <h5 class="mb-0" id="total-cost">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <small class="text-muted">Accumulated Depreciation</small>
                                        <h5 class="mb-0 text-danger" id="total-accum-dep">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <small class="text-muted">Net Book Value</small>
                                        <h5 class="mb-0 text-success" id="total-nbv">TZS 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <small class="text-muted">Total Assets</small>
                                        <h5 class="mb-0" id="total-assets">0</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover" id="register-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Asset Code</th>
                                        <th>Asset Name</th>
                                        <th>Category</th>
                                        <th>Location</th>
                                        <th>Custodian</th>
                                        <th>Serial No</th>
                                        <th>Purchase Date</th>
                                        <th>Cap. Date</th>
                                        <th class="text-end">Cost</th>
                                        <th>Useful Life</th>
                                        <th>Method</th>
                                        <th class="text-end">Accum. Dep</th>
                                        <th class="text-end">Impairment</th>
                                        <th class="text-end">Carrying Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="register-tbody">
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
        const asOfDate = $('#as_of_date').val();
        const categoryId = $('#asset_category_id').val();
        const status = $('#status').val();

        if (!asOfDate) {
            Swal.fire('Error!', 'Please select a date.', 'error');
            return;
        }

        $.ajax({
            url: '{{ route('assets.reports.register.data') }}',
            type: 'GET',
            data: {
                as_of_date: asOfDate,
                asset_category_id: categoryId,
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
                    renderReport(response.data, response.summary, asOfDate);
                    $('#report-date').text(asOfDate);
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

    function renderReport(data, summary, asOfDate) {
        let html = '';

        // Update summary totals
        $('#total-cost').text(formatCurrency(summary.total_cost));
        $('#total-accum-dep').text(formatCurrency(summary.total_accumulated_dep));
        $('#total-nbv').text(formatCurrency(summary.total_nbv));
        $('#total-assets').text(summary.count);

        data.forEach(function(asset) {
            html += '<tr>';
            html += '<td>' + (asset.code || 'N/A') + '</td>';
            html += '<td>' + (asset.name || 'N/A') + '</td>';
            html += '<td>' + (asset.category ? asset.category.name : 'N/A') + '</td>';
            html += '<td>' + (asset.location || '-') + '</td>';
            html += '<td>' + (asset.custodian ? asset.custodian.name : '-') + '</td>';
            html += '<td>' + (asset.serial_number || '-') + '</td>';
            html += '<td>' + (asset.purchase_date || '-') + '</td>';
            html += '<td>' + (asset.capitalization_date || '-') + '</td>';
            html += '<td class="text-end">' + formatCurrency(asset.purchase_cost) + '</td>';
            html += '<td>' + (asset.useful_life ? asset.useful_life + ' yrs' : '-') + '</td>';
            html += '<td>' + (asset.depreciation_method_display || '-') + '</td>';
            html += '<td class="text-end text-danger">' + formatCurrency(asset.accumulated_depreciation) + '</td>';
            html += '<td class="text-end">' + formatCurrency(asset.impairment_amount) + '</td>';
            html += '<td class="text-end fw-bold">' + formatCurrency(asset.carrying_amount) + '</td>';
            html += '<td><span class="badge ' + getStatusBadge(asset.status) + '">' + (asset.status_display || 'N/A') + '</span></td>';
            html += '</tr>';
        });

        $('#register-tbody').html(html);
    }

    function getStatusBadge(status) {
        const badges = {
            'active': 'bg-success',
            'disposed': 'bg-secondary',
            'under_repair': 'bg-warning',
            'idle': 'bg-info'
        };
        return badges[status] || 'bg-secondary';
    }

    function formatCurrency(amount) {
        return 'TZS ' + parseFloat(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function exportToExcel() {
        const asOfDate = $('#as_of_date').val();
        const categoryId = $('#asset_category_id').val();
        const status = $('#status').val();

        if (!asOfDate) {
            Swal.fire('Error!', 'Please select a date and generate the report first.', 'error');
            return;
        }

        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.register.export-excel') }}'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'as_of_date',
            'value': asOfDate
        }));

        if (categoryId) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'asset_category_id',
                'value': categoryId
            }));
        }

        if (status) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'status',
                'value': status
            }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }

    function exportToPdf() {
        const asOfDate = $('#as_of_date').val();
        const categoryId = $('#asset_category_id').val();
        const status = $('#status').val();

        if (!asOfDate) {
            Swal.fire('Error!', 'Please select a date and generate the report first.', 'error');
            return;
        }

        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.register.export-pdf') }}'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'as_of_date',
            'value': asOfDate
        }));

        if (categoryId) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'asset_category_id',
                'value': categoryId
            }));
        }

        if (status) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'status',
                'value': status
            }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }

    $('#as_of_date').on('keypress', function(e) {
        if (e.which === 13) {
            $('#generate_report').click();
        }
    });
});
</script>
@endpush
