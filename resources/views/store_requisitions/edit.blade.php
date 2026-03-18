@extends('layouts.main')

@section('title', 'Edit Store Requisition')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Requisitions', 'url' => route('store-requisitions.index'), 'icon' => 'bx bx-package'],
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
                                <a href="{{ route('store-requisitions.show', $storeRequisition->id) }}" class="btn btn-info me-2">
                                    <i class="bx bx-show me-1"></i> View Details
                                </a>
                                <a href="{{ route('store-requisitions.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>

                    @if($storeRequisition->status !== 'draft' && $storeRequisition->status !== 'pending')
                        <div class="alert alert-warning alert-dismissible fade show m-3" role="alert">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Note:</strong> This requisition has been {{ $storeRequisition->status }}. Only certain fields can be modified.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('store-requisitions.update', $storeRequisition->id) }}" method="POST" id="requisitionForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <!-- Requisition Details -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="branch_id" class="form-label required">Branch</label>
                                    <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" 
                                            {{ in_array($storeRequisition->status, ['draft', 'pending']) ? '' : 'disabled' }} required>
                                        <option value="">Select Branch</option>
                                        @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" 
                                            {{ (old('branch_id', $storeRequisition->branch_id) == $branch->id) ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('branch_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" 
                                           name="department" 
                                           id="department" 
                                           class="form-control @error('department') is-invalid @enderror"
                                           value="{{ old('department', $storeRequisition->department) }}"
                                           placeholder="Enter department"
                                           {{ in_array($storeRequisition->status, ['draft', 'pending']) ? '' : 'readonly' }}>
                                    @error('department')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="priority" class="form-label required">Priority</label>
                                    <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" 
                                            {{ in_array($storeRequisition->status, ['draft', 'pending']) ? '' : 'disabled' }} required>
                                        <option value="">Select Priority</option>
                                        <option value="low" {{ old('priority', $storeRequisition->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="medium" {{ old('priority', $storeRequisition->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ old('priority', $storeRequisition->priority) == 'high' ? 'selected' : '' }}>High</option>
                                        <option value="urgent" {{ old('priority', $storeRequisition->priority) == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="required_date" class="form-label required">Required Date</label>
                                    <input type="date" 
                                           name="required_date" 
                                           id="required_date" 
                                           class="form-control @error('required_date') is-invalid @enderror"
                                           value="{{ old('required_date', $storeRequisition->required_date->format('Y-m-d')) }}"
                                           min="{{ date('Y-m-d') }}"
                                           {{ in_array($storeRequisition->status, ['draft', 'pending']) ? '' : 'readonly' }}
                                           required>
                                    @error('required_date')
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
                                              {{ in_array($storeRequisition->status, ['draft', 'pending']) ? '' : 'readonly' }}
                                              required>{{ old('purpose', $storeRequisition->purpose) }}</textarea>
                                    @error('purpose')
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
                                    @if(in_array($storeRequisition->status, ['draft', 'pending']))
                                    <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                                        <i class="bx bx-plus me-1"></i> Add Item
                                    </button>
                                    @endif
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="itemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="35%">Product</th>
                                                <th width="15%">Quantity</th>
                                                <th width="15%">Unit</th>
                                                <th width="25%">Notes</th>
                                                @if(in_array($storeRequisition->status, ['draft', 'pending']))
                                                <th width="10%">Action</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody id="itemsBody">
                                            @foreach($storeRequisition->items as $index => $item)
                                            <tr>
                                                <td>
                                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                                    <div class="fw-medium">{{ $item->product->name }}</div>
                                                </td>
                                                <td>
                                                    <input type="number" 
                                                           name="items[{{ $index }}][quantity]" 
                                                           class="form-control form-control-sm" 
                                                           min="1" 
                                                           step="0.01"
                                                           value="{{ old("items.{$index}.quantity", $item->quantity) }}"
                                                           {{ in_array($storeRequisition->status, ['draft', 'pending']) ? '' : 'readonly' }}
                                                           required>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">{{ $item->product->unit }}</span>
                                                </td>
                                                <td>
                                                    <input type="text" 
                                                           name="items[{{ $index }}][notes]" 
                                                           class="form-control form-control-sm" 
                                                           value="{{ old("items.{$index}.notes", $item->notes) }}"
                                                           placeholder="Optional notes"
                                                           {{ in_array($storeRequisition->status, ['draft', 'pending']) ? '' : 'readonly' }}>
                                                </td>
                                                @if(in_array($storeRequisition->status, ['draft', 'pending']))
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this, {{ $item->id }})">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </td>
                                                @endif
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if($storeRequisition->items->count() === 0)
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
                            @if(in_array($storeRequisition->status, ['draft', 'pending']))
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="button" class="btn btn-light" onclick="resetForm()">
                                        <i class="bx bx-refresh me-1"></i> Reset Changes
                                    </button>
                                </div>
                                <div>
                                    @if($storeRequisition->status === 'draft')
                                    <button type="button" class="btn btn-secondary me-2" onclick="saveDraft()">
                                        <i class="bx bx-save me-1"></i> Save as Draft
                                    </button>
                                    @endif
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-check me-1"></i> Update Requisition
                                    </button>
                                </div>
                            </div>
                            @else
                            <div class="text-center">
                                <div class="alert alert-info">
                                    <i class="bx bx-info-circle me-2"></i>
                                    This requisition cannot be modified as it has been {{ $storeRequisition->status }}.
                                </div>
                            </div>
                            @endif

                            <!-- Hidden field to track deleted items -->
                            <input type="hidden" name="deleted_items" id="deletedItems" value="">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Selection Modal -->
@if(in_array($storeRequisition->status, ['draft', 'pending']))
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="productsTable">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category->name ?? 'N/A' }}</td>
                                <td>{{ $product->unit }}</td>
                                <td>{{ $product->current_stock ?? 0 }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            onclick="selectProduct({{ $product->id }}, '{{ $product->name }}', '{{ $product->unit }}')">
                                        Select
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
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
let currentRowIndex = null;
let deletedItems = [];

function addItem() {
    currentRowIndex = itemCounter;
    $('#productModal').modal('show');
}

function selectProduct(productId, productName, unit) {
    const itemsBody = document.getElementById('itemsBody');
    const noItemsMessage = document.getElementById('noItemsMessage');
    
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="hidden" name="items[${itemCounter}][product_id]" value="${productId}">
            <div class="fw-medium">${productName}</div>
        </td>
        <td>
            <input type="number" 
                   name="items[${itemCounter}][quantity]" 
                   class="form-control form-control-sm" 
                   min="1" 
                   step="0.01"
                   required>
        </td>
        <td>
            <span class="badge bg-light text-dark">${unit}</span>
        </td>
        <td>
            <input type="text" 
                   name="items[${itemCounter}][notes]" 
                   class="form-control form-control-sm" 
                   placeholder="Optional notes">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this, null)">
                <i class="bx bx-trash"></i>
            </button>
        </td>
    `;
    
    itemsBody.appendChild(row);
    itemCounter++;
    
    // Hide no items message
    noItemsMessage.style.display = 'none';
    
    // Close modal
    $('#productModal').modal('hide');
}

function removeItem(button, itemId) {
    const row = button.closest('tr');
    
    if (itemId) {
        // Add to deleted items list
        deletedItems.push(itemId);
        document.getElementById('deletedItems').value = deletedItems.join(',');
    }
    
    row.remove();
    
    // Show no items message if no items left
    const itemsBody = document.getElementById('itemsBody');
    const noItemsMessage = document.getElementById('noItemsMessage');
    
    if (itemsBody.children.length === 0) {
        noItemsMessage.style.display = 'block';
    }
}

function resetForm() {
    if (confirm('Are you sure you want to reset all changes? This will revert to the original values.')) {
        location.reload();
    }
}

function saveDraft() {
    const form = document.getElementById('requisitionForm');
    const formData = new FormData(form);
    formData.append('status', 'draft');
    
    $.ajax({
        url: form.action,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                Swal.fire('Saved!', 'Requisition updated and saved as draft successfully.', 'success').then(() => {
                    window.location.href = "{{ route('store-requisitions.index') }}";
                });
            } else {
                Swal.fire('Error!', response.message || 'Failed to save draft.', 'error');
            }
        },
        error: function(xhr) {
            let message = 'Failed to save draft.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            Swal.fire('Error!', message, 'error');
        }
    });
}

// Form validation
document.getElementById('requisitionForm').addEventListener('submit', function(e) {
    const itemsBody = document.getElementById('itemsBody');
    
    if (itemsBody.children.length === 0) {
        e.preventDefault();
        Swal.fire('Validation Error!', 'Please add at least one item to the requisition.', 'error');
        return false;
    }
    
    // Validate all quantity fields
    const quantityInputs = itemsBody.querySelectorAll('input[name*="[quantity]"]');
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
        Swal.fire('Validation Error!', 'Please enter valid quantities for all items.', 'error');
        return false;
    }
});

// Product search functionality
@if(in_array($storeRequisition->status, ['draft', 'pending']))
document.getElementById('productSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#productsTable tbody tr');
    
    rows.forEach(row => {
        const productName = row.cells[0].textContent.toLowerCase();
        const category = row.cells[1].textContent.toLowerCase();
        
        if (productName.includes(searchTerm) || category.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
@endif

// Set minimum required date to today
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const requiredDateInput = document.getElementById('required_date');
    if (requiredDateInput && !requiredDateInput.hasAttribute('readonly')) {
        requiredDateInput.setAttribute('min', today);
    }
});
</script>
@endpush