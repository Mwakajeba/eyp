@extends('layouts.main')

@section('title', 'Edit Retirement')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Request Details', 'url' => route('imprest.requests.show', $retirement->imprest_request_id), 'icon' => 'bx bx-show'],
            ['label' => 'Retirement Details', 'url' => route('imprest.retirement.show', $retirement->id), 'icon' => 'bx bx-receipt'],
            ['label' => 'Edit Retirement', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">Edit Retirement - {{ $retirement->retirement_number }}</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('imprest.retirement.show', $retirement->id) }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to Details
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <form id="retirementForm" method="POST" action="{{ route('imprest.retirement.update', $retirement->id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <!-- Imprest Request Info -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Related Imprest Request</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Request Number</label>
                                    <div class="form-control-plaintext">
                                        <a href="{{ route('imprest.requests.show', $retirement->imprestRequest->id) }}" class="text-primary">
                                            {{ $retirement->imprestRequest->request_number }}
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Employee</label>
                                    <div class="form-control-plaintext">{{ $retirement->imprestRequest->employee->name ?? 'N/A' }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Amount Disbursed</label>
                                    <div class="form-control-plaintext fw-bold text-success">
                                        TZS {{ number_format($retirement->imprestRequest->disbursed_amount ?? 0, 2) }}
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Purpose</label>
                                    <div class="form-control-plaintext">{{ $retirement->imprestRequest->purpose }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Retirement Items -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Expenditure Breakdown</h6>
                            <button type="button" class="btn btn-light btn-sm" id="addItemBtn">
                                <i class="bx bx-plus me-1"></i>Add Item
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="retirementItemsContainer">
                                @foreach($retirement->retirementItems as $index => $item)
                                <div class="retirement-item border rounded p-3 mb-3" data-index="{{ $index }}">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="text-primary mb-0">Item {{ $index + 1 }}</h6>
                                        @if($index > 0)
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                        @endif
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Chart Account <span class="text-danger">*</span></label>
                                            <select class="form-select chart-account-select" name="retirement_items[{{ $index }}][chart_account_id]" required>
                                                <option value="">Select Account</option>
                                                @foreach($chartAccounts as $account)
                                                <option value="{{ $account->id }}" {{ $item->chart_account_id == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Amount Requested</label>
                                            <input type="number" step="0.01" min="0" 
                                                   class="form-control requested-amount" 
                                                   name="retirement_items[{{ $index }}][requested_amount]" 
                                                   value="{{ $item->requested_amount }}"
                                                   readonly style="background-color: #f8f9fa;">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Amount Used <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" min="0" 
                                                   class="form-control actual-amount" 
                                                   name="retirement_items[{{ $index }}][actual_amount]" 
                                                   value="{{ $item->actual_amount }}"
                                                   required>
                                        </div>
                                    </div>
                                    
                                    <!-- Hidden field for description -->
                                    <input type="hidden" name="retirement_items[{{ $index }}][description]" 
                                           value="{{ $item->description ?: 'Retirement expense' }}">
                                </div>
                                @endforeach
                            </div>

                            <!-- Summary Section -->
                            <div class="card bg-light mt-4">
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Total Requested</label>
                                            <div class="h5 text-primary" id="totalRequested">TZS 0.00</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Total Actual</label>
                                            <div class="h5 text-success" id="totalActual">TZS 0.00</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Total Variance</label>
                                            <div class="h5" id="totalVariance">TZS 0.00</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Remaining Balance</label>
                                            <div class="h5" id="remainingBalance">TZS 0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Supporting Document & Notes -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="bx bx-file me-2"></i>Supporting Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="supportingDocument" class="form-label">Supporting Document</label>
                                    @if($retirement->supporting_document)
                                    <div class="mb-2">
                                        <small class="text-muted">Current document:</small>
                                        <a href="{{ Storage::url($retirement->supporting_document) }}" target="_blank" class="text-primary ms-2">
                                            <i class="bx bx-file me-1"></i>{{ basename($retirement->supporting_document) }}
                                        </a>
                                    </div>
                                    @endif
                                    <input type="file" class="form-control" id="supportingDocument" name="supporting_document" 
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                    <small class="form-text text-muted">
                                        Upload receipts, invoices, or other supporting documents (PDF, JPG, PNG, DOC, DOCX - Max 5MB)
                                        @if($retirement->supporting_document)
                                        <br><em>Leave empty to keep current document</em>
                                        @endif
                                    </small>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="retirementNotes" class="form-label">Retirement Notes</label>
                                    <textarea class="form-control" id="retirementNotes" name="retirement_notes" rows="4" 
                                              placeholder="Additional notes about this retirement...">{{ $retirement->retirement_notes }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mb-4">
                        <a href="{{ route('imprest.retirement.show', $retirement->id) }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bx bx-save me-1"></i>Update Retirement
                        </button>
                    </div>
                </form>
            </div>

            <div class="col-md-4">
                <!-- Quick Info -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Quick Info</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Status</small>
                            <div class="{{ $retirement->getStatusBadgeClass() }}">{{ $retirement->getStatusLabel() }}</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Submitted Date</small>
                            <div>{{ $retirement->submitted_at->format('M d, Y H:i') }}</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Amount Disbursed</small>
                            <div class="fw-bold text-success">TZS {{ number_format($retirement->imprestRequest->disbursed_amount ?? 0, 2) }}</div>
                        </div>
                        @if($retirement->retirement_notes)
                        <div class="mb-3">
                            <small class="text-muted">Current Notes</small>
                            <div class="small border rounded p-2 bg-light">{{ Str::limit($retirement->retirement_notes, 100) }}</div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Instructions -->
                <div class="card mt-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bx bx-bulb me-2"></i>Instructions</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2"><i class="bx bx-check-circle text-success me-1"></i>Update actual amounts based on receipts</li>
                            <li class="mb-2"><i class="bx bx-check-circle text-success me-1"></i>Attach supporting documents</li>
                            <li class="mb-2"><i class="bx bx-check-circle text-success me-1"></i>Review variance calculations</li>
                            <li class="mb-2"><i class="bx bx-check-circle text-success me-1"></i>Provide detailed notes if needed</li>
                            <li><i class="bx bx-info-circle text-info me-1"></i>You can only edit pending retirements</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let itemIndex = {{ $retirement->retirementItems->count() }};
    const disbursedAmount = {{ $retirement->imprestRequest->disbursed_amount ?? 0 }};

    // Calculate totals
    function calculateTotals() {
        let totalRequested = 0;
        let totalActual = 0;

        $('.retirement-item').each(function() {
            const requested = parseFloat($(this).find('.requested-amount').val()) || 0;
            const actual = parseFloat($(this).find('.actual-amount').val()) || 0;
            
            totalRequested += requested;
            totalActual += actual;
            
            // Calculate and update variance for this item
            const variance = actual - requested;
            $(this).find('.variance-amount').val(formatNumber(variance));
            
            // Update variance color
            const varianceField = $(this).find('.variance-amount');
            varianceField.removeClass('text-success text-danger text-muted');
            if (variance > 0) {
                varianceField.addClass('text-danger');
            } else if (variance < 0) {
                varianceField.addClass('text-success');
            } else {
                varianceField.addClass('text-muted');
            }
        });

        const totalVariance = totalActual - totalRequested;
        const remainingBalance = disbursedAmount - totalActual;

        // Update display
        $('#totalRequested').text('TZS ' + formatNumber(totalRequested));
        $('#totalActual').text('TZS ' + formatNumber(totalActual));
        
        const varianceElement = $('#totalVariance');
        varianceElement.text('TZS ' + formatNumber(totalVariance));
        varianceElement.removeClass('text-success text-danger text-muted');
        if (totalVariance > 0) {
            varianceElement.addClass('text-danger');
        } else if (totalVariance < 0) {
            varianceElement.addClass('text-success');
        } else {
            varianceElement.addClass('text-muted');
        }
        
        const balanceElement = $('#remainingBalance');
        balanceElement.text('TZS ' + formatNumber(remainingBalance));
        balanceElement.removeClass('text-success text-danger text-muted');
        if (remainingBalance > 0) {
            balanceElement.addClass('text-success');
        } else if (remainingBalance < 0) {
            balanceElement.addClass('text-danger');
        } else {
            balanceElement.addClass('text-muted');
        }
    }

    // Format number with commas
    function formatNumber(num) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num);
    }

    // Add new item
    $('#addItemBtn').click(function() {
        const newItem = `
        <div class="retirement-item border rounded p-3 mb-3" data-index="${itemIndex}">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="text-primary mb-0">Expenditure Item #${itemIndex + 1}</h6>
                <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                    <i class="bx bx-trash"></i>
                </button>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Chart Account <span class="text-danger">*</span></label>
                    <select class="form-select chart-account-select" name="retirement_items[${itemIndex}][chart_account_id]" required>
                        <option value="">Select Account</option>
                        @foreach($chartAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Amount Requested</label>
                    <input type="number" step="0.01" min="0" class="form-control requested-amount" 
                           name="retirement_items[${itemIndex}][requested_amount]" 
                           readonly style="background-color: #f8f9fa;">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Amount Used <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control actual-amount" 
                           name="retirement_items[${itemIndex}][actual_amount]" required>
                </div>
            </div>
            
            <!-- Hidden field for description -->
            <input type="hidden" name="retirement_items[${itemIndex}][description]" value="Retirement expense">
        </div>`;
        
        $('#retirementItemsContainer').append(newItem);
        itemIndex++;
        updateItemNumbers();
        calculateTotals();
    });

    // Remove item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('.retirement-item').remove();
        updateItemNumbers();
        calculateTotals();
    });

    // Update item numbers
    function updateItemNumbers() {
        $('.retirement-item').each(function(index) {
            $(this).find('h6').text(`Item ${index + 1}`);
        });
    }

    // Calculate on amount change
    $(document).on('input', '.requested-amount, .actual-amount', function() {
        calculateTotals();
    });

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
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...');
        
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
                        text: 'Retirement updated successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = response.redirect || "{{ route('imprest.retirement.show', $retirement->id) }}";
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

    // Initial calculation
    calculateTotals();
});
</script>
@endpush