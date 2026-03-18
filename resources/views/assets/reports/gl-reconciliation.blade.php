@extends('layouts.main')

@section('title', 'GL Reconciliation Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'GL Reconciliation', 'url' => '#', 'icon' => 'bx bx-shuffle']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">GL Reconciliation Report</h4>
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
                                <label class="form-label">As of Date <span class="text-danger">*</span></label>
                                <input type="date" id="as_of_date" name="as_of_date" class="form-control" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select id="category_id" name="category_id" class="form-select select2-single">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
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
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-shuffle me-2"></i>GL Reconciliation - As Of <span id="report-date"></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="bx bx-info-circle me-1"></i>
                            <strong>Month-End Critical:</strong> This reconciliation must be performed before closing the period. Any variances must be investigated and resolved.
                        </div>

                        <!-- Reconciliation Status -->
                        <div class="row mb-4" id="reconciliation-status">
                            <div class="col-md-4">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted">Subledger Balance</h6>
                                        <h4 class="mb-0 text-primary" id="subledger-balance">TZS 0.00</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted">GL Balance</h6>
                                        <h4 class="mb-0 text-info" id="gl-balance">TZS 0.00</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card" id="variance-card">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted">Variance</h6>
                                        <h4 class="mb-0" id="variance-amount">TZS 0.00</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Reconciliation -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="reconciliation-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>GL Account Code</th>
                                        <th>GL Account Name</th>
                                        <th>Account Type</th>
                                        <th class="text-end">GL Balance</th>
                                        <th class="text-end">Subledger Balance</th>
                                        <th class="text-end">Difference</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="reconciliation-tbody">
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
        const asOfDate = $('#as_of_date').val();
        const categoryId = $('#category_id').val();

        if (!asOfDate) {
            Swal.fire('Error!', 'Please select a date.', 'error');
            return;
        }

        $.ajax({
            url: '{{ route('assets.reports.gl-reconciliation.data') }}',
            type: 'GET',
            data: {
                as_of_date: asOfDate,
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

        // Update summary cards
        $('#subledger-balance').text(formatCurrency(summary.total_subledger));
        $('#gl-balance').text(formatCurrency(summary.total_gl));
        $('#variance-amount').text(formatCurrency(summary.total_variance));

        // Update variance card color
        if (Math.abs(summary.total_variance) < 0.01) {
            $('#variance-card').removeClass('border-danger').addClass('border-success');
            $('#variance-amount').removeClass('text-danger').addClass('text-success');
        } else {
            $('#variance-card').removeClass('border-success').addClass('border-danger');
            $('#variance-amount').removeClass('text-success').addClass('text-danger');
        }

        data.forEach(function(account) {
            const isReconciled = Math.abs(account.difference) < 0.01;
            const statusBadge = isReconciled 
                ? '<span class="badge bg-success">Reconciled</span>' 
                : '<span class="badge bg-danger">Variance</span>';
            
            const diffClass = isReconciled ? 'text-success' : 'text-danger fw-bold';

            html += '<tr>';
            html += '<td>' + (account.gl_account_code || 'N/A') + '</td>';
            html += '<td>' + (account.gl_account_name || 'N/A') + '</td>';
            html += '<td>' + account.account_type + '</td>';
            html += '<td class="text-end">' + formatCurrency(account.gl_balance) + '</td>';
            html += '<td class="text-end">' + formatCurrency(account.subledger_balance) + '</td>';
            html += '<td class="text-end ' + diffClass + '">' + formatCurrency(account.difference) + '</td>';
            html += '<td class="text-center">' + statusBadge + '</td>';
            html += '</tr>';
        });

        // Add totals row
        const totalReconciled = Math.abs(summary.total_variance) < 0.01;
        const totalDiffClass = totalReconciled ? 'text-success' : 'text-danger';

        html += '<tr class="table-warning fw-bold">';
        html += '<td colspan="3">TOTAL</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_gl) + '</td>';
        html += '<td class="text-end">' + formatCurrency(summary.total_subledger) + '</td>';
        html += '<td class="text-end ' + totalDiffClass + '">' + formatCurrency(summary.total_variance) + '</td>';
        html += '<td class="text-center">' + (totalReconciled 
            ? '<span class="badge bg-success">Reconciled</span>' 
            : '<span class="badge bg-danger">Variance</span>') + '</td>';
        html += '</tr>';

        $('#reconciliation-tbody').html(html);
    }

    function formatCurrency(amount) {
        return 'TZS ' + parseFloat(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function exportToExcel() {
        const asOfDate = $('#as_of_date').val();
        const categoryId = $('#category_id').val();

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
            'action': '{{ route('assets.reports.gl-reconciliation.export-excel') }}'
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
                'name': 'category_id',
                'value': categoryId
            }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }

    function exportToPdf() {
        const asOfDate = $('#as_of_date').val();
        const categoryId = $('#category_id').val();

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
            'action': '{{ route('assets.reports.gl-reconciliation.export-pdf') }}'
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
                'name': 'category_id',
                'value': categoryId
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
