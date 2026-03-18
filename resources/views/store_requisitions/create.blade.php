@extends('layouts.main')

@section('title', 'Create Store Requisition')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Requisitions', 'url' => route('store-requisitions.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Create Requisition', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0 text-primary">
                                    <i class="bx bx-package me-2"></i>Create New Store Requisition
                                </h5>
                                <small class="text-muted">Add items to request from store</small>
                            </div>
                            <div>
                                <a href="{{ route('store-requisitions.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('store-requisitions.store') }}" method="POST" id="requisitionForm">
                        @csrf
                        <div class="card-body">
                            <!-- Requisition Details -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="branch_id" class="form-label required">Branch</label>
                                    <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                        <option value="">Select Branch</option>
                                        @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
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
                                           value="{{ old('department') }}"
                                           placeholder="Enter department">
                                    @error('department')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="priority" class="form-label required">Priority</label>
                                    <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                        <option value="">Select Priority</option>
                                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
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
                                           value="{{ old('required_date') }}"
                                           min="{{ date('Y-m-d') }}"
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
                                              required>{{ old('purpose') }}</textarea>
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
                                    <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                                        <i class="bx bx-plus me-1"></i> Add Item
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="itemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="35%">Product</th>
                                                <th width="15%">Quantity</th>
                                                <th width="15%">Unit</th>
                                                <th width="25%">Notes</th>
                                                <th width="10%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsBody">
                                            <!-- Items will be added here -->
                                        </tbody>
                                    </table>
                                </div>

                                <div class="text-muted text-center py-3" id="noItemsMessage">
                                    <i class="bx bx-info-circle me-2"></i>
                                    No items added yet. Click "Add Item" to start.
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="button" class="btn btn-light" onclick="resetForm()">
                                        <i class="bx bx-refresh me-1"></i> Reset Form
                                    </button>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-secondary me-2" onclick="saveDraft()">
                                        <i class="bx bx-save me-1"></i> Save as Draft
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-check me-1"></i> Submit Requisition
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

<!-- Product Selection Modal -->
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
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
let itemCounter = 0;
let currentRowIndex = null;

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
            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
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
    if (confirm('Are you sure you want to reset the form? All data will be lost.')) {
        document.getElementById('requisitionForm').reset();
        document.getElementById('itemsBody').innerHTML = '';
        document.getElementById('noItemsMessage').style.display = 'block';
        itemCounter = 0;
    }
}

function saveDraft() {
    const form = document.getElementById('requisitionForm');
    const formData = new FormData(form);
    formData.append('status', 'draft');
    
    $.ajax({
        url: "{{ route('store-requisitions.store') }}",
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                Swal.fire('Saved!', 'Requisition saved as draft successfully.', 'success').then(() => {
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

// Set minimum required date to today
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('required_date').setAttribute('min', today);
});
</script>
@endpush