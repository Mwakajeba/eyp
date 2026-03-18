@extends('layouts.main')

@section('title', 'Create Store Requisition')

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
                                <small class="text-muted">Request items from store inventory</small>
                            </div>
                            <div>
                                <a href="{{ route('store-requisitions.requisitions.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('store-requisitions.requisitions.store') }}" method="POST" id="requisitionForm">
                        @csrf
                        
                        <!-- Display validation errors -->
                        @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Validation Errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif
                        
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
                                            <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                                {{ $employee->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <!-- Regular user can only create for themselves -->
                                        <input type="text" 
                                               class="form-control" 
                                               value="{{ auth()->user()->name }}" 
                                               readonly>
                                        <input type="hidden" 
                                               name="employee_id" 
                                               value="{{ auth()->user()->id }}">
                                        <small class="text-muted">You can only create requisitions for yourself</small>
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
                                           value="{{ old('request_date', date('Y-m-d')) }}"
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
                                              required>{{ old('purpose') }}</textarea>
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
                                              placeholder="Any additional information or notes">{{ old('description') }}</textarea>
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

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="itemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="35%">Product</th>
                                                <th width="15%">Quantity <small class="text-muted">(Editable)</small></th>
                                                <th width="15%">Unit</th>
                                                <th width="25%">Notes <small class="text-muted">(Editable)</small></th>
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
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
// Counter for unique item IDs
let itemCounter = 0;
let selectedItemId = null;

// Initialize search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('itemSearch');
    const searchResults = document.getElementById('searchResults');
    const hiddenSelect = document.getElementById('itemSelect');
    
    // Handle search input
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        
        if (query.length === 0) {
            searchResults.classList.add('d-none');
            selectedItemId = null;
            document.getElementById('unitDisplay').textContent = '-';
            return;
        }
        
        // Filter options
        const options = Array.from(hiddenSelect.options);
        const matches = options.filter(option => {
            if (!option.value) return false; // Skip empty option
            const searchData = option.getAttribute('data-search') || '';
            return searchData.includes(query);
        });
        
        // Display results
        if (matches.length > 0) {
            searchResults.innerHTML = '';
            matches.slice(0, 10).forEach(option => { // Limit to 10 results
                const resultItem = document.createElement('div');
                resultItem.className = 'p-2 border-bottom cursor-pointer hover-bg-light';
                resultItem.style.cursor = 'pointer';
                resultItem.innerHTML = `
                    <div class="fw-medium">${option.getAttribute('data-name')}</div>
                    <small class="text-muted">Stock: ${option.getAttribute('data-stock')} ${option.getAttribute('data-unit')}</small>
                `;
                
                resultItem.addEventListener('click', function() {
                    selectItem(option);
                });
                
                searchResults.appendChild(resultItem);
            });
            searchResults.classList.remove('d-none');
        } else {
            searchResults.innerHTML = '<div class="p-2 text-muted">No items found</div>';
            searchResults.classList.remove('d-none');
        }
    });
    
    // Handle keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        const results = searchResults.querySelectorAll('.cursor-pointer');
        const activeResult = searchResults.querySelector('.bg-primary');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (activeResult) {
                activeResult.classList.remove('bg-primary', 'text-white');
                const next = activeResult.nextElementSibling;
                if (next) {
                    next.classList.add('bg-primary', 'text-white');
                } else {
                    results[0]?.classList.add('bg-primary', 'text-white');
                }
            } else {
                results[0]?.classList.add('bg-primary', 'text-white');
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (activeResult) {
                activeResult.classList.remove('bg-primary', 'text-white');
                const prev = activeResult.previousElementSibling;
                if (prev) {
                    prev.classList.add('bg-primary', 'text-white');
                } else {
                    results[results.length - 1]?.classList.add('bg-primary', 'text-white');
                }
            } else {
                results[results.length - 1]?.classList.add('bg-primary', 'text-white');
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeResult) {
                activeResult.click();
            } else if (results.length === 1) {
                results[0].click();
            }
        } else if (e.key === 'Escape') {
            searchResults.classList.add('d-none');
        }
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('d-none');
        }
    });
});

// Select item function
function selectItem(option) {
    selectedItemId = option.value;
    const itemName = option.getAttribute('data-name');
    const itemUnit = option.getAttribute('data-unit');
    
    document.getElementById('itemSearch').value = itemName;
    document.getElementById('searchResults').classList.add('d-none');
    document.getElementById('unitDisplay').textContent = itemUnit;
    
    // Focus on quantity input
    document.getElementById('itemQuantity').focus();
}

// Add item to table function
function addItemToTable() {
    const searchInput = document.getElementById('itemSearch');
    const quantityInput = document.getElementById('itemQuantity');
    const notesInput = document.getElementById('itemNotes');
    const hiddenSelect = document.getElementById('itemSelect');
    
    const itemId = selectedItemId;
    const quantity = quantityInput.value;
    const notes = notesInput.value;
    
    // Validation
    if (!itemId) {
        alert('Please select an item.');
        searchInput.focus();
        return;
    }
    
    if (!quantity || parseFloat(quantity) <= 0) {
        alert('Please enter a valid quantity.');
        quantityInput.focus();
        return;
    }
    
    // Check if item already exists
    const existingItems = document.querySelectorAll('input[name*="[product_id]"]');
    for (let input of existingItems) {
        if (input.value == itemId) {
            alert('This item has already been added.');
            return;
        }
    }
    
    // Get item details from hidden select
    const selectedOption = hiddenSelect.querySelector(`option[value="${itemId}"]`);
    const itemName = selectedOption.getAttribute('data-name');
    const itemUnit = selectedOption.getAttribute('data-unit');
    
    // Add row to table
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
    
    // Reset form
    searchInput.value = '';
    quantityInput.value = '';
    notesInput.value = '';
    selectedItemId = null;
    document.getElementById('unitDisplay').textContent = '-';
    
    // Focus back to search input for next item
    searchInput.focus();
}

// Handle Enter key press for quick addition
document.getElementById('itemQuantity').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        addItemToTable();
    }
});

document.getElementById('itemNotes').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        addItemToTable();
    }
});

// Remove item function
function removeItem(button) {
    const row = button.closest('tr');
    row.remove();
    
    // Show no items message if table is empty
    const itemsBody = document.getElementById('itemsBody');
    const noItemsMessage = document.getElementById('noItemsMessage');
    
    if (itemsBody.children.length === 0) {
        noItemsMessage.style.display = 'block';
    }
}

// Reset form function
function resetForm() {
    if (confirm('Are you sure you want to reset the form? All data will be lost.')) {
        document.getElementById('requisitionForm').reset();
        document.getElementById('itemsBody').innerHTML = '';
        document.getElementById('noItemsMessage').style.display = 'block';
        document.getElementById('unitDisplay').textContent = '-';
        itemCounter = 0;
    }
}

// Form validation
document.getElementById('requisitionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const itemsBody = document.getElementById('itemsBody');
    
    if (itemsBody.children.length === 0) {
        showError('Please add at least one item to the requisition.');
        return false;
    }
    
    // Submit form via AJAX to catch server errors
    const form = this;
    const formData = new FormData(form);
    
    // Disable submit button during submission
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Submitting...';
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        
        if (contentType && contentType.includes('application/json')) {
            return response.json().then(data => ({
                status: response.status,
                data: data
            }));
        } else {
            // Assume success on non-JSON response (redirect)
            return response.text().then(() => ({
                status: 200,
                data: { success: true }
            }));
        }
    })
    .then(({status, data}) => {
        if (status === 200 && data.success) {
            // Success - show success message then redirect
            showSuccess(data.message || 'Store requisition created successfully!');
            setTimeout(() => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.href = form.action.replace('store', 'index');
                }
            }, 1500);
        } else if (status === 422) {
            // Validation error
            if (data.errors) {
                showValidationErrors(data.errors);
            } else {
                showError(data.message || 'Validation failed');
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bx bx-check me-1"></i> Submit Requisition';
        } else {
            // Other errors
            showError(data.message || 'An error occurred while creating the requisition');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bx bx-check me-1"></i> Submit Requisition';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while creating the requisition. Please check the console for details.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bx bx-check me-1"></i> Submit Requisition';
    });
});

// Show error message function
function showError(message) {
    removeExistingAlert();
    
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        <i class="bx bx-error-circle me-2"></i>
        <strong>Error!</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    insertAlert(alertDiv);
}

// Show success message function
function showSuccess(message) {
    removeExistingAlert();
    
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show';
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        <i class="bx bx-check-circle me-2"></i>
        <strong>Success!</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    insertAlert(alertDiv);
}

// Show validation errors
function showValidationErrors(errors) {
    removeExistingAlert();
    
    const errorMessages = [];
    
    // Flatten error messages
    Object.keys(errors).forEach(field => {
        const fieldErrors = errors[field];
        if (Array.isArray(fieldErrors)) {
            fieldErrors.forEach(msg => {
                errorMessages.push(msg);
            });
        } else {
            errorMessages.push(fieldErrors);
        }
    });
    
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.role = 'alert';
    
    let html = `
        <i class="bx bx-error-circle me-2"></i>
        <strong>Validation Errors:</strong>
        <ul class="mb-0 mt-2">
    `;
    
    errorMessages.forEach(msg => {
        html += `<li>${msg}</li>`;
    });
    
    html += `
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertDiv.innerHTML = html;
    insertAlert(alertDiv);
}

// Helper function to remove existing alert
function removeExistingAlert() {
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
}

// Helper function to insert alert at top
function insertAlert(alertDiv) {
    const formCard = document.querySelector('.card');
    const cardBody = formCard.querySelector('.card-body');
    
    // Insert after card-header or at start of card-body
    if (cardBody) {
        cardBody.insertBefore(alertDiv, cardBody.firstChild);
    } else {
        formCard.insertBefore(alertDiv, formCard.firstChild);
    }
    
    // Scroll to error
    alertDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Set minimum required date to today
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('request_date').setAttribute('min', today);
});
</script>
@endpush