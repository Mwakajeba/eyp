@extends('layouts.main')

@section('title', 'Compliance & Safety - Fleet Management')

@push('styles')
<style>
    .widgets-icons-2 { width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background-color: #ededed; font-size: 27px; }
    .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0a58ca) !important; }
    .bg-gradient-success { background: linear-gradient(45deg, #198754, #146c43) !important; }
    .bg-gradient-danger { background: linear-gradient(45deg, #dc3545, #bb2d3b) !important; }
    .bg-gradient-warning { background: linear-gradient(45deg, #ffc107, #ffb300) !important; }
    .card { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
    .card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
    .radius-10 { border-radius: 10px; }
    .border-start { border-left-width: 3px !important; }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Compliance & Safety', 'url' => '#', 'icon' => 'bx bx-shield-check']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-shield-check me-2"></i>Compliance & Safety</h5>
                                <p class="mb-0 text-muted">Manage insurance, licenses, inspections, and safety compliance records</p>
                            </div>
                            <div>
                                <a href="{{ route('fleet.compliance.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Add Compliance Record
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Records</p>
                                <h4 class="my-1 text-primary">{{ number_format($totalRecords) }}</h4>
                                <p class="mb-0 font-13"><span class="text-primary"><i class="bx bx-file align-middle"></i> All compliance</span></p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-file"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Active</p>
                                <h4 class="my-1 text-success">{{ number_format($activeRecords) }}</h4>
                                <p class="mb-0 font-13"><span class="text-success"><i class="bx bx-check-circle align-middle"></i> Valid</span></p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Expiring Soon</p>
                                <h4 class="my-1 text-warning">{{ number_format($expiringRecords) }}</h4>
                                <p class="mb-0 font-13"><span class="text-warning"><i class="bx bx-time align-middle"></i> Renew soon</span></p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-time"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Expired</p>
                                <h4 class="my-1 text-danger">{{ number_format($expiredRecords) }}</h4>
                                <p class="mb-0 font-13"><span class="text-danger"><i class="bx bx-error-circle align-middle"></i> Action needed</span></p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-danger text-white ms-auto">
                                <i class="bx bx-error-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label small">Compliance Type</label>
                        <select id="filter-compliance-type" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="vehicle_insurance">Vehicle Insurance</option>
                            <option value="driver_license">Driver License</option>
                            <option value="vehicle_inspection">Vehicle Inspection</option>
                            <option value="safety_certification">Safety Certification</option>
                            <option value="registration">Registration</option>
                            <option value="permit">Permit</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select id="filter-status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="pending_renewal">Pending Renewal</option>
                            <option value="expired">Expired</option>
                            <option value="renewed">Renewed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Compliance Status</label>
                        <select id="filter-compliance-status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="compliant">Compliant</option>
                            <option value="warning">Warning</option>
                            <option value="non_compliant">Non-Compliant</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-filters">
                            <i class="bx bx-x me-1"></i>Clear Filters
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="compliance-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Record #</th>
                                <th>Type</th>
                                <th>Entity</th>
                                <th>Document #</th>
                                <th>Issuer</th>
                                <th>Issue Date</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Compliance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #000;
    }
    .btn-warning:hover {
        background-color: #e0a800;
        border-color: #e0a800;
        color: #000;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const complianceTable = $('#compliance-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("fleet.compliance.data") }}',
            data: function(d) {
                d.compliance_type = $('#filter-compliance-type').val();
                d.status = $('#filter-status').val();
                d.compliance_status = $('#filter-compliance-status').val();
            }
        },
        columns: [
            {
                data: null,
                name: 'serial',
                orderable: false,
                searchable: false,
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'record_number', name: 'record_number' },
            { data: 'type_display', name: 'compliance_type', orderable: false },
            { data: 'entity_display', name: 'entity_display', orderable: false },
            { data: 'document_number', name: 'document_number' },
            { data: 'issuer_name', name: 'issuer_name' },
            { data: 'issue_date', name: 'issue_date' },
            { data: 'expiry_display', name: 'expiry_date', orderable: false },
            { data: 'status_display', name: 'status', orderable: false },
            { data: 'compliance_status_display', name: 'compliance_status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[7, 'asc']], // Sort by expiry date ascending
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="text-center"><i class="bx bx-loader-alt bx-spin bx-lg"></i> Loading compliance records...</div>'
        }
    });

    // Filter event handlers
    $('#filter-compliance-type, #filter-status, #filter-compliance-status').on('change', function() {
        complianceTable.ajax.reload();
    });

    // Clear filters
    $('#clear-filters').on('click', function() {
        $('#filter-compliance-type, #filter-status, #filter-compliance-status').val('');
        complianceTable.ajax.reload();
    });
});
</script>
@endpush
