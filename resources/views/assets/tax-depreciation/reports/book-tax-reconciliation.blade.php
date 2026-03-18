@extends('layouts.main')

@section('title', 'Book vs Tax Reconciliation')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Book vs Tax Reconciliation', 'url' => '#', 'icon' => 'bx bx-line-chart']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Book vs Tax Reconciliation Report</h4>
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
                                <label class="form-label">As Of Date <span class="text-danger">*</span></label>
                                <input type="date" id="as_of_date" name="as_of_date" class="form-control" value="{{ $asOfDate }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Deferred Tax Rate (%) <span class="text-danger">*</span></label>
                                <input type="number" id="tax_rate" name="tax_rate" class="form-control" value="30" min="0" max="100" step="0.01" required>
                            </div>
                            <div class="col-md-5 d-flex align-items-end">
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
                            <i class="bx bx-line-chart me-2"></i>Book vs Tax Reconciliation - As Of <span id="report-date"></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="reconciliation-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Asset Code</th>
                                        <th>Asset Name</th>
                                        <th class="text-end">Book Carrying Amount</th>
                                        <th class="text-end">Tax Base</th>
                                        <th class="text-end">Temporary Difference</th>
                                        <th class="text-end">Deferred Tax Rate (%)</th>
                                        <th class="text-end">Deferred Tax Asset/Liability</th>
                                    </tr>
                                </thead>
                                <tbody id="reconciliation-tbody">
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
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
        const asOfDate = $('#as_of_date').val();
        const taxRate = $('#tax_rate').val();

        if (!asOfDate) {
            Swal.fire('Error!', 'Please select a date.', 'error');
            return;
        }

        if (!taxRate || taxRate <= 0) {
            Swal.fire('Error!', 'Please enter a valid tax rate.', 'error');
            return;
        }

        $.ajax({
            url: '{{ route('assets.tax-depreciation.reports.book-tax-reconciliation.data') }}',
            type: 'GET',
            data: {
                as_of_date: asOfDate
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderReport(response.data, asOfDate, taxRate);
                    $('#report-date').text(response.as_of_date);
                    $('#report-results').show();
                    $('#export-buttons').show();
                } else {
                    Swal.fire('Error!', 'Failed to generate report.', 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error!', 'An error occurred while generating the report.', 'error');
            }
        });
    }

    function renderReport(data, asOfDate, taxRate) {
        let html = '';
        const taxRateDecimal = parseFloat(taxRate) / 100;
        
        let totals = {
            book_nbv: 0,
            tax_wdv: 0,
            temporary_difference: 0,
            deferred_tax: 0
        };

        data.forEach(function(item) {
            const bookCarryingAmount = parseFloat(item.book_nbv);
            const taxBase = parseFloat(item.tax_wdv);
            const temporaryDifference = bookCarryingAmount - taxBase;
            const deferredTax = temporaryDifference * taxRateDecimal;
            
            html += '<tr>';
            html += '<td>' + (item.asset.code || 'N/A') + '</td>';
            html += '<td>' + (item.asset.name || 'N/A') + '</td>';
            html += '<td class="text-end">' + formatCurrency(bookCarryingAmount) + '</td>';
            html += '<td class="text-end">' + formatCurrency(taxBase) + '</td>';
            
            const tempDiffClass = temporaryDifference >= 0 ? 'text-danger' : 'text-success';
            html += '<td class="text-end ' + tempDiffClass + '">' + formatCurrency(temporaryDifference) + '</td>';
            
            html += '<td class="text-end">' + parseFloat(taxRate).toFixed(2) + '%</td>';
            
            const deferredTaxClass = deferredTax >= 0 ? 'text-danger' : 'text-success';
            const deferredTaxLabel = deferredTax >= 0 ? ' (DTL)' : ' (DTA)';
            html += '<td class="text-end ' + deferredTaxClass + '">' + formatCurrency(Math.abs(deferredTax)) + deferredTaxLabel + '</td>';
            html += '</tr>';

            totals.book_nbv += bookCarryingAmount;
            totals.tax_wdv += taxBase;
            totals.temporary_difference += temporaryDifference;
            totals.deferred_tax += deferredTax;
        });

        // Add totals row
        html += '<tr class="table-warning fw-bold">';
        html += '<td colspan="2">Total</td>';
        html += '<td class="text-end">' + formatCurrency(totals.book_nbv) + '</td>';
        html += '<td class="text-end">' + formatCurrency(totals.tax_wdv) + '</td>';
        
        const tempDiffTotalClass = totals.temporary_difference >= 0 ? 'text-danger' : 'text-success';
        html += '<td class="text-end ' + tempDiffTotalClass + '">' + formatCurrency(totals.temporary_difference) + '</td>';
        
        html += '<td class="text-end">' + parseFloat(taxRate).toFixed(2) + '%</td>';
        
        const deferredTaxTotalClass = totals.deferred_tax >= 0 ? 'text-danger' : 'text-success';
        const deferredTaxTotalLabel = totals.deferred_tax >= 0 ? ' (DTL)' : ' (DTA)';
        html += '<td class="text-end ' + deferredTaxTotalClass + '">' + formatCurrency(Math.abs(totals.deferred_tax)) + deferredTaxTotalLabel + '</td>';
        html += '</tr>';

        $('#reconciliation-tbody').html(html);
    }

    function formatCurrency(amount) {
        return 'TZS ' + parseFloat(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function exportToExcel() {
        const asOfDate = $('#as_of_date').val();
        const taxRate = $('#tax_rate').val();

        if (!asOfDate) {
            Swal.fire('Error!', 'Please select a date and generate the report first.', 'error');
            return;
        }

        // Check if report has been generated
        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        // Create a form and submit it
        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.tax-depreciation.reports.book-tax-reconciliation.export-excel') }}'
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

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'tax_rate',
            'value': taxRate
        }));

        $('body').append(form);
        form.submit();
        form.remove();
    }

    function exportToPdf() {
        const asOfDate = $('#as_of_date').val();
        const taxRate = $('#tax_rate').val();

        if (!asOfDate) {
            Swal.fire('Error!', 'Please select a date and generate the report first.', 'error');
            return;
        }

        // Check if report has been generated
        if ($('#report-results').is(':hidden')) {
            Swal.fire('Error!', 'Please generate the report first before exporting.', 'error');
            return;
        }

        // Create a form and submit it
        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route('assets.tax-depreciation.reports.book-tax-reconciliation.export-pdf') }}'
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

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'tax_rate',
            'value': taxRate
        }));

        $('body').append(form);
        form.submit();
        form.remove();
    }

    // Allow generating report with Enter key
    $('#as_of_date').on('keypress', function(e) {
        if (e.which === 13) {
            $('#generate_report').click();
        }
    });
});
</script>
@endpush

