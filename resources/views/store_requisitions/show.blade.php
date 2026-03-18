@extends('layouts.main')

@section('title', 'Store Requisition Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Requisitions', 'url' => route('store-requisitions.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Requisition #' . $storeRequisition->requisition_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <!-- Header with Actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Store Requisition #{{ $storeRequisition->requisition_number }}</h5>
                <small class="text-muted">Created on {{ $storeRequisition->created_at->format('M d, Y \a\t h:i A') }}</small>
            </div>
            <div class="d-flex gap-2">
                @if(in_array($storeRequisition->status, ['draft', 'pending']))
                <a href="{{ route('store-requisitions.edit', $storeRequisition->id) }}" class="btn btn-warning">
                    <i class="bx bx-edit me-1"></i> Edit
                </a>
                @endif
                
                @if($storeRequisition->status === 'approved' && $storeRequisition->approved_quantity > 0)
                <a href="{{ route('store-issues.create', ['requisition_id' => $storeRequisition->id]) }}" class="btn btn-success">
                    <i class="bx bx-package me-1"></i> Issue Items
                </a>
                @endif

                @if($storeRequisition->status === 'fully_issued')
                <button type="button" class="btn btn-danger" onclick="returnItems()">
                    <i class="bx bx-undo me-1"></i> Return Items
                </button>
                @endif

                <button type="button" class="btn btn-info" onclick="printRequisition()">
                    <i class="bx bx-printer me-1"></i> Print
                </button>
                
                <a href="{{ route('store-requisitions.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to List
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Requisition Details -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-info-circle me-2"></i>Requisition Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Requisition Number:</strong>
                                <div class="text-primary fw-bold">{{ $storeRequisition->requisition_number }}</div>
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong>
                                <div>
                                    @if($storeRequisition->status === 'draft')
                                        <span class="badge bg-secondary">Draft</span>
                                    @elseif($storeRequisition->status === 'pending')
                                        <span class="badge bg-warning">Pending Approval</span>
                                    @elseif($storeRequisition->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @elseif($storeRequisition->status === 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                    @elseif($storeRequisition->status === 'partially_issued')
                                        <span class="badge bg-info">Partially Issued</span>
                                    @elseif($storeRequisition->status === 'fully_issued')
                                        <span class="badge bg-dark">Fully Issued</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Requested by:</strong>
                                <div>{{ $storeRequisition->user->name }}</div>
                                <small class="text-muted">{{ $storeRequisition->user->email }}</small>
                            </div>
                            <div class="col-md-6">
                                <strong>Branch:</strong>
                                <div>{{ $storeRequisition->branch->name }}</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Department:</strong>
                                <div>{{ $storeRequisition->department ?: 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <strong>Priority:</strong>
                                <div>
                                    @if($storeRequisition->priority === 'low')
                                        <span class="badge bg-light text-dark">Low</span>
                                    @elseif($storeRequisition->priority === 'medium')
                                        <span class="badge bg-warning">Medium</span>
                                    @elseif($storeRequisition->priority === 'high')
                                        <span class="badge bg-danger">High</span>
                                    @elseif($storeRequisition->priority === 'urgent')
                                        <span class="badge bg-dark">Urgent</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Required Date:</strong>
                                <div>{{ $storeRequisition->required_date->format('M d, Y') }}</div>
                            </div>
                            <div class="col-md-6">
                                <strong>Created Date:</strong>
                                <div>{{ $storeRequisition->created_at->format('M d, Y h:i A') }}</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <strong>Purpose/Reason:</strong>
                                <div class="mt-1 p-3 bg-light rounded">
                                    {{ $storeRequisition->purpose }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Requisition Items -->
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-list-check me-2"></i>Requested Items
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Requested Qty</th>
                                        <th>Approved Qty</th>
                                        <th>Issued Qty</th>
                                        <th>Unit</th>
                                        <th>Notes</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($storeRequisition->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-medium">{{ $item->product->name }}</div>
                                            <small class="text-muted">{{ $item->product->category->name ?? 'N/A' }}</small>
                                        </td>
                                        <td>{{ number_format($item->quantity, 2) }}</td>
                                        <td>
                                            @if($item->approved_quantity !== null)
                                                <span class="text-success fw-bold">{{ number_format($item->approved_quantity, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->issued_quantity > 0)
                                                <span class="text-info fw-bold">{{ number_format($item->issued_quantity, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $item->product->unit }}</span>
                                        </td>
                                        <td>{{ $item->notes ?: '-' }}</td>
                                        <td>
                                            @if($storeRequisition->status === 'approved' && $item->approved_quantity > 0)
                                                @if($item->issued_quantity >= $item->approved_quantity)
                                                    <span class="badge bg-success">Fully Issued</span>
                                                @elseif($item->issued_quantity > 0)
                                                    <span class="badge bg-warning">Partially Issued</span>
                                                @else
                                                    <span class="badge bg-info">Pending Issue</span>
                                                @endif
                                            @elseif($storeRequisition->status === 'rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($storeRequisition->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Status Timeline -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-time me-2"></i>Status Timeline
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item active">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Created</h6>
                                    <p class="timeline-description">{{ $storeRequisition->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>

                            @if($storeRequisition->status !== 'draft')
                            <div class="timeline-item active">
                                <div class="timeline-marker bg-warning"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Submitted</h6>
                                    <p class="timeline-description">{{ $storeRequisition->updated_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>
                            @endif

                            @if(in_array($storeRequisition->status, ['approved', 'rejected', 'partially_issued', 'fully_issued']))
                            <div class="timeline-item active">
                                <div class="timeline-marker {{ $storeRequisition->status === 'rejected' ? 'bg-danger' : 'bg-success' }}"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">{{ $storeRequisition->status === 'rejected' ? 'Rejected' : 'Approved' }}</h6>
                                    <p class="timeline-description">
                                        @if($storeRequisition->approved_at)
                                            {{ $storeRequisition->approved_at->format('M d, Y h:i A') }}
                                        @else
                                            {{ $storeRequisition->updated_at->format('M d, Y h:i A') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @endif

                            @if(in_array($storeRequisition->status, ['partially_issued', 'fully_issued']))
                            <div class="timeline-item active">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Items Issued</h6>
                                    <p class="timeline-description">{{ $storeRequisition->updated_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Approval Details -->
                @if($storeRequisition->approvals->count() > 0)
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-check-shield me-2"></i>Approval History
                        </h6>
                    </div>
                    <div class="card-body">
                        @foreach($storeRequisition->approvals->sortBy('level') as $approval)
                        <div class="d-flex align-items-center mb-3 p-2 border rounded">
                            <div class="flex-shrink-0">
                                <div class="badge {{ $approval->status === 'approved' ? 'bg-success' : ($approval->status === 'rejected' ? 'bg-danger' : 'bg-warning') }} rounded-circle p-2">
                                    {{ $approval->level }}
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Level {{ $approval->level }}</h6>
                                <div class="text-muted small">
                                    @if($approval->approver)
                                        by {{ $approval->approver->name }}
                                    @else
                                        Pending
                                    @endif
                                </div>
                                @if($approval->approved_at)
                                    <div class="text-muted small">{{ $approval->approved_at->format('M d, Y h:i A') }}</div>
                                @endif
                                @if($approval->remarks)
                                    <div class="text-muted small mt-1">"{{ $approval->remarks }}"</div>
                                @endif
                            </div>
                            <div class="flex-shrink-0">
                                @if($approval->status === 'approved')
                                    <i class="bx bx-check-circle text-success"></i>
                                @elseif($approval->status === 'rejected')
                                    <i class="bx bx-x-circle text-danger"></i>
                                @else
                                    <i class="bx bx-time-five text-warning"></i>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Quick Actions -->
                @if(in_array($storeRequisition->status, ['pending']) && $canApprove)
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-cog me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" onclick="approveRequisition()">
                                <i class="bx bx-check me-1"></i> Approve
                            </button>
                            <button type="button" class="btn btn-danger" onclick="rejectRequisition()">
                                <i class="bx bx-x me-1"></i> Reject
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Summary -->
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-calculator me-2"></i>Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="text-primary fw-bold">{{ $storeRequisition->items->count() }}</div>
                                <small class="text-muted">Items</small>
                            </div>
                            <div class="col-4">
                                <div class="text-success fw-bold">{{ number_format($storeRequisition->approved_quantity, 0) }}</div>
                                <small class="text-muted">Approved</small>
                            </div>
                            <div class="col-4">
                                <div class="text-info fw-bold">{{ number_format($storeRequisition->issued_quantity, 0) }}</div>
                                <small class="text-muted">Issued</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalTitle">Approve Requisition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approvalForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks (Optional)</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="3" 
                                  placeholder="Add any comments or remarks..."></textarea>
                    </div>
                    <div id="itemApprovalSection" style="display: none;">
                        <label class="form-label">Item Approval</label>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Requested</th>
                                        <th>Approve Qty</th>
                                    </tr>
                                </thead>
                                <tbody id="itemApprovalBody">
                                    <!-- Items will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="approvalSubmitBtn">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return Items Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Return Issued Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="returnForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bx bx-warning me-2"></i>
                        <strong>Warning:</strong> Returning items will reverse the accounting entries and adjust inventory back to the original location.
                    </div>
                    
                    <div class="mb-3">
                        <label for="return_reason" class="form-label">Return Reason <span class="text-danger">*</span></label>
                        <textarea name="return_reason" id="return_reason" class="form-control" rows="3" 
                                  placeholder="Explain why items are being returned..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Items to Return</label>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Issued Qty</th>
                                        <th>Return Qty</th>
                                        <th>Unit Cost</th>
                                        <th>Total Cost</th>
                                    </tr>
                                </thead>
                                <tbody id="returnItemsBody">
                                    <!-- Items will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Total Return Amount</h6>
                                    <h4 class="text-primary" id="totalReturnAmount">0.00</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-undo me-1"></i> Process Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
let approvalAction = '';

function approveRequisition() {
    approvalAction = 'approve';
    document.getElementById('approvalModalTitle').textContent = 'Approve Requisition';
    document.getElementById('approvalSubmitBtn').textContent = 'Approve';
    document.getElementById('approvalSubmitBtn').className = 'btn btn-success';
    document.getElementById('itemApprovalSection').style.display = 'block';
    
    // Populate items for approval
    populateItemApproval();
    
    $('#approvalModal').modal('show');
}

function rejectRequisition() {
    approvalAction = 'reject';
    document.getElementById('approvalModalTitle').textContent = 'Reject Requisition';
    document.getElementById('approvalSubmitBtn').textContent = 'Reject';
    document.getElementById('approvalSubmitBtn').className = 'btn btn-danger';
    document.getElementById('itemApprovalSection').style.display = 'none';
    
    $('#approvalModal').modal('show');
}

function populateItemApproval() {
    const tbody = document.getElementById('itemApprovalBody');
    tbody.innerHTML = '';
    
    @foreach($storeRequisition->items as $item)
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>{{ $item->product->name }}</td>
        <td>{{ number_format($item->quantity, 2) }}</td>
        <td>
            <input type="number" name="items[{{ $item->id }}]" class="form-control form-control-sm" 
                   min="0" max="{{ $item->quantity }}" step="0.01" value="{{ $item->quantity }}"
                   required>
        </td>
    `;
    tbody.appendChild(row);
    @endforeach
}

document.getElementById('approvalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', approvalAction);
    
    $.ajax({
        url: "{{ route('store-requisitions.approve', $storeRequisition->id) }}",
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                Swal.fire('Success!', response.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error!', response.message || 'Action failed.', 'error');
            }
        },
        error: function(xhr) {
            let message = 'Action failed.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            Swal.fire('Error!', message, 'error');
        }
    });
});

function printRequisition() {
    window.open("{{ route('store-requisitions.print', $storeRequisition->id) }}", '_blank');
}

function returnItems() {
    // Populate return items
    populateReturnItems();
    $('#returnModal').modal('show');
}

function populateReturnItems() {
    const tbody = document.getElementById('returnItemsBody');
    tbody.innerHTML = '';
    
    @foreach($storeRequisition->items->where('issued_quantity', '>', 0) as $item)
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <div class="fw-medium">{{ $item->product->name }}</div>
            <small class="text-muted">{{ $item->product->category->name ?? 'N/A' }}</small>
        </td>
        <td>{{ number_format($item->issued_quantity, 2) }}</td>
        <td>
            <input type="number" name="return_items[{{ $item->id }}][quantity]" 
                   class="form-control return-quantity" 
                   min="0" max="{{ $item->issued_quantity }}" step="0.01" 
                   value="{{ $item->issued_quantity }}"
                   data-item-id="{{ $item->id }}" 
                   data-unit-cost="{{ $item->product->unit_cost ?? 0 }}"
                   onchange="calculateReturnTotal()">
            <input type="hidden" name="return_items[{{ $item->id }}][product_id]" value="{{ $item->product_id }}">
            <input type="hidden" name="return_items[{{ $item->id }}][inventory_item_id]" value="{{ $item->inventory_item_id }}">
        </td>
        <td class="unit-cost">{{ number_format($item->product->unit_cost ?? 0, 2) }}</td>
        <td class="item-total">{{ number_format(($item->product->unit_cost ?? 0) * $item->issued_quantity, 2) }}</td>
    `;
    tbody.appendChild(row);
    @endforeach
    
    calculateReturnTotal();
}

function calculateReturnTotal() {
    let total = 0;
    document.querySelectorAll('.return-quantity').forEach(input => {
        const quantity = parseFloat(input.value) || 0;
        const unitCost = parseFloat(input.dataset.unitCost) || 0;
        const itemTotal = quantity * unitCost;
        
        // Update row total
        const row = input.closest('tr');
        row.querySelector('.item-total').textContent = itemTotal.toFixed(2);
        
        total += itemTotal;
    });
    
    document.getElementById('totalReturnAmount').textContent = total.toFixed(2);
}

document.getElementById('returnForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: "{{ route('store-requisitions.actions.return', $storeRequisition->id) }}",
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                Swal.fire('Success!', response.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error!', response.message || 'Return failed.', 'error');
            }
        },
        error: function(xhr) {
            let message = 'Return failed.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            Swal.fire('Error!', message, 'error');
        }
    });
});
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -38px;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -30px;
    top: 16px;
    bottom: -20px;
    width: 2px;
    background-color: #e9ecef;
}

.timeline-item.active .timeline-marker {
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px currentColor;
}

.timeline-title {
    margin-bottom: 4px;
    font-size: 14px;
    font-weight: 600;
}

.timeline-description {
    margin-bottom: 0;
    font-size: 12px;
    color: #6c757d;
}
</style>
@endpush