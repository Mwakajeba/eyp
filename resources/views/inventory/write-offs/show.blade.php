@extends('layouts.main')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Write-offs', 'url' => route('inventory.write-offs.index'), 'icon' => 'bx bx-x-circle'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">Write-off Details - {{ $movement->reference ?? 'REF-' . $movement->id }}</h5>
                            </div>
                            <div class="d-flex gap-2">
                                @can('manage inventory movements')
                                <a href="{{ route('inventory.write-offs.edit', $movement->hash_id) }}" class="btn btn-warning">
                                    <i class="bx bx-edit me-1"></i>Edit
                                </a>
                                @endcan
                                @can('manage inventory movements')
                                <form action="{{ route('inventory.write-offs.destroy', $movement->hash_id) }}" 
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Are you sure you want to delete this write-off?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="bx bx-trash me-1"></i>Delete
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                <div class="row">
                    <!-- Write-off Information -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-light">
                                <h6 class="card-title mb-0">Write-off Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Reference:</strong></div>
                                    <div class="col-sm-8">{{ $movement->reference ?? 'REF-' . $movement->id }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Date:</strong></div>
                                    <div class="col-sm-8">{{ $movement->movement_date ? $movement->movement_date->format('M d, Y') : 'N/A' }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Type:</strong></div>
                                    <div class="col-sm-8">
                                        <span class="badge bg-dark">Write Off</span>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Created:</strong></div>
                                    <div class="col-sm-8">{{ $movement->created_at->format('M d, Y h:i A') }}</div>
                                </div>
                                @if($movement->updated_at != $movement->created_at)
                                <div class="row mb-0">
                                    <div class="col-sm-4"><strong>Updated:</strong></div>
                                    <div class="col-sm-8">{{ $movement->updated_at->format('M d, Y h:i A') }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Item Information -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-light">
                                <h6 class="card-title mb-0">Item Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Item Code:</strong></div>
                                    <div class="col-sm-8">{{ $movement->item->code }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Item Name:</strong></div>
                                    <div class="col-sm-8">{{ $movement->item->name }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Category:</strong></div>
                                    <div class="col-sm-8">{{ $movement->item->category->name ?? 'N/A' }}</div>
                                </div>
                                <div class="row mb-0">
                                    <div class="col-sm-4"><strong>Unit:</strong></div>
                                    <div class="col-sm-8">{{ $movement->item->unit_of_measure }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <!-- Quantity and Cost -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-light">
                                <h6 class="card-title mb-0">Quantity & Cost</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Quantity:</strong></div>
                                    <div class="col-sm-8">
                                        <span class="fw-bold">{{ number_format($movement->quantity, 2) }}</span>
                                        {{ $movement->item->unit_of_measure }}
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Unit Cost:</strong></div>
                                    <div class="col-sm-8">{{ number_format($movement->unit_cost, 2) }}</div>
                                </div>
                                <div class="row mb-0">
                                    <div class="col-sm-4"><strong>Total Cost:</strong></div>
                                    <div class="col-sm-8">
                                        <span class="fw-bold text-danger">{{ number_format($movement->total_cost, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Impact -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-light">
                                <h6 class="card-title mb-0">Stock Impact</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-6"><strong>Balance Before:</strong></div>
                                    <div class="col-sm-6">
                                        <span class="fw-bold">{{ number_format($movement->balance_before, 2) }}</span>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-6"><strong>Quantity Written Off:</strong></div>
                                    <div class="col-sm-6">
                                        <span class="text-danger fw-bold">-{{ number_format($movement->quantity, 2) }}</span>
                                    </div>
                                </div>
                                <div class="row mb-0">
                                    <div class="col-sm-6"><strong>Balance After:</strong></div>
                                    <div class="col-sm-6">
                                        <span class="fw-bold">{{ number_format($movement->balance_after, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <!-- Reason and Notes -->
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-header bg-light">
                                <h6 class="card-title mb-0">Reason & Notes</h6>
                            </div>
                            <div class="card-body">
                                @if($movement->reference)
                                <div class="row mb-3">
                                    <div class="col-sm-2"><strong>Reference:</strong></div>
                                    <div class="col-sm-10">{{ $movement->reference }}</div>
                                </div>
                                @endif
                                @if($movement->reason)
                                <div class="row mb-3">
                                    <div class="col-sm-2"><strong>Reason:</strong></div>
                                    <div class="col-sm-10">{{ $movement->reason }}</div>
                                </div>
                                @endif
                                @if($movement->notes)
                                <div class="row mb-0">
                                    <div class="col-sm-2"><strong>Notes:</strong></div>
                                    <div class="col-sm-10">{{ $movement->notes }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <a href="{{ route('inventory.write-offs.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Back to Write-offs
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

