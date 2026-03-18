@extends('layouts.main')

@section('title', 'Store Issue Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Issues', 'url' => route('store-issues.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Issue #' . $storeIssue->issue_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <!-- Header with Actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Store Issue #{{ $storeIssue->issue_number }}</h5>
                <small class="text-muted">Issued on {{ $storeIssue->issue_date->format('M d, Y') }}</small>
            </div>
            <div class="d-flex gap-2">
                @if($storeIssue->status === 'partial')
                <a href="{{ route('store-issues.edit', $storeIssue->id) }}" class="btn btn-warning">
                    <i class="bx bx-edit me-1"></i> Continue Issue
                </a>
                @endif

                @if($storeIssue->status === 'completed')
                <a href="{{ route('store-returns.create', ['issue_id' => $storeIssue->id]) }}" class="btn btn-info">
                    <i class="bx bx-undo me-1"></i> Create Return
                </a>
                @endif

                <button type="button" class="btn btn-success" onclick="printIssue()">
                    <i class="bx bx-printer me-1"></i> Print
                </button>
                
                <a href="{{ route('store-issues.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to List
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Issue Details -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-info-circle me-2"></i>Issue Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Issue Number:</strong>
                                <div class="text-primary fw-bold">{{ $storeIssue->issue_number }}</div>
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong>
                                <div>
                                    @if($storeIssue->status === 'partial')
                                        <span class="badge bg-warning">Partial</span>
                                    @elseif($storeIssue->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($storeIssue->status) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Related Requisition:</strong>
                                <div>
                                    <a href="{{ route('store-requisitions.show', $storeIssue->store_requisition_id) }}" class="text-info">
                                        {{ $storeIssue->storeRequisition->requisition_number }}
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <strong>Requested by:</strong>
                                <div>{{ $storeIssue->storeRequisition->user->name }}</div>
                                <small class="text-muted">{{ $storeIssue->storeRequisition->user->email }}</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Issued by:</strong>
                                <div>{{ $storeIssue->issuedBy->name }}</div>
                                <small class="text-muted">{{ $storeIssue->issuedBy->email }}</small>
                            </div>
                            <div class="col-md-6">
                                <strong>Branch:</strong>
                                <div>{{ $storeIssue->branch->name }}</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Issue Date:</strong>
                                <div>{{ $storeIssue->issue_date->format('M d, Y') }}</div>
                            </div>
                            <div class="col-md-6">
                                <strong>Created:</strong>
                                <div>{{ $storeIssue->created_at->format('M d, Y h:i A') }}</div>
                            </div>
                        </div>

                        @if($storeIssue->notes)
                        <div class="row">
                            <div class="col-12">
                                <strong>Issue Notes:</strong>
                                <div class="mt-1 p-3 bg-light rounded">
                                    {{ $storeIssue->notes }}
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Issued Items -->
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-list-check me-2"></i>Issued Items
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Approved Qty</th>
                                        <th>Issued Qty</th>
                                        <th>Unit</th>
                                        <th>Notes</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($storeIssue->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-medium">{{ $item->product->name }}</div>
                                            <small class="text-muted">{{ $item->product->category->name ?? 'N/A' }}</small>
                                        </td>
                                        <td>{{ number_format($item->storeRequisitionItem->approved_quantity, 2) }}</td>
                                        <td>
                                            <span class="text-success fw-bold">{{ number_format($item->quantity, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $item->product->unit }}</span>
                                        </td>
                                        <td>{{ $item->notes ?: '-' }}</td>
                                        <td>
                                            @php
                                                $requisitionItem = $item->storeRequisitionItem;
                                                $totalIssued = $requisitionItem->issued_quantity;
                                                $approved = $requisitionItem->approved_quantity;
                                            @endphp
                                            @if($totalIssued >= $approved)
                                                <span class="badge bg-success">Fully Issued</span>
                                            @else
                                                <span class="badge bg-warning">Partially Issued</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Returns (if any) -->
                @if($storeIssue->returns->count() > 0)
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-undo me-2"></i>Related Returns
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Return Number</th>
                                        <th>Return Date</th>
                                        <th>Items Count</th>
                                        <th>Returned By</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($storeIssue->returns as $return)
                                    <tr>
                                        <td>
                                            <a href="{{ route('store-returns.show', $return->id) }}" class="text-info">
                                                {{ $return->return_number }}
                                            </a>
                                        </td>
                                        <td>{{ $return->return_date->format('M d, Y') }}</td>
                                        <td>{{ $return->items->count() }}</td>
                                        <td>{{ $return->returnedBy->name }}</td>
                                        <td>
                                            <a href="{{ route('store-returns.show', $return->id) }}" class="btn btn-sm btn-outline-info">
                                                <i class="bx bx-show"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Status Timeline -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-time me-2"></i>Issue Timeline
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item active">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Requisition Created</h6>
                                    <p class="timeline-description">{{ $storeIssue->storeRequisition->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>

                            <div class="timeline-item active">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Requisition Approved</h6>
                                    <p class="timeline-description">{{ $storeIssue->storeRequisition->approved_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>

                            <div class="timeline-item active">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Items Issued</h6>
                                    <p class="timeline-description">{{ $storeIssue->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>

                            @if($storeIssue->status === 'completed')
                            <div class="timeline-item active">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Issue Completed</h6>
                                    <p class="timeline-description">{{ $storeIssue->updated_at->format('M d, Y h:i A') }}</p>
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
                            <i class="bx bx-calculator me-2"></i>Issue Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="text-primary fw-bold">{{ $storeIssue->items->count() }}</div>
                                <small class="text-muted">Items Issued</small>
                            </div>
                            <div class="col-6">
                                <div class="text-success fw-bold">{{ number_format($storeIssue->items->sum('quantity'), 0) }}</div>
                                <small class="text-muted">Total Quantity</small>
                            </div>
                        </div>
                        
                        @if($storeIssue->returns->count() > 0)
                        <hr>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="text-warning fw-bold">{{ $storeIssue->returns->count() }}</div>
                                <small class="text-muted">Returns</small>
                            </div>
                            <div class="col-6">
                                <div class="text-info fw-bold">{{ number_format($storeIssue->returns->sum(function($return) { return $return->items->sum('quantity'); }), 0) }}</div>
                                <small class="text-muted">Returned Qty</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                @if($storeIssue->status === 'partial')
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-cog me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('store-issues.edit', $storeIssue->id) }}" class="btn btn-warning">
                                <i class="bx bx-edit me-1"></i> Continue Issue
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                @if($storeIssue->status === 'completed')
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-cog me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('store-returns.create', ['issue_id' => $storeIssue->id]) }}" class="btn btn-info">
                                <i class="bx bx-undo me-1"></i> Create Return
                            </a>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function printIssue() {
    window.open("{{ route('store-issues.print', $storeIssue->id) }}", '_blank');
}
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