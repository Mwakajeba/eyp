@extends('layouts.main')

@section('title', 'Store Requisition Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Requisitions', 'url' => route('store-requisitions.requisitions.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Requisition #' . $storeRequisition->requisition_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <!-- Header with Actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Store Requisition #{{ $storeRequisition->requisition_number }}</h5>
                <small class="text-muted">Created on {{ $storeRequisition->created_at->format('M d, Y \a\t h:i A') }}</small>
            </div>
            <div class="d-flex gap-2">
                @if(in_array($storeRequisition->status, ['pending']))
                <a href="{{ route('store-requisitions.requisitions.edit', $storeRequisition->hash_id) }}" class="btn btn-warning">
                    <i class="bx bx-edit me-1"></i> Edit
                </a>
                @endif
                
                @if($canApprove && in_array($storeRequisition->status, ['pending']))
                <button type="button" class="btn btn-success" onclick="showApprovalModal('approve')">
                    <i class="bx bx-check me-1"></i> Approve (Level {{ $storeRequisition->current_approval_level }})
                </button>
                
                <button type="button" class="btn btn-danger" onclick="showApprovalModal('reject')">
                    <i class="bx bx-x me-1"></i> Reject
                </button>
                @endif

                @if($storeRequisition->status === 'approved')
                <a href="{{ route('store-issues.create', ['requisition' => $storeRequisition->hash_id]) }}" class="btn btn-primary">
                    <i class="bx bx-package me-1"></i> Issue Items
                </a>
                @endif

                {{-- Show Return Items button when requisition is fully issued, partially issued, or completed --}}
                @if(in_array($storeRequisition->status, ['completed', 'fully_issued', 'partially_issued']))
                <button type="button" class="btn btn-danger" onclick="returnItems()">
                    <i class="bx bx-undo me-1"></i> Return Items
                </button>
                @endif

                <button type="button" class="btn btn-info" onclick="printRequisition()">
                    <i class="bx bx-printer me-1"></i> Print
                </button>
                
                <a href="{{ route('store-requisitions.requisitions.index') }}" class="btn btn-secondary">
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
                                    {!! $storeRequisition->status_badge !!}
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Requested by:</strong>
                                <div>{{ $storeRequisition->requestedBy->name }}</div>
                                <small class="text-muted">{{ $storeRequisition->requestedBy->email }}</small>
                            </div>
                            <div class="col-md-6">
                                <strong>Branch:</strong>
                                <div>{{ $storeRequisition->branch->name }}</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Department:</strong>
                                <div>{{ $storeRequisition->department->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <strong>Priority:</strong>
                                <div>
                                    @if($storeRequisition->priority === 'low')
                                        <span class="badge bg-light text-dark">Low</span>
                                    @elseif($storeRequisition->priority === 'normal')
                                        <span class="badge bg-info">Normal</span>
                                    @elseif($storeRequisition->priority === 'high')
                                        <span class="badge bg-warning">High</span>
                                    @elseif($storeRequisition->priority === 'urgent')
                                        <span class="badge bg-danger">Urgent</span>
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

                        @if($storeRequisition->purpose)
                        <div class="row mb-3">
                            <div class="col-12">
                                <strong>Purpose/Reason:</strong>
                                <div class="mt-1 p-3 bg-light rounded">
                                    {{ $storeRequisition->purpose }}
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($storeRequisition->notes)
                        <div class="row">
                            <div class="col-12">
                                <strong>Additional Notes:</strong>
                                <div class="mt-1 p-3 bg-light rounded">
                                    {{ $storeRequisition->notes }}
                                </div>
                            </div>
                        </div>
                        @endif
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
                                        <td>{{ number_format($item->quantity_requested, 2) }}</td>
                                        <td>
                                            @if($item->quantity_approved !== null)
                                                <span class="text-success fw-bold">{{ number_format($item->quantity_approved, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->quantity_issued > 0)
                                                <span class="text-info fw-bold">{{ number_format($item->quantity_issued, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $item->unit_of_measure }}</span>
                                        </td>
                                        <td>{{ $item->item_notes ?: '-' }}</td>
                                        <td>
                                            {!! $item->status_badge !!}
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

                            @if($storeRequisition->submitted_at)
                            <div class="timeline-item active">
                                <div class="timeline-marker bg-warning"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Submitted</h6>
                                    <p class="timeline-description">{{ $storeRequisition->submitted_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>
                            @endif

                            <!-- Multi-Level Approval Progress -->
                            @if($storeRequisition->status !== 'pending')
                            @php
                                $approvalSettings = null;
                                $currentLevel = $storeRequisition->current_approval_level;
                                $approvals = collect();
                                
                                try {
                                    $approvalSettings = App\Models\StoreRequisitionApprovalSettings::where('company_id', $storeRequisition->company_id)->first();
                                    $approvals = $storeRequisition->approvals ?? collect();
                                } catch (Exception $e) {
                                    // Handle any errors gracefully
                                }
                            @endphp
                            
                            @if($approvalSettings)
                                @for($level = 1; $level <= 5; $level++)
                                    @if($approvalSettings->{"level_{$level}_enabled"})
                                        @php
                                            $levelApproval = $approvals->where('approval_level', $level)->first();
                                            $levelUserId = $approvalSettings->{"level_{$level}_user_id"};
                                            $levelUser = $levelUserId ? App\Models\User::find($levelUserId) : null;
                                            $isCurrentLevel = $currentLevel == $level && in_array($storeRequisition->status, ['pending']);
                                            $isApproved = $levelApproval && $levelApproval->action === 'approved';
                                            $isRejected = $levelApproval && $levelApproval->action === 'rejected';
                                        @endphp
                                        
                                        <div class="timeline-item {{ $isApproved || $isRejected || $isCurrentLevel ? 'active' : '' }}">
                                            <div class="timeline-marker 
                                                {{ $isApproved ? 'bg-success' : ($isRejected ? 'bg-danger' : ($isCurrentLevel ? 'bg-primary' : 'bg-light')) }}">
                                                @if($isApproved)
                                                    <i class="bx bx-check text-white"></i>
                                                @elseif($isRejected)
                                                    <i class="bx bx-x text-white"></i>
                                                @elseif($isCurrentLevel)
                                                    <i class="bx bx-time text-white"></i>
                                                @else
                                                    {{ $level }}
                                                @endif
                                            </div>
                                            <div class="timeline-content">
                                                <h6 class="timeline-title">
                                                    Level {{ $level }} Approval
                                                    @if($isCurrentLevel)
                                                        <span class="badge bg-primary ms-2">Current</span>
                                                    @elseif($isApproved)
                                                        <span class="badge bg-success ms-2">Approved</span>
                                                    @elseif($isRejected)
                                                        <span class="badge bg-danger ms-2">Rejected</span>
                                                    @endif
                                                </h6>
                                                <p class="timeline-description mb-1">
                                                    <strong>Approver:</strong> {{ $levelUser ? $levelUser->name : 'Not assigned' }}
                                                </p>
                                                @if($levelApproval)
                                                    <p class="timeline-description mb-1">
                                                        <strong>{{ ucfirst($levelApproval->action) }}</strong> on {{ $levelApproval->action_date->format('M d, Y h:i A') }}
                                                    </p>
                                                    @if($levelApproval->comments)
                                                        <p class="timeline-description text-muted">
                                                            <em>"{{ $levelApproval->comments }}"</em>
                                                        </p>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endfor
                            @endif
                            @endif

                            @if(in_array($storeRequisition->status, ['partially_issued', 'completed']))
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
                                <div class="text-success fw-bold">{{ number_format($storeRequisition->items->sum('quantity_approved') ?? 0, 0) }}</div>
                                <small class="text-muted">Approved</small>
                            </div>
                            <div class="col-4">
                                <div class="text-info fw-bold">{{ number_format($storeRequisition->items->sum('quantity_issued') ?? 0, 0) }}</div>
                                <small class="text-muted">Issued</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Return Information (if any returns exist) -->
                @php
                    $returns = \App\Models\StoreRequisitionReturn::where('store_requisition_id', $storeRequisition->id)->get();
                @endphp
                
                @if($returns->isNotEmpty())
                <div class="card mt-3 border-warning">
                    <div class="card-header bg-warning bg-opacity-10 border-warning">
                        <h6 class="mb-0 text-warning">
                            <i class="bx bx-undo me-2"></i>Return Information
                        </h6>
                    </div>
                    <div class="card-body">
                        @foreach($returns as $return)
                        <div class="mb-4 pb-4" style="border-bottom: 1px solid #e9ecef;">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong>Return Date:</strong>
                                    <div class="text-info">{{ $return->return_date->format('M d, Y') }}</div>
                                </div>
                                <div class="col-md-34">
                                    <strong>Processed By:</strong>
                                    <div>{{ $return->processedBy?->name ?? 'System' }}</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <strong>Reason:</strong>
                                <div class="text-muted">{{ $return->return_reason }}</div>
                            </div>

                            <!-- Returned Items Table -->
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Unit Cost</th>
                                            <th>Total Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $returnItems = $return->returnItems;
                                        @endphp
                                        @forelse($returnItems as $item)
                                        <tr>
                                            <td>
                                                <div class="fw-medium">{{ $item->product->name }}</div>
                                                <small class="text-muted">{{ $item->product->category->name ?? 'N/A' }}</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-danger">{{ number_format($item->quantity_returned, 2) }}</span>
                                            </td>
                                            <td class="text-right">{{ number_format($item->unit_cost, 2) }}</td>
                                            <td class="text-right fw-bold">{{ number_format($item->total_cost, 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No items in this return</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endforeach

                        <div class="alert alert-info mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Total Returned Across All Returns:</strong> 
                            {{ number_format($returns->sum('total_return_amount'), 2) }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
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

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalLabel">
                    <span id="modalTitle">Approve Store Requisition</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approvalForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="approvalAction" name="action" value="">
                    
                    <div class="alert" id="approvalAlert" style="display: none;"></div>
                    
                    <!-- Items Approval Section (only show for approve action) -->
                    <div id="itemsApprovalSection" style="display: none;">
                        <h6 class="mb-3">
                            <i class="bx bx-check-square me-2"></i>Set Approved Quantities
                        </h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Requested</th>
                                        <th>Approved Qty</th>
                                        <th>Unit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($storeRequisition->items as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-medium">{{ $item->product->item_name ?? $item->product->name ?? 'Unknown Item' }}</div>
                                            <small class="text-muted">Code: {{ $item->product->item_code ?? 'N/A' }}</small>
                                        </td>
                                        <td>{{ number_format($item->quantity_requested, 2) }}</td>
                                        <td>
                                            <input type="number" 
                                                   class="form-control form-control-sm" 
                                                   name="items[{{ $item->id }}][quantity_approved]" 
                                                   value="{{ $item->quantity_approved ?? $item->quantity_requested }}"
                                                   min="0" 
                                                   max="{{ $item->quantity_requested }}"
                                                   step="0.01" 
                                                   placeholder="0.00">
                                        </td>
                                        <td>{{ $item->unit_of_measure ?? 'Units' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="approvalComments" class="form-label">
                            Comments <span id="commentsRequired" class="text-danger" style="display: none;">*</span>
                        </label>
                        <textarea class="form-control" id="approvalComments" name="comments" rows="4" 
                                placeholder="Enter your comments here..."></textarea>
                        <div class="form-text" id="commentsHelp">
                            Enter any additional comments for this approval action.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="approvalSubmitBtn">
                        <i class="bx bx-check me-1"></i> <span id="submitButtonText">Approve</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function printRequisition() {
    // Redirect to PDF print route
    window.location.href = "{{ route('store-requisitions.requisitions.print', $storeRequisition->hash_id) }}";
}

function showApprovalModal(action) {
    const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
    const modalTitle = document.getElementById('modalTitle');
    const approvalAction = document.getElementById('approvalAction');
    const submitBtn = document.getElementById('approvalSubmitBtn');
    const submitBtnText = document.getElementById('submitButtonText');
    const commentsRequired = document.getElementById('commentsRequired');
    const commentsHelp = document.getElementById('commentsHelp');
    const commentsField = document.getElementById('approvalComments');
    const itemsSection = document.getElementById('itemsApprovalSection');
    
    // Reset form
    document.getElementById('approvalForm').reset();
    commentsField.required = false;
    commentsRequired.style.display = 'none';
    
    if (action === 'approve') {
        modalTitle.textContent = 'Approve Store Requisition';
        approvalAction.value = 'approve';
        submitBtn.className = 'btn btn-success';
        submitBtn.innerHTML = '<i class="bx bx-check me-1"></i> Approve';
        submitBtnText.textContent = 'Approve';
        commentsHelp.textContent = 'Enter any additional comments for this approval.';
        itemsSection.style.display = 'block'; // Show items approval section
    } else {
        modalTitle.textContent = 'Reject Store Requisition';
        approvalAction.value = 'reject';
        submitBtn.className = 'btn btn-danger';
        submitBtn.innerHTML = '<i class="bx bx-x me-1"></i> Reject';
        submitBtnText.textContent = 'Reject';
        commentsRequired.style.display = 'inline';
        commentsHelp.textContent = 'Please provide a reason for rejection (required).';
        commentsField.required = true;
        itemsSection.style.display = 'none'; // Hide items approval section
    }
    
    modal.show();
}

// Handle approval form submission
document.getElementById('approvalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = document.getElementById('approvalSubmitBtn');
    const alert = document.getElementById('approvalAlert');
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Processing...';
    
    // Hide any previous alerts
    alert.style.display = 'none';
    
    fetch(`{{ route('store-requisitions.actions.approve', $storeRequisition->hash_id) }}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert.className = 'alert alert-success';
            alert.innerHTML = '<i class="bx bx-check me-1"></i> ' + data.message;
            alert.style.display = 'block';
            
            // Reload page after 2 seconds
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            // Show error message
            alert.className = 'alert alert-danger';
            alert.innerHTML = '<i class="bx bx-error me-1"></i> ' + (data.message || 'An error occurred');
            alert.style.display = 'block';
            
            // Re-enable submit button
            submitBtn.disabled = false;
            const action = document.getElementById('approvalAction').value;
            if (action === 'approve') {
                submitBtn.innerHTML = '<i class="bx bx-check me-1"></i> Approve';
            } else {
                submitBtn.innerHTML = '<i class="bx bx-x me-1"></i> Reject';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert.className = 'alert alert-danger';
        alert.innerHTML = '<i class="bx bx-error me-1"></i> An error occurred while processing your request.';
        alert.style.display = 'block';
        
        // Re-enable submit button
        submitBtn.disabled = false;
        const action = document.getElementById('approvalAction').value;
        if (action === 'approve') {
            submitBtn.innerHTML = '<i class="bx bx-check me-1"></i> Approve';
        } else {
            submitBtn.innerHTML = '<i class="bx bx-x me-1"></i> Reject';
        }
    });
});

// Return items functions
function returnItems() {
    try {
        populateReturnItems();
        const modal = new bootstrap.Modal(document.getElementById('returnModal'));
        modal.show();
    } catch (error) {
        console.error('Error in returnItems():', error);
        alert('Error opening return modal: ' + error.message);
    }
}

function populateReturnItems() {
    const tbody = document.getElementById('returnItemsBody');
    
    if (!tbody) {
        console.error('returnItemsBody element not found');
        return;
    }
    
    tbody.innerHTML = '';
    
    // Items data from server
    const returnableItems = {!! json_encode($storeRequisition->items->where('quantity_issued', '>', 0)->map(function($item) {
        return [
            'id' => $item->id,
            'product_name' => $item->product->name,
            'category_name' => $item->product->category->name ?? 'N/A',
            'quantity_issued' => $item->quantity_issued,
            'cost_price' => $item->product->cost_price ?? 0,
            'inventory_item_id' => $item->inventory_item_id
        ];
    })->values()) !!};
    
    if (returnableItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No items available for return</td></tr>';
        return;
    }
    
    returnableItems.forEach(function(item) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="fw-medium">${item.product_name}</div>
                <small class="text-muted">${item.category_name}</small>
            </td>
            <td>${parseFloat(item.quantity_issued).toFixed(2)}</td>
            <td>
                <input type="number" name="return_items[${item.id}][quantity]" 
                       class="form-control return-quantity" 
                       min="0" max="${item.quantity_issued}" step="0.01" 
                       value="${item.quantity_issued}"
                       data-item-id="${item.id}" 
                       data-unit-cost="${item.cost_price}"
                       onchange="calculateReturnTotal()">
                <input type="hidden" name="return_items[${item.id}][inventory_item_id]" value="${item.inventory_item_id}">
            </td>
            <td class="unit-cost">${parseFloat(item.cost_price).toFixed(2)}</td>
            <td class="item-total">${(parseFloat(item.cost_price) * parseFloat(item.quantity_issued)).toFixed(2)}</td>
        `;
        tbody.appendChild(row);
    });
    
    calculateReturnTotal();
}

function calculateReturnTotal() {
    let total = 0;
    const returnQuantityInputs = document.querySelectorAll('.return-quantity');
    
    returnQuantityInputs.forEach(input => {
        const quantity = parseFloat(input.value) || 0;
        const unitCost = parseFloat(input.dataset.unitCost) || 0;
        const itemTotal = quantity * unitCost;
        
        // Update row total
        const row = input.closest('tr');
        const itemTotalCell = row.querySelector('.item-total');
        if (itemTotalCell) {
            itemTotalCell.textContent = itemTotal.toFixed(2);
        }
        
        total += itemTotal;
    });
    
    const totalElement = document.getElementById('totalReturnAmount');
    if (totalElement) {
        totalElement.textContent = total.toFixed(2);
    } else {
        console.error('totalReturnAmount element not found');
    }
}

document.getElementById('returnForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch("{{ route('store-requisitions.actions.return', $storeRequisition->hash_id) }}", {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Success!', data.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Error', data.message || 'Return failed', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'An error occurred while processing the return.', 'error');
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