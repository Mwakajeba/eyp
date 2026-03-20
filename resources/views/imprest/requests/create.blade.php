@extends('layouts.main')

@section('title', 'Create Imprest Request')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'All Requests', 'url' => route('imprest.requests.index'), 'icon' => 'bx bx-list-ul'],
            ['label' => 'Create Request', 'url' => '#', 'icon' => 'bx bx-plus-circle']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">Create New Imprest Request</h5>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('imprest.requests.store') }}" method="POST" id="imprestRequestForm">
                    @csrf

                    @if(session('success'))
                    <div class="alert alert-success d-flex align-items-start" role="alert">
                        <i class="bx bx-check-circle me-2 fs-4"></i>
                        <div>
                            <strong>Success!</strong>
                            {{ session('success') }}
                        </div>
                    </div>
                    @endif

                    @if ($errors->any())
                    <div class="alert alert-danger d-flex align-items-start" role="alert">
                        <i class="bx bx-error me-2 fs-4"></i>
                        <div>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                            <select name="department_id" id="department_id" class="form-select select2-single @error('department_id') is-invalid @enderror" required>
                                @if($departments->count() > 0)
                                    <option value="">-- Select Department --</option>
                                    @foreach($departments as $department)
                                    <option value="{{ $department->id }}" 
                                        {{ old('department_id', $employee && $employee->department_id ? $employee->department_id : '') == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                    @endforeach
                                @else
                                    <option value="">No department available</option>
                                @endif
                            </select>
                            @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($employee && $employee->department && $departments->count() > 0)
                                <small class="form-text text-success">
                                    <i class="bx bx-check-circle"></i> Showing your assigned department: {{ $employee->department->name }}
                                </small>
                            @elseif(!$employee)
                                <small class="form-text text-danger">
                                    <i class="bx bx-error-circle"></i> No employee profile found. Please contact HR to create your employee profile.
                                </small>
                            @elseif($departments->count() == 0)
                                <small class="form-text text-warning">
                                    <i class="bx bx-info-circle"></i> No department assigned to your employee profile. Please contact HR.
                                </small>
                            @endif
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                            <select name="project_id" id="project_id" class="form-select select2-single @error('project_id') is-invalid @enderror" required>
                                <option value="">-- Select Project --</option>
                                @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->project_code ? $project->project_code . ' - ' : '' }}{{ $project->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('project_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3" id="activity-wrapper">
                            <label for="project_activity_id" class="form-label">Project Activity <span class="text-muted">(Optional)</span></label>
                            <select name="project_activity_id" id="project_activity_id" class="form-select select2-single @error('project_activity_id') is-invalid @enderror" {{ old('project_id') ? '' : 'disabled' }}>
                                <option value="">-- Select project first --</option>
                            </select>
                            @error('project_activity_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted" id="activity-hint">Select a project to load its activities.</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="amount_requested" class="form-label">Total Amount Requested (TZS) <span class="text-danger">*</span></label>
                            <input type="number" 
                                class="form-control @error('amount_requested') is-invalid @enderror"
                                id="amount_requested"
                                name="amount_requested"
                                value="{{ old('amount_requested') }}"
                                step="0.01"
                                min="0.01"
                                placeholder="0.00"
                                readonly
                                required>
                            @error('amount_requested')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">This will be calculated automatically from line items below</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="date_required" class="form-label">Date Required <span class="text-danger">*</span></label>
                            <input type="date"
                                class="form-control @error('date_required') is-invalid @enderror"
                                id="date_required"
                                name="date_required"
                                value="{{ old('date_required', date('Y-m-d', strtotime('+1 day'))) }}"
                                min="{{ date('Y-m-d') }}"
                                required>
                            @error('date_required')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="purpose" class="form-label">Purpose <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control @error('purpose') is-invalid @enderror"
                                id="purpose"
                                name="purpose"
                                value="{{ old('purpose') }}"
                                placeholder="Brief purpose of the imprest"
                                maxlength="500"
                                required>
                            @error('purpose')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">Detailed Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Provide detailed explanation of how the funds will be used...">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Imprest Items Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Imprest Items Breakdown <span class="text-danger">*</span></h6>
                                        <button type="button" class="btn btn-primary btn-sm" id="add-item" onclick="window.showItemModal()">
                                            <i class="bx bx-plus me-1"></i>Add Item
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table" id="items-table">
                                            <thead>
                                                <tr>
                                                    <th width="40%">Chart Account</th>
                                                    <th width="40%">Notes/Description</th>
                                                    <th width="15%">Amount (TZS)</th>
                                                    <th width="5%">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="items-tbody">
                                                <!-- Items will be added here dynamically -->
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <td colspan="2" class="text-end fw-bold">Total Amount:</td>
                                                    <td class="fw-bold">
                                                        <span id="total-amount">0.00</span>
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div class="alert alert-info mt-3" id="no-items-alert">
                                        <i class="bx bx-info-circle me-1"></i>
                                        Please add at least one item to specify how the imprest funds will be used.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Information (Read-only) -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="text-secondary border-bottom pb-2">Requestor Information</h6>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee Name</label>
                            <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee ID</label>
                            <input type="text" class="form-control {{ !$employee ? 'text-warning' : 'text-success' }}" 
                                   value="{{ $employee ? $employee->employee_number : 'Not Available' }}" readonly>
                            @if(!$employee)
                                <small class="form-text text-warning">
                                    <i class="bx bx-warning"></i> Employee record not found. Contact HR to create your employee profile.
                                </small>
                            @endif
                        </div>
                        @if($employee)
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee Department</label>
                            <input type="text" class="form-control text-success" 
                                   value="{{ $employee->department->name ?? 'No Department' }}" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee Position</label>
                            <input type="text" class="form-control" 
                                   value="{{ $employee->designation ?? 'Not Set' }}" readonly>
                        </div>
                        @endif
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="{{ route('imprest.requests.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to List
                            </a>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <span class="btn-text">
                                    <i class="bx bx-check me-1"></i> Submit Request
                                </span>
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Imprest Item</h5>
                <button type="button" class="btn-close" onclick="window.hideItemModal()"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="modal_chart_account" class="form-label">Chart Account <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_chart_account" required>
                                <option value="">Select Chart Account</option>
                                @foreach($chartAccounts as $account)
                                <option value="{{ $account->id }}" data-code="{{ $account->account_code }}" data-name="{{ $account->account_name }}">
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="modal_notes" class="form-label">Notes/Description</label>
                            <textarea class="form-control" id="modal_notes" rows="3" placeholder="Enter notes or description for this item..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_amount" class="form-label">Amount (TZS) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="modal_amount" step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Line Total</label>
                            <div class="border rounded p-2 bg-light">
                                <span class="fw-bold" id="modal-line-total">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="window.hideItemModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="window.addItemFromModal()">Add Item</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Budget validation modal styling */
    .swal2-wide {
        width: 600px !important;
    }

    .budget-details {
        font-family: 'Courier New', monospace;
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 5px;
        margin-top: 1rem;
    }

    /* Ensure modal is visible when shown */
    .modal.show {
        display: block !important;
    }
    
    .modal {
        z-index: 9999 !important;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
// Global function to show modal
window.showItemModal = function() {
    console.log('showItemModal called');
    
    // Clear modal fields
    $('#modal_chart_account').val('').trigger('change');
    $('#modal_notes').val('');
    $('#modal_amount').val('');
    $('#modal-line-total').text('0.00');
    
    // Show modal using jQuery
    $('#itemModal').modal('show');
    console.log('Modal show called via jQuery');
};

$(document).ready(function() {
    console.log('Document ready - Imprest create form loaded');

    // Ensure project->activity loading is bound after jQuery/bootstrap scripts are ready.
    const activitiesBaseUrl = '{{ url("imprest/requests/project-activities") }}';
    const oldProjectId = '{{ old("project_id") }}';
    const oldActivityId = '{{ old("project_activity_id") }}';

    function bindProjectActivities(projectId, selectedActivityId) {
        const $project = $('#project_id');
        const $activity = $('#project_activity_id');
        const $hint = $('#activity-hint');

        if (! $activity.length || ! $project.length) {
            return;
        }

        if (!projectId) {
            if ($activity.hasClass('select2-hidden-accessible')) {
                $activity.select2('destroy');
            }
            $activity.prop('disabled', true).html('<option value="">-- Select project first --</option>');
            $hint.text('Select a project to load its activities.');
            return;
        }

        $activity.prop('disabled', true).html('<option value="">Loading...</option>');

        $.ajax({
            url: activitiesBaseUrl + '/' + encodeURIComponent(projectId),
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                let options = '<option value="">-- Select Activity --</option>';

                if (!Array.isArray(data) || data.length === 0) {
                    $hint.text('This project has no activities defined.');
                } else {
                    $hint.text(data.length + ' activit' + (data.length === 1 ? 'y' : 'ies') + ' available.');
                }

                (data || []).forEach(function(activity) {
                    const label = activity.activity_code
                        ? activity.activity_code + ' - ' + activity.description
                        : activity.description;
                    const selected = String(activity.id) === String(selectedActivityId) ? ' selected' : '';
                    options += '<option value="' + activity.id + '"' + selected + '>' + label + '</option>';
                });

                if ($activity.hasClass('select2-hidden-accessible')) {
                    $activity.select2('destroy');
                }

                $activity.html(options).prop('disabled', false);
                $activity.select2({
                    placeholder: '-- Select Activity --',
                    allowClear: true,
                    width: '100%'
                });
            },
            error: function(xhr) {
                $activity.prop('disabled', true).html('<option value="">Failed to load activities</option>');
                $hint.text('Could not load activities. Please try again.');
                console.error('Activity load failed', xhr.status, xhr.responseText);
            }
        });
    }

    $('#project_id').off('change.projectActivity').on('change.projectActivity', function() {
        bindProjectActivities($(this).val(), null);
    });

    bindProjectActivities(oldProjectId || $('#project_id').val(), oldActivityId);
    
    const form = $('#imprestRequestForm');
    const submitBtn = $('#submitBtn');
    const btnText = submitBtn.find('.btn-text');
    const spinner = submitBtn.find('.spinner-border');
    let itemCounter = 0;

    // Format amount input
    $('#amount_requested').on('input', function() {
        let value = $(this).val();
        if (value && !isNaN(value)) {
            // Remove any existing formatting
            value = value.replace(/,/g, '');
            // Format with commas for thousands
            if (value !== '') {
                $(this).val(parseFloat(value).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 2}));
            }
        }
    });

    // Remove formatting on form submit for server processing
    form.on('submit', function(e) {
        // Check if at least one item is added
        if ($('#items-tbody tr').length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'No Items Added',
                text: 'Please add at least one imprest item before submitting.',
                confirmButtonText: 'OK'
            });
            return false;
        }

        let amount = $('#amount_requested').val().replace(/,/g, '');
        $('#amount_requested').val(amount);

        // Show loading state
        submitBtn.prop('disabled', true);
        btnText.html('<i class="bx bx-loader-alt bx-spin me-1"></i> Submitting...');
        spinner.removeClass('d-none');
    });

    // Character counter for purpose
    $('#purpose').on('input', function() {
        let length = $(this).val().length;
        let maxLength = 500;
        let remaining = maxLength - length;
        
        if (!$('#purpose-counter').length) {
            $(this).after('<small id="purpose-counter" class="form-text text-muted"></small>');
        }
        
        $('#purpose-counter').text(remaining + ' characters remaining');
        
        if (remaining < 50) {
            $('#purpose-counter').removeClass('text-muted').addClass('text-warning');
        } else {
            $('#purpose-counter').removeClass('text-warning').addClass('text-muted');
        }
    });

    // Trigger character counter on page load
    $('#purpose').trigger('input');

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    $('#date_required').attr('min', today);

    // Initialize select2 for department and chart account
    $('#department_id').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    $('#project_id').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    $('#project_activity_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- Select Activity --',
        allowClear: true
    });

    $('#modal_chart_account').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#itemModal'),
        placeholder: 'Select Chart Account',
        allowClear: true
    });

    // =========== IMPREST ITEMS FUNCTIONALITY ===========

    // Add item button
    $('#add-item').on('click', function() {
        console.log('Add item button clicked via jQuery event handler');
        window.showItemModal();
    });

    // Modal amount calculation
    $('#modal_amount').on('input', function() {
        calculateModalTotal();
    });

    // Add item from modal
    $('#add-item-btn').on('click', function() {
        addItemToTable();
    });

    // Remove item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calculateTotals();
        toggleNoItemsAlert();
    });

    function clearModal() {
        $('#modal_chart_account').val('');
        $('#modal_notes').val('');
        $('#modal_amount').val('');
        $('#modal-line-total').text(formatCurrency(0));
    }

    function calculateModalTotal() {
        const amount = parseFloat($('#modal_amount').val()) || 0;
        $('#modal-line-total').text(formatCurrency(amount));
    }

    function addItemToTable() {
        const chartAccountId = $('#modal_chart_account').val();
        const chartAccountText = $('#modal_chart_account option:selected').text();
        const notes = $('#modal_notes').val();
        const amount = parseFloat($('#modal_amount').val()) || 0;

        if (!chartAccountId) {
            Swal.fire({
                icon: 'error',
                title: 'Missing Information',
                text: 'Please select a chart account.',
                confirmButtonText: 'OK'
            });
            return;
        }

        if (amount <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Amount',
                text: 'Please enter a valid amount greater than 0.',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Check if chart account already exists
        let exists = false;
        $('#items-tbody input[name$="[chart_account_id]"]').each(function() {
            if ($(this).val() == chartAccountId) {
                exists = true;
                return false;
            }
        });

        if (exists) {
            Swal.fire({
                icon: 'error',
                title: 'Duplicate Account',
                text: 'This chart account has already been added. Please select a different account.',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Validate budget if budget checking is enabled
        validateBudgetAndAddItem(chartAccountId, chartAccountText, notes, amount);
    }

    function validateBudgetAndAddItem(chartAccountId, chartAccountText, notes, amount) {
        // Show loading state
        Swal.fire({
            title: 'Validating Budget...',
            text: 'Please wait while we check budget availability.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '{{ route("imprest.validate-budget") }}',
            method: 'POST',
            data: {
                chart_account_id: chartAccountId,
                amount: amount,
                date_required: $('#date_required').val(),
                project_id: $('#project_id').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                Swal.close();
                
                if (response.success) {
                    // Budget validation passed, add item to table
                    addItemToTableDirectly(chartAccountId, chartAccountText, notes, amount);
                    
                    if (response.budget_check_enabled && response.budget_details) {
                        // Show budget info as success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Budget Validation Passed',
                            html: `
                                <div class="text-start">
                                    <strong>Budget Details:</strong><br>
                                    Budgeted Amount: <span class="text-info">TZS ${formatCurrency(response.budget_details.budgeted_amount)}</span><br>
                                    Used Amount: <span class="text-warning">TZS ${formatCurrency(response.budget_details.used_amount)}</span><br>
                                    Available After Request: <span class="text-success">TZS ${formatCurrency(response.budget_details.available_after_request)}</span>
                                </div>
                            `,
                            confirmButtonText: 'OK',
                            timer: 3000
                        });
                    }
                } else {
                    // Budget checking disabled or passed without details
                    addItemToTableDirectly(chartAccountId, chartAccountText, notes, amount);
                }
            },
            error: function(xhr) {
                Swal.close();
                
                let message = 'An error occurred while validating budget.';
                let details = '';
                let allowAddItem = false;
                
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.error) {
                        message = xhr.responseJSON.error;
                    }
                    
                    // If budget checking is disabled, allow item to be added
                    if (xhr.responseJSON.budget_check_enabled === false) {
                        allowAddItem = true;
                    }
                    
                    if (xhr.responseJSON.budget_details) {
                        const bd = xhr.responseJSON.budget_details;
                        details = `
                            <div class="text-start mt-3">
                                <strong>Budget Summary:</strong><br>
                                <div class="row">
                                    <div class="col-6">Budgeted:</div>
                                    <div class="col-6 text-end">TZS ${formatCurrency(bd.budgeted_amount)}</div>
                                </div>
                                <div class="row">
                                    <div class="col-6">Used:</div>
                                    <div class="col-6 text-end">TZS ${formatCurrency(bd.used_amount)}</div>
                                </div>
                                <div class="row">
                                    <div class="col-6">Available:</div>
                                    <div class="col-6 text-end text-success">TZS ${formatCurrency(bd.remaining_budget)}</div>
                                </div>
                                <div class="row border-top pt-2">
                                    <div class="col-6"><strong>Requested:</strong></div>
                                    <div class="col-6 text-end text-danger"><strong>TZS ${formatCurrency(bd.requested_amount)}</strong></div>
                                </div>
                                <div class="row">
                                    <div class="col-6 text-danger"><strong>Excess:</strong></div>
                                    <div class="col-6 text-end text-danger"><strong>TZS ${formatCurrency(bd.excess_amount)}</strong></div>
                                </div>
                            </div>
                        `;
                    }
                } else {
                    // Network error or server error - allow adding if budget check isn't critical
                    allowAddItem = true;
                    message = 'Could not validate budget. The item will be added without budget validation.';
                }
                
                if (allowAddItem) {
                    // Add item and show warning
                    addItemToTableDirectly(chartAccountId, chartAccountText, notes, amount);
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'Budget Check Skipped',
                        text: message,
                        confirmButtonText: 'OK'
                    });
                } else {
                    // Budget exceeded - don't add item
                    Swal.fire({
                        icon: 'error',
                        title: 'Budget Exceeded',
                        html: message + details,
                        confirmButtonText: 'OK',
                        customClass: {
                            popup: 'swal2-wide'
                        }
                    });
                }
            }
        });
    }

    function addItemToTableDirectly(chartAccountId, chartAccountText, notes, amount) {
        const row = `
            <tr data-row-id="${itemCounter}">
                <td>
                    <strong>${chartAccountText}</strong>
                    <input type="hidden" name="items[${itemCounter}][chart_account_id]" value="${chartAccountId}">
                </td>
                <td>
                    <span class="item-notes">${notes || 'No notes'}</span>
                    <input type="hidden" name="items[${itemCounter}][notes]" value="${notes}">
                </td>
                <td>
                    <span class="fw-bold item-amount-display">${formatCurrency(amount)}</span>
                    <input type="hidden" class="item-amount" name="items[${itemCounter}][amount]" value="${amount}">
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#items-tbody').append(row);
        window.hideItemModal(); // Use global function to hide modal
        itemCounter++;
        calculateTotals();
        toggleNoItemsAlert();
    }

    function calculateTotals() {
        let total = 0;
        
        $('#items-tbody input.item-amount').each(function() {
            const amount = parseFloat($(this).val()) || 0;
            total += amount;
        });

        $('#total-amount').text(formatCurrency(total));
        $('#amount_requested').val(total.toFixed(2));
    }

    function toggleNoItemsAlert() {
        if ($('#items-tbody tr').length === 0) {
            $('#no-items-alert').show();
        } else {
            $('#no-items-alert').hide();
        }
    }

    function formatCurrency(value) {
        return (Number(value) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Expose modal actions globally because inline onclick handlers call these names.
    window.addItemFromModal = addItemToTable;
    window.hideItemModal = function() {
        $('#itemModal').modal('hide');
    };

    // Initialize
    toggleNoItemsAlert();
});
</script>
@endpush