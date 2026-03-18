@extends('layouts.main')

@section('title', 'Movement Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-archive'],
            ['label' => 'Movements', 'url' => route('assets.movements.index'), 'icon' => 'bx bx-transfer-alt'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="card border-top border-0 border-4 border-primary">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-1 text-primary">Movement Voucher</h5>
                        <h4 class="mb-0">{{ $movement->movement_voucher }}</h4>
                    </div>
                    <span class="badge bg-{{ $movement->status === 'completed' ? 'success' : ($movement->status === 'approved' ? 'info' : ($movement->status === 'rejected' ? 'danger' : 'secondary')) }} px-3 py-2 fs-6">
                        {{ strtoupper($movement->status) }}
                    </span>
                </div>
                <hr>

                <div class="row g-4">
                    <!-- Asset Information -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-light">
                                <strong><i class="bx bx-cube me-1 text-primary"></i> Asset Information</strong>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-3">
                                    <i class="bx bx-cube fs-3 text-primary me-3"></i>
                                    <div>
                                        <div class="fw-semibold fs-5">{{ optional($movement->asset)->name }}</div>
                                        <small class="text-muted">Code: {{ optional($movement->asset)->code }}</small>
                                    </div>
                                </div>
                                @if($movement->reason)
                                <div class="mt-3 pt-3 border-top">
                                    <small class="text-muted d-block mb-1">Reason</small>
                                    <div class="fw-semibold">{{ $movement->reason }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-light">
                                <strong><i class="bx bx-time me-1 text-primary"></i> Timeline</strong>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <div class="timeline-item {{ $movement->initiated_at ? 'completed' : '' }}">
                                        <div class="timeline-marker bg-primary"></div>
                                        <div class="timeline-content">
                                            <div class="fw-semibold">Initiated</div>
                                            <small class="text-muted">{{ optional($movement->initiated_at)->format('d M Y, H:i') ?? 'Pending' }}</small>
                                            @if($movement->initiated_by)
                                            <div><small class="text-muted">By: {{ optional(\App\Models\User::find($movement->initiated_by))->name }}</small></div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="timeline-item {{ $movement->reviewed_at ? 'completed' : '' }}">
                                        <div class="timeline-marker bg-info"></div>
                                        <div class="timeline-content">
                                            <div class="fw-semibold">Reviewed</div>
                                            <small class="text-muted">{{ optional($movement->reviewed_at)->format('d M Y, H:i') ?? 'Pending' }}</small>
                                            @if($movement->reviewed_by)
                                            <div><small class="text-muted">By: {{ optional(\App\Models\User::find($movement->reviewed_by))->name }}</small></div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="timeline-item {{ $movement->approved_at ? 'completed' : '' }}">
                                        <div class="timeline-marker bg-success"></div>
                                        <div class="timeline-content">
                                            <div class="fw-semibold">Approved</div>
                                            <small class="text-muted">{{ optional($movement->approved_at)->format('d M Y, H:i') ?? 'Pending' }}</small>
                                            @if($movement->approved_by)
                                            <div><small class="text-muted">By: {{ optional(\App\Models\User::find($movement->approved_by))->name }}</small></div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="timeline-item {{ $movement->completed_at ? 'completed' : '' }}">
                                        <div class="timeline-marker bg-warning"></div>
                                        <div class="timeline-content">
                                            <div class="fw-semibold">Completed</div>
                                            <small class="text-muted">{{ optional($movement->completed_at)->format('d M Y, H:i') ?? 'Pending' }}</small>
                                            @if($movement->completed_by)
                                            <div><small class="text-muted">By: {{ optional(\App\Models\User::find($movement->completed_by))->name }}</small></div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- From Location -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100 border-start border-4 border-danger">
                            <div class="card-header bg-light">
                                <strong><i class="bx bx-map me-1 text-danger"></i> From Location</strong>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Branch</small>
                                    <div class="fw-semibold">{{ optional($movement->fromBranch)->name ?? 'N/A' }}</div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Department</small>
                                    <div class="fw-semibold">{{ optional($movement->fromDepartment)->name ?? 'N/A' }}</div>
                                </div>
                                <div>
                                    <small class="text-muted d-block mb-1">Custodian</small>
                                    <div class="fw-semibold">{{ optional($movement->fromUser)->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- To Location -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100 border-start border-4 border-success">
                            <div class="card-header bg-light">
                                <strong><i class="bx bx-map me-1 text-success"></i> To Location</strong>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Branch</small>
                                    <div class="fw-semibold">{{ optional($movement->toBranch)->name ?? 'No change' }}</div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Department</small>
                                    <div class="fw-semibold">{{ optional($movement->toDepartment)->name ?? 'No change' }}</div>
                                </div>
                                <div>
                                    <small class="text-muted d-block mb-1">Custodian</small>
                                    <div class="fw-semibold">{{ optional($movement->toUser)->name ?? 'No change' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- GL Posting Status -->
                    @if($movement->gl_post)
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <strong><i class="bx bx-book me-1 text-primary"></i> GL Posting Status</strong>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <div class="fw-semibold">General Ledger Reclassification</div>
                                        <small class="text-muted">This movement will post a journal entry to reclassify the asset's NBV.</small>
                                    </div>
                                    <span class="badge bg-{{ $movement->gl_posted ? 'success' : 'warning' }} px-3 py-2">
                                        {{ $movement->gl_posted ? 'Posted' : 'Pending' }}
                                    </span>
                                </div>
                                @if($movement->gl_posted_at)
                                <div class="mb-3">
                                    <small class="text-muted">Posted on: {{ optional($movement->gl_posted_at)->format('d M Y, H:i') }}</small>
                                </div>
                                @endif
                                
                                @if($movement->journal)
                                <div class="mt-3 pt-3 border-top">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <strong>Journal Entry</strong>
                                        <a href="{{ route('accounting.journals.show', $movement->journal->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-show me-1"></i>View Journal
                                        </a>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Reference</small>
                                            <div class="fw-semibold">{{ $movement->journal->reference }}</div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Date</small>
                                            <div class="fw-semibold">{{ $movement->journal->date->format('d M Y') }}</div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Total Amount</small>
                                            <div class="fw-semibold">{{ number_format($movement->journal->total, 2) }}</div>
                                        </div>
                                    </div>
                                    @if($movement->journal->items && $movement->journal->items->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Account</th>
                                                    <th class="text-end">Debit</th>
                                                    <th class="text-end">Credit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($movement->journal->items as $item)
                                                <tr>
                                                    <td>{{ optional($item->chartAccount)->account_name ?? 'N/A' }}</td>
                                                    <td class="text-end">{{ $item->nature === 'debit' ? number_format($item->amount, 2) : '—' }}</td>
                                                    <td class="text-end">{{ $item->nature === 'credit' ? number_format($item->amount, 2) : '—' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="mt-4 d-flex gap-2">
                    @if($movement->status === 'pending_review')
                        @can('approve asset movements')
                        <button type="button" class="btn btn-info" onclick="confirmApprove()">
                            <i class="bx bx-check me-1"></i>Approve
                        </button>
                        @endcan
                        @can('reject asset movements')
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bx bx-x me-1"></i>Reject
                        </button>
                        @endcan
                    @endif
                    @if($movement->status === 'approved')
                        @can('complete asset movements')
                        <button type="button" class="btn btn-success" onclick="confirmComplete()">
                            <i class="bx bx-check-double me-1"></i>Complete Movement
                        </button>
                        @endcan
                    @endif
                    <a href="{{ route('assets.movements.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
@if($movement->status === 'pending_review')
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('assets.movements.reject', $movement->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Movement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Please provide a reason for rejecting this movement..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Movement</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
}
.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -25px;
    top: 20px;
    width: 2px;
    height: calc(100% - 10px);
    background: #e0e0e0;
}
.timeline-item.completed:not(:last-child)::before {
    background: #28a745;
}
.timeline-marker {
    position: absolute;
    left: -30px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px currentColor;
}
.timeline-content {
    padding-left: 10px;
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function confirmApprove() {
    Swal.fire({
        title: 'Approve Movement?',
        html: '<div class="text-start">' +
              '<p>Are you sure you want to approve this movement?</p>' +
              '<div class="mt-3">' +
              '<strong>Movement:</strong> {{ $movement->movement_voucher }}<br>' +
              '<strong>Asset:</strong> {{ optional($movement->asset)->name ?? 'N/A' }}<br>' +
              @if($movement->gl_post)
              '<div class="alert alert-warning mt-2 mb-0 p-2"><small><i class="bx bx-info-circle me-1"></i>GL posting is enabled. A journal entry will be created when this movement is completed.</small></div>' +
              @endif
              '</div>' +
              '</div>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0dcaf0',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bx bx-check me-1"></i>Yes, Approve',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Create and submit the form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('assets.movements.approve', $movement->id) }}';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function confirmComplete() {
    Swal.fire({
        title: 'Complete Movement?',
        html: '<div class="text-start">' +
              '<p>Are you sure you want to complete this movement?</p>' +
              '<div class="mt-3">' +
              '<strong>Movement:</strong> {{ $movement->movement_voucher }}<br>' +
              '<strong>Asset:</strong> {{ optional($movement->asset)->name ?? 'N/A' }}<br>' +
              '<strong>From:</strong> ' +
              '{{ optional($movement->fromDepartment)->name ?? 'N/A' }}' +
              @if($movement->fromBranch)
              ' ({{ $movement->fromBranch->name }})' +
              @endif
              '<br>' +
              '<strong>To:</strong> ' +
              '{{ optional($movement->toDepartment)->name ?? 'No change' }}' +
              @if($movement->toBranch)
              ' ({{ $movement->toBranch->name }})' +
              @endif
              '<br>' +
              @if($movement->gl_post)
              '<div class="alert alert-info mt-2 mb-0 p-2"><small><i class="bx bx-info-circle me-1"></i>GL posting is enabled. This will create a journal entry to reclassify the asset\'s Net Book Value.</small></div>' +
              @endif
              '</div>' +
              '</div>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bx bx-check-double me-1"></i>Yes, Complete',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Create and submit the form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('assets.movements.complete', $movement->id) }}';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush
@endsection
