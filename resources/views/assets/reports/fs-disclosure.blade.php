@extends('layouts.main')

@section('title', 'Fixed Asset Disclosure Note (IFRS)')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'FS Disclosure', 'url' => '#', 'icon' => 'bx bx-file-blank']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Fixed Asset Disclosure Note (IFRS Format)</h4>
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
                                <label class="form-label">From Date (Opening Balance)</label>
                                <input type="date" id="from_date" name="from_date" class="form-control" value="{{ \Carbon\Carbon::now()->startOfYear()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">To Date (Closing Balance)</label>
                                <input type="date" id="to_date" name="to_date" class="form-control" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Category</label>
                                <select id="category_id" name="category_id" class="form-select select2-single">
                                    <option value="">All Categories</option>
                                    @foreach($categories ?? [] as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
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
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-file-blank me-2"></i>Note X: Property, Plant and Equipment
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>IFRS Compliance:</strong> This disclosure note follows IAS 16 requirements for Property, Plant and Equipment.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="disclosure-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Opening Balance</th>
                                        <th class="text-end">Additions</th>
                                        <th class="text-end">Disposals</th>
                                        <th class="text-end">Revaluations</th>
                                        <th class="text-end">Depreciation</th>
                                        <th class="text-end">Closing Balance</th>
                                    </tr>
                                </thead>
                                <tbody id="disclosure-tbody">
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
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const categoryId = $('#category_id').val();

        if (!fromDate || !toDate) {
            Swal.fire('Error!', 'Please select both from and to dates.', 'error');
            return;
        }

        $.ajax({
            url: '{{ route('assets.reports.fs-disclosure.data') }}',
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
                    renderReport(response.data);
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

    function renderReport(data) {
        let html = '';
        let totalOpening = 0, totalAdditions = 0, totalDisposals = 0, totalRevaluations = 0, totalDepreciation = 0, totalClosing = 0;

        // Render category rows
        data.forEach(function(item) {
            html += '<tr>';
            html += '<td><strong>' + item.category + '</strong></td>';
            html += '<td class="text-end">' + formatCurrency(item.opening_balance) + '</td>';
            html += '<td class="text-end">' + formatCurrency(item.additions) + '</td>';
            html += '<td class="text-end">' + formatCurrency(item.disposals) + '</td>';
            html += '<td class="text-end">' + formatCurrency(item.revaluations) + '</td>';
            html += '<td class="text-end">' + formatCurrency(item.depreciation) + '</td>';
            html += '<td class="text-end"><strong>' + formatCurrency(item.closing_balance) + '</strong></td>';
            html += '</tr>';

            totalOpening += parseFloat(item.opening_balance);
            totalAdditions += parseFloat(item.additions);
            totalDisposals += parseFloat(item.disposals);
            totalRevaluations += parseFloat(item.revaluations);
            totalDepreciation += parseFloat(item.depreciation);
            totalClosing += parseFloat(item.closing_balance);
        });

        // Add totals row
        html += '<tr class="table-warning fw-bold">';
        html += '<td>TOTAL</td>';
        html += '<td class="text-end">' + formatCurrency(totalOpening) + '</td>';
        html += '<td class="text-end">' + formatCurrency(totalAdditions) + '</td>';
        html += '<td class="text-end">' + formatCurrency(totalDisposals) + '</td>';
        html += '<td class="text-end">' + formatCurrency(totalRevaluations) + '</td>';
        html += '<td class="text-end">' + formatCurrency(totalDepreciation) + '</td>';
        html += '<td class="text-end">' + formatCurrency(totalClosing) + '</td>';
        html += '</tr>';

        if (data.length === 0) {
            html = '<tr><td colspan="7" class="text-center text-muted">No data available.</td></tr>';
        }

        $('#disclosure-tbody').html(html);
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

        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const categoryId = $('#category_id').val();

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.fs-disclosure.export-excel') }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'from_date', 'value': fromDate }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'to_date', 'value': toDate }));
        if (categoryId) form.append($('<input>', { 'type': 'hidden', 'name': 'category_id', 'value': categoryId }));

        $('body').append(form);
        form.submit();
        form.remove();
    }

    function exportToPdf() {
        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const categoryId = $('#category_id').val();

        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.reports.fs-disclosure.export-pdf') }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'from_date', 'value': fromDate }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'to_date', 'value': toDate }));
        if (categoryId) form.append($('<input>', { 'type': 'hidden', 'name': 'category_id', 'value': categoryId }));

        $('body').append(form);
        form.submit();
        form.remove();
    }
});
</script>
@endpush
