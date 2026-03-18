@extends('layouts.main')

@section('title', 'Work Order Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Production Management', 'url' => '#', 'icon' => 'bx bx-cog'],
            ['label' => 'Work Orders', 'url' => route('production.work-orders.index'), 'icon' => 'bx bx-list-ul'],
            ['label' => $workOrder->wo_number, 'url' => '#', 'icon' => 'bx bx-file']
        ]" />

        <!-- Work Order Header -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h3>{{ $workOrder->product_name }} - {{ $workOrder->style }}</h3>
                            <p class="text-muted mb-2">
                                <strong>Customer:</strong> {{ $workOrder->customer->name ?? 'N/A' }} | 
                                <strong>Due Date:</strong> {{ $workOrder->due_date->format('M d, Y') }}
                            </p>
                            <p class="mb-0">
                                <strong>Total Quantity:</strong> {{ $workOrder->total_quantity }} pieces |
                                <strong>Logo Required:</strong> {{ $workOrder->requires_logo ? 'Yes' : 'No' }}
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="mb-2">
                                {!! $workOrder->status_badge !!}
                            </div>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar" role="progressbar" style="width: {{ $workOrder->getProgressPercentage() }}%;">
                                    {{ $workOrder->getProgressPercentage() }}%
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Size Breakdown -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Size Breakdown:</h6>
                            @foreach($workOrder->sizes_quantities as $size => $quantity)
                                <span class="badge bg-info me-2">{{ $size }}: {{ $quantity }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Production Timeline -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Production Timeline</h4>
                    
                    <div class="timeline-alt pb-0">
                        @foreach($workOrder->processes as $process)
                            @php
                                $isActive = $process->process_stage === $workOrder->status;
                                $isCompleted = $process->status === 'completed';
                                $isPending = $process->status === 'pending';
                            @endphp
                            
                            <div class="timeline-item">
                                <i class="mdi mdi-{{ $isCompleted ? 'check-circle' : ($isActive ? 'clock-outline' : 'circle-outline') }} 
                                          bg-{{ $isCompleted ? 'success' : ($isActive ? 'warning' : 'secondary') }}-lighten 
                                          text-{{ $isCompleted ? 'success' : ($isActive ? 'warning' : 'secondary') }}"></i>
                                <div class="timeline-item-info">
                                    <h5 class="mt-0 mb-1">
                                        {{ \App\Models\Production\WorkOrder::getStatuses()[$process->process_stage] }}
                                        {!! $process->status_badge !!}
                                    </h5>
                                    @if($process->started_at)
                                        <p class="font-14 text-muted mt-2 mb-1">
                                            <strong>Started:</strong> {{ $process->started_at->format('M d, Y H:i') }}
                                        </p>
                                    @endif
                                    @if($process->completed_at)
                                        <p class="font-14 text-muted mt-2 mb-1">
                                            <strong>Completed:</strong> {{ $process->completed_at->format('M d, Y H:i') }}
                                            @if($process->duration)
                                                <span class="text-success">({{ round($process->duration) }} minutes)</span>
                                            @endif
                                        </p>
                                    @endif
                                    @if($process->operator)
                                        <p class="font-14 text-muted mt-2 mb-1">
                                            <strong>Operator:</strong> {{ $process->operator->name }}
                                        </p>
                                    @endif
                                    @if($process->machine)
                                        <p class="font-14 text-muted mt-2 mb-1">
                                            <strong>Machine:</strong> {{ $process->machine->machine_name }}
                                        </p>
                                    @endif
                                    @if($process->notes)
                                        <p class="font-14 text-muted mt-2 mb-0">{{ $process->notes }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- BOM -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Bill of Materials</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th>Type</th>
                                    <th>Required Qty</th>
                                    <th>Variance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workOrder->bom as $bomItem)
                                    <tr>
                                        <td>
                                            <strong>{{ $bomItem->materialItem->name }}</strong><br>
                                            <small class="text-muted">{{ $bomItem->materialItem->code }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst($bomItem->material_type) }}</span>
                                        </td>
                                        <td>{{ number_format($bomItem->required_quantity, 3) }} {{ $bomItem->unit_of_measure }}</td>
                                        <td>±{{ $bomItem->variance_allowed }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Material Issues -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title mb-0">Material Issues</h4>
                        @if($workOrder->status === \App\Models\Production\WorkOrder::STATUS_MATERIAL_ISSUED)
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#issueMaterialModal">
                                Issue Materials
                            </button>
                        @endif
                    </div>
                    
                    @if($workOrder->materialIssues->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Voucher #</th>
                                        <th>Material</th>
                                        <th>Quantity</th>
                                        <th>Lot #</th>
                                        <th>Issued</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($workOrder->materialIssues as $issue)
                                        <tr>
                                            <td>{{ $issue->issue_voucher_number }}</td>
                                            <td>{{ $issue->materialItem->name }}</td>
                                            <td>{{ $issue->formatted_quantity }}</td>
                                            <td>{{ $issue->lot_number ?: 'N/A' }}</td>
                                            <td>{{ $issue->issued_at->format('M d, H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No materials issued yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Production Records & Quality Checks -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title mb-0">Production Records</h4>
                        @if(in_array($workOrder->status, ['KNITTING', 'CUTTING', 'JOINING', 'EMBROIDERY', 'IRONING_FINISHING', 'PACKAGING']))
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#recordProductionModal">
                                Record Production
                            </button>
                        @endif
                    </div>

                    @if($workOrder->productionRecords->count() > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Stage</th>
                                        <th>Operator</th>
                                        <th>Machine</th>
                                        <th>Yield %</th>
                                        <th>Time</th>
                                        <th>Recorded</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($workOrder->productionRecords as $record)
                                        <tr>
                                            <td>{!! $record->stage_badge !!}</td>
                                            <td>{{ $record->operator->name ?? 'N/A' }}</td>
                                            <td>{{ $record->machine->machine_name ?? 'N/A' }}</td>
                                            <td>{{ $record->yield_percentage ? number_format($record->yield_percentage, 1) . '%' : 'N/A' }}</td>
                                            <td>{{ $record->formatted_operator_time }}</td>
                                            <td>{{ $record->recorded_at->format('M d, H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No production records yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title mb-0">Quality Checks</h4>
                        @if($workOrder->status === \App\Models\Production\WorkOrder::STATUS_QC)
                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#qualityCheckModal">
                                QC Check
                            </button>
                        @endif
                    </div>

                    @if($workOrder->qualityChecks->count() > 0)
                        @foreach($workOrder->qualityChecks as $qc)
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between">
                                    {!! $qc->result_badge !!}
                                    <small class="text-muted">{{ $qc->inspected_at->format('M d, H:i') }}</small>
                                </div>
                                <p class="mb-1"><strong>Inspector:</strong> {{ $qc->inspector->name }}</p>
                                @if($qc->defect_codes)
                                    <p class="mb-1"><strong>Defects:</strong> {{ $qc->defect_codes_string }}</p>
                                @endif
                                @if($qc->rework_notes)
                                    <p class="mb-0"><strong>Rework Notes:</strong> {{ $qc->rework_notes }}</p>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">No quality checks yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Packaging Records -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title mb-0">Packaging Records</h4>
                        @if($workOrder->status === 'PACKAGING')
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#recordPackagingModal">
                                Add Packaging Record
                            </button>
                        @endif
                    </div>
                    
                    @if($workOrder->packagingRecords->count() > 0)
                        @foreach($workOrder->packagingRecords as $packaging)
                            <div class="border rounded p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6>Packed Quantities</h6>
                                        <p>{{ $packaging->packed_quantities_string }}</p>
                                        <p><strong>Total:</strong> {{ $packaging->total_packed }} pieces</p>
                                    </div>
                                    <div class="col-md-4">
                                        <h6>Carton Numbers</h6>
                                        <p>{{ $packaging->carton_numbers_string }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <h6>Packed By</h6>
                                        <p>{{ $packaging->packedBy->name }}</p>
                                        <p><small class="text-muted">{{ $packaging->packed_at->format('M d, Y H:i') }}</small></p>
                                    </div>
                                </div>
                                @if($packaging->notes)
                                    <div class="row">
                                        <div class="col-12">
                                            <h6>Notes</h6>
                                            <p>{{ $packaging->notes }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">No packaging records found.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@include('production.work-orders.modals.issue-material')
@include('production.work-orders.modals.record-production')
@include('production.work-orders.modals.quality-check')
@include('production.work-orders.modals.record-packaging')
@endsection

@push('styles')
<style>
.timeline-alt {
    position: relative;
    padding: 0;
}

.timeline-item {
    position: relative;
    padding-left: 31px;
    padding-bottom: 20px;
}

.timeline-item i {
    position: absolute;
    left: 0;
    top: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    text-align: center;
    font-size: 12px;
    line-height: 20px;
}

.timeline-item:not(:last-child):before {
    content: '';
    background-color: #dee2e6;
    position: absolute;
    left: 9px;
    top: 20px;
    width: 2px;
    height: calc(100% - 20px);
}

.badge {
    font-size: 0.75rem;
}
</style>
@endpush