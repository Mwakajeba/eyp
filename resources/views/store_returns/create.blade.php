@extends('layouts.main')

@section('title', 'Create Store Return')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Returns', 'url' => route('store-returns.index'), 'icon' => 'bx bx-undo'],
            ['label' => 'Create Return', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0 text-primary">
                                    <i class="bx bx-undo me-2"></i>Create Store Return
                                </h5>
                                <small class="text-muted">Return issued items to store</small>
                            </div>
                            <div>
                                <a href="{{ route('store-returns.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('store-returns.store') }}" method="POST" id="returnForm">
                        @csrf
                        <div class="card-body">
                            <!-- Source Selection -->
                            <div class="row mb-4">
                                @if(request('issue_id'))
                                    <input type="hidden" name="store_issue_id" value="{{ request('issue_id') }}">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="bx bx-info-circle me-2"></i>
                                            <strong>Creating return for Issue:</strong> {{ $issue->issue_number ?? 'N/A' }}
                                        </div>
                                    </div>
                                @else
                                    <div class="col-md-6">
                                        <label for="store_issue_id" class="form-label required">Select Store Issue</label>
                                        <select name="store_issue_id" id="store_issue_id" class="form-select @error('store_issue_id') is-invalid @enderror" required>
                                            <option value="">Select Completed Issue</option>
                                            @foreach($issues as $storeIssue)
                                            <option value="{{ $storeIssue->id }}" 
                                                data-branch="{{ $storeIssue->branch->name }}"
                                                data-user="{{ $storeIssue->issuedBy->name }}"
                                                data-items="{{ $storeIssue->items->count() }}"
                                                {{ old('store_issue_id') == $storeIssue->id ? 'selected' : '' }}>
                                                {{ $storeIssue->issue_number }} - {{ $storeIssue->branch->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('store_issue_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif

                                <div class="col-md-6">
                                    <label for="return_date" class="form-label required">Return Date</label>
                                    <input type="date" 
                                           name="return_date" 
                                           id="return_date" 
                                           class="form-control @error('return_date') is-invalid @enderror"
                                           value="{{ old('return_date', date('Y-m-d')) }}"
                                           max="{{ date('Y-m-d') }}"
                                           required>
                                    @error('return_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="reason" class="form-label required">Return Reason</label>
                                    <select name="reason" id="reason" class="form-select @error('reason') is-invalid @enderror" required>
                                        <option value="">Select Reason</option>
                                        <option value="damaged" {{ old('reason') == 'damaged' ? 'selected' : '' }}>Damaged</option>
                                        <option value="defective" {{ old('reason') == 'defective' ? 'selected' : '' }}>Defective</option>
                                        <option value="excess" {{ old('reason') == 'excess' ? 'selected' : '' }}>Excess/Not Needed</option>
                                        <option value="wrong_item" {{ old('reason') == 'wrong_item' ? 'selected' : '' }}>Wrong Item</option>
                                        <option value="expired" {{ old('reason') == 'expired' ? 'selected' : '' }}>Expired</option>
                                        <option value="quality_issue" {{ old('reason') == 'quality_issue' ? 'selected' : '' }}>Quality Issue</option>
                                        <option value="other" {{ old('reason') == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="condition" class="form-label">Item Condition</label>
                                    <select name="condition" id="condition" class="form-select @error('condition') is-invalid @enderror">
                                        <option value="">Select Condition</option>
                                        <option value="good" {{ old('condition') == 'good' ? 'selected' : '' }}>Good</option>
                                        <option value="damaged" {{ old('condition') == 'damaged' ? 'selected' : '' }}>Damaged</option>
                                        <option value="unusable" {{ old('condition') == 'unusable' ? 'selected' : '' }}>Unusable</option>
                                    </select>
                                    @error('condition')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <label for="notes" class="form-label">Return Notes</label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              class="form-control @error('notes') is-invalid @enderror"
                                              rows="3"
                                              placeholder="Add any notes about this return">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Issue Details -->
                            <div id="issueDetails" style="{{ request('issue_id') ? '' : 'display: none;' }}">
                                <div class="alert alert-light border">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Branch:</strong> <span id="detailBranch">{{ $issue->branch->name ?? '' }}</span><br>
                                            <strong>Issued by:</strong> <span id="detailUser">{{ $issue->issuedBy->name ?? '' }}</span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Issue Date:</strong> <span id="detailDate">{{ $issue->issue_date->format('M d, Y') ?? '' }}</span><br>
                                            <strong>Status:</strong> <span id="detailStatus">{{ ucfirst($issue->status ?? '') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Items Section -->
                                <div class="border rounded p-3 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0 text-primary">
                                            <i class="bx bx-list-check me-2"></i>Items to Return
                                        </h6>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="returnMode" id="returnAll" value="all">
                                            <label class="btn btn-outline-primary" for="returnAll">Return All</label>

                                            <input type="radio" class="btn-check" name="returnMode" id="returnPartial" value="partial" checked>
                                            <label class="btn btn-outline-primary" for="returnPartial">Partial Return</label>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="itemsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="30%">Product</th>
                                                    <th width="15%">Issued Qty</th>
                                                    <th width="15%">Already Returned</th>
                                                    <th width="15%">Return Qty</th>
                                                    <th width="10%">Unit</th>
                                                    <th width="15%">Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody id="itemsBody">
                                                @if(request('issue_id') && isset($issue))
                                                    @foreach($issue->items as $index => $item)
                                                    @php
                                                        $returnedQty = $item->returns->sum('quantity');
                                                        $availableQty = $item->quantity - $returnedQty;
                                                    @endphp
                                                    @if($availableQty > 0)
                                                    <tr>
                                                        <td>
                                                            <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->id }}">
                                                            <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                                            <div class="fw-medium">{{ $item->product->name }}</div>
                                                            <small class="text-muted">{{ $item->product->category->name ?? 'N/A' }}</small>
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold text-success">{{ number_format($item->quantity, 2) }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="text-info">{{ number_format($returnedQty, 2) }}</span>
                                                        </td>
                                                        <td>
                                                            <input type="number" 
                                                                   name="items[{{ $index }}][quantity_returned]" 
                                                                   class="form-control form-control-sm return-quantity" 
                                                                   min="0" 
                                                                   max="{{ $availableQty }}" 
                                                                   step="0.01"
                                                                   value="0"
                                                                   data-max="{{ $availableQty }}">
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark">{{ $item->product->unit }}</span>
                                                        </td>
                                                        <td>
                                                            <input type="text" 
                                                                   name="items[{{ $index }}][notes]" 
                                                                   class="form-control form-control-sm" 
                                                                   placeholder="Return notes">
                                                        </td>
                                                    </tr>
                                                    @endif
                                                    @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="text-muted text-center py-3" id="noItemsMessage" style="{{ (request('issue_id') && isset($issue) && $issue->items->count() > 0) ? 'display: none;' : '' }}">
                                        <i class="bx bx-info-circle me-2"></i>
                                        No items available for return. Please select an issue with items.
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
                                        <i class="bx bx-check me-1"></i> Create Return
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
    // Handle issue selection
    $('#store_issue_id').change(function() {
        const issueId = $(this).val();
        
        if (issueId) {
            loadIssueDetails(issueId);
        } else {
            $('#issueDetails').hide();
            $('#itemsBody').empty();
            $('#noItemsMessage').show();
        }
    });

    // Handle return mode change
    $('input[name="returnMode"]').change(function() {
        const mode = $(this).val();
        const quantityInputs = $('.return-quantity');
        
        quantityInputs.each(function() {
            if (mode === 'all') {
                $(this).val($(this).data('max'));
            } else {
                $(this).val(0);
            }
        });
    });

    // Validate quantities
    $(document).on('input', '.return-quantity', function() {
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

function loadIssueDetails(issueId) {
    $.ajax({
        url: `/store-issues/${issueId}/details`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const issue = response.data;
                
                // Update details
                $('#detailBranch').text(issue.branch.name);
                $('#detailUser').text(issue.issued_by.name);
                $('#detailDate').text(new Date(issue.issue_date).toLocaleDateString());
                $('#detailStatus').text(issue.status.charAt(0).toUpperCase() + issue.status.slice(1));
                
                // Load items
                loadIssueItems(issue.items);
                
                $('#issueDetails').show();
            } else {
                showToast('error', response.message || 'Failed to load issue details');
            }
        },
        error: function(xhr) {
            showToast('error', 'Failed to load issue details');
        }
    });
}

function loadIssueItems(items) {
    const tbody = $('#itemsBody');
    const noItemsMessage = $('#noItemsMessage');
    
    tbody.empty();
    
    const availableItems = items.filter(item => {
        const returnedQty = item.returns ? item.returns.reduce((sum, ret) => sum + parseFloat(ret.quantity), 0) : 0;
        return (item.quantity - returnedQty) > 0;
    });
    
    if (availableItems.length === 0) {
        noItemsMessage.show();
        return;
    }
    
    noItemsMessage.hide();
    
    availableItems.forEach((item, index) => {
        const returnedQty = item.returns ? item.returns.reduce((sum, ret) => sum + parseFloat(ret.quantity), 0) : 0;
        const availableQty = item.quantity - returnedQty;
        
        const row = `
            <tr>
                <td>
                    <input type="hidden" name="items[${index}][item_id]" value="${item.id}">
                    <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                    <div class="fw-medium">${item.product.name}</div>
                    <small class="text-muted">${item.product.category ? item.product.category.name : 'N/A'}</small>
                </td>
                <td>
                    <span class="fw-bold text-success">${parseFloat(item.quantity).toFixed(2)}</span>
                </td>
                <td>
                    <span class="text-info">${returnedQty.toFixed(2)}</span>
                </td>
                <td>
                    <input type="number" 
                           name="items[${index}][quantity_returned]" 
                           class="form-control form-control-sm return-quantity" 
                           min="0" 
                           max="${availableQty}" 
                           step="0.01"
                           value="0"
                           data-max="${availableQty}">
                </td>
                <td>
                    <span class="badge bg-light text-dark">${item.product.unit}</span>
                </td>
                <td>
                    <input type="text" 
                           name="items[${index}][notes]" 
                           class="form-control form-control-sm" 
                           placeholder="Return notes">
                </td>
            </tr>
        `;
        
        tbody.append(row);
    });
}

function resetForm() {
    if (confirm('Are you sure you want to reset the form? All data will be lost.')) {
        document.getElementById('returnForm').reset();
        $('#issueDetails').hide();
        $('#itemsBody').empty();
        $('#noItemsMessage').show();
    }
}

// Form validation
document.getElementById('returnForm').addEventListener('submit', function(e) {
    const itemsBody = document.getElementById('itemsBody');
    
    console.log('Form submission - items in body:', itemsBody.children.length);
    console.log('Form data:', new FormData(this));
    
    if (itemsBody.children.length === 0) {
        e.preventDefault();
        showToast('error', 'No items available for return.');
        return false;
    }
    
    // Check if at least one item has quantity > 0
    const quantityInputs = itemsBody.querySelectorAll('input[name*="[quantity_returned]"]');
    console.log('Quantity inputs found:', quantityInputs.length);
    
    let hasValidQuantity = false;
    
    quantityInputs.forEach(input => {
        console.log('Input value:', input.name, '=', input.value);
        if (parseFloat(input.value) > 0) {
            hasValidQuantity = true;
        }
    });
    
    console.log('Has valid quantity:', hasValidQuantity);
    
    if (!hasValidQuantity) {
        e.preventDefault();
        showToast('error', 'Please enter at least one item quantity to return.');
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

// Set maximum return date to today
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('return_date').setAttribute('max', today);
});
</script>
@endpush