@extends('layouts.main')

@section('title', 'Spare Part Replacement - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Spare Parts Replacement', 'url' => route('fleet.spare-part-replacements.index'), 'icon' => 'bx bx-refresh'],
            ['label' => 'Replacement #' . $replacement->id, 'url' => '#', 'icon' => 'bx bx-show']
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
            <div class="card-header bg-orange text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bx bx-refresh me-2"></i>Replacement #{{ $replacement->id }} — {{ $replacement->sparePartCategory?->name ?? $replacement->spare_part_category_id }}</h6>
                <div>
                    @if($replacement->status === 'pending')
                        <form action="{{ route('fleet.spare-part-replacements.approve', $replacement) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm"><i class="bx bx-check me-1"></i>Approve</button>
                        </form>
                        <form action="{{ route('fleet.spare-part-replacements.reject', $replacement) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm"><i class="bx bx-x me-1"></i>Reject</button>
                        </form>
                    @endif
                    <a href="{{ route('fleet.spare-part-replacements.index') }}" class="btn btn-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Vehicle</label>
                        <p class="mb-0">{{ $replacement->vehicle?->name ?? $replacement->vehicle_id }} ({{ $replacement->vehicle?->registration_number ?? '—' }})</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Part category</label>
                        <p class="mb-0">{{ $replacement->sparePartCategory?->name ?? $replacement->spare_part_category_id }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Replaced at</label>
                        <p class="mb-0">{{ $replacement->replaced_at?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Odometer at replacement</label>
                        <p class="mb-0">{{ $replacement->odometer_at_replacement ? number_format($replacement->odometer_at_replacement) . ' km' : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Cost</label>
                        <p class="mb-0">{{ $replacement->cost ? number_format($replacement->cost) : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status</label>
                        <p class="mb-0"><span class="badge bg-{{ $replacement->status === 'approved' ? 'success' : ($replacement->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($replacement->status) }}</span></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Recorded by</label>
                        <p class="mb-0">{{ $replacement->createdBy?->name ?? '—' }} at {{ $replacement->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                    @if($replacement->approved_by)
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Approved/Rejected by</label>
                        <p class="mb-0">{{ $replacement->approvedBy?->name ?? '—' }} at {{ $replacement->approved_at?->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif
                    @if($replacement->reason)
                    <div class="col-12">
                        <label class="form-label fw-bold">Reason / notes</label>
                        <p class="mb-0">{{ $replacement->reason }}</p>
                    </div>
                    @endif
                </div>
                @if(isset($lastReplacement) && $lastReplacement)
                <hr>
                <h6 class="text-info"><i class="bx bx-history me-1"></i>Previous replacement (for comparison)</h6>
                <div class="row g-2 small">
                    <div class="col-md-3"><span class="text-muted">Replaced at</span><br>{{ $lastReplacement->replaced_at?->format('d/m/Y') ?? '—' }}</div>
                    <div class="col-md-3"><span class="text-muted">Odometer</span><br>{{ $lastReplacement->odometer_at_replacement ? number_format($lastReplacement->odometer_at_replacement) . ' km' : '—' }}</div>
                    <div class="col-md-3"><span class="text-muted">Cost</span><br>{{ $lastReplacement->cost ? number_format($lastReplacement->cost) : '—' }}</div>
                    <div class="col-md-3"><span class="text-muted">Recorded by</span><br>{{ $lastReplacement->createdBy?->name ?? '—' }}</div>
                    @if($lastReplacement->reason)
                    <div class="col-12"><span class="text-muted">Reason</span><br>{{ $lastReplacement->reason }}</div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
