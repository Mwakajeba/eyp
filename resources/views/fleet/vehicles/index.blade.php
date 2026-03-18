@extends('layouts.main')

@section('title', 'Vehicle Master - Fleet Management')

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
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .table-light {
        background-color: #f8f9fa;
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Vehicle Master', 'url' => '#', 'icon' => 'bx bx-car']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-car me-2"></i>Vehicle Master</h5>
                                <p class="mb-0 text-muted">Master list of fleet vehicles and their specifications</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-info" onclick="downloadSampleTemplate()">
                                    <i class="bx bx-download me-1"></i> Download Sample
                                </button>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#vehicleImportModal">
                                    <i class="bx bx-import me-1"></i> Import Vehicles
                                </button>
                                <a href="{{ route('fleet.vehicles.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Add Vehicle
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
                                <p class="mb-0 text-secondary">Total Vehicles</p>
                                <h4 class="my-1 text-primary">{{ number_format($totalVehicles) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-car align-middle"></i> Fleet size</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-car"></i>
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
                                <p class="mb-0 text-secondary">Total Fleet Cost</p>
                                <h4 class="my-1 text-success">TZS {{ number_format($totalCost, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-dollar align-middle"></i> Asset value</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-dollar"></i>
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
                                <p class="mb-0 text-secondary">Available</p>
                                <h4 class="my-1 text-info">{{ number_format($availableVehicles) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-check-circle align-middle"></i> Ready to use</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
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
                                <p class="mb-0 text-secondary">In Repair</p>
                                <h4 class="my-1 text-warning">{{ number_format($inRepairVehicles) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-wrench align-middle"></i> Under maintenance</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-wrench"></i>
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
                        <label class="form-label small">HFS Status</label>
                        <select id="filter-hfs-status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="none">Not HFS</option>
                            <option value="pending">Pending</option>
                            <option value="classified">Classified</option>
                            <option value="sold">Sold</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Depreciation</label>
                        <select id="filter-depreciation" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="0">Active</option>
                            <option value="1">Stopped</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Operational Status</label>
                        <select id="filter-operational-status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="available">Available</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_repair">In Repair</option>
                            <option value="retired">Retired</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-filters">
                            <i class="bx bx-x me-1"></i>Clear Filters
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="vehicles-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Vehicle</th>
                                <th>Registration</th>
                                <th>Fuel Type</th>
                                <th>Capacity</th>
                                <th>Operational Status</th>
                                <th>Cost</th>
                                <th>Status</th>
                                <th>HFS Status</th>
                                <th>Depreciation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <div class="modal fade" id="vehicleImportModal" tabindex="-1" aria-labelledby="vehicleImportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="vehicleImportModalLabel">
                            <i class="bx bx-import me-2"></i>Import Vehicles
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="vehicleImportForm" enctype="multipart/form-data" action="{{ route('fleet.vehicles.import') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">Import File <span class="text-danger">*</span></label>
                                    <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                    <div class="form-text">Supported formats: Excel (.xlsx, .xls) or CSV</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <strong>Import Instructions:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li><strong>Download the sample template first</strong> - it contains dropdown lists and detailed instructions</li>
                                            <li><strong>Required columns:</strong> name, registration_number</li>
                                            <li><strong>Registration numbers must be unique</strong> - duplicate vehicles will be skipped</li>
                                            <li>All imported vehicles will be automatically categorized as "Motor Vehicles"</li>
                                            <li>The Excel template includes dropdown validations for easy data entry</li>
                                            <li>Dates should be in YYYY-MM-DD format</li>
                                            <li>Asset codes will be auto-generated if not provided</li>
                                        </ul>
                                    </div>
                                    <div class="alert alert-success">
                                        <strong>Excel Template Features:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>✅ Dropdown lists for fuel_type, ownership_type, and operational_status</li>
                                            <li>✅ Proper column formatting and data validation</li>
                                            <li>✅ Sample data and detailed instructions sheet</li>
                                            <li>✅ Date format validation and comments</li>
                                            <li>✅ Number formatting for costs and capacities</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-upload me-1"></i>Import Vehicles
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
// Function to download sample template
function downloadSampleTemplate() {
    // Redirect to the sample download route
    window.location.href = '{{ route("fleet.vehicles.sample.download") }}';
}

$(document).ready(function() {
    // Initialize DataTable
    const vehiclesTable = $('#vehicles-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("fleet.vehicles.data") }}',
            data: function(d) {
                d.hfs_status = $('#filter-hfs-status').val();
                d.depreciation_stopped = $('#filter-depreciation').val();
                d.operational_status = $('#filter-operational-status').val();
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
            { data: 'name', name: 'assets.name' },
            { data: 'registration_number', name: 'assets.registration_number', orderable: false },
            { data: 'fuel_type', name: 'assets.fuel_type', orderable: false },
            {
                data: 'capacity_tons',
                name: 'assets.capacity_tons',
                orderable: false,
                render: function(data, type, row) {
                    let capacity = '';
                    if (row.capacity_tons) capacity += row.capacity_tons + ' tons ';
                    if (row.capacity_volume) capacity += row.capacity_volume + ' L/km ';
                    if (row.capacity_passengers) capacity += row.capacity_passengers + ' pax ';
                    return capacity || '-';
                }
            },
            { data: 'operational_status_display', name: 'assets.operational_status', orderable: false },
            {
                data: 'purchase_cost',
                name: 'assets.purchase_cost',
                render: function(data) {
                    return new Intl.NumberFormat('en-TZ', {
                        style: 'currency',
                        currency: 'TZS'
                    }).format(data || 0);
                }
            },
            {
                data: 'status',
                name: 'assets.status',
                render: function(data) {
                    const statusColors = {
                        'active': 'success',
                        'inactive': 'secondary',
                        'disposed': 'danger'
                    };
                    const color = statusColors[data] || 'secondary';
                    return `<span class="badge bg-${color}">${data ? data.charAt(0).toUpperCase() + data.slice(1) : 'N/A'}</span>`;
                }
            },
            { data: 'hfs_status_display', name: 'assets.hfs_status', orderable: false },
            { data: 'depreciation_display', name: 'assets.depreciation_stopped', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="text-center"><i class="bx bx-loader-alt bx-spin bx-lg"></i> Loading vehicles...</div>'
        }
    });

    // Filter event handlers
    $('#filter-hfs-status, #filter-depreciation, #filter-operational-status').on('change', function() {
        vehiclesTable.ajax.reload();
    });

    $('#clear-filters').on('click', function() {
        $('#filter-hfs-status, #filter-depreciation, #filter-operational-status').val('');
        vehiclesTable.ajax.reload();
    });

    // Import form handling
    $('#vehicleImportForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        $.ajax({
            url: '{{ route("fleet.vehicles.import") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function() {
                // Show loading state
                const submitBtn = $('#vehicleImportForm button[type="submit"]');
                submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Importing...');
            },
            success: function(response) {
                console.log('Import response:', response);
                $('#vehicleImportModal').modal('hide');
                $('#vehicleImportForm')[0].reset();
                var hasErrors = response.errors && response.errors.length > 0;
                var imported = response.imported || 0;
                var detail = response.message || (imported > 0 ? 'Vehicles imported successfully!' : 'Import completed with no new vehicles.');
                if (hasErrors) {
                    detail += '<br><br><strong>Row errors:</strong><ul class="text-start mb-0 mt-2">';
                    response.errors.forEach(function(err) { detail += '<li>' + err + '</li>'; });
                    detail += '</ul>';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: imported > 0 ? (hasErrors ? 'warning' : 'success') : (hasErrors ? 'error' : 'info'),
                        title: imported > 0 ? 'Import completed' : (hasErrors ? 'Import had errors' : 'Import completed'),
                        html: detail,
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert(detail.replace(/<[^>]*>/g, ' '));
                }
                setTimeout(function() {
                    vehiclesTable.ajax.reload();
                }, 500);
            },
            error: function(xhr) {
                var message = 'Import failed!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Import failed', text: message });
                } else {
                    alert(message);
                }
            },
            complete: function() {
                // Reset loading state
                const submitBtn = $('#vehicleImportForm button[type="submit"]');
                submitBtn.prop('disabled', false).html('<i class="bx bx-upload me-1"></i>Import Vehicles');
            }
        });
    });

    // Change vehicle status
    $(document).on('click', '.change-vehicle-status', function(e) {
        e.preventDefault();
        const vehicleId = $(this).data('vehicle-id');
        const status = $(this).data('status');
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Change Vehicle Status?',
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
                        url: '{{ url("/fleet/vehicles") }}/' + vehicleId + '/change-status',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            operational_status: status
                        },
                        success: function(response) {
                            Swal.fire('Success!', response.message, 'success');
                            vehiclesTable.ajax.reload(null, false);
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
                    url: '{{ url("/fleet/vehicles") }}/' + vehicleId + '/change-status',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        operational_status: status
                    },
                    success: function(response) {
                        alert(response.message);
                        vehiclesTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        alert('Failed to change status');
                    }
                });
            }
        }
    });

    // Delete vehicle
    $(document).on('click', '.delete-vehicle', function(e) {
        e.preventDefault();
        const vehicleId = $(this).data('vehicle-id');
        const vehicleName = $(this).data('vehicle-name');
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete Vehicle?',
                html: 'Are you sure you want to delete <strong>' + vehicleName + '</strong>?<br><br><span class="text-danger">This will also delete the vehicle from the asset registry. This action cannot be undone.</span>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ url("/fleet/vehicles") }}/' + vehicleId,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        success: function(response) {
                            Swal.fire('Deleted!', response.message || 'Vehicle deleted successfully.', 'success');
                            vehiclesTable.ajax.reload();
                        },
                        error: function(xhr) {
                            var message = 'Failed to delete vehicle';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }
                            Swal.fire('Error!', message, 'error');
                        }
                    });
                }
            });
        } else {
            if (confirm('Are you sure you want to delete ' + vehicleName + '? This will also delete the vehicle from the asset registry. This action cannot be undone.')) {
                $.ajax({
                    url: '{{ url("/fleet/vehicles") }}/' + vehicleId,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        alert(response.message || 'Vehicle deleted successfully.');
                        vehiclesTable.ajax.reload();
                    },
                    error: function(xhr) {
                        var message = 'Failed to delete vehicle';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        alert(message);
                    }
                });
            }
        }
    });
});
</script>
@endpush