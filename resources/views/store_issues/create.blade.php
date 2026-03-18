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
                                <small class="text-muted">Issue approved items from store requisition</small>
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
                            <!-- Source Selection -->
                            <div class="row mb-4">
                                @if(request('requisition_id'))
                                    <input type="hidden" name="store_requisition_id" value="{{ request('requisition_id') }}">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="bx bx-info-circle me-2"></i>
                                            <strong>Issuing for Requisition:</strong> {{ $requisition->requisition_number ?? 'N/A' }}
                                        </div>
                                    </div>
                                @else
                                    <div class="col-md-6">
                                        <label for="store_requisition_id" class="form-label required">Select Requisition</label>
                                        <select name="store_requisition_id" id="store_requisition_id" class="form-select @error('store_requisition_id') is-invalid @enderror" required>
                                            <option value="">Select Approved Requisition</option>
                                            @foreach($requisitions as $req)
                                            <option value="{{ $req->id }}" 
                                                data-branch="{{ $req->branch->name }}"
                                                data-user="{{ $req->user->name }}"
                                                data-items="{{ $req->items->count() }}"
                                                {{ old('store_requisition_id') == $req->id ? 'selected' : '' }}>
                                                {{ $req->requisition_number }} - {{ $req->branch->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('store_requisition_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif

                                <div class="col-md-6">
                                    <label for="issue_date" class="form-label required">Issue Date</label>
                                    <input type="date" 
                                           name="issue_date" 
                                           id="issue_date" 
                                           class="form-control @error('issue_date') is-invalid @enderror"
                                           value="{{ old('issue_date', date('Y-m-d')) }}"
                                           max="{{ date('Y-m-d') }}"
                                           required>
                                    @error('issue_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <label for="notes" class="form-label">Issue Notes</label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              class="form-control @error('notes') is-invalid @enderror"
                                              rows="3"
                                              placeholder="Add any notes about this issue">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Requisition Details -->
                            <div id="requisitionDetails" style="{{ request('requisition_id') ? '' : 'display: none;' }}">
                                <div class="alert alert-light border">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Branch:</strong> <span id="detailBranch">{{ $requisition->branch->name ?? '' }}</span><br>
                                            <strong>Requested by:</strong> <span id="detailUser">{{ $requisition->user->name ?? '' }}</span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Required Date:</strong> <span id="detailDate">{{ $requisition->required_date->format('M d, Y') ?? '' }}</span><br>
                                            <strong>Priority:</strong> <span id="detailPriority">{{ ucfirst($requisition->priority ?? '') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Items Section -->
                                <div class="border rounded p-3 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0 text-primary">
                                            <i class="bx bx-list-check me-2"></i>Items to Issue
                                        </h6>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="issueMode" id="issueAll" value="all" checked>
                                            <label class="btn btn-outline-primary" for="issueAll">Issue All</label>

                                            <input type="radio" class="btn-check" name="issueMode" id="issuePartial" value="partial">
                                            <label class="btn btn-outline-primary" for="issuePartial">Partial Issue</label>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="itemsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="30%">Product</th>
                                                    <th width="15%">Approved Qty</th>
                                                    <th width="15%">Already Issued</th>
                                                    <th width="15%">Issue Qty</th>
                                                    <th width="10%">Unit</th>
                                                    <th width="15%">Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody id="itemsBody">
                                                @if(request('requisition_id') && isset($requisition))
                                                    @foreach($requisition->items->where('approved_quantity', '>', 0) as $index => $item)
                                                    @php
                                                        $remainingQty = $item->approved_quantity - $item->issued_quantity;
                                                    @endphp
                                                    @if($remainingQty > 0)
                                                    <tr>
                                                        <td>
                                                            <input type="hidden" name="items[{{ $index }}][requisition_item_id]" value="{{ $item->id }}">
                                                            <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                                            <div class="fw-medium">{{ $item->product->name }}</div>
                                                            <small class="text-muted">{{ $item->product->category->name ?? 'N/A' }}</small>
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold text-success">{{ number_format($item->approved_quantity, 2) }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="text-info">{{ number_format($item->issued_quantity, 2) }}</span>
                                                        </td>
                                                        <td>
                                                            <input type="number" 
                                                                   name="items[{{ $index }}][quantity]" 
                                                                   class="form-control form-control-sm issue-quantity" 
                                                                   min="0" 
                                                                   max="{{ $remainingQty }}" 
                                                                   step="0.01"
                                                                   value="{{ $remainingQty }}"
                                                                   data-max="{{ $remainingQty }}"
                                                                   required>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark">{{ $item->product->unit }}</span>
                                                        </td>
                                                        <td>
                                                            <input type="text" 
                                                                   name="items[{{ $index }}][notes]" 
                                                                   class="form-control form-control-sm" 
                                                                   placeholder="Issue notes">
                                                        </td>
                                                    </tr>
                                                    @endif
                                                    @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="text-muted text-center py-3" id="noItemsMessage" style="{{ (request('requisition_id') && isset($requisition) && $requisition->items->where('approved_quantity', '>', 0)->count() > 0) ? 'display: none;' : '' }}">
                                        <i class="bx bx-info-circle me-2"></i>
                                        No items available for issue. Please select a requisition with approved items.
                                    </div>
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
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bx bx-check me-1"></i> Issue Items
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
    // Handle requisition selection
    $('#store_requisition_id').change(function() {
        const requisitionId = $(this).val();
        
        if (requisitionId) {
            loadRequisitionDetails(requisitionId);
        } else {
            $('#requisitionDetails').hide();
            $('#itemsBody').empty();
            $('#noItemsMessage').show();
        }
    });

    // Handle issue mode change
    $('input[name="issueMode"]').change(function() {
        const mode = $(this).val();
        const quantityInputs = $('.issue-quantity');
        
        quantityInputs.each(function() {
            if (mode === 'all') {
                $(this).val($(this).data('max'));
                $(this).prop('readonly', true);
            } else {
                $(this).prop('readonly', false);
            }
        });
    });

    // Validate quantities
    $(document).on('input', '.issue-quantity', function() {
        const max = parseFloat($(this).data('max'));
        const value = parseFloat($(this).val());
        
        if (value > max) {
            $(this).val(max);
            showToast('warning', `Maximum quantity is ${max}`);
        }
        
        if (value < 0) {
            $(this).val(0);
        }
    });
});

function loadRequisitionDetails(requisitionId) {
    $.ajax({
        url: `/store-requisitions/${requisitionId}/details`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const req = response.data;
                
                // Update details
                $('#detailBranch').text(req.branch.name);
                $('#detailUser').text(req.user.name);
                $('#detailDate').text(new Date(req.required_date).toLocaleDateString());
                $('#detailPriority').text(req.priority.charAt(0).toUpperCase() + req.priority.slice(1));
                
                // Load items
                loadRequisitionItems(req.items);
                
                $('#requisitionDetails').show();
            } else {
                showToast('error', response.message || 'Failed to load requisition details');
            }
        },
        error: function(xhr) {
            showToast('error', 'Failed to load requisition details');
        }
    });
}

function loadRequisitionItems(items) {
    const tbody = $('#itemsBody');
    const noItemsMessage = $('#noItemsMessage');
    
    tbody.empty();
    
    const availableItems = items.filter(item => 
        item.approved_quantity > 0 && 
        (item.approved_quantity - item.issued_quantity) > 0
    );
    
    if (availableItems.length === 0) {
        noItemsMessage.show();
        return;
    }
    
    noItemsMessage.hide();
    
    availableItems.forEach((item, index) => {
        const remainingQty = item.approved_quantity - item.issued_quantity;
        
        const row = `
            <tr>
                <td>
                    <input type="hidden" name="items[${index}][requisition_item_id]" value="${item.id}">
                    <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                    <div class="fw-medium">${item.product.name}</div>
                    <small class="text-muted">${item.product.category ? item.product.category.name : 'N/A'}</small>
                </td>
                <td>
                    <span class="fw-bold text-success">${parseFloat(item.approved_quantity).toFixed(2)}</span>
                </td>
                <td>
                    <span class="text-info">${parseFloat(item.issued_quantity).toFixed(2)}</span>
                </td>
                <td>
                    <input type="number" 
                           name="items[${index}][quantity]" 
                           class="form-control form-control-sm issue-quantity" 
                           min="0" 
                           max="${remainingQty}" 
                           step="0.01"
                           value="${remainingQty}"
                           data-max="${remainingQty}"
                           required>
                </td>
                <td>
                    <span class="badge bg-light text-dark">${item.product.unit}</span>
                </td>
                <td>
                    <input type="text" 
                           name="items[${index}][notes]" 
                           class="form-control form-control-sm" 
                           placeholder="Issue notes">
                </td>
            </tr>
        `;
        
        tbody.append(row);
    });
    
    // Apply current issue mode
    const issueMode = $('input[name="issueMode"]:checked').val();
    if (issueMode === 'all') {
        $('.issue-quantity').prop('readonly', true);
    }
}

function resetForm() {
    if (confirm('Are you sure you want to reset the form? All data will be lost.')) {
        document.getElementById('issueForm').reset();
        $('#requisitionDetails').hide();
        $('#itemsBody').empty();
        $('#noItemsMessage').show();
    }
}

// Form validation
document.getElementById('issueForm').addEventListener('submit', function(e) {
    const itemsBody = document.getElementById('itemsBody');
    
    if (itemsBody.children.length === 0) {
        e.preventDefault();
        showToast('error', 'No items available for issue.');
        return false;
    }
    
    // Check if at least one item has quantity > 0
    const quantityInputs = itemsBody.querySelectorAll('input[name*="[quantity]"]');
    let hasValidQuantity = false;
    
    quantityInputs.forEach(input => {
        if (parseFloat(input.value) > 0) {
            hasValidQuantity = true;
        }
    });
    
    if (!hasValidQuantity) {
        e.preventDefault();
        showToast('error', 'Please enter at least one item quantity to issue.');
        return false;
    }
    
    // Disable submit button to prevent double submission
    $('#submitBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Processing...');
});

function showToast(type, message) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type,
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

// Set maximum issue date to today
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('issue_date').setAttribute('max', today);
});
</script>
@endpush