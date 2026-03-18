@extends('layouts.main')

@section('title', 'Edit Imprest Request')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'All Requests', 'url'        });
    }

    // Format currency helper function
    function formatCurrency(value) {
        return (Number(value) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Initialize total calculation
    calculateTotal();
});ute('imprest.requests.index'), 'icon' => 'bx bx-list-ul'],
            ['label' => 'Request Details', 'url' => route('imprest.requests.show', $imprestRequest->id), 'icon' => 'bx bx-show'],
            ['label' => 'Edit Request', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">Edit Imprest Request - {{ $imprestRequest->request_number }}</h5>
            <div>
                <a href="{{ route('imprest.requests.show', $imprestRequest->id) }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Details
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-1"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error me-1"></i>
            @foreach($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('imprest.requests.update', $imprestRequest->id) }}" method="POST" id="imprestRequestForm">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                            <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                                <option value="">-- Select Department --</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department_id', $imprestRequest->department_id) == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="amount_requested" class="form-label">Total Amount Requested (TZS) <span class="text-danger">*</span></label>
                            <input type="number" 
                                class="form-control @error('amount_requested') is-invalid @enderror"
                                id="amount_requested"
                                name="amount_requested"
                                value="{{ old('amount_requested', $imprestRequest->amount_requested) }}"
                                step="0.01"
                                min="0.01"
                                placeholder="0.00"
                                readonly
                                required>
                            @error('amount_requested')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="purpose" class="form-label">Purpose <span class="text-danger">*</span></label>
                            <textarea name="purpose" 
                                id="purpose" 
                                class="form-control @error('purpose') is-invalid @enderror" 
                                rows="3" 
                                placeholder="Enter the purpose for this imprest request..."
                                required>{{ old('purpose', $imprestRequest->purpose) }}</textarea>
                            @error('purpose')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Imprest Items Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Imprest Items</h6>
                                <button type="button" class="btn btn-light btn-sm" id="addItemBtn">
                                    <i class="bx bx-plus me-1"></i>Add Item
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="imprestItemsTable">
                                    <thead>
                                        <tr>
                                            <th width="30%">Chart Account</th>
                                            <th width="40%">Description</th>
                                            <th width="20%">Amount (TZS)</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="imprestItemsBody">
                                        @forelse($imprestRequest->imprestItems ?? [] as $index => $item)
                                        <tr data-row="{{ $index }}">
                                            <td>
                                                <select name="items[{{ $index }}][chart_account_id]" class="form-select chart-account-select" required>
                                                    <option value="">-- Select Account --</option>
                                                    @foreach($chartAccounts as $account)
                                                    <option value="{{ $account->id }}" {{ $item->chart_account_id == $account->id ? 'selected' : '' }}>
                                                        {{ $account->account_code }} - {{ $account->account_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" 
                                                    name="items[{{ $index }}][notes]" 
                                                    class="form-control item-description" 
                                                    value="{{ $item->notes }}"
                                                    placeholder="Enter description..." 
                                                    required>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                    name="items[{{ $index }}][amount]" 
                                                    class="form-control item-amount" 
                                                    value="{{ $item->amount }}"
                                                    step="0.01" 
                                                    min="0.01" 
                                                    placeholder="0.00" 
                                                    required>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-item-btn">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr data-row="0">
                                            <td>
                                                <select name="items[0][chart_account_id]" class="form-select chart-account-select" required>
                                                    <option value="">-- Select Account --</option>
                                                    @foreach($chartAccounts as $account)
                                                    <option value="{{ $account->id }}">
                                                        {{ $account->account_code }} - {{ $account->account_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" 
                                                    name="items[0][notes]" 
                                                    class="form-control item-description" 
                                                    placeholder="Enter description..." 
                                                    required>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                    name="items[0][amount]" 
                                                    class="form-control item-amount" 
                                                    step="0.01" 
                                                    min="0.01" 
                                                    placeholder="0.00" 
                                                    required>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-item-btn">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-info">
                                            <td colspan="2" class="text-end"><strong>Total Amount:</strong></td>
                                            <td><strong>TZS <span id="totalAmount">{{ number_format($imprestRequest->amount_requested ?? 0, 2) }}</span></strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 text-end">
                            <button type="button" class="btn btn-secondary me-2" onclick="window.history.back()">
                                <i class="bx bx-x me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-success" id="submitBtn">
                                <i class="bx bx-save me-1"></i>Update Request
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let itemCounter = {{ count($imprestRequest->imprestItems ?? []) }};

    // Add item functionality
    $('#addItemBtn').click(function() {
        addNewItem();
    });

    // Remove item functionality
    $(document).on('click', '.remove-item-btn', function() {
        if ($('#imprestItemsBody tr').length > 1) {
            $(this).closest('tr').remove();
            calculateTotal();
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Remove',
                text: 'At least one item is required.',
                confirmButtonText: 'OK'
            });
        }
    });

    // Calculate total when amount changes
    $(document).on('input', '.item-amount', function() {
        calculateTotal();
    });

    // Auto-populate description based on chart account selection
    $(document).on('change', '.chart-account-select', function() {
        const selectedText = $(this).find('option:selected').text();
        const accountName = selectedText.split(' - ')[1]; // Get account name part
        const descriptionField = $(this).closest('tr').find('.item-description');
        
        // Only populate if description is empty
        if (descriptionField.val().trim() === '' && accountName) {
            descriptionField.val(accountName);
        }
    });

    // Add new item row
    function addNewItem() {
        const newRow = `
            <tr data-row="${itemCounter}">
                <td>
                    <select name="items[${itemCounter}][chart_account_id]" class="form-select chart-account-select" required>
                        <option value="">-- Select Account --</option>
                        @foreach($chartAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" 
                        name="items[${itemCounter}][notes]" 
                        class="form-control item-description" 
                        placeholder="Enter description..." 
                        required>
                </td>
                <td>
                    <input type="number" 
                        name="items[${itemCounter}][amount]" 
                        class="form-control item-amount" 
                        step="0.01" 
                        min="0.01" 
                        placeholder="0.00" 
                        required>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-item-btn">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#imprestItemsBody').append(newRow);
        itemCounter++;
    }

    // Calculate total amount
    function calculateTotal() {
        let total = 0;
        $('.item-amount').each(function() {
            const amount = parseFloat($(this).val()) || 0;
            total += amount;
        });
        
        $('#totalAmount').text(total.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
        
        $('#amount_requested').val(total.toFixed(2));
    }

    // Budget validation for edit form
    $(document).on('blur', '.item-amount', function() {
        const row = $(this).closest('tr');
        const chartAccountSelect = row.find('.chart-account-select');
        const amountInput = $(this);
        
        const chartAccountId = chartAccountSelect.val();
        const amount = parseFloat(amountInput.val());
        
        if (chartAccountId && amount && amount > 0) {
            validateBudgetForEdit(chartAccountId, amount, row);
        }
    });

    function validateBudgetForEdit(chartAccountId, amount, row) {
        $.ajax({
            url: '{{ route("imprest.validate-budget") }}',
            method: 'POST',
            data: {
                chart_account_id: chartAccountId,
                amount: amount,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // Remove any existing budget warning
                row.find('.budget-warning').remove();
                
                if (response.success && response.budget_check_enabled && response.budget_details) {
                    // Add budget info as a small indicator
                    const budgetInfo = `
                        <small class="text-success budget-warning">
                            ✓ Budget: TZS ${formatCurrency(response.budget_details.available_after_request)} remaining
                        </small>
                    `;
                    row.find('.item-amount').after(budgetInfo);
                }
            },
            error: function(xhr) {
                // Remove any existing budget warning
                row.find('.budget-warning').remove();
                
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    // Add budget warning
                    const budgetWarning = `
                        <small class="text-danger budget-warning">
                            ⚠ Budget exceeded
                        </small>
                    `;
                    row.find('.item-amount').after(budgetWarning);
                    
                    // Show detailed error on focus
                    row.find('.item-amount').on('focus.budget-error', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Budget Exceeded',
                            text: xhr.responseJSON.error,
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'swal2-wide'
                            }
                        });
                        $(this).off('focus.budget-error');
                    });
                }
            }
        });
    }

    // Form validation
    $('#imprestRequestForm').on('submit', function(e) {
        e.preventDefault();
        
        const items = $('.item-amount').length;
        const totalAmount = parseFloat($('#amount_requested').val()) || 0;
        
        if (items === 0) {
            Swal.fire({
                icon: 'error',
                title: 'No Items',
                text: 'Please add at least one item to the request.',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        if (totalAmount <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Amount',
                text: 'Total amount must be greater than zero.',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Show confirmation
        Swal.fire({
            icon: 'question',
            title: 'Update Request',
            text: 'Are you sure you want to update this imprest request?',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Update',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Disable submit button and show loading
                $('#submitBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...');
                
                // Submit the form
                this.submit();
            }
        });
    });

    // Initialize total calculation
    calculateTotal();
});
</script>
@endpush

@push('styles')
<style>
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.item-amount {
    text-align: right;
}

.table-responsive {
    border-radius: 0.375rem;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,.125);
}

.remove-item-btn {
    padding: 0.25rem 0.5rem;
}

.chart-account-select {
    font-size: 0.875rem;
}

/* Budget validation styling */
.budget-warning {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
}

.swal2-wide {
    width: 600px !important;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>
@endpush