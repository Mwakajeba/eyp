@extends('layouts.main')

@section('title', 'Revaluation Batch Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Revaluations', 'url' => route('assets.revaluations.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Batch Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">Revaluation Batch Details</h5>
                <p class="text-muted mb-0">{{ $batch->batch_number }} - {{ $batch->revaluations->count() }} assets</p>
            </div>
            <div class="d-flex gap-2">
                @if($batch->status == 'draft')
                    <form action="{{ route('assets.revaluations.batch.submit', \Vinkla\Hashids\Facades\Hashids::encode($batch->id)) }}" 
                          method="POST" class="d-inline" id="submitBatchForm">
                        @csrf
                        <button type="submit" class="btn btn-warning" id="submitBatchBtn">
                            <i class="bx bx-send me-1"></i>Submit Batch for Approval
                        </button>
                    </form>
                @endif
                @if($batch->status == 'pending_approval' && (auth()->user()->hasRole('finance_manager') || auth()->user()->hasRole('accountant') || auth()->user()->hasRole('cfo') || auth()->user()->hasRole('director')))
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveBatchModal">
                        <i class="bx bx-check me-1"></i>Approve Batch
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectBatchModal">
                        <i class="bx bx-x me-1"></i>Reject Batch
                    </button>
                @endif
                <a href="{{ route('assets.revaluations.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="row">
            <!-- Batch Information -->
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Batch Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <strong>Batch Number:</strong><br>
                                <span class="badge bg-light text-dark">{{ $batch->batch_number }}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Revaluation Date:</strong><br>
                                {{ $batch->revaluation_date->format('d M Y') }}
                            </div>
                            <div class="col-md-3">
                                <strong>Status:</strong><br>
                                @php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'pending_approval' => 'warning',
                                        'approved' => 'info',
                                        'rejected' => 'danger',
                                        'partially_approved' => 'warning'
                                    ];
                                    $color = $statusColors[$batch->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">
                                    {{ ucfirst(str_replace('_', ' ', $batch->status)) }}
                                </span>
                            </div>
                            <div class="col-md-3">
                                <strong>Total Assets:</strong><br>
                                <span class="badge bg-info">{{ $batch->revaluations->count() }}</span>
                            </div>
                            <div class="col-md-12">
                                <strong>Reason:</strong><br>
                                {{ $batch->reason }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Total Fair Value</h6>
                                <h4 class="text-primary mb-0">{{ number_format($batch->total_fair_value, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Total Increase</h6>
                                <h4 class="text-success mb-0">+{{ number_format($batch->total_increase, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Total Decrease</h6>
                                <h4 class="text-danger mb-0">{{ number_format($batch->total_decrease, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Net Change</h6>
                                <h4 class="text-info mb-0">{{ number_format($batch->total_increase - $batch->total_decrease, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revaluations Table -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Revaluations in Batch</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Revaluation #</th>
                                        <th>Asset</th>
                                        <th>Category</th>
                                        <th class="text-end">Carrying Amount</th>
                                        <th class="text-end">Fair Value</th>
                                        <th class="text-end">Difference</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($batch->revaluations as $revaluation)
                                    <tr>
                                        <td>
                                            <a href="{{ route('assets.revaluations.show', \Vinkla\Hashids\Facades\Hashids::encode($revaluation->id)) }}" 
                                               class="text-primary">
                                                {{ $revaluation->revaluation_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <strong>{{ $revaluation->asset->code }}</strong><br>
                                            <small class="text-muted">{{ $revaluation->asset->name }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                {{ $revaluation->asset->category->name ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format($revaluation->carrying_amount_before ?? 0, 2) }}</td>
                                        <td class="text-end">{{ number_format($revaluation->fair_value ?? 0, 2) }}</td>
                                        <td class="text-end">
                                            @php
                                                $difference = ($revaluation->fair_value ?? 0) - ($revaluation->carrying_amount_before ?? 0);
                                            @endphp
                                            @if($difference >= 0)
                                                <span class="text-success">+{{ number_format($difference, 2) }}</span>
                                            @else
                                                <span class="text-danger">{{ number_format($difference, 2) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'draft' => 'secondary',
                                                    'pending_approval' => 'warning',
                                                    'approved' => 'info',
                                                    'rejected' => 'danger',
                                                    'posted' => 'success'
                                                ];
                                                $color = $statusColors[$revaluation->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">
                                                {{ ucfirst(str_replace('_', ' ', $revaluation->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('assets.revaluations.show', \Vinkla\Hashids\Facades\Hashids::encode($revaluation->id)) }}" 
                                               class="btn btn-sm btn-outline-primary">
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
            </div>
        </div>
    </div>
</div>

<!-- Approve Batch Modal -->
<div class="modal fade" id="approveBatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Approve Batch</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('assets.revaluations.batch.approve', \Vinkla\Hashids\Facades\Hashids::encode($batch->id)) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to approve this batch? This will approve all <strong>{{ $batch->revaluations->count() }}</strong> revaluations.</p>
                    <div class="mb-3">
                        <label class="form-label">Approval Notes (Optional)</label>
                        <textarea name="approval_notes" class="form-control" rows="3" placeholder="Add any notes about this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-check me-1"></i>Approve Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Batch Modal -->
<div class="modal fade" id="rejectBatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Reject Batch</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('assets.revaluations.batch.reject', \Vinkla\Hashids\Facades\Hashids::encode($batch->id)) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to reject this batch? This will reject all <strong>{{ $batch->revaluations->count() }}</strong> revaluations.</p>
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="approval_notes" class="form-control" rows="3" placeholder="Please provide a reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-x me-1"></i>Reject Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Submit batch form with confirmation
    $('#submitBatchForm').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = $('#submitBatchBtn');
        const originalText = btn.html();
        
        Swal.fire({
            title: 'Submit Batch for Approval?',
            text: 'This will submit all {{ $batch->revaluations->count() }} revaluations for approval. Continue?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Submit',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
@endsection

