@extends('layouts.main')

@section('title', 'Tyre Replacement Request - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Tyre Replacement Requests', 'url' => route('fleet.tyre-replacement-requests.index'), 'icon' => 'bx bx-error-circle'],
            ['label' => 'Request #' . $tyreReplacementRequest->id, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bx bx-error-circle me-2"></i>Replacement Request #{{ $tyreReplacementRequest->id }}</h6>
                <div>
                    @if($tyreReplacementRequest->status === 'pending')
                        <form action="{{ route('fleet.tyre-replacement-requests.approve', $tyreReplacementRequest) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm"><i class="bx bx-check me-1"></i>Approve</button>
                        </form>
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal"><i class="bx bx-x me-1"></i>Reject</button>
                    @endif
                    <a href="{{ route('fleet.tyre-replacement-requests.index') }}" class="btn btn-light btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Vehicle</label>
                        <p class="mb-0">{{ $tyreReplacementRequest->vehicle?->name ?? $tyreReplacementRequest->vehicle_id }} ({{ $tyreReplacementRequest->vehicle?->registration_number ?? '—' }})</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Position</label>
                        <p class="mb-0">{{ $tyreReplacementRequest->tyrePosition?->position_name ?? $tyreReplacementRequest->tyre_position_id }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Reason</label>
                        <p class="mb-0">{{ ucfirst(str_replace('_', ' ', $tyreReplacementRequest->reason)) }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Current tyre</label>
                        <p class="mb-0">{{ $tyreReplacementRequest->currentTyre?->tyre_serial ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Mileage at request</label>
                        <p class="mb-0">{{ $tyreReplacementRequest->mileage_at_request ? number_format($tyreReplacementRequest->mileage_at_request) . ' km' : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status</label>
                        <p class="mb-0"><span class="badge bg-{{ $tyreReplacementRequest->status === 'approved' ? 'success' : ($tyreReplacementRequest->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($tyreReplacementRequest->status) }}</span></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Requested by</label>
                        <p class="mb-0">{{ $tyreReplacementRequest->requestedBy?->name ?? '—' }} at {{ $tyreReplacementRequest->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                    @if($tyreReplacementRequest->approved_by)
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Approved/Rejected by</label>
                        <p class="mb-0">{{ $tyreReplacementRequest->approvedBy?->name ?? '—' }} at {{ $tyreReplacementRequest->approved_at?->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif
                    @if($tyreReplacementRequest->rejection_reason)
                    <div class="col-12">
                        <label class="form-label fw-bold">Rejection reason</label>
                        <p class="mb-0">{{ $tyreReplacementRequest->rejection_reason }}</p>
                    </div>
                    @endif
                    @if($tyreReplacementRequest->notes)
                    <div class="col-12">
                        <label class="form-label fw-bold">Notes</label>
                        <p class="mb-0">{{ $tyreReplacementRequest->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if($tyreReplacementRequest->currentInstallation || $tyreReplacementRequest->currentTyre)
        <div class="card border-info mt-3">
            <div class="card-header bg-info text-white py-2">
                <strong><i class="bx bx-info-circle me-1"></i>Current installation at this position (for comparison)</strong>
            </div>
            <div class="card-body py-3">
                <div class="row g-3 small">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tyre</label>
                        <p class="mb-0">{{ $tyreReplacementRequest->currentTyre?->tyre_serial ?? '—' }} {{ $tyreReplacementRequest->currentTyre?->brand ? ' — ' . $tyreReplacementRequest->currentTyre->brand : '' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Installed at</label>
                        <p class="mb-0">{{ $tyreReplacementRequest->currentInstallation?->installed_at?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Odometer at install</label>
                        <p class="mb-0">{{ $tyreReplacementRequest->currentInstallation?->odometer_at_install ? number_format($tyreReplacementRequest->currentInstallation->odometer_at_install) . ' km' : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Expected lifespan</label>
                        <p class="mb-0">{{ $tyreReplacementRequest->currentTyre?->expected_lifespan_km ? number_format($tyreReplacementRequest->currentTyre->expected_lifespan_km) . ' km' : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Installer</label>
                        <p class="mb-0">{{ $tyreReplacementRequest->currentInstallation?->installer_name ?? $tyreReplacementRequest->currentInstallation?->installer_type ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('fleet.tyre-replacement-requests.reject', $tyreReplacementRequest) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Reason (optional)</label>
                    <textarea name="rejection_reason" class="form-control" rows="3" maxlength="1000" placeholder="Reason for rejection"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
