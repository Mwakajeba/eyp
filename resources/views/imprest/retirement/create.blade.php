@extends('layouts.main')

@section('title', 'Submit Retirement')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Request Details', 'url' => route('imprest.requests.show', $imprestRequest->id), 'icon' => 'bx bx-show'],
            ['label' => 'Submit Retirement', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">
                <i class="bx bx-receipt me-2"></i>Submit Imprest Retirement
            </h5>
            <span class="{{ $imprestRequest->getStatusBadgeClass() }}">{{ $imprestRequest->getStatusLabel() }}</span>
        </div>

        <form action="{{ route('imprest.retirement.store', $imprestRequest->id) }}" method="POST" enctype="multipart/form-data" id="retirementForm">
            @csrf
            
            <!-- Error Messages -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <h6><i class="bx bx-error-circle me-1"></i>Please fix the following errors:</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <div class="row">
                <div class="col-md-8">
                    <!-- Imprest Summary -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Imprest Request Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">Request Number</small>
                                    <div class="fw-bold">{{ $imprestRequest->request_number }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Employee</small>
                                    <div class="fw-bold">{{ $imprestRequest->employee->name ?? 'N/A' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Purpose</small>
                                    <div>{{ $imprestRequest->purpose }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Amount Disbursed</small>
                                    <div class="fw-bold text-success">TZS {{ number_format($imprestRequest->disbursed_amount ?? 0, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Original Imprest Items (Reference) -->
                    @if($imprestRequest->imprestItems && $imprestRequest->imprestItems->count() > 0)
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Original Imprest Items (Reference)</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Account</th>
                                            <th>Description</th>
                                            <th class="text-end">Requested Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($imprestRequest->imprestItems as $item)
                                        <tr>
                                            <td>
                                                <small><strong>{{ $item->chartAccount->account_code ?? 'N/A' }}</strong></small><br>
                                                <small class="text-muted">{{ $item->chartAccount->account_name ?? 'N/A' }}</small>
                                            </td>
                                            <td><small>{{ $item->notes ?: 'No description' }}</small></td>
                                            <td class="text-end"><small>{{ number_format($item->amount, 2) }}</small></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-info">
                                        <tr>
                                            <th colspan="2">Total Requested:</th>
                                            <th class="text-end">{{ number_format($imprestRequest->imprestItems->sum('amount'), 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Retirement Items Form -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Actual Expenditure Details</h6>
                        </div>
                        <div class="card-body">
                            <div id="retirement-items">
                                @if($imprestItems && $imprestItems->count() > 0)
                                    @foreach($imprestItems as $index => $imprestItem)
                                    <!-- Pre-populated retirement item from imprest request -->
                                    <div class="retirement-item border rounded p-3 mb-3" data-index="{{ $index }}">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0">Expenditure Item #{{ $index + 1 }}</h6>
                                            @if($index > 0)
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-item">
                                                <i class="bx bx-trash"></i> Remove
                                            </button>
                                            @else
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-item" style="display: none;">
                                                <i class="bx bx-trash"></i> Remove
                                            </button>
                                            @endif
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Chart Account <span class="text-danger">*</span></label>
                                                <select name="retirement_items[{{ $index }}][chart_account_id]" class="form-select" required>
                                                    <option value="">Select Account</option>
                                                    @foreach($chartAccounts as $account)
                                                    <option value="{{ $account->id }}" {{ $imprestItem->chart_account_id == $account->id ? 'selected' : '' }}>
                                                        {{ $account->account_code }} - {{ $account->account_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Amount Requested</label>
                                                <input type="number" name="retirement_items[{{ $index }}][requested_amount]" 
                                                       class="form-control requested-amount" step="0.01" min="0" 
                                                       value="{{ $imprestItem->amount }}"
                                                       readonly style="background-color: #f8f9fa;">
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Amount Used <span class="text-danger">*</span></label>
                                                <input type="number" name="retirement_items[{{ $index }}][actual_amount]" 
                                                       class="form-control actual-amount" step="0.01" min="0" required>
                                            </div>
                                        </div>
                                        
                                        <!-- Hidden field for description -->
                                        <input type="hidden" name="retirement_items[{{ $index }}][description]" 
                                               value="{{ $imprestItem->notes ?: 'Retirement expense' }}">
                                    </div>
                                    @endforeach
                                @else
                                    <!-- Default empty item if no imprest items -->
                                    <div class="retirement-item border rounded p-3 mb-3" data-index="0">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0">Expenditure Item #1</h6>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-item" style="display: none;">
                                                <i class="bx bx-trash"></i> Remove
                                            </button>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Chart Account <span class="text-danger">*</span></label>
                                                <select name="retirement_items[0][chart_account_id]" class="form-select" required>
                                                    <option value="">Select Account</option>
                                                    @foreach($chartAccounts as $account)
                                                    <option value="{{ $account->id }}">
                                                        {{ $account->account_code }} - {{ $account->account_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Amount Requested</label>
                                                <input type="number" name="retirement_items[0][requested_amount]" 
                                                       class="form-control requested-amount" step="0.01" min="0" 
                                                       readonly style="background-color: #f8f9fa;">
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Amount Used <span class="text-danger">*</span></label>
                                                <input type="number" name="retirement_items[0][actual_amount]" 
                                                       class="form-control actual-amount" step="0.01" min="0" required>
                                            </div>
                                        </div>
                                        
                                        <!-- Hidden field for description -->
                                        <input type="hidden" name="retirement_items[0][description]" value="Retirement expense">
                                    </div>
                                @endif
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="button" id="add-item" class="btn btn-outline-primary">
                                    <i class="bx bx-plus me-1"></i>Add Another Item
                                </button>
                                <div class="text-end">
                                    <div class="mb-1">
                                        <small class="text-muted">Total Amount Used:</small>
                                        <span id="total-actual" class="fw-bold text-primary">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Supporting Document -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="bx bx-file me-2"></i>Supporting Documents</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="supporting_document" class="form-label">Upload Supporting Document</label>
                                <input type="file" class="form-control @error('supporting_document') is-invalid @enderror" 
                                       id="supporting_document" name="supporting_document" 
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <div class="form-text">
                                    Accepted formats: PDF, JPG, PNG, DOC, DOCX. Max size: 5MB
                                </div>
                                @error('supporting_document')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="retirement_notes" class="form-label">Retirement Notes</label>
                                <textarea class="form-control @error('retirement_notes') is-invalid @enderror" 
                                          id="retirement_notes" name="retirement_notes" rows="4" 
                                          placeholder="General notes about this retirement submission">{{ old('retirement_notes') }}</textarea>
                                @error('retirement_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Summary Card -->
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Retirement Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted">Amount Disbursed</small>
                                <div class="fw-bold text-info">{{ number_format($imprestRequest->disbursed_amount ?? 0, 2) }}</div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Total Amount Used</small>
                                <div class="fw-bold text-primary" id="summary-actual">0.00</div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Balance to Return</small>
                                <div class="fw-bold" id="summary-balance">{{ number_format($imprestRequest->disbursed_amount ?? 0, 2) }}</div>
                            </div>
                            
                            <hr>
                            
                            <div class="alert alert-info alert-sm">
                                <i class="bx bx-info-circle me-1"></i>
                                <small><strong>Note:</strong> This retirement will go through the same approval process as imprest requests.</small>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="{{ route('imprest.requests.show', $imprestRequest->id) }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="bx bx-paper-plane me-1"></i>Submit Retirement
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
.alert-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.retirement-item {
    background-color: #f8f9fa;
}

.variance-positive {
    color: #dc3545 !important;
}

.variance-negative {
    color: #198754 !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let itemIndex = {{ $imprestItems ? $imprestItems->count() - 1 : 0 }};
    const disbursedAmount = {{ $imprestRequest->disbursed_amount ?? 0 }};
    
    // Add new retirement item
    $('#add-item').click(function() {
        itemIndex++;
        const newItem = $('.retirement-item').first().clone();
        
        // Update the item
        newItem.attr('data-index', itemIndex);
        newItem.find('h6').text('Expenditure Item #' + (itemIndex + 1));
        newItem.find('.remove-item').show();
        
        // Clear values and update names
        newItem.find('input, select').each(function() {
            const name = $(this).attr('name');
            if (name) {
                // Update the index in the name attribute
                const newName = name.replace(/\[\d+\]/, '[' + itemIndex + ']');
                $(this).attr('name', newName);
            }
            
            // Clear values except for hidden description field
            if ($(this).attr('type') !== 'hidden') {
                $(this).val('');
            } else if (name && name.includes('description')) {
                $(this).val('Retirement expense');
            }
        });
        
        $('#retirement-items').append(newItem);
        updateTotals();
    });
    
    // Remove retirement item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('.retirement-item').remove();
        updateItemNumbers();
        updateTotals();
    });
    
    // Calculate totals when amounts change
    $(document).on('input', '.requested-amount, .actual-amount', function() {
        updateTotals();
    });
    
    function updateItemNumbers() {
        $('.retirement-item').each(function(index) {
            $(this).find('h6').text('Expenditure Item #' + (index + 1));
            $(this).find('.remove-item').toggle(index > 0);
        });
    }
    
    function updateTotals() {
        let totalActual = 0;
        
        $('.retirement-item').each(function() {
            const actual = parseFloat($(this).find('.actual-amount').val()) || 0;
            totalActual += actual;
        });
        
        $('#total-actual').text(totalActual.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#summary-actual').text(totalActual.toLocaleString('en-US', {minimumFractionDigits: 2}));
        
        const balance = disbursedAmount - totalActual;
        $('#summary-balance').text(balance.toLocaleString('en-US', {minimumFractionDigits: 2}));
        
        // Color balance
        $('#summary-balance').removeClass('text-success text-danger text-muted');
        if (balance > 0) {
            $('#summary-balance').addClass('text-success');
        } else if (balance < 0) {
            $('#summary-balance').addClass('text-danger');
        } else {
            $('#summary-balance').addClass('text-muted');
        }
    }
    
    // Form submission
    $('#retirementForm').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        const submitBtn = $('#submitBtn');
        const originalText = submitBtn.html();
        const form = $(this);
        
        console.log('Form submission started...');
        
        // Validate that we have at least one item with actual amount
        let hasValidItems = false;
        $('.retirement-item').each(function() {
            const actual = parseFloat($(this).find('.actual-amount').val()) || 0;
            if (actual > 0) {
                hasValidItems = true;
            }
        });
        
        if (!hasValidItems) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Please enter at least one amount used.',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Submitting...');
        
        console.log('Form validation passed, submitting...');
        
        // Create FormData to handle file uploads
        const formData = new FormData(form[0]);
        
        // AJAX submission
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                console.log('Success response:', response);
                
                if (response.success || response.redirect) {
                    // Success - show message and redirect
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Retirement submitted successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = response.redirect || "{{ route('imprest.retirement.index') }}";
                    });
                } else {
                    // Unexpected response format
                    console.log('Unexpected response format:', response);
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.log('Error occurred:', xhr, status, error);
                
                // Re-enable submit button
                submitBtn.prop('disabled', false).html(originalText);
                
                // Handle validation errors
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors || {};
                    let errorMessage = 'Please fix the following errors:\n';
                    
                    // Clear previous errors
                    $('.is-invalid').removeClass('is-invalid');
                    $('.invalid-feedback').hide();
                    $('#errorDisplay').hide();
                    
                    // Display errors
                    Object.keys(errors).forEach(field => {
                        const errorText = errors[field].join(', ');
                        errorMessage += `• ${errorText}\n`;
                        
                        // Highlight field if it exists
                        const fieldElement = $(`[name="${field}"]`);
                        if (fieldElement.length) {
                            fieldElement.addClass('is-invalid');
                            fieldElement.next('.invalid-feedback').text(errorText).show();
                        }
                    });
                    
                    // Show error alert
                    $('#errorDisplay .alert').text('Please fix the validation errors below.');
                    $('#errorDisplay').show();
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Errors',
                        text: errorMessage,
                        confirmButtonText: 'OK'
                    });
                    
                } else if (xhr.status === 500) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Server error occurred. Please try again or contact support.',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred: ' + (xhr.responseJSON?.message || error),
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
        
        return false;
    });
    
    // Initialize totals
    updateTotals();
});
</script>
@endpush