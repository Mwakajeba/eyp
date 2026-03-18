@extends('layouts.main')

@section('title', 'Imprest Approval Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Approval Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">Imprest Approval Settings</h5>
            <div>
                <a href="{{ route('imprest.multi-approval-settings.index') }}" class="btn btn-info me-2">
                    <i class="bx bx-git-branch me-1"></i> Multi-Level Approvals
                </a>
                <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                    <i class="bx bx-plus me-1"></i> Add Approval Setting
                </button>
            </div>
        </div>

        @if(isset($stats))
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bx bx-cog fs-1 text-primary mb-2"></i>
                        <h4 class="text-primary">{{ $stats['total_settings'] ?? 0 }}</h4>
                        <p class="mb-0">Total Settings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bx bx-check-circle fs-1 text-info mb-2"></i>
                        <h4 class="text-info">{{ $stats['active_checkers'] ?? 0 }}</h4>
                        <p class="mb-0">Active Checkers</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bx bx-shield-check fs-1 text-success mb-2"></i>
                        <h4 class="text-success">{{ $stats['active_approvers'] ?? 0 }}</h4>
                        <p class="mb-0">Active Approvers</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bx bx-credit-card fs-1 text-warning mb-2"></i>
                        <h4 class="text-warning">{{ $stats['active_providers'] ?? 0 }}</h4>
                        <p class="mb-0">Active Providers</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Main Data Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-table me-2"></i>Approval Settings</h6>
            </div>
            <div class="card-body">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <div class="table-responsive">
                    <table id="approval-settings-table" class="table table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Amount Limit</th>
                                <th>Departments</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="approvalSettingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Approval Setting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approvalSettingForm">
                @csrf
                <input type="hidden" id="setting_id" name="setting_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="user_id" class="form-label">User <span class="text-danger">*</span></label>
                                <select class="form-select" id="user_id" name="user_id" required>
                                    <option value="">Select User</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="approval_role" class="form-label">Approval Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="approval_role" name="approval_role" required>
                                    <option value="">Select Role</option>
                                    <option value="checker">Checker/Reviewer</option>
                                    <option value="approver">Approver</option>
                                    <option value="provider">Provider</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="amount_limit" class="form-label">Amount Limit (TZS)</label>
                                <input type="number" class="form-control" id="amount_limit" name="amount_limit" 
                                       step="0.01" min="0" placeholder="Leave empty for no limit">
                                <small class="form-text text-muted">Maximum amount this user can handle</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="department_ids" class="form-label">Departments (Optional)</label>
                                <select class="form-select" id="department_ids" name="department_ids[]" multiple>
                                    <!-- Options will be populated dynamically -->
                                </select>
                                <small class="form-text text-muted">Leave empty to handle all departments</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        Save Setting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- DataTables -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    let table = $('#approval-settings-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("imprest.approval-settings.index") }}',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'user_name', name: 'user.name' },
            { data: 'role_badge', name: 'approval_role' },
            { data: 'status_badge', name: 'is_active' },
            { data: 'amount_limit', name: 'amount_limit' },
            { data: 'departments', name: 'department_ids', orderable: false, searchable: false },
            { data: 'created_info', name: 'creator.name' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        responsive: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>'
        }
    });

    // Form submission
    $('#approvalSettingForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const settingId = $('#setting_id').val();
        const isEdit = settingId && settingId !== '';
        
        const url = isEdit 
            ? '{{ route("imprest.approval-settings.update", ":id") }}'.replace(':id', settingId)
            : '{{ route("imprest.approval-settings.store") }}';
            
        const method = isEdit ? 'PUT' : 'POST';
        
        if (isEdit) {
            formData.append('_method', 'PUT');
        }

        // Show loading state
        const submitBtn = $('#submitBtn');
        const spinner = submitBtn.find('.spinner-border');
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#approvalSettingModal').modal('hide');
                    table.ajax.reload();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                let message = 'An error occurred';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors);
                    message = errors.flat().join('\n');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });

    // Reset modal on hide
    $('#approvalSettingModal').on('hidden.bs.modal', function() {
        $('#approvalSettingForm')[0].reset();
        $('#setting_id').val('');
        $('.modal-title').text('Add Approval Setting');
    });
});

// Global functions for buttons
function openCreateModal() {
    loadUsersAndDepartments(function() {
        $('#approvalSettingModal').modal('show');
    });
}

function editSetting(id) {
    $.ajax({
        url: '{{ route("imprest.approval-settings.edit", ":id") }}'.replace(':id', id),
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const setting = response.setting;
                const users = response.users;
                const departments = response.departments;
                
                // Populate users dropdown
                $('#user_id').empty().append('<option value="">Select User</option>');
                users.forEach(function(user) {
                    $('#user_id').append(`<option value="${user.id}">${user.name}</option>`);
                });
                
                // Populate departments dropdown
                $('#department_ids').empty();
                departments.forEach(function(dept) {
                    $('#department_ids').append(`<option value="${dept.id}">${dept.name}</option>`);
                });
                
                // Set form values
                $('#setting_id').val(setting.id);
                $('#user_id').val(setting.user_id);
                $('#approval_role').val(setting.approval_role);
                $('#amount_limit').val(setting.amount_limit || '');
                $('#is_active').prop('checked', setting.is_active);
                
                // Set selected departments
                if (setting.department_ids && Array.isArray(setting.department_ids)) {
                    $('#department_ids').val(setting.department_ids);
                }
                
                $('.modal-title').text('Edit Approval Setting');
                $('#approvalSettingModal').modal('show');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Failed to load setting data', 'error');
        }
    });
}

function deleteSetting(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This approval setting will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("imprest.approval-settings.destroy", ":id") }}'.replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $('#approval-settings-table').DataTable().ajax.reload();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to delete setting', 'error');
                }
            });
        }
    });
}

function loadUsersAndDepartments(callback) {
    $.ajax({
        url: '{{ route("imprest.approval-settings.create") }}',
        type: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            if (response.success) {
                // Populate users dropdown
                $('#user_id').empty().append('<option value="">Select User</option>');
                response.users.forEach(function(user) {
                    $('#user_id').append(`<option value="${user.id}">${user.name}</option>`);
                });
                
                // Populate departments dropdown
                $('#department_ids').empty();
                response.departments.forEach(function(dept) {
                    $('#department_ids').append(`<option value="${dept.id}">${dept.name}</option>`);
                });
                
                if (typeof callback === 'function') {
                    callback();
                }
            }
        },
        error: function() {
            Swal.fire('Error!', 'Failed to load form data', 'error');
        }
    });
}
</script>
@endpush