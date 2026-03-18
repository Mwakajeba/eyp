@extends('layouts.main')

@section('title', 'Store Return Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Returns', 'url' => route('store-returns.index'), 'icon' => 'bx bx-undo'],
            ['label' => 'Return Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Store Return Details</h5>
                <small class="text-muted">Return processed on {{ $storeReturn->return_date ? $storeReturn->return_date->format('M d, Y') : 'N/A' }}</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('store-returns.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Returns
                </a>
                <button type="button" class="btn btn-info" onclick="printReturn()">
                    <i class="bx bx-printer me-1"></i> Print
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Return Information -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-info-circle me-2"></i>Return Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Original Requisition</label>
                                    <div class="fw-bold">
                                        @if($storeReturn->storeRequisition)
                                            <a href="{{ route('store-requisitions.requisitions.show', $storeReturn->storeRequisition->hash_id) }}" class="text-primary">
                                                {{ $storeReturn->storeRequisition->requisition_number ?? 'N/A' }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted">Return Date</label>
                                    <div class="fw-bold">{{ $storeReturn->return_date ? $storeReturn->return_date->format('M d, Y') : 'N/A' }}</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted">Processed By</label>
                                    <div class="fw-bold">{{ $storeReturn->processedBy->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Branch</label>
                                    <div class="fw-bold">{{ $storeReturn->branch->name ?? 'N/A' }}</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted">Total Return Amount</label>
                                    <div class="fw-bold text-danger fs-5">TZS {{ number_format($storeReturn->total_return_amount, 2) }}</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted">Return Reason</label>
                                    <div class="fw-bold">{{ $storeReturn->return_reason ?? 'No reason specified' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Original Requisition Items -->
                @if($storeReturn->storeRequisition && $storeReturn->storeRequisition->items)
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-list-ul me-2"></i>Original Requisition Items
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Requested Qty</th>
                                        <th>Issued Qty</th>
                                        <th>Unit Price</th>
                                        <th>Total Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($storeReturn->storeRequisition->items as $item)
                                    @php
                                        // Use product cost price if unit_cost is zero
                                        $actualUnitCost = $item->unit_cost > 0 
                                            ? $item->unit_cost 
                                            : ($item->product->cost_price ?? $item->product->unit_price ?? 0);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $item->product->name ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $item->product->code ?? '' }}</small>
                                        </td>
                                        <td>{{ number_format($item->quantity_requested) }}</td>
                                        <td>{{ number_format($item->quantity_issued ?? 0) }}</td>
                                        <td>TZS {{ number_format($actualUnitCost, 2) }}</td>
                                        <td>TZS {{ number_format(($item->quantity_issued ?? 0) * $actualUnitCost, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Summary -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-calculator me-2"></i>Return Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Return Amount:</span>
                            <span class="fw-bold text-danger">TZS {{ number_format($storeReturn->total_return_amount, 2) }}</span>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Return ID</small>
                            <span class="fw-bold">#{{ $storeReturn->id }}</span>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Processed On</small>
                            <span class="fw-bold">{{ $storeReturn->created_at ? $storeReturn->created_at->format('M d, Y h:i A') : 'N/A' }}</span>
                        </div>
                        
                        @if($storeReturn->storeRequisition && $storeReturn->storeRequisition->requestedBy)
                        <div class="mb-3">
                            <small class="text-muted d-block">Originally Requested By</small>
                            <span class="fw-bold">{{ $storeReturn->storeRequisition->requestedBy->name ?? 'N/A' }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function printReturn() {
    window.print();
}
</script>
@endpush