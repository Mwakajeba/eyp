<form action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Employee <span class="text-danger">*</span></label>
            <select name="employee_id" class="form-select select2-single @error('employee_id') is-invalid @enderror"
                required>
                <option value="">-- Select Employee --</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ old('employee_id', $loan->employee_id ?? '') == $employee->id ? 'selected' : '' }}>
                        {{ $employee->full_name }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted"><i class="bx bx-info-circle me-1"></i>Select the employee who has taken the external loan</small>
            @error('employee_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Institution Name <span class="text-danger">*</span></label>
            <div class="input-group">
                <select name="institution_name" id="institution_name" class="form-select select2-single @error('institution_name') is-invalid @enderror" required>
                    <option value="">-- Select Institution --</option>
                    @if(isset($institutions) && $institutions->count() > 0)
                        @foreach($institutions as $institution)
                            <option value="{{ $institution->name }}" {{ old('institution_name', $loan->institution_name ?? '') == $institution->name ? 'selected' : '' }}>
                                {{ $institution->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
                <button type="button" class="btn btn-outline-primary" id="refresh-institutions-btn" title="Refresh Institutions">
                    <i class="bx bx-refresh"></i>
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInstitutionModal" title="Add New Institution">
                    <i class="bx bx-plus"></i>
                </button>
            </div>
            <small class="text-muted"><i class="bx bx-info-circle me-1"></i>Select the bank or financial institution providing the loan</small>
            @error('institution_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Reference Number</label>
            <input type="text" name="reference_number"
                class="form-control @error('reference_number') is-invalid @enderror"
                value="{{ old('reference_number', $loan->reference_number ?? '') }}"
                placeholder="e.g., LOAN-2024-001" />
            <small class="text-muted"><i class="bx bx-info-circle me-1"></i>Optional: Loan reference number from the institution</small>
            @error('reference_number')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Total Loan <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" name="total_loan" id="total_loan"
                class="form-control @error('total_loan') is-invalid @enderror"
                value="{{ old('total_loan', $loan->total_loan ?? '') }}" required />
            <small class="text-muted"><i class="bx bx-info-circle me-1"></i>Enter the total loan amount in TZS</small>
            @error('total_loan')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Deduction Type <span class="text-danger">*</span></label>
            <select name="deduction_type" id="deduction_type" class="form-select @error('deduction_type') is-invalid @enderror" required>
                <option value="fixed" {{ old('deduction_type', $loan->deduction_type ?? 'fixed') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                <option value="percentage" {{ old('deduction_type', $loan->deduction_type ?? 'fixed') == 'percentage' ? 'selected' : '' }}>Percentage</option>
            </select>
            <small class="text-muted"><i class="bx bx-info-circle me-1"></i>Choose whether deduction is a fixed amount or percentage of salary</small>
            @error('deduction_type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Monthly Deduction <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" step="0.01" min="0" name="monthly_deduction" id="monthly_deduction"
                    class="form-control @error('monthly_deduction') is-invalid @enderror"
                    value="{{ old('monthly_deduction', $loan->monthly_deduction ?? '') }}" required />
                <span class="input-group-text" id="deduction_suffix">TZS</span>
            </div>
            <small class="text-muted" id="deduction_help"><i class="bx bx-info-circle me-1"></i>Enter the fixed monthly deduction amount</small>
            @error('monthly_deduction')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                value="{{ old('date', optional($loan->date ?? null)->format('Y-m-d')) }}" required />
            <small class="text-muted"><i class="bx bx-info-circle me-1"></i>Date when the loan repayment starts (first deduction date)</small>
            @error('date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">End Date</label>
            <input type="date" name="date_end_of_loan"
                class="form-control @error('date_end_of_loan') is-invalid @enderror"
                value="{{ old('date_end_of_loan', optional($loan->date_end_of_loan ?? null)->format('Y-m-d')) }}" />
            <small class="text-muted"><i class="bx bx-info-circle me-1"></i>Optional: Expected date when the loan will be fully repaid</small>
            @error('date_end_of_loan')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Enter any additional notes or details about this loan...">{{ old('description', $loan->description ?? '') }}</textarea>
            <small class="text-muted"><i class="bx bx-info-circle me-1"></i>Optional: Add any additional notes, terms, or special conditions</small>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" {{ old('is_active', $loan->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                    Active
                </label>
            </div>
            <small class="text-muted d-block mt-1"><i class="bx bx-info-circle me-1"></i>Active loans will be included in payroll deductions</small>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save</button>
        <a href="{{ route('hr.external-loans.index') }}" class="btn btn-secondary"><i class="bx bx-x"></i> Cancel</a>
    </div>
</form>

<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    const deductionType = document.getElementById('deduction_type');
    const monthlyDeduction = document.getElementById('monthly_deduction');
    const deductionSuffix = document.getElementById('deduction_suffix');
    const deductionHelp = document.getElementById('deduction_help');

    // Handle deduction type change
    if (deductionType) {
        deductionType.addEventListener('change', function() {
            if (this.value === 'percentage') {
                deductionSuffix.textContent = '%';
                deductionHelp.innerHTML = '<i class="bx bx-info-circle me-1"></i>Enter the percentage of salary to deduct (e.g., 10 for 10%)';
                monthlyDeduction.setAttribute('max', '100');
            } else {
                deductionSuffix.textContent = 'TZS';
                deductionHelp.innerHTML = '<i class="bx bx-info-circle me-1"></i>Enter the fixed monthly deduction amount';
                monthlyDeduction.removeAttribute('max');
            }
        });

        // Trigger on load to set initial state
        deductionType.dispatchEvent(new Event('change'));
    }

    // Refresh institutions dropdown
    function refreshInstitutions() {
        const select = $('#institution_name');
        const currentValue = select.val();
        
        $.ajax({
            url: '{{ route("hr.external-loan-institutions.index") }}',
            type: 'GET',
            data: { json: true },
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Clear existing options except the first one
                    select.find('option:not(:first)').remove();
                    
                    // Add new institutions
                    if (response.data.length > 0) {
                        response.data.forEach(function(institution) {
                            const option = new Option(institution.name, institution.name, false, false);
                            select.append(option);
                        });
                    }
                    
                    // Update Select2
                    if (select.hasClass('select2-hidden-accessible')) {
                        select.trigger('change.select2');
                    }
                    
                    // Restore previous selection if it still exists
                    if (currentValue) {
                        select.val(currentValue);
                        if (select.hasClass('select2-hidden-accessible')) {
                            select.trigger('change.select2');
                        } else {
                            select.trigger('change');
                        }
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error refreshing institutions:', error);
                // Fallback: reload page
                if (confirm('Unable to refresh institutions. Reload page?')) {
                    location.reload();
                }
            }
        });
    }

    // Refresh button click handler
    $('#refresh-institutions-btn').on('click', function() {
        const btn = $(this);
        const icon = btn.find('i');
        icon.addClass('bx-spin');
        
        refreshInstitutions();
        
        setTimeout(function() {
            icon.removeClass('bx-spin');
        }, 1000);
    });

    // Handle modal form submission
    $('#addInstitutionForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        const modal = $('#addInstitutionModal');
        
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i>Creating...');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                // Close modal
                modal.modal('hide');
                
                // Reset form
                form[0].reset();
                
                // Refresh institutions dropdown
                setTimeout(function() {
                    refreshInstitutions();
                    
                    // Select the newly created institution
                    if (response.institution && response.institution.name) {
                        setTimeout(function() {
                            $('#institution_name').val(response.institution.name).trigger('change');
                        }, 500);
                    }
                }, 300);
                
                // Show success message
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Institution created successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert(response.message || 'Institution created successfully');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while creating the institution';
                
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        let errorList = '<ul class="text-start mb-0">';
                        Object.keys(errors).forEach(function(key) {
                            errors[key].forEach(function(error) {
                                errorList += '<li>' + error + '</li>';
                            });
                        });
                        errorList += '</ul>';
                        errorMessage = errorList;
                    } else if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                }
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: errorMessage
                    });
                } else {
                    alert(errorMessage);
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>

<!-- Add Institution Modal -->
<div class="modal fade" id="addInstitutionModal" tabindex="-1" aria-labelledby="addInstitutionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addInstitutionModalLabel">
                    <i class="bx bx-plus me-2"></i>Add New Institution
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addInstitutionForm" action="{{ route('hr.external-loan-institutions.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="modal_institution_name" class="form-label">Institution Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="modal_institution_name" name="name" required>
                        <small class="text-muted"><i class="bx bx-info-circle me-1"></i>Enter the name of the institution</small>
                    </div>

                    <div class="mb-3">
                        <label for="modal_institution_code" class="form-label">Code</label>
                        <input type="text" class="form-control" id="modal_institution_code" name="code">
                        <small class="text-muted"><i class="bx bx-info-circle me-1"></i>Optional: Internal reference code</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="modal_contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="modal_contact_person" name="contact_person">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="modal_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="modal_email" name="email">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="modal_phone" name="phone">
                    </div>

                    <div class="mb-3">
                        <label for="modal_address" class="form-label">Address</label>
                        <textarea class="form-control" id="modal_address" name="address" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="modal_is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="modal_is_active">Active</label>
                        </div>
                        <small class="text-muted"><i class="bx bx-info-circle me-1"></i>Active institutions appear in the dropdown</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addInstitutionForm" class="btn btn-primary">
                    <i class="bx bx-save me-1"></i>Create Institution
                </button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
// Reset modal form when modal is closed
$('#addInstitutionModal').on('hidden.bs.modal', function () {
    $('#addInstitutionForm')[0].reset();
    $('#addInstitutionForm').find('.is-invalid').removeClass('is-invalid');
    $('#addInstitutionForm').find('.invalid-feedback').remove();
});
</script>