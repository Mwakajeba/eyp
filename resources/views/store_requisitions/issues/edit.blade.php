@extends('layouts.main')

@section('title', 'Edit Store Issue')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Issues', 'url' => route('store-issues.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Edit Issue', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0 text-primary">
                                    <i class="bx bx-edit me-2"></i>Edit Store Issue #{{ $storeIssue->voucher_no }}
                                </h5>
                                <small class="text-muted">
                                    Status: 
                                    @if($storeIssue->status === 'pending')
                                        <span class="badge bg-warning">{{ ucfirst($storeIssue->status) }}</span>
                                    @elseif($storeIssue->status === 'completed')
                                        <span class="badge bg-success">{{ ucfirst($storeIssue->status) }}</span>
                                    @elseif($storeIssue->status === 'partial')
                                        <span class="badge bg-info">{{ ucfirst($storeIssue->status) }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($storeIssue->status) }}</span>
                                    @endif
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

                    @if($storeIssue->status !== 'pending')
                        <div class="alert alert-warning alert-dismissible fade show m-3" role="alert">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Note:</strong> This issue has been {{ $storeIssue->status }}. Only certain fields can be modified.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('store-issues.update', $storeIssue->id) }}" method="POST" id="issueForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <!-- Issue Details -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="store_requisition_id" class="form-label">Requisition</label>
                                    <select name="store_requisition_id" id="store_requisition_id" class="form-select" disabled>
                                        <option value="{{ $storeIssue->store_requisition_id }}" selected>
                                            {{ $storeIssue->storeRequisition->requisition_number }} - {{ $storeIssue->storeRequisition->purpose }}
                                        </option>
                                    </select>
                                    <input type="hidden" name="store_requisition_id" value="{{ $storeIssue->store_requisition_id }}">
                                </div>

                                <div class="col-md-6">
                                    <label for="issue_date" class="form-label required">Issue Date</label>
                                    <input type="date" 
                                           name="issue_date" 
                                           id="issue_date" 
                                           class="form-control @error('issue_date') is-invalid @enderror"
                                           value="{{ old('issue_date', $storeIssue->issue_date->format('Y-m-d')) }}"
                                           {{ $storeIssue->status !== 'pending' ? 'readonly' : '' }}
                                           required>
                                    @error('issue_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="issued_to" class="form-label required">Issued To</label>
                                    <select name="issued_to" id="issued_to" class="form-select @error('issued_to') is-invalid @enderror" 
                                            {{ $storeIssue->status !== 'pending' ? 'disabled' : '' }} required>
                                        <option value="">Select Employee</option>
                                        <option value="{{ $storeIssue->issued_to }}" selected>{{ $storeIssue->issuedTo->name }}</option>
                                    </select>
                                    @if($storeIssue->status !== 'pending')
                                        <input type="hidden" name="issued_to" value="{{ $storeIssue->issued_to }}">
                                    @endif
                                    @error('issued_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" 
                                           name="description" 
                                           id="description" 
                                           class="form-control @error('description') is-invalid @enderror"
                                           value="{{ old('description', $storeIssue->description) }}"
                                           placeholder="Optional description">
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea name="remarks" 
                                              id="remarks" 
                                              class="form-control @error('remarks') is-invalid @enderror"
                                              rows="3"
                                              placeholder="Optional remarks">{{ old('remarks', $storeIssue->remarks) }}</textarea>
                                    @error('remarks')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Issue Items -->
                            <div class="border rounded p-3 mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 text-primary">
                                        <i class="bx bx-list-check me-2"></i>Issue Items
                                    </h6>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="itemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="30%">Product</th>
                                                <th width="15%">Approved Qty</th>
                                                <th width="15%">Issue Qty</th>
                                                <th width="15%">Unit</th>
                                                <th width="25%">Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsBody">
                                            @foreach($storeIssue->items as $index => $item)
                                            <tr>
                                                <td>
                                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                                    <div class="fw-medium">{{ $item->product->name }}</div>
                                                    <small class="text-muted">{{ $item->product->category->name ?? 'N/A' }}</small>
                                                </td>
                                                <td>
                                                    <span class="text-success fw-bold">{{ number_format($item->quantity_approved, 2) }}</span>
                                                </td>
                                                <td>
                                                    <input type="number" 
                                                           name="items[{{ $index }}][quantity_issued]" 
                                                           class="form-control form-control-sm" 
                                                           min="0" 
                                                           max="{{ $item->quantity_approved }}" 
                                                           step="0.01"
                                                           value="{{ $item->quantity_issued }}"
                                                           {{ $storeIssue->status !== 'pending' ? 'readonly' : '' }}
                                                           required>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">{{ $item->unit_of_measure }}</span>
                                                </td>
                                                <td>
                                                    <input type="text" 
                                                           name="items[{{ $index }}][issue_notes]" 
                                                           class="form-control form-control-sm" 
                                                           value="{{ $item->issue_notes }}"
                                                           placeholder="Optional notes">
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between">
                                <div>
                                    @if($storeIssue->status === 'pending')
                                    <button type="button" class="btn btn-light" onclick="resetForm()">
                                        <i class="bx bx-refresh me-1"></i> Reset Changes
                                    </button>
                                    @endif
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i> Update Issue
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
    @if($storeIssue->status === 'pending')
    // Load employees for editing
    loadEmployees();
    @endif
});

function loadEmployees() {
    $.get("{{ route('users.employees') }}", function(data) {
        const select = $('#issued_to');
        const currentValue = select.val();
        
        select.empty().append('<option value="">Select Employee</option>');
        
        data.forEach(function(employee) {
            const selected = employee.id == {{ $storeIssue->issued_to }} ? 'selected' : '';
            select.append(`<option value="${employee.id}" ${selected}>${employee.name}</option>`);
        });
    });
}

function resetForm() {
    if (confirm('Are you sure you want to reset changes? All unsaved data will be lost.')) {
        location.reload();
    }
}

// Form validation
document.getElementById('issueForm').addEventListener('submit', function(e) {
    const itemsBody = document.getElementById('itemsBody');
    
    // Validate quantity fields - at least one item must have a quantity
    const quantityInputs = itemsBody.querySelectorAll('input[name*="[quantity_issued]"]');
    let hasValidQuantity = false;
    
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