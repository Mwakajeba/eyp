@extends('layouts.main')

@section('title', 'Physical Verification Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Physical Verification', 'url' => '#', 'icon' => 'bx bx-check-shield']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Physical Verification Report</h4>
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
                                    <option value="">All Categories</option>
                                    @foreach($categories ?? [] as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="disposed">Disposed</option>
                                    <option value="under_construction">Under Construction</option>
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
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-check-shield me-2"></i>Asset Physical Verification Report
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Summary Cards -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="card border-warning">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Total Assets to Verify</small>
                                        <h5 class="mb-0 text-warning" id="summary-count">0</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Note:</strong> This report lists all active assets that require physical verification. 
                            Update the actual verification status in the asset management module.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover" id="verification-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Asset Code</th>
                                        <th>Asset Name</th>
                                        <th>Location</th>
                                        <th>System Status</th>
                                        <th>Physical Status</th>
                                        <th>Variance</th>
                                        <th>Verified By</th>
                                        <th>Verification Date</th>
                                    </tr>
                                </thead>
                                <tbody id="verification-tbody">
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
            url: '{{ route('assets.reports.physical-verification.data') }}',
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
        $('#summary-count').text(summary.count);

        // Render verification rows
        data.forEach(function(item) {
            html += '<tr>';
            html += '<td>' + item.asset_code + '</td>';
            html += '<td>' + item.asset_name + '</td>';
            html += '<td>' + item.location + '</td>';
            html += '<td><span class="badge bg-success">' + item.system_status + '</span></td>';
            html += '<td><span class="badge bg-secondary">' + item.physical_status + '</span></td>';
            html += '<td>' + item.variance + '</td>';
            html += '<td>' + item.verified_by + '</td>';
            html += '<td>' + item.verification_date + '</td>';
            html += '</tr>';
        });

        if (data.length === 0) {
            html = '<tr><td colspan="8" class="text-center text-muted">No assets found for verification.</td></tr>';
        }

        $('#verification-tbody').html(html);
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
            'action': '{{ route('assets.reports.physical-verification.export-excel') }}'
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
            'action': '{{ route('assets.reports.physical-verification.export-pdf') }}'
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
