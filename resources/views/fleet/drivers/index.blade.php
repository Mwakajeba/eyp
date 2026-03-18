@extends('layouts.main')

@section('title', 'Driver Master - Fleet Management')

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0a58ca) !important; }
    .bg-gradient-success { background: linear-gradient(45deg, #198754, #146c43) !important; }
    .bg-gradient-info { background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important; }
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
            ['label' => 'Driver Master', 'url' => '#', 'icon' => 'bx bx-user']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-user me-2"></i>Driver Master</h5>
                                <p class="mb-0 text-muted">Manage drivers, licenses, assignments, and compliance records</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-info" onclick="window.location.href='{{ route('fleet.drivers.sample.download') }}'">
                                    <i class="bx bx-download me-1"></i>Download Sample
                                </button>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#driverImportModal">
                                    <i class="bx bx-import me-1"></i>Import Drivers
                                </button>
                                <a href="{{ route('fleet.drivers.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Add Driver
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
                                <p class="mb-0 text-secondary">Total Drivers</p>
                                <h4 class="my-1 text-primary">{{ number_format($totalDrivers) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-user align-middle"></i> All drivers</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-user"></i>
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
                                <h4 class="my-1 text-success">{{ number_format($activeDrivers) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Working</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Assigned</p>
                                <h4 class="my-1 text-info">{{ number_format($assignedDrivers) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-car align-middle"></i> With vehicle</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-car"></i>
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
                                <p class="mb-0 text-secondary">Available</p>
                                <h4 class="my-1 text-warning">{{ number_format($availableDrivers) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-time align-middle"></i> Unassigned</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-time"></i>
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
                        <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select id="filter-status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Employment Type</label>
                        <select id="filter-employment-type" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="employee">Employee</option>
                            <option value="contractor">Contractor</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">License Status</label>
                        <select id="filter-license-valid" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="1">Valid</option>
                            <option value="0">Expired/Expiring</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-filters">
                            <i class="bx bx-x me-1"></i>Clear Filters
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="drivers-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Driver Code</th>
                                <th>Full Name</th>
                                <th>License Number</th>
                                <th>License Class</th>
                                <th>License Expiry</th>
                                <th>License Status</th>
                                <th>Employment Type</th>
                                <th>Assigned Vehicle</th>
                                <th>Assigned Card</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Drivers Modal -->
<div class="modal fade" id="driverImportModal" tabindex="-1" aria-labelledby="driverImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="driverImportModalLabel"><i class="bx bx-import me-2"></i>Import Drivers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="driverImportForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Import File <span class="text-danger">*</span></label>
                        <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">Supported: Excel (.xlsx, .xls) or CSV. Max 10MB.</div>
                    </div>
                    <div class="alert alert-info mb-0">
                        <strong>Instructions:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Download the sample template first — it has the correct columns and dropdowns.</li>
                            <li><strong>Required columns (same as Add Driver form):</strong> full_name, license_number, license_expiry_date, employment_type, status</li>
                            <li>Optional: license_class, license_issuing_authority, phone_number, email, address, emergency_contact_*</li>
                            <li>employment_type: employee or contractor. status: active, inactive, suspended, or terminated</li>
                            <li>Dates in YYYY-MM-DD. Driver codes and user accounts are auto-created.</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bx bx-upload me-1"></i>Import Drivers</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Driver to Vehicle Modal -->
<div class="modal fade" id="assignVehicleModal" tabindex="-1" aria-labelledby="assignVehicleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignVehicleModalLabel"><i class="bx bx-car me-2"></i>Assign Driver to Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignVehicleForm">
                @csrf
                <div class="modal-body">
                    <p class="mb-3"><strong>Driver:</strong> <span id="assign-driver-name"></span></p>
                    <input type="hidden" name="driver_id" id="assign-driver-id">
                    <div class="mb-3">
                        <label class="form-label">Vehicle <span class="text-muted">(leave empty to unassign)</span></label>
                        <select name="assigned_vehicle_id" id="assign-vehicle-select" class="form-select">
                            <option value="">— Not Assigned —</option>
                            @foreach($vehiclesForAssign ?? [] as $v)
                                <option value="{{ $v->id }}" data-reg="{{ $v->registration_number ?? 'N/A' }}">
                                    {{ $v->name }} ({{ $v->registration_number ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Assignment Start Date</label>
                            <input type="date" name="assignment_start_date" id="assign-start-date" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Assignment End Date</label>
                            <input type="date" name="assignment_end_date" id="assign-end-date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bx bx-check me-1"></i>Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Card Modal -->
<div class="modal fade" id="assignCardModal" tabindex="-1" aria-labelledby="assignCardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignCardModalLabel"><i class="bx bx-credit-card me-2"></i>Assign Fuel Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignCardForm">
                @csrf
                <div class="modal-body">
                    <p class="mb-3"><strong>Driver:</strong> <span id="assign-card-driver-name"></span></p>
                    <input type="hidden" name="driver_id" id="assign-card-driver-id">
                    <div class="mb-3">
                        <label class="form-label">Card (Bank account with nature = Card)</label>
                        <select name="fuel_card_bank_account_id" id="assign-card-select" class="form-select">
                            <option value="">— No card —</option>
                        </select>
                        <small class="text-muted">Only card accounts from Accounting > Bank Accounts (nature = Card) are listed.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="bx bx-credit-card me-1"></i>Assign Card</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .badge {
        font-size: 0.75rem;
        padding: 0.5em 0.75em;
    }

    .table th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
    }

    .card-header {
        border-radius: 0.375rem 0.375rem 0 0 !important;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    const driversTable = $('#drivers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("fleet.drivers.data") }}',
            data: function(d) {
                d.status = $('#filter-status').val();
                d.employment_type = $('#filter-employment-type').val();
                d.license_valid = $('#filter-license-valid').val();
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
            { data: 'driver_code', name: 'driver_code' },
            { data: 'full_name', name: 'full_name' },
            { data: 'license_number', name: 'license_number' },
            { data: 'license_class', name: 'license_class' },
            { data: 'license_expiry_date', name: 'license_expiry_date' },
            { data: 'license_status', name: 'license_status', orderable: false, searchable: false },
            { data: 'employment_type', name: 'employment_type' },
            { data: 'assigned_vehicle_display', name: 'assigned_vehicle_display', orderable: false, searchable: false },
            { data: 'assigned_card_display', name: 'assigned_card_display', orderable: false, searchable: false },
            { data: 'status_display', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[2, 'asc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="text-center"><i class="bx bx-loader-alt bx-spin bx-lg"></i> Loading drivers...</div>'
        }
    });

    // Filter event handlers
    $('#filter-status, #filter-employment-type, #filter-license-valid').on('change', function() {
        driversTable.ajax.reload();
    });

    // Clear filters
    $('#clear-filters').on('click', function() {
        $('#filter-status, #filter-employment-type, #filter-license-valid').val('');
        driversTable.ajax.reload();
    });

    // Import form
    $('#driverImportForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        var $btn = $('#driverImportForm button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Importing...');
        $.ajax({
            url: '{{ route("fleet.drivers.import") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                $('#driverImportModal').modal('hide');
                $('#driverImportForm')[0].reset();
                var detail = response.message || 'Import completed.';
                if (response.errors && response.errors.length > 0) {
                    detail += '<br><br><strong>Errors:</strong><ul class="text-start mb-0 mt-2">';
                    response.errors.forEach(function(err) { detail += '<li>' + err + '</li>'; });
                    detail += '</ul>';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: (response.imported || 0) > 0 ? (response.errors && response.errors.length ? 'warning' : 'success') : 'info',
                        title: 'Import completed',
                        html: detail,
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert(detail.replace(/<[^>]*>/g, ' '));
                }
                driversTable.ajax.reload();
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Import failed.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Import failed', text: msg });
                } else {
                    alert(msg);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-upload me-1"></i>Import Drivers');
            }
        });
    });

    // Assign driver to vehicle: open modal
    $(document).on('click', '.assign-driver-to-vehicle', function() {
        var driverId = $(this).data('driver-id');
        var driverName = $(this).data('driver-name');
        var vehicleId = $(this).data('vehicle-id') || '';
        var start = $(this).data('start') || '';
        var end = $(this).data('end') || '';
        $('#assign-driver-id').val(driverId);
        $('#assign-driver-name').text(driverName);
        $('#assign-vehicle-select').val(vehicleId);
        $('#assign-start-date').val(start);
        $('#assign-end-date').val(end);
        $('#assignVehicleModal').modal('show');
    });

    // Reset form when modal is hidden
    $('#assignVehicleModal').on('hidden.bs.modal', function() {
        $('#assignVehicleForm')[0].reset();
        $('#assignVehicleForm button[type="submit"]').prop('disabled', false).html('<i class="bx bx-check me-1"></i>Assign');
    });

    // Assign vehicle form submit
    $('#assignVehicleForm').on('submit', function(e) {
        e.preventDefault();
        var driverId = $('#assign-driver-id').val();
        if (!driverId) return;
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        
        var payload = {
            _token: '{{ csrf_token() }}',
            assigned_vehicle_id: $('#assign-vehicle-select').val() || null,
            assignment_start_date: $('#assign-start-date').val() || null,
            assignment_end_date: $('#assign-end-date').val() || null
        };
        
        $.ajax({
            url: '{{ url("/fleet/drivers") }}/' + driverId + '/assign-vehicle',
            type: 'POST',
            data: payload,
            beforeSend: function() {
                $submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Assigning...');
            },
            success: function(response) {
                $('#assignVehicleModal').modal('hide');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Success', text: response.message || 'Assignment updated.' });
                } else {
                    alert(response.message || 'Assignment updated.');
                }
                driversTable.ajax.reload(null, false);
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to update assignment.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                } else {
                    alert(msg);
                }
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Assign');
            }
        });
    });

    // Assign card: open modal and load card accounts
    $(document).on('click', '.assign-driver-card', function() {
        var driverId = $(this).data('driver-id');
        var driverName = $(this).data('driver-name');
        var cardId = $(this).data('card-id') || '';
        $('#assign-card-driver-id').val(driverId);
        $('#assign-card-driver-name').text(driverName);
        var $sel = $('#assign-card-select');
        $sel.html('<option value="">— No card —</option>');
        $.get('{{ route("fleet.drivers.card-accounts") }}', function(response) {
            if (response.success && response.data && response.data.length) {
                response.data.forEach(function(acc) {
                    $sel.append($('<option></option>').attr('value', acc.id).text(acc.name + (acc.account_number ? ' - ' + acc.account_number : '')));
                });
                $sel.val(cardId);
            }
        });
        $('#assignCardModal').modal('show');
    });

    $('#assignCardForm').on('submit', function(e) {
        e.preventDefault();
        var driverId = $('#assign-card-driver-id').val();
        if (!driverId) return;
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        $.ajax({
            url: '{{ url("/fleet/drivers") }}/' + driverId + '/assign-card',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                fuel_card_bank_account_id: $('#assign-card-select').val() || null
            },
            beforeSend: function() {
                $submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Assigning...');
            },
            success: function(response) {
                $('#assignCardModal').modal('hide');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Success', text: response.message || 'Card assignment updated.' });
                } else {
                    alert(response.message || 'Card assignment updated.');
                }
                driversTable.ajax.reload(null, false);
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to update card assignment.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                } else {
                    alert(msg);
                }
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html('<i class="bx bx-credit-card me-1"></i>Assign Card');
            }
        });
    });

    // Change driver status
    $(document).on('click', '.change-driver-status', function(e) {
        e.preventDefault();
        const driverId = $(this).data('driver-id');
        const status = $(this).data('status');
        const table = driversTable;
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Change Driver Status?',
                text: 'Are you sure you want to change the status to ' + status.replace('_', ' ').toUpperCase() + '?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Change Status',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ url("/fleet/drivers") }}/' + driverId + '/change-status',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            status: status
                        },
                        success: function(response) {
                            Swal.fire('Success!', response.message, 'success');
                            table.ajax.reload(null, false);
                        },
                        error: function(xhr) {
                            Swal.fire('Error!', 'Failed to change status', 'error');
                        }
                    });
                }
            });
        } else {
            if (confirm('Are you sure you want to change the status to ' + status.replace('_', ' ').toUpperCase() + '?')) {
                $.ajax({
                    url: '{{ url("/fleet/drivers") }}/' + driverId + '/change-status',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        status: status
                    },
                    success: function(response) {
                        alert(response.message);
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        alert('Failed to change status');
                    }
                });
            }
        }
    });
});
</script>
@endpush
