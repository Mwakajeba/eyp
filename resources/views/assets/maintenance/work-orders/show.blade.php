@extends('layouts.main')

@section('title', 'Work Order Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Maintenance', 'url' => route('assets.maintenance.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Work Orders', 'url' => route('assets.maintenance.work-orders.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">Work Order: {{ $workOrder->wo_number }}</h5>
                <p class="text-muted mb-0">View work order details and status</p>
            </div>
            <div class="d-flex gap-2">
                @if(in_array($workOrder->status, ['approved', 'in_progress']) && auth()->user()->can('execute work orders'))
                <a href="{{ route('assets.maintenance.work-orders.execute', $encodedId) }}" class="btn btn-primary">
                    <i class="bx bx-wrench me-1"></i>Execute
                </a>
                @endif
                @if($workOrder->status === 'completed' && $workOrder->cost_classification === 'pending_review' && auth()->user()->can('review work orders'))
                <a href="{{ route('assets.maintenance.work-orders.review', $encodedId) }}" class="btn btn-warning">
                    <i class="bx bx-check-circle me-1"></i>Review & Classify
                </a>
                @endif
                @if(in_array($workOrder->status, ['draft', 'approved']) && auth()->user()->can('edit work orders'))
                <a href="{{ route('assets.maintenance.work-orders.edit', $encodedId) }}" class="btn btn-outline-primary">
                    <i class="bx bx-edit me-1"></i>Edit
                </a>
                @endif
                <a href="{{ route('assets.maintenance.work-orders.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">Work Order Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small">WO Number</label>
                                <div class="fw-bold">{{ $workOrder->wo_number }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Status</label>
                                <div>
                                    @php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'approved' => 'success',
                                            'in_progress' => 'primary',
                                            'on_hold' => 'warning',
                                            'completed' => 'info',
                                            'cancelled' => 'danger'
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$workOrder->status] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $workOrder->status)) }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Asset</label>
                                <div class="fw-bold">{{ $workOrder->asset->name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $workOrder->asset->code ?? '' }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Maintenance Type</label>
                                <div>{{ $workOrder->maintenanceType->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Execution Type</label>
                                <div>{{ ucfirst(str_replace('_', ' ', $workOrder->execution_type)) }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Assigned To</label>
                                <div>
                                    @if($workOrder->assignedTechnician)
                                        {{ $workOrder->assignedTechnician->name }}
                                    @elseif($workOrder->vendor)
                                        {{ $workOrder->vendor->name }}
                                    @else
                                        Unassigned
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Estimated Start Date</label>
                                <div>{{ $workOrder->estimated_start_date->format('M d, Y') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Estimated Completion Date</label>
                                <div>{{ $workOrder->estimated_completion_date->format('M d, Y') }}</div>
                            </div>
                            @if($workOrder->actual_start_date)
                            <div class="col-md-6">
                                <label class="text-muted small">Actual Start Date</label>
                                <div>{{ $workOrder->actual_start_date->format('M d, Y') }}</div>
                            </div>
                            @endif
                            @if($workOrder->actual_completion_date)
                            <div class="col-md-6">
                                <label class="text-muted small">Actual Completion Date</label>
                                <div>{{ $workOrder->actual_completion_date->format('M d, Y') }}</div>
                            </div>
                            @endif
                            @if($workOrder->work_description)
                            <div class="col-12">
                                <label class="text-muted small">Work Description</label>
                                <div>{{ $workOrder->work_description }}</div>
                            </div>
                            @endif
                            @if($workOrder->work_performed)
                            <div class="col-12">
                                <label class="text-muted small">Work Performed</label>
                                <div>{{ $workOrder->work_performed }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Costs -->
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">Cost Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Estimated Labor Cost</label>
                                <div class="fw-bold">TZS {{ number_format($workOrder->estimated_labor_cost, 2) }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Estimated Material Cost</label>
                                <div class="fw-bold">TZS {{ number_format($workOrder->estimated_material_cost, 2) }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Estimated Other Cost</label>
                                <div class="fw-bold">TZS {{ number_format($workOrder->estimated_other_cost, 2) }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Total Estimated Cost</label>
                                <div class="fw-bold text-primary">TZS {{ number_format($workOrder->total_estimated_cost, 2) }}</div>
                            </div>
                            @if($workOrder->status === 'completed')
                            <div class="col-12"><hr></div>
                            <div class="col-md-6">
                                <label class="text-muted small">Actual Labor Cost</label>
                                <div class="fw-bold">TZS {{ number_format($workOrder->actual_labor_cost, 2) }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Actual Material Cost</label>
                                <div class="fw-bold">TZS {{ number_format($workOrder->actual_material_cost, 2) }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Actual Other Cost</label>
                                <div class="fw-bold">TZS {{ number_format($workOrder->actual_other_cost, 2) }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Total Actual Cost</label>
                                <div class="fw-bold text-success">TZS {{ number_format($workOrder->total_actual_cost, 2) }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($workOrder->costs && $workOrder->costs->count() > 0)
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Cost Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Quantity</th>
                                        <th>Unit Cost</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($workOrder->costs as $cost)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ ucfirst($cost->cost_type) }}</span></td>
                                        <td>{{ $cost->description }}</td>
                                        <td>{{ $cost->quantity }} {{ $cost->unit ?? '' }}</td>
                                        <td>TZS {{ number_format($cost->unit_cost, 2) }}</td>
                                        <td>TZS {{ number_format($cost->total_with_tax, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">Classification</h6>
                    </div>
                    <div class="card-body">
                        @if($workOrder->status === 'completed')
                            <div class="mb-3">
                                <label class="text-muted small">Cost Classification</label>
                                <div>
                                    @php
                                        $classColors = [
                                            'expense' => 'warning',
                                            'capitalized' => 'success',
                                            'pending_review' => 'info'
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $classColors[$workOrder->cost_classification] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $workOrder->cost_classification)) }}
                                    </span>
                                </div>
                            </div>
                            @if($workOrder->cost_classification === 'capitalized')
                            <div class="mb-3">
                                <label class="text-muted small">Life Extension</label>
                                <div>{{ $workOrder->life_extension_months ?? 0 }} months</div>
                            </div>
                            @endif
                            @if($workOrder->gl_posted)
                            <div class="mb-3">
                                <label class="text-muted small">GL Posted</label>
                                <div>
                                    <span class="badge bg-success">Yes</span>
                                    <small class="d-block text-muted mt-1">{{ $workOrder->gl_posted_at->format('M d, Y H:i') }}</small>
                                </div>
                            </div>
                            @endif
                        @else
                            <div class="text-muted">Classification will be available after completion</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

