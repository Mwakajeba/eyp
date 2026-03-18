@extends('layouts.main')

@section('title', 'Maintenance Request Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Maintenance', 'url' => route('assets.maintenance.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Requests', 'url' => route('assets.maintenance.requests.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">Maintenance Request: {{ $maintenanceRequest->request_number }}</h5>
                <p class="text-muted mb-0">View request details and status</p>
            </div>
            <div class="d-flex gap-2">
                @if($maintenanceRequest->status === 'approved' && auth()->user()->can('create work orders'))
                <a href="{{ route('assets.maintenance.work-orders.create', ['request_id' => $encodedId]) }}" class="btn btn-primary">
                    <i class="bx bx-wrench me-1"></i>Create Work Order
                </a>
                @endif
                @if($maintenanceRequest->status === 'pending' && auth()->user()->can('edit maintenance requests'))
                <a href="{{ route('assets.maintenance.requests.edit', $encodedId) }}" class="btn btn-outline-primary">
                    <i class="bx bx-edit me-1"></i>Edit
                </a>
                @endif
                <a href="{{ route('assets.maintenance.requests.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">Request Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Request Number</label>
                                <div class="fw-bold">{{ $maintenanceRequest->request_number }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Status</label>
                                <div>
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            'converted_to_wo' => 'info',
                                            'cancelled' => 'secondary'
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$maintenanceRequest->status] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $maintenanceRequest->status)) }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Asset</label>
                                <div class="fw-bold">{{ $maintenanceRequest->asset->name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $maintenanceRequest->asset->code ?? '' }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Maintenance Type</label>
                                <div class="fw-bold">{{ $maintenanceRequest->maintenanceType->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Trigger Type</label>
                                <div>{{ ucfirst(str_replace('_', ' ', $maintenanceRequest->trigger_type)) }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Priority</label>
                                <div>
                                    @php
                                        $priorityColors = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
                                    @endphp
                                    <span class="badge bg-{{ $priorityColors[$maintenanceRequest->priority] ?? 'secondary' }}">
                                        {{ ucfirst($maintenanceRequest->priority) }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Requested Date</label>
                                <div>{{ format_date($maintenanceRequest->requested_date, 'M d, Y') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Preferred Start Date</label>
                                <div>{{ $maintenanceRequest->preferred_start_date ? format_date($maintenanceRequest->preferred_start_date, 'M d, Y') : 'Not specified' }}</div>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small">Description</label>
                                <div>{{ $maintenanceRequest->description }}</div>
                            </div>
                            @if($maintenanceRequest->issue_details)
                            <div class="col-12">
                                <label class="text-muted small">Issue Details</label>
                                <div>{{ $maintenanceRequest->issue_details }}</div>
                            </div>
                            @endif
                            @if($maintenanceRequest->notes)
                            <div class="col-12">
                                <label class="text-muted small">Notes</label>
                                <div>{{ $maintenanceRequest->notes }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($maintenanceRequest->supervisor_approved_at)
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">Supervisor Approval</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Approved By</label>
                                <div>{{ $maintenanceRequest->supervisorApprovedBy->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Approved At</label>
                                <div>{{ $maintenanceRequest->supervisor_approved_at->format('M d, Y H:i') }}</div>
                            </div>
                            @if($maintenanceRequest->supervisor_notes)
                            <div class="col-12">
                                <label class="text-muted small">Supervisor Notes</label>
                                <div>{{ $maintenanceRequest->supervisor_notes }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">Request Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small">Requested By</label>
                            <div class="fw-bold">{{ $maintenanceRequest->requestedBy->name ?? 'N/A' }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Custodian</label>
                            <div>{{ $maintenanceRequest->custodian->name ?? 'N/A' }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Department</label>
                            <div>{{ $maintenanceRequest->department->name ?? 'N/A' }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Created</label>
                            <div>{{ $maintenanceRequest->created_at->format('M d, Y H:i') }}</div>
                        </div>
                        @if($maintenanceRequest->workOrder)
                        <div class="mb-3">
                            <label class="text-muted small">Work Order</label>
                            <div>
                                <a href="{{ route('assets.maintenance.work-orders.show', \Vinkla\Hashids\Facades\Hashids::encode($maintenanceRequest->workOrder->id)) }}" class="text-primary">
                                    {{ $maintenanceRequest->workOrder->wo_number }}
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

