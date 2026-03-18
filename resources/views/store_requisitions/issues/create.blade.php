@extends('layouts.main')

@section('title', 'Create Store Issue')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Issues', 'url' => route('store-issues.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Create Issue', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0 text-primary">
                                    <i class="bx bx-package me-2"></i>Create Store Issue
                                </h5>
                                <small class="text-muted">Issue items from approved requisition</small>
                            </div>
                            <div>
                                <a href="{{ route('store-issues.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('store-issues.store') }}" method="POST" id="issueForm">
                        @csrf
                        <div class="card-body">
                            <!-- Issue Details -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="store_requisition_id" class="form-label required">Requisition</label>
                                    <select name="store_requisition_id" id="store_requisition_id" class="form-select @error('store_requisition_id') is-invalid @enderror" required>
                                        <option value="">Select Requisition</option>
                                        @if(isset($requisition))
                                        <option value="{{ $requisition->id }}" selected 
                                                data-requestor-id="{{ $requisition->requested_by }}" 
                                                data-requestor-name="{{ $requisition->requestedBy->name ?? 'Unknown User' }}">
                                            {{ $requisition->requisition_number }} - {{ $requisition->purpose }}
                                        </option>
                                        @endif
                                    </select>
                                    @error('store_requisition_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="issue_date" class="form-label required">Issue Date</label>
                                    <input type="date" 
                                           name="issue_date" 
                                           id="issue_date" 
                                           class="form-control @error('issue_date') is-invalid @enderror"
                                           value="{{ old('issue_date', date('Y-m-d')) }}"
                                           required>
                                    @error('issue_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="issued_to" class="form-label">Issued To</label>
                                    <input type="hidden" name="issued_to" id="issued_to_hidden" value="">
                                    <input type="text" 
                                           id="issued_to_display" 
                                           class="form-control @error('issued_to') is-invalid @enderror"
                                           value="@if(isset($requisition)){{ $requisition->requestedBy->name ?? 'Unknown User' }}@endif"
                                           readonly
                                           placeholder="Will be populated when requisition is selected">
                                    <small class="text-muted">Items will be issued to the person who made the requisition</small>
                                    @error('issued_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="location_id" class="form-label required">Issue From Location</label>
                                    <select name="location_id" 
                                            id="location_id" 
                                            class="form-select @error('location_id') is-invalid @enderror" 
                                            required>
                                        <option value="">Select Location</option>
                                    </select>
                                    <small class="text-muted">Choose the location from which items will be issued</small>
                                    @error('location_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" 
                                           name="description" 
                                           id="description" 
                                           class="form-control @error('description') is-invalid @enderror"
                                           value="{{ old('description') }}"
                                           placeholder="Optional description">
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea name="remarks" 
                                              id="remarks" 
                                              class="form-control @error('remarks') is-invalid @enderror"
                                              rows="3"
                                              placeholder="Optional remarks">{{ old('remarks') }}</textarea>
                                    @error('remarks')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Requisition Items -->
                            <div class="border rounded p-3 mb-4" id="requisitionItemsSection" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 text-primary">
                                        <i class="bx bx-list-check me-2"></i>Items to Issue
                                    </h6>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="itemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="25%">Product</th>
                                                <th width="12%">Approved Qty</th>
                                                <th width="12%">Available Stock</th>
                                                <th width="12%">Issue Qty</th>
                                                <th width="10%">Unit</th>
                                                <th width="29%">Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsBody">
                                            <!-- Items will be loaded here -->
                                        </tbody>
                                    </table>
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
                                        <i class="bx bx-check me-1"></i> Create Issue
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
$(document).ready(function() {
    // Load approved requisitions
    loadApprovedRequisitions();
    
    // Load locations
    loadLocations();
    
    // Load employees
    loadEmployees();
    
    // Handle requisition selection
    $('#store_requisition_id').on('change', function() {
        const requisitionId = $(this).val();
        if (requisitionId) {
            // Get the selected option to extract requestor details
            const selectedOption = $(this).find('option:selected');
            const requestorId = selectedOption.data('requestor-id');
            const requestorName = selectedOption.data('requestor-name');
            
            // Update the issued to fields
            $('#issued_to_hidden').val(requestorId);
            $('#issued_to_display').val(requestorName);
            
            loadRequisitionItems(requisitionId);
        } else {
            $('#requisitionItemsSection').hide();
            $('#issued_to_hidden').val('');
            $('#issued_to_display').val('');
        }
    });

    // Handle location selection - update available stock display
    $('#location_id').on('change', function() {
        const locationId = $(this).val();
        if (locationId) {
            updateStockDisplay(locationId);
        }
    });
    
    // If we have a pre-selected requisition, load its items and set issued_to
    @if(isset($requisition))
    $('#issued_to_hidden').val({{ $requisition->requested_by }});
    $('#issued_to_display').val('{{ $requisition->requestedBy->name ?? 'Unknown User' }}');
    loadRequisitionItems({{ $requisition->id }});
    @endif
});

function loadApprovedRequisitions() {
    $.get("{{ route('store-requisitions.approved') }}", function(data) {
        const select = $('#store_requisition_id');
        const currentValue = select.val();
        
        if (!currentValue) {
            select.empty().append('<option value="">Select Requisition</option>');
            
            data.forEach(function(requisition) {
                select.append(`<option value="${requisition.id}" data-requestor-id="${requisition.requested_by}" data-requestor-name="${requisition.requested_by?.name || 'Unknown User'}">${requisition.requisition_number} - ${requisition.purpose}</option>`);
            });
        }
    });
}

function loadLocations() {
    $.get("{{ route('inventory.locations.user-assigned') }}", function(data) {
        const select = $('#location_id');
        select.empty().append('<option value="">Select Location</option>');
        
        data.forEach(function(location) {
            select.append(`<option value="${location.id}">${location.name} (${location.branch_name})</option>`);
        });
    }).fail(function() {
        console.error('Failed to load locations');
    });
}

function loadEmployees() {
    // No longer needed since we auto-populate from requisition
    console.log('Employee loading skipped - using requisition requestor');
}

function updateStockDisplay(locationId) {
    if (!locationId) return;
    
    // Update stock display for all items in the table
    $('#itemsBody tr').each(function() {
        const row = $(this);
        const inventoryItemId = row.find('input[name*="[quantity_issued]"]').data('item-id');
        
        if (!inventoryItemId) {
            console.warn('No inventory item ID found for row:', row);
            return;
        }
        
        if (inventoryItemId) {
            console.log('Checking stock for item:', inventoryItemId, 'at location:', locationId);
            // Get stock for this item at selected location
            $.get(`{{ route('inventory.stock.item-location') }}`, {
                item_id: inventoryItemId,
                location_id: locationId
            }, function(stockData) {
                console.log('Stock response for item', inventoryItemId, ':', stockData);
                // Update the available stock display
                const stockCell = row.find('.stock-display');
                if (stockCell.length) {
                    stockCell.html(`<span class="badge ${stockData.stock > 0 ? 'bg-success' : 'bg-danger'}">${stockData.stock} available</span>`);
                }
                
                // Update max quantity for input
                const quantityInput = row.find('input[name*="[quantity_issued]"]');
                const approvedQty = parseFloat(quantityInput.attr('max')) || 0;
                const maxAllowed = Math.min(approvedQty, stockData.stock);
                quantityInput.attr('data-stock', stockData.stock);
                
                if (stockData.stock <= 0) {
                    quantityInput.val(0).prop('disabled', true);
                    row.addClass('table-warning');
                } else {
                    quantityInput.prop('disabled', false);
                    row.removeClass('table-warning');
                    // Update quantity if it exceeds available stock
                    if (parseFloat(quantityInput.val()) > stockData.stock) {
                        quantityInput.val(maxAllowed);
                    }
                }
            }).fail(function(xhr, status, error) {
                console.error('Failed to get stock for item', inventoryItemId, ':', error);
                const stockCell = row.find('.stock-display');
                if (stockCell.length) {
                    stockCell.html('<span class="badge bg-warning">Error loading stock</span>');
                }
            });
        }
    });
}

function loadRequisitionItems(requisitionId) {
    const baseUrl = "{{ route('store-requisitions.items', ['requisitionId' => 'PLACEHOLDER']) }}";
    const url = baseUrl.replace('PLACEHOLDER', requisitionId);
    $.get(url, function(data) {
        const tbody = $('#itemsBody');
        tbody.empty();
        
        if (data.length > 0) {
            data.forEach(function(item, index) {
                const row = `
                    <tr>
                        <td>
                            <input type="hidden" name="items[${index}][requisition_item_id]" value="${item.id}">
                            <div class="fw-medium">${item.product.name}</div>
                            <small class="text-muted">${item.product.category?.name || 'N/A'}</small>
                        </td>
                        <td>
                            <span class="text-success fw-bold">${parseFloat(item.quantity_approved).toFixed(2)}</span>
                        </td>
                        <td class="stock-display">
                            <span class="badge bg-secondary">Select location first</span>
                        </td>
                        <td>
                            <input type="number" 
                                   name="items[${index}][quantity_issued]" 
                                   class="form-control form-control-sm" 
                                   min="0" 
                                   max="${item.quantity_approved}" 
                                   step="0.01"
                                   value="${item.quantity_approved}"
                                   data-item-id="${item.inventory_item_id}"
                                   required>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">${item.unit_of_measure}</span>
                        </td>
                        <td>
                            <input type="text" 
                                   name="items[${index}][issue_notes]" 
                                   class="form-control form-control-sm" 
                                   placeholder="Optional notes">
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
            
            $('#requisitionItemsSection').show();
        } else {
            $('#requisitionItemsSection').hide();
        }
    }).fail(function() {
        Swal.fire('Error!', 'Failed to load requisition items.', 'error');
    });
}

function resetForm() {
    if (confirm('Are you sure you want to reset the form? All data will be lost.')) {
        document.getElementById('issueForm').reset();
        $('#requisitionItemsSection').hide();
        $('#itemsBody').empty();
    }
}

// Form validation
document.getElementById('issueForm').addEventListener('submit', function(e) {
    const itemsBody = document.getElementById('itemsBody');
    const issuedToHidden = document.getElementById('issued_to_hidden');
    const locationSelect = document.getElementById('location_id');
    
    if (itemsBody.children.length === 0) {
        e.preventDefault();
        Swal.fire('Validation Error!', 'Please select a requisition to load items.', 'error');
        return false;
    }
    
    if (!issuedToHidden.value) {
        e.preventDefault();
        Swal.fire('Validation Error!', 'Please select a requisition to set the issue recipient.', 'error');
        return false;
    }

    if (!locationSelect.value) {
        e.preventDefault();
        Swal.fire('Validation Error!', 'Please select a location from which to issue items.', 'error');
        return false;
    }
    
    // Validate quantity fields - at least one item must have a quantity
    const quantityInputs = itemsBody.querySelectorAll('input[name*="[quantity_issued]"]');
    let hasValidQuantity = false;
    let hasError = false;
    
    quantityInputs.forEach(input => {
        if (input.value && parseFloat(input.value) > 0) {
            hasValidQuantity = true;
            input.classList.remove('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    if (!hasValidQuantity) {
        e.preventDefault();
        Swal.fire('Validation Error!', 'Please enter at least one item quantity to issue.', 'error');
        return false;
    }
});
</script>
@endpush