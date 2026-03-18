@extends('layouts.main')

@section('title', 'TRA Tax Depreciation Schedule')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'TRA Tax Depreciation Schedule', 'url' => '#', 'icon' => 'bx bx-calculator']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">TRA Tax Depreciation Schedule</h4>
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
                                <label class="form-label">Tax Year <span class="text-danger">*</span></label>
                                <input type="number" id="tax_year" name="tax_year" class="form-control" value="{{ $taxYear }}" min="2000" max="2100" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tax Class</label>
                                <select id="tax_class_id" name="tax_class_id" class="form-select select2-single">
                                    <option value="">All Classes</option>
                                    @foreach($taxClasses as $taxClass)
                                        <option value="{{ $taxClass->id }}" {{ $taxClassId == $taxClass->id ? 'selected' : '' }}>
                                            {{ $taxClass->class_code }} - {{ $taxClass->description }}
                                        </option>
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

        <!-- Report Results -->
        <div class="row" id="report-results" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-table me-2"></i>TRA Tax Depreciation Schedule - Year <span id="report-year"></span>
                        </h5>
                    </div>
                    <div class="card-body" id="report-content">
                        <!-- Report will be loaded here -->
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
        const taxYear = $('#tax_year').val();
        const taxClassId = $('#tax_class_id').val();

        if (!taxYear) {
            Swal.fire('Error!', 'Please select a tax year.', 'error');
            return;
        }

        $.ajax({
            url: '{{ route('assets.tax-depreciation.reports.tra-schedule.data') }}',
            type: 'GET',
            data: {
                tax_year: taxYear,
                tax_class_id: taxClassId
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderReport(response.data, taxYear);
                    $('#report-year').text(taxYear);
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

    function renderReport(data, taxYear) {
        let html = '';

        data.forEach(function(classData) {
            html += '<div class="mb-4">';
            html += '<h5 class="border-bottom pb-2">' + classData.tax_class.class_code + ' - ' + classData.tax_class.description + '</h5>';
            html += '<div class="table-responsive">';
            html += '<table class="table table-bordered table-sm">';
            html += '<thead class="table-light">';
            html += '<tr>';
            html += '<th>Asset Category</th><th>Tax Pool Class</th><th class="text-end">Opening Tax WDV</th>';
            html += '<th class="text-end">Additions</th><th class="text-end">Disposals</th>';
            html += '<th class="text-end">Tax Depreciation</th><th class="text-end">Closing Tax WDV</th>';
            html += '</tr></thead><tbody>';

            classData.categories.forEach(function(category) {
                html += '<tr>';
                html += '<td>' + (category.category ? category.category.name : 'N/A') + '</td>';
                html += '<td>' + classData.tax_class.class_code + '</td>';
                html += '<td class="text-end">' + formatCurrency(category.opening_wdv) + '</td>';
                html += '<td class="text-end">' + formatCurrency(category.additions) + '</td>';
                html += '<td class="text-end">' + formatCurrency(category.disposals) + '</td>';
                html += '<td class="text-end text-danger">' + formatCurrency(category.tax_depreciation) + '</td>';
                html += '<td class="text-end">' + formatCurrency(category.closing_wdv) + '</td>';
                html += '</tr>';
            });

            html += '<tr class="table-warning fw-bold">';
            html += '<td colspan="2"><strong>Class Total</strong></td>';
            html += '<td class="text-end">' + formatCurrency(classData.total_opening_wdv) + '</td>';
            html += '<td class="text-end">' + formatCurrency(classData.total_additions) + '</td>';
            html += '<td class="text-end">' + formatCurrency(classData.total_disposals) + '</td>';
            html += '<td class="text-end">' + formatCurrency(classData.total_tax_depreciation) + '</td>';
            html += '<td class="text-end">' + formatCurrency(classData.total_closing_wdv) + '</td>';
            html += '</tr>';

            html += '</tbody></table></div></div>';
        });

        $('#report-content').html(html);
    }

    function formatCurrency(amount) {
        return 'TZS ' + parseFloat(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function exportToExcel() {
        const taxYear = $('#tax_year').val();
        const taxClassId = $('#tax_class_id').val();

        if (!taxYear) {
            Swal.fire('Error!', 'Please select a tax year and generate the report first.', 'error');
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
            'action': '{{ route('assets.tax-depreciation.reports.tra-schedule.export-excel') }}'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'tax_year',
            'value': taxYear
        }));

        if (taxClassId) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'tax_class_id',
                'value': taxClassId
            }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }

    function exportToPdf() {
        const taxYear = $('#tax_year').val();
        const taxClassId = $('#tax_class_id').val();

        if (!taxYear) {
            Swal.fire('Error!', 'Please select a tax year and generate the report first.', 'error');
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
            'action': '{{ route('assets.tax-depreciation.reports.tra-schedule.export-pdf') }}'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'tax_year',
            'value': taxYear
        }));

        if (taxClassId) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'tax_class_id',
                'value': taxClassId
            }));
        }

        $('body').append(form);
        form.submit();
        form.remove();
    }

    // Allow exporting with Enter key on inputs
    $('#tax_year, #tax_class_id').on('keypress', function(e) {
        if (e.which === 13) {
            $('#generate_report').click();
        }
    });
});
</script>
@endpush

