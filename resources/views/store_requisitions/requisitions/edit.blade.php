@extends('layouts.main')

@section('title', 'Edit Store Requisition')

@push('styles')
<style>
.hover-bg-light:hover {
    background-color: #f8f9fa !important;
}
.cursor-pointer {
    cursor: pointer;
}
#searchResults .cursor-pointer:hover {
    background-color: #e9ecef !important;
}
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Requisitions', 'url' => route('store-requisitions.requisitions.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Edit Requisition', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0 text-primary">
                                    <i class="bx bx-edit me-2"></i>Edit Store Requisition #{{ $storeRequisition->requisition_number }}
                                </h5>
                                <small class="text-muted">
                                    Status: 
                                    @if($storeRequisition->status === 'pending')
                                        <span class="badge bg-warning">{{ ucfirst($storeRequisition->status) }}</span>
                                    @elseif($storeRequisition->status === 'approved')
                                        <span class="badge bg-success">{{ ucfirst($storeRequisition->status) }}</span>
                                    @elseif($storeRequisition->status === 'rejected')
                                        <span class="badge bg-danger">{{ ucfirst($storeRequisition->status) }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($storeRequisition->status) }}</span>
                                    @endif
                                </small>
                            </div>
                            <div>
                                <a href="{{ route('store-requisitions.requisitions.show', $storeRequisition->hash_id) }}" class="btn btn-info me-2">
                                    <i class="bx bx-show me-1"></i> View Details
                                </a>
                                <a href="{{ route('store-requisitions.requisitions.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>

                    @if($storeRequisition->status !== 'pending')
                        <div class="alert alert-warning alert-dismissible fade show m-3" role="alert">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Note:</strong> This requisition has been {{ $storeRequisition->status }}. Only certain fields can be modified.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('store-requisitions.requisitions.update', $storeRequisition->hash_id) }}" method="POST" id="requisitionForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <!-- Requisition Details -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="employee_id" class="form-label required">Requested By</label>
                                    @if(auth()->user()->hasRole(['admin', 'manager']) || auth()->user()->can('create_requisitions_for_others'))
                                        <!-- Admin/Manager can select any employee -->
                                        <select name="employee_id" id="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                                            <option value="">Select Employee</option>
                                            @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}" {{ (old('employee_id', $storeRequisition->requested_by) == $employee->id) ? 'selected' : '' }}>
                                                {{ $employee->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <!-- Regular user can only edit their own requisitions -->
                                        <input type="text" 
                                               class="form-control" 
                                               value="{{ $storeRequisition->requestedBy->name ?? 'Unknown Employee' }}" 
                                               readonly>
                                        <input type="hidden" 
                                               name="employee_id" 
                                               value="{{ $storeRequisition->requested_by }}">
                                        <small class="text-muted">Requested by employee cannot be changed</small>
                                    @endif
                                    @error('employee_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="request_date" class="form-label required">Request Date</label>
                                    <input type="date" 
                                           name="request_date" 
                                           id="request_date" 
                                           class="form-control @error('request_date') is-invalid @enderror"
                                           value="{{ old('request_date', $storeRequisition->required_date?->format('Y-m-d')) }}"
                                           min="{{ date('Y-m-d') }}"
                                           required>
                                    @error('request_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <label for="purpose" class="form-label required">Purpose/Reason</label>
                                    <textarea name="purpose" 
                                              id="purpose" 
                                              class="form-control @error('purpose') is-invalid @enderror"
                                              rows="3"
                                              placeholder="Describe the purpose or reason for this requisition"
                                              required>{{ old('purpose', $storeRequisition->purpose) }}</textarea>
                                    @error('purpose')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <label for="description" class="form-label">Additional Notes</label>
                                    <textarea name="description" 
                                              id="description" 
                                              class="form-control @error('description') is-invalid @enderror"
                                              rows="2"
                                              placeholder="Any additional information or notes">{{ old('description', $storeRequisition->notes) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Items Section -->
                            <div class="border rounded p-3 mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 text-primary">
                                        <i class="bx bx-list-check me-2"></i>Requisition Items
                                    </h6>
                                </div>

                                @if($storeRequisition->status === 'pending')
                                <!-- Add Item Form -->
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label for="itemSelect" class="form-label">Select Item</label>
                                                <div class="position-relative">
                                                    <input type="text" 
                                                           id="itemSearch" 
                                                           class="form-control" 
                                                           placeholder="Search items..." 
                                                           autocomplete="off">
                                                    <select id="itemSelect" class="form-select d-none">
                                                        <option value="">Choose an item...</option>
                                                        @foreach($products as $product)
                                                        <option value="{{ $product->id }}" 
                                                                data-name="{{ $product->name }}" 
                                                                data-unit="{{ $product->unit_of_measure }}"
                                                                data-stock="{{ $product->current_stock ?? 0 }}"
                                                                data-search="{{ strtolower($product->name . ' ' . ($product->category->name ?? '')) }}">
                                                            {{ $product->name }} (Stock: {{ $product->current_stock ?? 0 }})
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                    <div id="searchResults" class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-sm d-none" style="z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="itemQuantity" class="form-label">Quantity</label>
                                                <input type="number" id="itemQuantity" class="form-control" min="0.01" step="0.01" placeholder="0.00">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Unit</label>
                                                <div class="form-control-plaintext" id="unitDisplay">-</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="itemNotes" class="form-label">Notes (Optional)</label>
                                                <input type="text" id="itemNotes" class="form-control" placeholder="Optional notes">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label">&nbsp;</label>
                                                <button type="button" class="btn btn-primary d-block" onclick="addItemToTable()">
                                                    <i class="bx bx-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="itemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="35%">Product</th>
                                                <th width="15%">Quantity @if($storeRequisition->status === 'pending')<small class="text-muted">(Editable)</small>@endif</th>
                                                <th width="15%">Unit</th>
                                                <th width="25%">Notes @if($storeRequisition->status === 'pending')<small class="text-muted">(Editable)</small>@endif</th>
                                                @if($storeRequisition->status === 'pending')
                                                <th width="10%">Action</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody id="itemsBody">
                                            @foreach($storeRequisition->items as $index => $item)
                                            <tr>
                                                <td>
                                                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->inventory_item_id }}">
                                                    <div class="fw-medium">{{ $item->product->name }}</div>
                                                </td>
                                                <td>
                                                    <input type="number" 
                                                           name="items[{{ $index }}][quantity_requested]" 
                                                           class="form-control form-control-sm" 
                                                           min="0.01" 
                                                           step="0.01"
                                                           value="{{ $item->quantity_requested }}"
                                                           {{ $storeRequisition->status !== 'pending' ? 'readonly' : '' }}
                                                           required>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">{{ $item->unit_of_measure }}</span>
                                                </td>
                                                <td>
                                                    <input type="text" 
                                                           name="items[{{ $index }}][item_notes]" 
                                                           class="form-control form-control-sm" 
                                                           value="{{ $item->item_notes }}"
                                                           {{ $storeRequisition->status !== 'pending' ? 'readonly' : '' }}
                                                           placeholder="Optional notes">
                                                </td>
                                                @if($storeRequisition->status === 'pending')
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </td>
                                                @endif
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if($storeRequisition->items->count() == 0)
                                <div class="text-muted text-center py-3" id="noItemsMessage">
                                    <i class="bx bx-info-circle me-2"></i>
                                    No items added yet. Click "Add Item" to start.
                                </div>
                                @else
                                <div class="text-muted text-center py-3" id="noItemsMessage" style="display: none;">
                                    <i class="bx bx-info-circle me-2"></i>
                                    No items added yet. Click "Add Item" to start.
                                </div>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between">
                                <div>
                                    @if($storeRequisition->status === 'pending')
                                    <button type="button" class="btn btn-light" onclick="resetForm()">
                                        <i class="bx bx-refresh me-1"></i> Reset Changes
                                    </button>
                                    @endif
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i> Update Requisition
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
let itemCounter = {{ $storeRequisition->items->count() }};
let selectedItemId = null;

@if($storeRequisition->status === 'pending')
function addItem() {
    // Reset form first - proper Select2 reset
    $('#itemSelect').val(null).trigger('change');
    document.getElementById('itemQuantity').value = '';
    document.getElementById('itemNotes').value = '';
    document.getElementById('unitDisplay').textContent = 'Unit';
    document.getElementById('stockDisplay').textContent = '0';
    
    // Use Bootstrap modal method for reliability
    $('#addItemModal').modal('show');
    
    // Focus on item select after modal is shown
    $('#addItemModal').on('shown.bs.modal', function () {
        $('#itemSelect').select2('open');
        $(this).off('shown.bs.modal'); // Remove this event listener after use
    });
}

function removeItem(button) {
    const row = button.closest('tr');
    row.remove();
    
    // Show no items message if no items left
    const itemsBody = document.getElementById('itemsBody');
    const noItemsMessage = document.getElementById('noItemsMessage');
    
    if (itemsBody.children.length === 0) {
        noItemsMessage.style.display = 'block';
    }
}

function resetForm() {
    if (confirm('Are you sure you want to reset changes? All unsaved data will be lost.')) {
        location.reload();
    }
}

// Initialize Select2 and form handlers when document is ready
$(document).ready(function() {
    // Initialize Select2 for item selection
    $('#itemSelect').select2({
        theme: 'bootstrap-5',
        placeholder: 'Search and select an item...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#addItemModal'),
        minimumResultsForSearch: 0,
        containerCssClass: 'select2-selection--clearable'
    });

    // Handle item selection change
    $('#itemSelect').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const unit = selectedOption.data('unit') || 'Unit';
        const stock = selectedOption.data('stock') || 0;
        
        document.getElementById('unitDisplay').textContent = unit;
        document.getElementById('stockDisplay').textContent = stock;
        
        // Focus on quantity input
        if (selectedOption.val()) {
            document.getElementById('itemQuantity').focus();
        }
    });

    // Handle add item form submission
    $('#addItemForm').on('submit', function(e) {
        e.preventDefault();
        
        const itemId = $('#itemSelect').val();
        const itemName = $('#itemSelect option:selected').data('name');
        const itemUnit = $('#itemSelect option:selected').data('unit');
        const quantity = $('#itemQuantity').val();
        const notes = $('#itemNotes').val();
        
        if (!itemId) {
            alert('Please select an item.');
            return;
        }
        
        if (!quantity || parseFloat(quantity) <= 0) {
            alert('Please enter a valid quantity.');
            return;
        }
        
        // Check if item already exists in the table
        const existingItems = document.querySelectorAll('input[name*="[product_id]"]');
        let itemExists = false;
        
        existingItems.forEach(input => {
            if (input.value == itemId) {
                itemExists = true;
            }
        });
        
        if (itemExists) {
            alert('This item has already been added to the requisition.');
            return;
        }
        
        // Add item to table
        const itemsBody = document.getElementById('itemsBody');
        const noItemsMessage = document.getElementById('noItemsMessage');
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="hidden" name="items[${itemCounter}][product_id]" value="${itemId}">
                <div class="fw-medium">${itemName}</div>
            </td>
            <td>
                <input type="number" 
                       name="items[${itemCounter}][quantity_requested]" 
                       class="form-control form-control-sm" 
                       min="0.01" 
                       step="0.01"
                       value="${quantity}"
                       required>
            </td>
            <td>
                <span class="badge bg-light text-dark">${itemUnit}</span>
            </td>
            <td>
                <input type="text" 
                       name="items[${itemCounter}][item_notes]" 
                       class="form-control form-control-sm" 
                       value="${notes}"
                       placeholder="Optional notes">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                    <i class="bx bx-trash"></i>
                </button>
            </td>
        `;
        
        itemsBody.appendChild(row);
        itemCounter++;
        
        // Hide no items message
        noItemsMessage.style.display = 'none';
        
        // Reset form immediately
        $('#itemSelect').val(null).trigger('change');
        $('#itemQuantity').val('');
        $('#itemNotes').val('');
        document.getElementById('unitDisplay').textContent = 'Unit';
        document.getElementById('stockDisplay').textContent = '0';
        
        // Close modal using Bootstrap method
        $('#addItemModal').modal('hide');
    });
});
@endif

// Form validation
document.getElementById('requisitionForm').addEventListener('submit', function(e) {
    const itemsBody = document.getElementById('itemsBody');
    
    if (itemsBody.children.length === 0) {
        e.preventDefault();
        Swal.fire('Validation Error!', 'Please add at least one item to the requisition.', 'error');
        return false;
    }
    
    // Validate all quantity fields
    const quantityInputs = itemsBody.querySelectorAll('input[name*="[quantity_requested]"]');
    let hasError = false;
    
    quantityInputs.forEach(input => {
        if (!input.value || parseFloat(input.value) <= 0) {
            input.classList.add('is-invalid');
            hasError = true;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    if (hasError) {
        e.preventDefault();
        alert('Please enter valid quantities for all items.');
        return false;
    }
});
</script>
@endpush