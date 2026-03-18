@extends('layouts.main')

@section('title', 'Continue Store Issue')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Issues', 'url' => route('store-issues.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Continue Issue', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0 text-primary">
                                    <i class="bx bx-edit me-2"></i>Continue Store Issue #{{ $storeIssue->issue_number }}
                                </h5>
                                <small class="text-muted">
                                    Status: <span class="badge bg-warning">{{ ucfirst($storeIssue->status) }}</span>
                                </small>
                            </div>
                            <div>
                                <a href="{{ route('store-issues.show', $storeIssue->id) }}" class="btn btn-info me-2">
                                    <i class="bx bx-show me-1"></i> View Details
                                </a>
                                <a href="{{ route('store-issues.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('store-issues.update', $storeIssue->id) }}" method="POST" id="issueForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <!-- Issue Details -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Related Requisition</label>
                                    <div class="form-control bg-light">
                                        <a href="{{ route('store-requisitions.show', $storeIssue->store_requisition_id) }}" class="text-info">
                                            {{ $storeIssue->storeRequisition->requisition_number }}
                                        </a>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="issue_date" class="form-label required">Issue Date</label>
                                    <input type="date" 
                                           name="issue_date" 
                                           id="issue_date" 
                                           class="form-control @error('issue_date') is-invalid @enderror"
                                           value="{{ old('issue_date', $storeIssue->issue_date->format('Y-m-d')) }}"
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
                                              placeholder="Add any notes about this issue">{{ old('notes', $storeIssue->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Requisition Overview -->
                            <div class="alert alert-light border">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Branch:</strong> {{ $storeIssue->storeRequisition->branch->name }}<br>
                                        <strong>Requested by:</strong> {{ $storeIssue->storeRequisition->user->name }}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Required Date:</strong> {{ $storeIssue->storeRequisition->required_date->format('M d, Y') }}<br>
                                        <strong>Priority:</strong> {{ ucfirst($storeIssue->storeRequisition->priority) }}
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
                                        <input type="radio" class="btn-check" name="issueMode" id="issueRemaining" value="remaining" checked>
                                        <label class="btn btn-outline-primary" for="issueRemaining">Issue Remaining</label>

                                        <input type="radio" class="btn-check" name="issueMode" id="issueCustom" value="custom">
                                        <label class="btn btn-outline-primary" for="issueCustom">Custom Quantities</label>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="itemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="25%">Product</th>
                                                <th width="12%">Approved Qty</th>
                                                <th width="12%">Already Issued</th>
                                                <th width="12%">Remaining</th>
                                                <th width="12%">Issue Now</th>
                                                <th width="10%">Unit</th>
                                                <th width="17%">Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsBody">
                                            @foreach($storeIssue->storeRequisition->items->where('approved_quantity', '>', 0) as $index => $item)
                                            @php
                                                $remainingQty = $item->approved_quantity - $item->issued_quantity;
                                                $currentIssued = $storeIssue->items->where('store_requisition_item_id', $item->id)->sum('quantity');
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
                                                    <span class="text-warning fw-bold">{{ number_format($remainingQty, 2) }}</span>
                                                </td>
                                                <td>
                                                    <input type="number" 
                                                           name="items[{{ $index }}][quantity]" 
                                                           class="form-control form-control-sm issue-quantity" 
                                                           min="0" 
                                                           max="{{ $remainingQty }}" 
                                                           step="0.01"
                                                           value="{{ old("items.{$index}.quantity", $remainingQty) }}"
                                                           data-max="{{ $remainingQty }}"
                                                           data-current-issued="{{ $currentIssued }}"
                                                           required>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">{{ $item->product->unit }}</span>
                                                </td>
                                                <td>
                                                    <input type="text" 
                                                           name="items[{{ $index }}][notes]" 
                                                           class="form-control form-control-sm" 
                                                           value="{{ old("items.{$index}.notes") }}"
                                                           placeholder="Issue notes">
                                                </td>
                                            </tr>
                                            @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if($storeIssue->storeRequisition->items->where('approved_quantity', '>', 0)->filter(function($item) { return ($item->approved_quantity - $item->issued_quantity) > 0; })->count() === 0)
                                <div class="text-muted text-center py-3">
                                    <i class="bx bx-info-circle me-2"></i>
                                    All approved items have been fully issued.
                                </div>
                                @endif
                            </div>

                            <!-- Previously Issued Items (for reference) -->
                            @if($storeIssue->items->count() > 0)
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="bx bx-history me-2"></i>Previously Issued in This Issue
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Quantity Issued</th>
                                                    <th>Unit</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($storeIssue->items as $issuedItem)
                                                <tr>
                                                    <td>{{ $issuedItem->product->name }}</td>
                                                    <td class="text-success fw-bold">{{ number_format($issuedItem->quantity, 2) }}</td>
                                                    <td>{{ $issuedItem->product->unit }}</td>
                                                    <td>{{ $issuedItem->notes ?: '-' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="button" class="btn btn-light" onclick="resetForm()">
                                        <i class="bx bx-refresh me-1"></i> Reset Changes
                                    </button>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bx bx-check me-1"></i> Continue Issue
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
    // Handle issue mode change
    $('input[name="issueMode"]').change(function() {
        const mode = $(this).val();
        const quantityInputs = $('.issue-quantity');
        
        quantityInputs.each(function() {
            if (mode === 'remaining') {
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

function resetForm() {
    if (confirm('Are you sure you want to reset all changes? This will revert to the original values.')) {
        location.reload();
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