@extends('layouts.main')

@section('title', 'Positions Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <!-- Breadcrumbs -->
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Positions', 'url' => '#', 'icon' => 'bx bx-briefcase']
            ]" />

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase">
                    <i class="bx bx-briefcase me-1"></i>Positions Management
                </h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPositionModal">
                        <i class="bx bx-plus me-1"></i>Add Position
                    </button>
                </div>
            </div>
            <hr />

            <!-- Positions Table Card -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="positionsTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Department</th>
                                    <th>Job Grade</th>
                                    <th>Salary Range</th>
                                    <th>Budgeted Salary</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Position Modal -->
    <div class="modal fade" id="createPositionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-plus me-2"></i>Create New Position
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createPositionForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Position Title -->
                            <div class="col-md-6">
                                <label for="title" class="form-label">Position Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Department -->
                            <div class="col-md-6">
                                <label for="department_id" class="form-label">Department</label>
                                <select class="form-select select2-single" id="department_id" name="department_id">
                                    <option value="">Select Department</option>
                                    @foreach($departments ?? [] as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="Enter position description..."></textarea>
                                <div class="invalid-feedback"></div>
                                <div class="form-text">Optional: Brief description of the position's responsibilities</div>
                            </div>
                            
                            <div class="col-12">
                                <hr>
                                <h6 class="mb-3"><i class="bx bx-layer me-1"></i>Job Grade & Salary Information</h6>
                            </div>
                            
                            <!-- Job Grade -->
                            <div class="col-md-6">
                                <label for="grade_id" class="form-label">Job Grade</label>
                                <select class="form-select select2-single" id="grade_id" name="grade_id">
                                    <option value="">-- Select Job Grade --</option>
                                    @foreach($jobGrades ?? [] as $grade)
                                        <option value="{{ $grade->id }}" 
                                                data-min="{{ $grade->minimum_salary ?? '' }}" 
                                                data-max="{{ $grade->maximum_salary ?? '' }}">
                                            {{ $grade->grade_code }} - {{ $grade->grade_name }}
                                            @if($grade->minimum_salary || $grade->maximum_salary)
                                                ({{ $grade->salary_range }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                                <div class="form-text">Select the job grade for this position. This determines the salary range.</div>
                            </div>
                            
                            <!-- Budgeted Salary -->
                            <div class="col-md-6">
                                <label for="budgeted_salary" class="form-label">Budgeted Salary <span class="text-muted">(Optional - For Planning Only)</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ auth()->user()->company->currency ?? 'TZS' }}</span>
                                    <input type="number" class="form-control" id="budgeted_salary" name="budgeted_salary" 
                                           step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="invalid-feedback"></div>
                                <div class="alert alert-info mt-2">
                                    <i class="bx bx-info-circle me-1"></i>
                                    <small><strong>Note:</strong> This is for budgeting and planning purposes only. Actual employee salaries are set individually when creating employees or contracts.</small>
                                </div>
                                <div class="form-text" id="salary_range_hint">If provided, must be within the selected grade's salary range.</div>
                                <div id="salary_validation_alert" class="alert alert-warning mt-2" style="display: none;">
                                    <i class="bx bx-error-circle me-1"></i><span id="salary_validation_message"></span>
                                </div>
                            </div>
                            
                            <!-- Approved Headcount -->
                            <div class="col-md-4">
                                <label for="approved_headcount" class="form-label">Approved Headcount</label>
                                <input type="number" class="form-control" id="approved_headcount" name="approved_headcount" 
                                       value="1" min="1" required>
                                <div class="invalid-feedback"></div>
                                <div class="form-text">Number of employees that can be assigned to this position</div>
                            </div>
                            
                            <!-- Status -->
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select select2-single" id="status" name="status">
                                    <option value="approved" selected>Approved</option>
                                    <option value="frozen">Frozen</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <!-- Effective Date -->
                            <div class="col-md-4">
                                <label for="effective_date" class="form-label">Effective Date</label>
                                <input type="date" class="form-control" id="effective_date" name="effective_date">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-check me-1"></i>Create Position
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Position Modal -->
    <div class="modal fade" id="editPositionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-edit me-2"></i>Edit Position
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editPositionForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_position_id" name="position_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Position Title -->
                            <div class="col-md-6">
                                <label for="edit_title" class="form-label">Position Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_title" name="title" required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Department -->
                            <div class="col-md-6">
                                <label for="edit_department_id" class="form-label">Department</label>
                                <select class="form-select" id="edit_department_id" name="department_id">
                                    <option value="">Select Department</option>
                                    @foreach($departments ?? [] as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="4" 
                                          placeholder="Enter position description..."></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-check me-1"></i>Update Position
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('css')
<style>
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    .table thead th {
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
    }

    .btn-outline-primary:hover,
    .btn-outline-danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for all select fields in modals
    $('#createPositionModal, #editPositionModal').on('shown.bs.modal', function() {
        $(this).find('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $(this),
            placeholder: function() {
                return $(this).data('placeholder') || '-- Select --';
            },
            allowClear: true
        });
    });
    
    // Initialize DataTables
    let table = $('#positionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.positions.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'title', name: 'title'},
            {data: 'department_name', name: 'department.name'},
            {data: 'grade_info', name: 'grade.grade_code', orderable: false, searchable: false},
            {data: 'salary_range', name: 'grade.minimum_salary', orderable: false, searchable: false},
            {data: 'budgeted_salary', name: 'budgeted_salary', orderable: false, searchable: false, 
             render: function(data) {
                 return data ? new Intl.NumberFormat().format(data) : '-';
             }},
            {data: 'description_display', name: 'description', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            lengthMenu: "Show _MENU_ entries",
            search: "Search positions:",
            searchPlaceholder: "Type to search...",
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'
        },
        pageLength: 25,
        responsive: true,
        order: [[1, 'asc']]
    });

    // Create Position Form Submit
    $('#createPositionForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let submitBtn = $(this).find('button[type="submit"]');
        let originalText = submitBtn.html();
        
        // Reset validation states
        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        submitBtn.html('<i class="bx bx-loader-alt bx-spin me-1"></i>Creating...');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: "{{ route('hr.positions.store') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#createPositionModal').modal('hide');
                    $('#createPositionForm')[0].reset();
                    table.ajax.reload();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        let input = $('[name="' + field + '"]');
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Something went wrong. Please try again.',
                    });
                }
            },
            complete: function() {
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    });

    // Edit Position
    $(document).on('click', '.btn-outline-primary', function(e) {
        e.preventDefault();
        let url = $(this).attr('href');
        
        $.get(url, function(data) {
            // This would require an API endpoint to return position data
            // For now, we'll redirect to the edit page
            window.location.href = url;
        });
    });

    // Delete Position
    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        
        Swal.fire({
            title: 'Delete Position',
            text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('hr.positions.index') }}/${id}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'Something went wrong. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    }
                });
            }
        });
    });

    // Grade and Salary Validation
    $('#grade_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const minSalary = selectedOption.data('min');
        const maxSalary = selectedOption.data('max');
        const budgetedSalary = parseFloat($('#budgeted_salary').val()) || 0;
        
        if (selectedOption.val() && (minSalary || maxSalary)) {
            const min = minSalary ? new Intl.NumberFormat().format(minSalary) : 'N/A';
            const max = maxSalary ? new Intl.NumberFormat().format(maxSalary) : 'N/A';
            $('#salary_range_hint').html(`<strong>Grade Salary Range:</strong> ${min} - ${max}`);
            
            // Validate budgeted salary if entered
            if (budgetedSalary > 0) {
                validateSalaryAgainstGrade(budgetedSalary, minSalary, maxSalary);
            }
        } else {
            $('#salary_range_hint').text('Enter the budgeted salary for this position. Must be within the selected grade\'s salary range.');
            $('#salary_validation_alert').hide();
        }
    });
    
    // Validate salary when budgeted_salary changes
    $('#budgeted_salary').on('blur', function() {
        const budgetedSalary = parseFloat($(this).val()) || 0;
        const selectedOption = $('#grade_id').find('option:selected');
        const minSalary = selectedOption.data('min');
        const maxSalary = selectedOption.data('max');
        
        if (selectedOption.val() && budgetedSalary > 0) {
            validateSalaryAgainstGrade(budgetedSalary, minSalary, maxSalary);
        } else {
            $('#salary_validation_alert').hide();
        }
    });
    
    function validateSalaryAgainstGrade(salary, minSalary, maxSalary) {
        const alert = $('#salary_validation_alert');
        const message = $('#salary_validation_message');
        
        let isValid = true;
        let errorMsg = '';
        
        if (minSalary && salary < parseFloat(minSalary)) {
            isValid = false;
            errorMsg = `Salary (${new Intl.NumberFormat().format(salary)}) is below the minimum (${new Intl.NumberFormat().format(minSalary)}) for this grade.`;
        } else if (maxSalary && salary > parseFloat(maxSalary)) {
            isValid = false;
            errorMsg = `Salary (${new Intl.NumberFormat().format(salary)}) exceeds the maximum (${new Intl.NumberFormat().format(maxSalary)}) for this grade.`;
        }
        
        if (!isValid) {
            alert.removeClass('alert-success').addClass('alert-warning');
            message.html(errorMsg);
            alert.show();
        } else {
            alert.removeClass('alert-warning').addClass('alert-success');
            message.html('<i class="bx bx-check-circle me-1"></i>Salary is within the acceptable range for this grade.');
            alert.show();
        }
    }

    // Modal reset on hide
    $('#createPositionModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#salary_validation_alert').hide();
        $('#salary_range_hint').text('Enter the budgeted salary for this position. Must be within the selected grade\'s salary range.');
    });
});
</script>
@endpush
