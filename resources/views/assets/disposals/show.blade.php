@extends('layouts.main')

@section('title', 'Disposal Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Disposals', 'url' => route('assets.disposals.index'), 'icon' => 'bx bx-trash'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">Disposal Details</h5>
                <p class="text-muted mb-0">{{ $disposal->disposal_number }}</p>
            </div>
            <div class="d-flex gap-2">
                @if(in_array($disposal->status, ['draft', 'rejected']) && $canSubmit)
                    <a href="{{ route('assets.disposals.edit', \Vinkla\Hashids\Facades\Hashids::encode($disposal->id)) }}" 
                       class="btn btn-info">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <form action="{{ route('assets.disposals.submit', \Vinkla\Hashids\Facades\Hashids::encode($disposal->id)) }}" 
                          method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="bx bx-send me-1"></i>Submit for Approval
                        </button>
                    </form>
                @endif
                @if($disposal->status == 'pending_approval' && $canApprove && $currentLevel)
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="bx bx-check me-1"></i>Approve
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bx bx-x me-1"></i>Reject
                    </button>
                @endif
                @if($disposal->status == 'approved' && !$disposal->gl_posted)
                    <form action="{{ route('assets.disposals.post-gl', \Vinkla\Hashids\Facades\Hashids::encode($disposal->id)) }}" 
                          method="POST" class="d-inline" id="postGlForm">
                        @csrf
                        <button type="submit" class="btn btn-primary" id="postGlBtn">
                            <i class="bx bx-book me-1"></i>Post to GL
                        </button>
                    </form>
                @endif
                <a href="{{ route('assets.disposals.index') }}" class="btn btn-secondary">
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
            <!-- Main Information -->
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Disposal Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Disposal Number:</strong><br>
                                <span class="badge bg-light text-dark">{{ $disposal->disposal_number }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Disposal Type:</strong><br>
                                @php
                                    $types = [
                                        'sale' => 'Sale',
                                        'scrap' => 'Scrap',
                                        'write_off' => 'Write-off',
                                        'donation' => 'Donation',
                                        'loss' => 'Loss/Theft'
                                    ];
                                @endphp
                                <span class="badge bg-info">{{ $types[$disposal->disposal_type] ?? $disposal->disposal_type }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Proposed Date:</strong><br>
                                {{ $disposal->proposed_disposal_date->format('d M Y') }}
                            </div>
                            <div class="col-md-6">
                                <strong>Actual Date:</strong><br>
                                {{ $disposal->actual_disposal_date ? $disposal->actual_disposal_date->format('d M Y') : '-' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                @php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'pending_approval' => 'warning',
                                        'approved' => 'info',
                                        'rejected' => 'danger',
                                        'completed' => 'success',
                                        'cancelled' => 'dark'
                                    ];
                                    $color = $statusColors[$disposal->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">
                                    {{ ucfirst(str_replace('_', ' ', $disposal->status)) }}
                                </span>
                                @if($disposal->status === 'pending_approval' && $currentLevel)
                                    <br><small class="text-muted">
                                        <i class="bx bx-user me-1"></i>
                                        Level {{ $disposal->current_approval_level }} - {{ $currentLevel->level_name }}
                                    </small>
                                @endif
                            </div>
                            @if($disposal->reasonCode)
                            <div class="col-md-6">
                                <strong>Reason Code:</strong><br>
                                {{ $disposal->reasonCode->code }} - {{ $disposal->reasonCode->name }}
                            </div>
                            @endif
                            <div class="col-md-12">
                                <strong>Disposal Reason:</strong><br>
                                {{ $disposal->disposal_reason }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Financial Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <td width="50%"><strong>Asset Cost:</strong></td>
                                <td class="text-end">{{ number_format($disposal->asset_cost ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Accumulated Depreciation:</strong></td>
                                <td class="text-end">{{ number_format($disposal->accumulated_depreciation ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Accumulated Impairment:</strong></td>
                                <td class="text-end">{{ number_format($disposal->accumulated_impairment ?? 0, 2) }}</td>
                            </tr>
                            <tr class="table-primary">
                                <td><strong>Net Book Value (NBV):</strong></td>
                                <td class="text-end"><strong>{{ number_format($disposal->net_book_value ?? 0, 2) }}</strong></td>
                            </tr>
                            @if($disposal->disposal_proceeds > 0)
                            <tr>
                                <td><strong>Disposal Proceeds:</strong></td>
                                <td class="text-end">{{ number_format($disposal->disposal_proceeds, 2) }}</td>
                            </tr>
                            @endif
                            @if($disposal->amount_paid > 0)
                            <tr>
                                <td><strong>Amount Paid:</strong></td>
                                <td class="text-end text-success">{{ number_format($disposal->amount_paid, 2) }}</td>
                            </tr>
                            @php
                                $netProceeds = $disposal->disposal_proceeds - ($disposal->vat_amount ?? 0);
                                $remainingReceivable = $netProceeds - $disposal->amount_paid;
                            @endphp
                            @if($remainingReceivable > 0)
                            <tr class="table-warning">
                                <td><strong>Remaining Receivable:</strong></td>
                                <td class="text-end"><strong class="text-warning">{{ number_format($remainingReceivable, 2) }}</strong></td>
                            </tr>
                            @endif
                            @endif
                            @if($disposal->fair_value > 0)
                            <tr>
                                <td><strong>Fair Value:</strong></td>
                                <td class="text-end">{{ number_format($disposal->fair_value, 2) }}</td>
                            </tr>
                            @endif
                            @if($disposal->vat_amount > 0)
                            <tr>
                                <td><strong>VAT Amount:</strong></td>
                                <td class="text-end">{{ number_format($disposal->vat_amount, 2) }}</td>
                            </tr>
                            @endif
                            @if($disposal->withholding_tax > 0)
                            <tr>
                                <td><strong>Withholding Tax:</strong></td>
                                <td class="text-end">{{ number_format($disposal->withholding_tax, 2) }}</td>
                            </tr>
                            @endif
                            <tr class="table-{{ $disposal->gain_loss >= 0 ? 'success' : 'danger' }}">
                                <td><strong>Gain / Loss on Disposal:</strong></td>
                                <td class="text-end">
                                    <strong>
                                        @if($disposal->gain_loss > 0)
                                            <span class="text-success">+{{ number_format($disposal->gain_loss, 2) }}</span>
                                        @elseif($disposal->gain_loss < 0)
                                            <span class="text-danger">{{ number_format($disposal->gain_loss, 2) }}</span>
                                        @else
                                            {{ number_format($disposal->gain_loss, 2) }}
                                        @endif
                                    </strong>
                                </td>
                            </tr>
                        </table>
                        @php
                            $netProceeds = $disposal->disposal_proceeds - ($disposal->vat_amount ?? 0);
                            $remainingReceivable = $netProceeds - ($disposal->amount_paid ?? 0);
                        @endphp
                        @if($disposal->disposal_type == 'sale' && $disposal->disposal_proceeds > 0 && $remainingReceivable > 0 && $disposal->gl_posted)
                        <div class="mt-3">
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#recordReceivableModal">
                                <i class="bx bx-money me-1"></i>Repay Remaining Receivable ({{ number_format($remainingReceivable, 2) }})
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                @if($disposal->buyer_name || $disposal->invoice_number)
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-user me-2"></i>Buyer/Recipient Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @if($disposal->buyer_name)
                            <div class="col-md-6">
                                <strong>Name:</strong><br>
                                {{ $disposal->buyer_name }}
                            </div>
                            @endif
                            @if($disposal->buyer_contact)
                            <div class="col-md-6">
                                <strong>Contact:</strong><br>
                                {{ $disposal->buyer_contact }}
                            </div>
                            @endif
                            @if($disposal->buyer_address)
                            <div class="col-md-12">
                                <strong>Address:</strong><br>
                                {{ $disposal->buyer_address }}
                            </div>
                            @endif
                            @if($disposal->invoice_number)
                            <div class="col-md-6">
                                <strong>Invoice Number:</strong><br>
                                {{ $disposal->invoice_number }}
                            </div>
                            @endif
                            @if($disposal->receipt_number)
                            <div class="col-md-6">
                                <strong>Receipt Number:</strong><br>
                                {{ $disposal->receipt_number }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                @if($disposal->insurance_recovery_amount > 0)
                <div class="card mb-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bx bx-shield me-2"></i>Insurance Recovery</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Recovery Amount:</strong><br>
                                {{ number_format($disposal->insurance_recovery_amount, 2) }}
                            </div>
                            @if($disposal->insurance_claim_number)
                            <div class="col-md-6">
                                <strong>Claim Number:</strong><br>
                                {{ $disposal->insurance_claim_number }}
                            </div>
                            @endif
                            @if($disposal->insurance_recovery_date)
                            <div class="col-md-6">
                                <strong>Recovery Date:</strong><br>
                                {{ $disposal->insurance_recovery_date->format('d M Y') }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                @if($disposal->journal)
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-book me-2"></i>General Ledger Entry</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Journal Reference:</strong><br>
                                {{ $disposal->journal->reference ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Posted Date:</strong><br>
                                {{ $disposal->gl_posted_at ? $disposal->gl_posted_at->format('d M Y H:i') : '-' }}
                            </div>
                            @if($disposal->journal->items && $disposal->journal->items->count() > 0)
                            <div class="col-12">
                                <strong>Journal Entries:</strong>
                                <table class="table table-sm table-bordered mt-2">
                                    <thead>
                                        <tr>
                                            <th>Account</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($disposal->journal->items as $item)
                                        <tr>
                                            <td>{{ $item->chartAccount->account_code ?? '' }} - {{ $item->chartAccount->account_name ?? '' }}</td>
                                            <td class="text-end">
                                                @if($item->nature == 'debit')
                                                    {{ number_format($item->amount, 2) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($item->nature == 'credit')
                                                    {{ number_format($item->amount, 2) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- GL Transactions Section -->
                @if(isset($glTransactions) && $glTransactions->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-transfer me-2"></i>General Ledger Transactions</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Account</th>
                                        <th>Description</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalDebit = 0;
                                        $totalCredit = 0;
                                    @endphp
                                    @foreach($glTransactions as $transaction)
                                    @php
                                        if ($transaction->nature == 'debit') {
                                            $totalDebit += $transaction->amount;
                                        } else {
                                            $totalCredit += $transaction->amount;
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $transaction->date ? \Carbon\Carbon::parse($transaction->date)->format('d M Y') : '-' }}</td>
                                        <td>
                                            <small class="text-muted">{{ $transaction->chartAccount->account_code ?? 'N/A' }}</small><br>
                                            <strong>{{ $transaction->chartAccount->account_name ?? 'N/A' }}</strong>
                                        </td>
                                        <td>
                                            <small>{{ $transaction->description ?? '-' }}</small>
                                            @if($transaction->transaction_type == 'receipt')
                                                <br><span class="badge bg-info">Receipt Payment</span>
                                            @elseif($transaction->transaction_type == 'journal')
                                                <br><span class="badge bg-success">Disposal Entry</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($transaction->nature == 'debit')
                                                <strong class="text-danger">{{ number_format($transaction->amount, 2) }}</strong>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($transaction->nature == 'credit')
                                                <strong class="text-success">{{ number_format($transaction->amount, 2) }}</strong>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="text-end text-danger">{{ number_format($totalDebit, 2) }}</th>
                                        <th class="text-end text-success">{{ number_format($totalCredit, 2) }}</th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end">Balance:</th>
                                        <th colspan="2" class="text-end">
                                            @php
                                                $balance = $totalDebit - $totalCredit;
                                            @endphp
                                            @if(abs($balance) < 0.01)
                                                <span class="badge bg-success">Balanced</span>
                                            @else
                                                <span class="badge bg-warning">{{ number_format($balance, 2) }}</span>
                                            @endif
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-package me-2"></i>Asset Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Code:</strong><br>{{ $disposal->asset->code }}</p>
                        <p><strong>Name:</strong><br>{{ $disposal->asset->name }}</p>
                        <p><strong>Category:</strong><br>{{ $disposal->asset->category->name ?? '-' }}</p>
                        <p><strong>Purchase Cost:</strong><br>{{ number_format($disposal->asset->purchase_cost ?? 0, 2) }}</p>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-time me-2"></i>Approval Workflow</h6>
                    </div>
                    <div class="card-body">
                        @if($disposal->submitted_by)
                            <p><strong>Submitted By:</strong><br>
                                {{ $disposal->submittedBy->name ?? 'N/A' }}
                                <small class="text-muted">({{ $disposal->submitted_at ? $disposal->submitted_at->format('M d, Y H:i') : 'N/A' }})</small>
                            </p>
                        @endif
                        @if($disposal->status === 'pending_approval' && $currentLevel)
                            <p><strong>Current Level:</strong><br>
                                <span class="badge bg-warning">Level {{ $disposal->current_approval_level }} - {{ $currentLevel->level_name }}</span>
                            </p>
                            @if($currentApprovers->count() > 0)
                                <p><strong>Current Approvers:</strong><br>
                                    {{ $currentApprovers->pluck('name')->join(', ') }}
                                </p>
                            @endif
                        @endif
                        @if($disposal->approved_by)
                            <p><strong>Approved By:</strong><br>
                                {{ $disposal->approvedBy->name ?? 'N/A' }}
                                <small class="text-muted">({{ $disposal->approved_at ? $disposal->approved_at->format('M d, Y H:i') : 'N/A' }})</small>
                            </p>
                        @endif
                        @if($disposal->rejected_by)
                            <p><strong>Rejected By:</strong><br>
                                {{ $disposal->rejectedBy->name ?? 'N/A' }}
                                <small class="text-muted">({{ $disposal->rejected_at ? $disposal->rejected_at->format('M d, Y H:i') : 'N/A' }})</small>
                                @if($disposal->rejection_reason)
                                    <br><small class="text-danger"><strong>Reason:</strong> {{ $disposal->rejection_reason }}</small>
                                @endif
                            </p>
                        @endif
                    </div>
                </div>

                @if($approvalHistory && $approvalHistory->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-history me-2"></i>Approval History</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($approvalHistory as $history)
                            <div class="timeline-item mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        @if($history->action === 'submitted')
                                            <i class="bx bx-send text-info fs-4"></i>
                                        @elseif($history->action === 'approved')
                                            <i class="bx bx-check-circle text-success fs-4"></i>
                                        @elseif($history->action === 'rejected')
                                            <i class="bx bx-x-circle text-danger fs-4"></i>
                                        @else
                                            <i class="bx bx-info-circle text-secondary fs-4"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">
                                            {{ ucfirst($history->action) }} at {{ $history->approvalLevel->level_name ?? 'N/A' }}
                                        </h6>
                                        <p class="mb-1 text-muted small">
                                            By: {{ $history->approver->name ?? 'System' }}
                                            <span class="ms-2">{{ $history->created_at->format('M d, Y H:i') }}</span>
                                        </p>
                                        @if($history->comments)
                                            <p class="mb-0 small">{{ $history->comments }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                @if($disposal->attachments && count($disposal->attachments) > 0)
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach($disposal->attachments as $attachment)
                                <li class="mb-2">
                                    <a href="{{ Storage::url($attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="bx bx-file me-1"></i>{{ basename($attachment) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        @if($disposal->valuation_report_path)
                        <hr>
                        <a href="{{ Storage::url($disposal->valuation_report_path) }}" target="_blank" class="btn btn-sm btn-outline-info w-100">
                            <i class="bx bx-file me-1"></i>Valuation Report
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                @if($disposal->notes)
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-note me-2"></i>Notes</h6>
                    </div>
                    <div class="card-body">
                        <p>{{ $disposal->notes }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
@if($disposal->status == 'pending_approval')
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('assets.disposals.approve', \Vinkla\Hashids\Facades\Hashids::encode($disposal->id)) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Disposal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Approval Notes (Optional)</label>
                        <textarea name="approval_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('assets.disposals.reject', \Vinkla\Hashids\Facades\Hashids::encode($disposal->id)) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Disposal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Record Receivable Modal -->
@php
    $netProceeds = $disposal->disposal_proceeds - ($disposal->vat_amount ?? 0);
    $remainingReceivable = $netProceeds - ($disposal->amount_paid ?? 0);
@endphp
@if($disposal->disposal_type == 'sale' && $disposal->disposal_proceeds > 0 && $remainingReceivable > 0 && $disposal->gl_posted)
<div class="modal fade" id="recordReceivableModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('assets.disposals.record-receivable', \Vinkla\Hashids\Facades\Hashids::encode($disposal->id)) }}" method="POST" id="recordReceivableForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Record Remaining Receivable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Receivable Amount:</strong> {{ number_format($remainingReceivable, 2) }}
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Account <span class="text-danger">*</span></label>
                            <select name="bank_account_id" class="form-select" required>
                                <option value="">-- Select Bank Account --</option>
                                @foreach(\App\Models\BankAccount::whereHas('chartAccount.accountClassGroup', function($q) {
                                    $q->where('company_id', auth()->user()->company_id);
                                })->with('chartAccount')->orderBy('name')->get() as $bankAccount)
                                    <option value="{{ $bankAccount->id }}">
                                        {{ $bankAccount->name }} 
                                        @if($bankAccount->account_number) ({{ $bankAccount->account_number }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $remainingReceivable }}" value="{{ $remainingReceivable }}" required>
                            <div class="form-text">Maximum receivable: {{ number_format($remainingReceivable, 2) }}</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2">Receipt for remaining balance on disposal {{ $disposal->disposal_number }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bx bx-money me-1"></i>Record Receivable
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Handle Post to GL form submission with SweetAlert
    $('#postGlForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const disposalNumber = '{{ $disposal->disposal_number }}';
        const disposalType = '{{ ucfirst(str_replace("_", " ", $disposal->disposal_type)) }}';
        const nbv = '{{ number_format($disposal->net_book_value ?? 0, 2) }}';
        const proceeds = '{{ number_format($disposal->disposal_proceeds ?? $disposal->fair_value ?? 0, 2) }}';
        const gainLoss = '{{ number_format($disposal->gain_loss ?? 0, 2) }}';
        
        Swal.fire({
            title: 'Post to General Ledger?',
            html: `
                <div class="text-start">
                    <p>Are you sure you want to post this disposal to the General Ledger?</p>
                    <div class="mt-3">
                        <strong>Disposal Number:</strong> ${disposalNumber}<br>
                        <strong>Disposal Type:</strong> ${disposalType}<br>
                        <strong>Net Book Value:</strong> ${nbv}<br>
                        <strong>Disposal Proceeds:</strong> ${proceeds}<br>
                        <strong>Gain/Loss:</strong> ${gainLoss}
                    </div>
                    <div class="alert alert-warning mt-3 mb-0">
                        <small><i class="bx bx-info-circle"></i> This action will create journal entries, update the asset status to "Disposed", and cannot be easily reversed.</small>
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Post to GL',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            allowOutsideClick: false,
            allowEscapeKey: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                const btn = $('#postGlBtn');
                const originalHtml = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Posting...');
                
                // Submit the form
                form.submit();
            }
        });
    });
});
</script>
@endpush

@endsection

