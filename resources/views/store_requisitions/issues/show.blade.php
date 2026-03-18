@extends('layouts.main')

@section('title', 'Store Issue Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Issues', 'url' => route('store-issues.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Issue #' . $storeIssue->voucher_no, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <!-- Header with Actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Store Issue #{{ $storeIssue->voucher_no }}</h5>
                <small class="text-muted">Created on {{ $storeIssue->created_at->format('M d, Y \a\t h:i A') }}</small>
            </div>
            <div class="d-flex gap-2">
                @if($storeIssue->status === 'pending')
                <a href="{{ route('store-issues.edit', $storeIssue->id) }}" class="btn btn-warning">
                    <i class="bx bx-edit me-1"></i> Edit
                </a>
                @endif
                
                <button type="button" class="btn btn-info" onclick="printIssue()">
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
                                <div class="text-primary fw-bold">{{ $storeIssue->voucher_no }}</div>
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong>
                                <div>
                                    {!! $storeIssue->status_badge !!}
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Requisition:</strong>
                                <div>
                                    <a href="{{ route('store-requisitions.requisitions.show', $storeIssue->storeRequisition->id) }}" class="text-decoration-none">
                                        {{ $storeIssue->storeRequisition->requisition_number }}
                                    </a>
                                </div>
                                <small class="text-muted">{{ $storeIssue->storeRequisition->purpose }}</small>
                            </div>
                            <div class="col-md-6">
                                <strong>Branch:</strong>
                                <div>{{ $storeIssue->branch->name }}</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Issued To:</strong>
                                <div>{{ $storeIssue->issuedTo->name }}</div>
                                <small class="text-muted">{{ $storeIssue->issuedTo->email }}</small>
                            </div>
                            <div class="col-md-6">
                                <strong>Issued By:</strong>
                                <div>{{ $storeIssue->issuedBy->name }}</div>
                                <small class="text-muted">{{ $storeIssue->issuedBy->email }}</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Issue Date:</strong>
                                <div>{{ $storeIssue->issue_date->format('M d, Y') }}</div>
                            </div>
                            <div class="col-md-6">
                                <strong>Total Amount:</strong>
                                <div class="text-success fw-bold">TZS {{ number_format($storeIssue->actual_total, 2) }}</div>
                            </div>
                        </div>

                        @if($storeIssue->description)
                        <div class="row mb-3">
                            <div class="col-12">
                                <strong>Description:</strong>
                                <div class="mt-1 p-3 bg-light rounded">
                                    {{ $storeIssue->description }}
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($storeIssue->remarks)
                        <div class="row">
                            <div class="col-12">
                                <strong>Remarks:</strong>
                                <div class="mt-1 p-3 bg-light rounded">
                                    {{ $storeIssue->remarks }}
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Issue Items -->
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
                                        <th>Requested Qty</th>
                                        <th>Approved Qty</th>
                                        <th>Net Qty</th>
                                        <th>Unit Cost</th>
                                        <th>Net Total</th>
                                        <th>Unit</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($storeIssue->storeRequisition->items as $index => $item)
                                    @php
                                        // Use item unit cost with fallback to product cost
                                        $unitCost = $item->unit_cost > 0 ? $item->unit_cost : ($item->product->cost_price ?? $item->product->unit_price ?? 0);
                                        
                                        // Calculate quantities
                                        $issuedQty = $item->quantity_issued ?? 0;
                                        $returnedQty = $item->quantity_returned ?? 0;
                                        $netQty = $issuedQty - $returnedQty;
                                        
                                        // Calculate totals
                                        $issuedTotal = $issuedQty * $unitCost;
                                        $returnedTotal = $returnedQty * $unitCost;
                                        $netTotal = $netQty * $unitCost;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-medium">{{ $item->product->name }}</div>
                                            <small class="text-muted">{{ $item->product->category->name ?? 'N/A' }}</small>
                                        </td>
                                        <td>{{ number_format($item->quantity_requested, 2) }}</td>
                                        <td>{{ number_format($item->quantity_approved, 2) }}</td>
                                        <td>
                                            <span class="text-info fw-bold">{{ number_format($netQty, 2) }}</span>
                                            @if($returnedQty > 0)
                                                <br><small class="text-muted">({{ number_format($issuedQty, 2) }} issued, {{ number_format($returnedQty, 2) }} returned)</small>
                                            @endif
                                        </td>
                                        <td>TZS {{ number_format($unitCost, 2) }}</td>
                                        <td>
                                            <span class="text-success fw-bold">TZS {{ number_format($netTotal, 2) }}</span>
                                            @if($returnedTotal > 0)
                                                <br><small class="text-muted">({{ number_format($issuedTotal, 2) }} issued, {{ number_format($returnedTotal, 2) }} returned)</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $item->unit_of_measure }}</span>
                                        </td>
                                        <td>{{ $item->issue_notes ?: '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="6" class="text-end">Net Total Amount:</th>
                                        <th class="text-success">TZS {{ number_format($storeIssue->actual_total, 2) }}</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
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
                                    <p class="timeline-description">{{ $storeIssue->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>

                            @if($storeIssue->status !== 'pending')
                            <div class="timeline-item active">
                                <div class="timeline-marker {{ $storeIssue->status === 'cancelled' ? 'bg-danger' : 'bg-success' }}"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">{{ ucfirst($storeIssue->status) }}</h6>
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
                            <i class="bx bx-calculator me-2"></i>Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="text-primary fw-bold">{{ $storeIssue->storeRequisition->items->count() }}</div>
                                <small class="text-muted">Items</small>
                            </div>
                            <div class="col-4">
                                <div class="text-info fw-bold">{{ number_format($storeIssue->storeRequisition->items->sum('quantity_issued'), 0) }}</div>
                                <small class="text-muted">Qty Issued</small>
                            </div>
                            <div class="col-4">
                                <div class="text-success fw-bold">TZS {{ number_format($storeIssue->actual_total, 0) }}</div>
                                <small class="text-muted">Total Value</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Requisition -->
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-link me-2"></i>Related Requisition
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-medium">{{ $storeIssue->storeRequisition->requisition_number }}</div>
                                <small class="text-muted">{{ $storeIssue->storeRequisition->purpose }}</small>
                            </div>
                            <div>
                                <a href="{{ route('store-requisitions.requisitions.show', $storeIssue->storeRequisition->id) }}" class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function printIssue() {
    window.print();
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