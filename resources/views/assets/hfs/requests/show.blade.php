@extends('layouts.main')

@section('title', 'HFS Request Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Held for Sale', 'url' => route('assets.hfs.requests.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">HFS Request Details</h5>
                <p class="text-muted mb-0">{{ $hfsRequest->request_no }}</p>
            </div>
            <div class="d-flex gap-2">
                @if(in_array($hfsRequest->status, ['draft', 'rejected']) && $canSubmit)
                    <a href="{{ route('assets.hfs.requests.edit', $encodedId) }}" class="btn btn-info">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <button type="button" class="btn btn-warning" onclick="submitForApproval()">
                        <i class="bx bx-send me-1"></i>Submit for Approval
                    </button>
                @endif
                @if($hfsRequest->status == 'approved' && !$hfsRequest->disposal)
                    <a href="{{ route('assets.hfs.valuations.create', $encodedId) }}" class="btn btn-primary">
                        <i class="bx bx-calculator me-1"></i>Record Valuation
                    </a>
                    <a href="{{ route('assets.hfs.disposals.create', $encodedId) }}" class="btn btn-success">
                        <i class="bx bx-money me-1"></i>Record Sale
                    </a>
                @endif
                @if($hfsRequest->status == 'approved' && $hfsRequest->isOverdue())
                    <span class="badge bg-danger">Overdue (>12 months)</span>
                @endif
                <a href="{{ route('assets.hfs.requests.index') }}" class="btn btn-secondary">
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

        <!-- Approval Actions Section -->
        @if($hfsRequest->status === 'in_review' && $canApprove && $currentLevel)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-check-circle me-2"></i>Approval Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">You are authorized to approve or reject this HFS request at the current level (<strong>{{ $currentLevel->level_name }}</strong>).</p>
                        
                        <div class="mb-3">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="bx bx-check-circle me-2"></i>Approve
                            </button>
                        </div>
                        
                        <div>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bx bx-x-circle me-2"></i>Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <!-- Main Information -->
            <div class="col-md-8">
                <!-- Request Information -->
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Request Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Request Number:</strong><br>
                                <span class="badge bg-light text-dark">{{ $hfsRequest->request_no }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                @php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'in_review' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'cancelled' => 'dark',
                                        'sold' => 'info'
                                    ];
                                    $color = $statusColors[$hfsRequest->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">
                                    {{ ucfirst(str_replace('_', ' ', $hfsRequest->status)) }}
                                </span>
                                @if($hfsRequest->status === 'in_review' && $currentLevel)
                                    <br><small class="text-muted">
                                        <i class="bx bx-user me-1"></i>
                                        Level {{ $hfsRequest->current_approval_level }} - {{ $currentLevel->level_name }}
                                    </small>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <strong>Intended Sale Date:</strong><br>
                                {{ $hfsRequest->intended_sale_date->format('d M Y') }}
                            </div>
                            <div class="col-md-6">
                                <strong>Expected Close Date:</strong><br>
                                {{ $hfsRequest->expected_close_date ? $hfsRequest->expected_close_date->format('d M Y') : '-' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Initiator:</strong><br>
                                {{ $hfsRequest->initiator->name ?? 'N/A' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Management Committed:</strong><br>
                                @if($hfsRequest->management_committed)
                                    <span class="badge bg-success">Yes</span>
                                    @if($hfsRequest->management_commitment_date)
                                        <small class="text-muted">({{ $hfsRequest->management_commitment_date->format('d M Y') }})</small>
                                    @endif
                                @else
                                    <span class="badge bg-danger">No</span>
                                @endif
                            </div>
                            @if($hfsRequest->exceeds_12_months)
                            <div class="col-12">
                                <div class="alert alert-warning mb-0">
                                    <strong>Extension Beyond 12 Months:</strong><br>
                                    {{ $hfsRequest->extension_justification }}
                                    @if($hfsRequest->extensionApprover)
                                        <br><small>Approved by: {{ $hfsRequest->extensionApprover->name }} on {{ $hfsRequest->extension_approved_at->format('d M Y') }}</small>
                                    @endif
                                </div>
                            </div>
                            @endif
                            @if($hfsRequest->is_disposal_group)
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    <strong>Disposal Group:</strong><br>
                                    {{ $hfsRequest->disposal_group_description }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Assets Information -->
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-building me-2"></i>Assets Classified as HFS</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th class="text-end">Carrying Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hfsRequest->hfsAssets as $hfsAsset)
                                    <tr>
                                        <td>{{ $hfsAsset->asset->code ?? 'N/A' }}</td>
                                        <td>{{ $hfsAsset->asset->name ?? 'N/A' }}</td>
                                        <td>{{ $hfsAsset->asset->category->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($hfsAsset->carrying_amount_at_reclass, 2) }}</td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'pending_reclass' => 'warning',
                                                    'classified' => 'success',
                                                    'sold' => 'info',
                                                    'cancelled' => 'dark'
                                                ];
                                                $color = $statusColors[$hfsAsset->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">
                                                {{ ucfirst(str_replace('_', ' ', $hfsAsset->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td colspan="3"><strong>Total Carrying Amount:</strong></td>
                                        <td class="text-end"><strong>{{ number_format($hfsRequest->total_carrying_amount, 2) }}</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Financial Summary</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <td width="50%"><strong>Total Carrying Amount at Classification:</strong></td>
                                <td class="text-end">{{ number_format($hfsRequest->total_carrying_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Current Total Carrying Amount:</strong></td>
                                <td class="text-end">{{ number_format($hfsRequest->current_total_carrying_amount, 2) }}</td>
                            </tr>
                            @if($hfsRequest->expected_fair_value > 0)
                            <tr>
                                <td><strong>Expected Fair Value:</strong></td>
                                <td class="text-end">{{ number_format($hfsRequest->expected_fair_value, 2) }}</td>
                            </tr>
                            @endif
                            @if($hfsRequest->expected_costs_to_sell > 0)
                            <tr>
                                <td><strong>Expected Costs to Sell:</strong></td>
                                <td class="text-end">{{ number_format($hfsRequest->expected_costs_to_sell, 2) }}</td>
                            </tr>
                            @endif
                            @php
                                $expectedFvLessCosts = $hfsRequest->expected_fair_value - $hfsRequest->expected_costs_to_sell;
                            @endphp
                            @if($expectedFvLessCosts > 0)
                            <tr class="table-info">
                                <td><strong>Expected FV Less Costs:</strong></td>
                                <td class="text-end"><strong>{{ number_format($expectedFvLessCosts, 2) }}</strong></td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- Valuations History -->
                @if($hfsRequest->valuations->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bx bx-line-chart me-2"></i>Valuation History</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Fair Value</th>
                                        <th class="text-end">Costs to Sell</th>
                                        <th class="text-end">FV Less Costs</th>
                                        <th class="text-end">Carrying Amount</th>
                                        <th class="text-end">Impairment</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hfsRequest->valuations as $valuation)
                                    <tr>
                                        <td>{{ $valuation->valuation_date->format('d M Y') }}</td>
                                        <td class="text-end">{{ number_format($valuation->fair_value, 2) }}</td>
                                        <td class="text-end">{{ number_format($valuation->costs_to_sell, 2) }}</td>
                                        <td class="text-end">{{ number_format($valuation->fv_less_costs, 2) }}</td>
                                        <td class="text-end">{{ number_format($valuation->carrying_amount, 2) }}</td>
                                        <td class="text-end">
                                            @if($valuation->impairment_amount > 0)
                                                <span class="text-{{ $valuation->is_reversal ? 'success' : 'danger' }}">
                                                    {{ $valuation->is_reversal ? '+' : '-' }}{{ number_format($valuation->impairment_amount, 2) }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($valuation->gl_posted)
                                                <span class="badge bg-success">Posted</span>
                                            @else
                                                <span class="badge bg-warning">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Disposal Information -->
                @if($hfsRequest->disposal)
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-money me-2"></i>Disposal Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <td width="50%"><strong>Disposal Date:</strong></td>
                                <td>{{ $hfsRequest->disposal->disposal_date->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Sale Proceeds:</strong></td>
                                <td class="text-end">{{ number_format($hfsRequest->disposal->sale_proceeds, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Costs Sold:</strong></td>
                                <td class="text-end">{{ number_format($hfsRequest->disposal->costs_sold, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Carrying Amount at Disposal:</strong></td>
                                <td class="text-end">{{ number_format($hfsRequest->disposal->carrying_amount_at_disposal, 2) }}</td>
                            </tr>
                            <tr class="table-{{ $hfsRequest->disposal->gain_loss_amount >= 0 ? 'success' : 'danger' }}">
                                <td><strong>Gain / Loss on Disposal:</strong></td>
                                <td class="text-end">
                                    <strong>{{ $hfsRequest->disposal->gain_loss_amount >= 0 ? '+' : '' }}{{ number_format($hfsRequest->disposal->gain_loss_amount, 2) }}</strong>
                                </td>
                            </tr>
                            @if($hfsRequest->disposal->buyer_name)
                            <tr>
                                <td><strong>Buyer:</strong></td>
                                <td>{{ $hfsRequest->disposal->buyer_name }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
                @endif

                <!-- General Ledger Transactions -->
                @if($hfsRequest->valuations->where('gl_posted', true)->count() > 0 || ($hfsRequest->disposal && $hfsRequest->disposal->gl_posted))
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-book me-2"></i>General Ledger Transactions</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Account</th>
                                        <th>Description</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hfsRequest->valuations->where('gl_posted', true) as $valuation)
                                        @if($valuation->impairmentJournal)
                                            @foreach($valuation->impairmentJournal->items as $item)
                                            <tr>
                                                <td>{{ $valuation->valuation_date->format('d M Y') }}</td>
                                                <td>{{ $item->chartAccount->account_name ?? 'N/A' }}</td>
                                                <td>{{ $item->description }}</td>
                                                <td class="text-end">{{ $item->nature == 'debit' ? number_format($item->amount, 2) : '-' }}</td>
                                                <td class="text-end">{{ $item->nature == 'credit' ? number_format($item->amount, 2) : '-' }}</td>
                                            </tr>
                                            @endforeach
                                        @endif
                                    @endforeach
                                    @if($hfsRequest->disposal && $hfsRequest->disposal->journal)
                                        @foreach($hfsRequest->disposal->journal->items as $item)
                                        <tr>
                                            <td>{{ $hfsRequest->disposal->disposal_date->format('d M Y') }}</td>
                                            <td>{{ $item->chartAccount->account_name ?? 'N/A' }}</td>
                                            <td>{{ $item->description }}</td>
                                            <td class="text-end">{{ $item->nature == 'debit' ? number_format($item->amount, 2) : '-' }}</td>
                                            <td class="text-end">{{ $item->nature == 'credit' ? number_format($item->amount, 2) : '-' }}</td>
                                        </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Approval Workflow -->
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-time me-2"></i>Approval Workflow</h6>
                    </div>
                    <div class="card-body">
                        @if($hfsRequest->submitted_by)
                            <p><strong>Submitted By:</strong><br>
                                {{ $hfsRequest->submittedBy->name ?? 'N/A' }}
                                <small class="text-muted">({{ $hfsRequest->submitted_at ? $hfsRequest->submitted_at->format('M d, Y H:i') : 'N/A' }})</small>
                            </p>
                        @endif
                        @if($hfsRequest->status === 'in_review' && $currentLevel)
                            <p><strong>Current Level:</strong><br>
                                <span class="badge bg-warning">Level {{ $hfsRequest->current_approval_level }} - {{ $currentLevel->level_name }}</span>
                            </p>
                            @if($currentApprovers->count() > 0)
                                <p><strong>Current Approvers:</strong><br>
                                    {{ $currentApprovers->pluck('name')->join(', ') }}
                                </p>
                            @endif
                        @endif
                        @if($hfsRequest->approved_at)
                            <p><strong>Approved At:</strong><br>
                                {{ $hfsRequest->approved_at->format('M d, Y H:i') }}
                            </p>
                        @endif
                        @if($hfsRequest->rejected_by)
                            <p><strong>Rejected By:</strong><br>
                                {{ $hfsRequest->rejectedBy->name ?? 'N/A' }}
                                <small class="text-muted">({{ $hfsRequest->rejected_at ? $hfsRequest->rejected_at->format('M d, Y H:i') : 'N/A' }})</small>
                                @if($hfsRequest->rejection_reason)
                                    <br><small class="text-danger"><strong>Reason:</strong> {{ $hfsRequest->rejection_reason }}</small>
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

                <!-- Approval Status (Legacy - keep for backward compatibility) -->
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-check-circle me-2"></i>Approval Status</h6>
                    </div>
                    <div class="card-body">
                        @if($hfsRequest->approvals->count() > 0)
                            <div class="timeline">
                                @foreach($hfsRequest->approvals as $approval)
                                <div class="timeline-item mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            @if($approval->status == 'approved')
                                                <i class="bx bx-check-circle text-success fs-4"></i>
                                            @elseif($approval->status == 'rejected')
                                                <i class="bx bx-x-circle text-danger fs-4"></i>
                                            @else
                                                <i class="bx bx-time-five text-warning fs-4"></i>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <strong>{{ ucfirst(str_replace('_', ' ', $approval->approval_level)) }}</strong><br>
                                            @if($approval->approver)
                                                <small>{{ $approval->approver->name }}</small><br>
                                            @endif
                                            @if($approval->approved_at)
                                                <small class="text-muted">{{ $approval->approved_at->format('d M Y H:i') }}</small>
                                            @endif
                                            @if($approval->comments)
                                                <div class="mt-1"><small>{{ $approval->comments }}</small></div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">No approvals yet</p>
                        @endif
                    </div>
                </div>

                <!-- Buyer Information -->
                @if($hfsRequest->buyer_name)
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-user me-2"></i>Buyer Information</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Name:</strong> {{ $hfsRequest->buyer_name }}</p>
                        @if($hfsRequest->buyer_contact)
                            <p class="mb-1"><strong>Contact:</strong> {{ $hfsRequest->buyer_contact }}</p>
                        @endif
                        @if($hfsRequest->buyer_address)
                            <p class="mb-0"><strong>Address:</strong> {{ $hfsRequest->buyer_address }}</p>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Sale Plan -->
                <div class="card mb-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bx bx-calendar me-2"></i>Sale Plan</h6>
                    </div>
                    <div class="card-body">
                        @if($hfsRequest->marketing_actions)
                            <p class="mb-2"><strong>Marketing Actions:</strong></p>
                            <p class="mb-3">{{ $hfsRequest->marketing_actions }}</p>
                        @endif
                        @if($hfsRequest->sale_price_range)
                            <p class="mb-1"><strong>Price Range:</strong> {{ $hfsRequest->sale_price_range }}</p>
                        @endif
                        @if($hfsRequest->probability_pct)
                            <p class="mb-0"><strong>Probability:</strong> {{ number_format($hfsRequest->probability_pct, 1) }}%</p>
                        @endif
                    </div>
                </div>

                <!-- Discontinued Operations -->
                @if($hfsRequest->discontinuedFlag && $hfsRequest->discontinuedFlag->is_discontinued)
                <div class="card mb-3 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0"><i class="bx bx-error-circle me-2"></i>Discontinued Operation</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">This disposal group is classified as a discontinued operation.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve HFS Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('assets.hfs.requests.approve', $encodedId) }}" method="POST">
                @csrf
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
            <div class="modal-header">
                <h5 class="modal-title">Reject HFS Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('assets.hfs.requests.reject', $encodedId) }}" method="POST">
                @csrf
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
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function submitForApproval() {
    Swal.fire({
        title: 'Submit for Approval?',
        text: 'This will submit the HFS request for approval workflow.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Submit',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("assets.hfs.requests.submit", $encodedId) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to submit for approval'
                    });
                }
            });
        }
    });
}

// Forms now submit directly - no AJAX needed
</script>
@endpush

