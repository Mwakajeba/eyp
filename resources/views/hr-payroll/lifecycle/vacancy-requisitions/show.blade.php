@extends('layouts.main')

@section('title', 'Vacancy Requisition Details')

@section('content')
    @php
        $eligibleCount = $vacancyRequisition->applicants->where('status', 'eligible')->count();
    @endphp
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Vacancy Requisitions', 'url' => route('hr.vacancy-requisitions.index'), 'icon' => 'bx bx-file-blank'],
                ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <!-- Header Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h4 class="mb-1">
                                <i class="bx bx-file-blank me-2"></i>
                                Vacancy Requisition - {{ $vacancyRequisition->requisition_number }}
                            </h4>
                            <p class="text-muted mb-0">
                                <i class="bx bx-briefcase me-1"></i>
                                {{ $vacancyRequisition->job_title }}
                                @if($vacancyRequisition->position)
                                    - {{ $vacancyRequisition->position->title }}
                                @endif
                            </p>
                            <p class="text-muted mb-0">
                                <i class="bx bx-calendar me-1"></i>
                                Created: {{ $vacancyRequisition->created_at->format('M d, Y h:i A') }}
                                @if($vacancyRequisition->requestedByUser)
                                    by {{ $vacancyRequisition->requestedByUser->name }}
                                @endif
                            </p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap mt-2">
                            @if($vacancyRequisition->status === 'draft')
                                <form action="{{ route('hr.vacancy-requisitions.submit', $vacancyRequisition->hash_id) }}" method="POST" class="d-inline" id="submit-for-approval-form">
                                    @csrf
                                    <button type="button" class="btn btn-primary" id="submit-for-approval-btn">
                                        <i class="bx bx-send me-1"></i>Submit for Approval
                                    </button>
                                </form>
                                <a href="{{ route('hr.vacancy-requisitions.edit', $vacancyRequisition->hash_id) }}" class="btn btn-warning">
                                    <i class="bx bx-edit me-1"></i>Edit
                                </a>
                                <button type="button" class="btn btn-danger" onclick="deleteRequisition('{{ $vacancyRequisition->hash_id }}')">
                                    <i class="bx bx-trash me-1"></i>Delete
                                </button>
                            @elseif($vacancyRequisition->status === 'pending_approval')
                                @if($canApprove ?? false)
                                    <button type="button" class="btn btn-success" onclick="approveRequisition('{{ $vacancyRequisition->hash_id }}')">
                                        <i class="bx bx-check me-1"></i>Approve
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="rejectRequisition('{{ $vacancyRequisition->hash_id }}')">
                                        <i class="bx bx-x me-1"></i>Reject
                                    </button>
                                @endif
                                @if($vacancyRequisition->requested_by === auth()->id())
                                    <a href="{{ route('hr.vacancy-requisitions.edit', $vacancyRequisition->hash_id) }}" class="btn btn-warning">
                                        <i class="bx bx-edit me-1"></i>Edit
                                    </a>
                                @endif
                            @elseif($vacancyRequisition->status === 'approved')
                                @if(!$vacancyRequisition->published_to_portal)
                                    <button type="button" class="btn btn-primary" onclick="publishRequisition('{{ $vacancyRequisition->hash_id }}')">
                                        <i class="bx bx-globe me-1"></i>Publish to Job Portal
                                    </button>
                                @else
                                    <span class="badge bg-success me-2">
                                        <i class="bx bx-check-circle me-1"></i>Published
                                    </span>
                                    <button type="button" class="btn btn-warning btn-sm" onclick="unpublishRequisition('{{ $vacancyRequisition->hash_id }}')">
                                        <i class="bx bx-x me-1"></i>Unpublish
                                    </button>
                                    @if($vacancyRequisition->published_to_portal)
                                        <a href="{{ route('public.job-portal.show', $vacancyRequisition->hash_id) }}" target="_blank" class="btn btn-info btn-sm">
                                            <i class="bx bx-link-external me-1"></i>View on Portal
                                        </a>
                                    @endif
                                @endif
                                <span class="btn btn-success disabled">
                                    <i class="bx bx-check-double me-1"></i>Approved
                                </span>
                            @elseif($vacancyRequisition->status === 'rejected')
                                @if($vacancyRequisition->requested_by === auth()->id())
                                    <form action="{{ route('hr.vacancy-requisitions.submit', $vacancyRequisition->hash_id) }}" method="POST" class="d-inline" id="resubmit-form">
                                        @csrf
                                        <button type="button" class="btn btn-primary" id="resubmit-btn">
                                            <i class="bx bx-send me-1"></i>Resubmit for Approval
                                        </button>
                                    </form>
                                @endif
                            @endif
                            <a href="{{ route('hr.vacancy-requisitions.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i>Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="row mb-4">
                <div class="col-12">
                    @php
                        $statusConfig = match ($vacancyRequisition->status) {
                            'draft' => ['class' => 'bg-secondary', 'icon' => 'bx bx-edit-alt', 'text' => 'Draft'],
                            'pending_approval' => ['class' => 'bg-warning', 'icon' => 'bx bx-time-five', 'text' => 'Pending Approval'],
                            'approved' => ['class' => 'bg-success', 'icon' => 'bx bx-check-circle', 'text' => 'Approved'],
                            'rejected' => ['class' => 'bg-danger', 'icon' => 'bx bx-x-circle', 'text' => 'Rejected'],
                            'closed' => ['class' => 'bg-dark', 'icon' => 'bx bx-lock', 'text' => 'Closed'],
                            'filled' => ['class' => 'bg-info', 'icon' => 'bx bx-user-check', 'text' => 'Filled'],
                            default => ['class' => 'bg-secondary', 'icon' => 'bx bx-question-mark', 'text' => 'Unknown']
                        };
                    @endphp
                    <div class="card border-{{ str_replace('bg-', '', $statusConfig['class']) }}">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <span class="badge {{ $statusConfig['class'] }} fs-6 px-3 py-2 me-3">
                                    <i class="{{ $statusConfig['icon'] }} me-1"></i>
                                    {{ $statusConfig['text'] }}
                                </span>
                                @if($vacancyRequisition->status === 'pending_approval' && isset($currentLevel))
                                    <span class="badge bg-info fs-6 px-3 py-2">
                                        <i class="bx bx-layer me-1"></i>
                                        Level {{ $currentLevel->level ?? 'N/A' }} - {{ $currentLevel->level_name ?? 'N/A' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit for Approval Section (Draft or Rejected) -->
            @if(in_array($vacancyRequisition->status, ['draft', 'rejected']) && $vacancyRequisition->requested_by === auth()->id())
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-send me-2"></i>Submit for Approval
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">Ready to submit this vacancy requisition for approval? Click the button below to start the approval process.</p>
                            <form action="{{ route('hr.vacancy-requisitions.submit', $vacancyRequisition->hash_id) }}" method="POST" id="submitApprovalForm">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-send me-2"></i>Submit for Approval
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Approval Status Section (Pending Approval) -->
            @if($vacancyRequisition->status === 'pending_approval')
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-white">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-time-five me-2"></i>Approval Status
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(isset($currentLevel))
                                <div class="mb-3">
                                    <h6 class="text-primary">
                                        <i class="bx bx-layer me-1"></i>Current Approval Level: Level {{ $currentLevel->level ?? 'N/A' }}
                                    </h6>
                                    <p class="text-muted mb-2">{{ $currentLevel->level_name ?? 'N/A' }}</p>
                                    @if(isset($currentApprovers) && $currentApprovers->count() > 0)
                                        <p class="mb-0"><strong>Approvers:</strong> {{ $currentApprovers->pluck('name')->join(', ') }}</p>
                                    @endif
                                </div>
                            @endif
                            @if(isset($approvalSummary))
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h4 class="mb-0 text-primary">{{ $approvalSummary['total_levels'] ?? 0 }}</h4>
                                            <small class="text-muted">Total Levels</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h4 class="mb-0 text-success">{{ $approvalSummary['approved_levels'] ?? 0 }}</h4>
                                            <small class="text-muted">Approved Levels</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h4 class="mb-0 text-warning">{{ $approvalSummary['pending_levels'] ?? 0 }}</h4>
                                            <small class="text-muted">Pending Levels</small>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Summary Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Number of Positions
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $vacancyRequisition->number_of_positions }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bx bx-group bx-lg text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Applicants
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $vacancyRequisition->applicants->count() }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bx bx-user-plus bx-lg text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($vacancyRequisition->budgeted_salary_min || $vacancyRequisition->budgeted_salary_max)
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Budgeted Salary Range
                                    </div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        @if($vacancyRequisition->budgeted_salary_min && $vacancyRequisition->budgeted_salary_max)
                                            TZS {{ number_format($vacancyRequisition->budgeted_salary_min, 2) }} - {{ number_format($vacancyRequisition->budgeted_salary_max, 2) }}
                                        @elseif($vacancyRequisition->budgeted_salary_min)
                                            Min: TZS {{ number_format($vacancyRequisition->budgeted_salary_min, 2) }}
                                        @elseif($vacancyRequisition->budgeted_salary_max)
                                            Max: TZS {{ number_format($vacancyRequisition->budgeted_salary_max, 2) }}
                                        @endif
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bx bx-money bx-lg text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Days Open
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        @php
                                            $startDate = $vacancyRequisition->posting_start_date ?? $vacancyRequisition->opening_date;
                                            $endDate = $vacancyRequisition->posting_end_date ?? $vacancyRequisition->closing_date;
                                            $days = 0;
                                            if ($startDate && $endDate) {
                                                $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate));
                                            } elseif ($startDate) {
                                                $days = \Carbon\Carbon::parse($startDate)->diffInDays(now());
                                            }
                                        @endphp
                                        {{ number_format($days, 0) }} days
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bx bx-calendar bx-lg text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Row -->
            <div class="row mb-4">
                <!-- Left Column - Main Details -->
                <div class="col-lg-8">
                    <!-- Requisition Information -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Requisition Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Requisition Number:</strong></div>
                                <div class="col-md-8">{{ $vacancyRequisition->requisition_number }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Job Title:</strong></div>
                                <div class="col-md-8">{{ $vacancyRequisition->job_title }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Position:</strong></div>
                                <div class="col-md-8">{{ $vacancyRequisition->position->title ?? 'N/A' }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Department:</strong></div>
                                <div class="col-md-8">{{ $vacancyRequisition->department->name ?? 'N/A' }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Number of Positions:</strong></div>
                                <div class="col-md-8">{{ $vacancyRequisition->number_of_positions }}</div>
                            </div>

                            @if($vacancyRequisition->budgeted_salary_min || $vacancyRequisition->budgeted_salary_max)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Salary Range:</strong></div>
                                <div class="col-md-8">
                                    @if($vacancyRequisition->budgeted_salary_min && $vacancyRequisition->budgeted_salary_max)
                                        TZS {{ number_format($vacancyRequisition->budgeted_salary_min, 2) }} - {{ number_format($vacancyRequisition->budgeted_salary_max, 2) }}
                                    @elseif($vacancyRequisition->budgeted_salary_min)
                                        Min: TZS {{ number_format($vacancyRequisition->budgeted_salary_min, 2) }}
                                    @elseif($vacancyRequisition->budgeted_salary_max)
                                        Max: TZS {{ number_format($vacancyRequisition->budgeted_salary_max, 2) }}
                                    @endif
                                </div>
                            </div>
                            @endif

                            @if($vacancyRequisition->opening_date || $vacancyRequisition->closing_date)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Opening/Closing Dates:</strong></div>
                                <div class="col-md-8">
                                    {{ $vacancyRequisition->opening_date?->format('d M Y') ?? 'N/A' }} - 
                                    {{ $vacancyRequisition->closing_date?->format('d M Y') ?? 'N/A' }}
                                </div>
                            </div>
                            @endif

                            @if($vacancyRequisition->job_description)
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <strong>Job Description:</strong>
                                    <div class="mt-2 p-3 bg-light rounded">
                                        {!! nl2br(e($vacancyRequisition->job_description)) !!}
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($vacancyRequisition->requirements)
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <strong>Requirements:</strong>
                                    <div class="mt-2 p-3 bg-light rounded">
                                        {!! nl2br(e($vacancyRequisition->requirements)) !!}
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Blueprint Enhancement Fields -->
                            @if($vacancyRequisition->hiring_justification || $vacancyRequisition->recruitment_type || $vacancyRequisition->cost_center_id || $vacancyRequisition->budget_line_id || $vacancyRequisition->project_grant_code || $vacancyRequisition->contract_period_months || $vacancyRequisition->is_publicly_posted)
                            <hr class="my-4">
                            <h6 class="mb-3 text-primary">
                                <i class="bx bx-info-circle me-1"></i>Additional Information
                            </h6>

                            @if($vacancyRequisition->hiring_justification)
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <strong>Hiring Justification:</strong>
                                    <div class="mt-2 p-3 bg-light rounded">
                                        {!! nl2br(e($vacancyRequisition->hiring_justification)) !!}
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                @if($vacancyRequisition->recruitment_type)
                                <div class="col-md-4">
                                    <strong>Recruitment Type:</strong><br>
                                    @php
                                        $recruitmentTypes = [
                                            'internal' => ['label' => 'Internal', 'badge' => 'info'],
                                            'external' => ['label' => 'External', 'badge' => 'primary'],
                                            'both' => ['label' => 'Both', 'badge' => 'success'],
                                        ];
                                        $type = $recruitmentTypes[$vacancyRequisition->recruitment_type] ?? ['label' => ucfirst($vacancyRequisition->recruitment_type), 'badge' => 'secondary'];
                                    @endphp
                                    <span class="badge bg-{{ $type['badge'] }}">{{ $type['label'] }}</span>
                                </div>
                                @endif

                                @if($vacancyRequisition->contract_period_months)
                                <div class="col-md-4">
                                    <strong>Contract Period:</strong><br>
                                    {{ $vacancyRequisition->contract_period_months }} month(s)
                                </div>
                                @endif

                                @if($vacancyRequisition->is_publicly_posted)
                                <div class="col-md-4">
                                    <strong>Public Posting:</strong><br>
                                    <span class="badge bg-success"><i class="bx bx-globe me-1"></i>Publicly Posted</span>
                                </div>
                                @endif
                            </div>

                            @if($vacancyRequisition->posting_start_date || $vacancyRequisition->posting_end_date)
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <strong>Posting Period:</strong><br>
                                    {{ $vacancyRequisition->posting_start_date?->format('d M Y') ?? 'N/A' }} - 
                                    {{ $vacancyRequisition->posting_end_date?->format('d M Y') ?? 'N/A' }}
                                </div>
                            </div>
                            @endif

                            @if($vacancyRequisition->budgetLine)
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Budget Line:</strong><br>
                                    {{ $vacancyRequisition->budgetLine->account->account_code ?? 'N/A' }} - 
                                    {{ $vacancyRequisition->budgetLine->account->account_name ?? 'N/A' }}
                                    <small class="text-muted">({{ number_format($vacancyRequisition->budgetLine->amount, 2) }})</small>
                                </div>
                            </div>
                            @endif

                            @if($vacancyRequisition->project_grant_code)
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <strong>Project/Grant Code:</strong><br>
                                    <span class="badge bg-info">{{ $vacancyRequisition->project_grant_code }}</span>
                                </div>
                            </div>
                            @endif
                            @endif
                        </div>
                    </div>

                    <!-- Eligibility & Validation Rules -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bx bx-check-shield me-2"></i>Eligibility & Validation Rules</h6>
                        </div>
                        <div class="card-body">
                            @if($vacancyRequisition->eligibilityRules->isEmpty())
                                <div class="text-center py-3">
                                    <i class="bx bx-info-circle fs-2 text-muted mb-2"></i>
                                    <p class="mb-0 text-muted">No eligibility rules have been set for this vacancy.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Type</th>
                                                <th>Applies To</th>
                                                <th>Criteria</th>
                                                <th>Requirement</th>
                                                <th>Mandatory</th>
                                                <th>Weight</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($vacancyRequisition->eligibilityRules as $rule)
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-light text-dark">
                                                            {{ ucfirst(str_replace('_', ' ', $rule->rule_type)) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ $rule->applies_to === 'conditional' ? 'warning text-dark' : 'info' }}">
                                                            {{ ucfirst($rule->applies_to) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $operators = [
                                                                'equals' => 'Is exactly',
                                                                'greater_than' => 'Is greater than',
                                                                'less_than' => 'Is less than',
                                                                'contains' => 'Contains',
                                                                'in' => 'Is one of',
                                                                'not_in' => 'Is not one of',
                                                                'between' => 'Is between',
                                                            ];
                                                        @endphp
                                                        {{ $operators[$rule->rule_operator] ?? $rule->rule_operator }}
                                                    </td>
                                                    <td>
                                                        @if(is_array($rule->rule_value))
                                                            @if(isset($rule->rule_value['min']) && isset($rule->rule_value['max']))
                                                                {{ $rule->rule_value['min'] }} - {{ $rule->rule_value['max'] }}
                                                            @else
                                                                {{ implode(', ', $rule->rule_value) }}
                                                            @endif
                                                        @else
                                                            {{ $rule->rule_value }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($rule->is_mandatory)
                                                            <span class="badge bg-danger"><i class="bx bx-lock-alt me-1"></i>Yes</span>
                                                        @else
                                                            <span class="badge bg-secondary">No</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($rule->weight > 0)
                                                            <span class="badge bg-primary">{{ $rule->weight }}%</span>
                                                        @else
                                                            <span class="text-muted small">N/A</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @if($rule->rule_description)
                                                    <tr>
                                                        <td colspan="6" class="bg-light py-1 ps-4">
                                                            <small class="text-muted italic"><i class="bx bx-info-circle me-1"></i>{{ $rule->rule_description }}</small>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Approval History -->
                    @if(isset($approvalHistory) && $approvalHistory->count() > 0)
                    <div class="card shadow mb-4">
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
                                                {{ ucfirst($history->action) }}
                                                @if(isset($history->approvalLevel))
                                                    at {{ $history->approvalLevel->level_name ?? 'Level ' . ($history->approval_level_id ?? 'N/A') }}
                                                @endif
                                            </h6>
                                            <p class="mb-1 text-muted small">
                                                By: {{ $history->approver->name ?? 'System' }}
                                                <span class="ms-2">{{ $history->created_at->format('M d, Y H:i') }}</span>
                                            </p>
                                            @if($history->comments)
                                                <p class="mb-0 small"><strong>Comments:</strong> {{ $history->comments }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Talent Pool & Ranking -->
                    @if($vacancyRequisition->status === 'approved' || $vacancyRequisition->applicants->count() > 0)
                    <div class="card shadow mb-4">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 d-inline-block"><i class="bx bx-trophy me-2"></i>Talent Pool & Candidate Ranking</h6>
                                @if($eligibleCount > 0)
                                    <button type="button" class="btn btn-xs btn-primary ms-3" onclick="launchBulkInvitationWizard()">
                                        <i class="bx bx-mail-send me-1"></i>Invite Eligible Applicants ({{ $eligibleCount }})
                                    </button>
                                @endif
                            </div>
                            <a href="{{ route('hr.applicants.index', ['vacancy_requisition_id' => $vacancyRequisition->hash_id]) }}" class="btn btn-xs btn-light text-dark">View All</a>
                        </div>
                        <div class="card-body">
                            @if($vacancyRequisition->applicants->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Rank</th>
                                                <th>Candidate</th>
                                                <th class="text-center">Score</th>
                                                <th>Status</th>
                                                <th class="text-center">Shortlist</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($vacancyRequisition->applicants->sortByDesc('total_eligibility_score')->take(10) as $index => $applicant)
                                                <tr>
                                                    <td>
                                                        @if($index == 0)
                                                            <span class="badge bg-warning text-dark"><i class="bx bxs-crown"></i> 1st</span>
                                                        @else
                                                            <span class="text-muted">{{ $index + 1 }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 32px; height: 32px; font-size: 13px;">
                                                            {{ strtoupper(substr($applicant->first_name, 0, 1)) }}
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold">{{ $applicant->full_name }}</div>
                                                            <small class="text-muted">{{ $applicant->application_number }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $score = number_format($applicant->total_eligibility_score, 0);
                                                        $class = $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
                                                    @endphp
                                                    <span class="fw-bold text-{{ $class }}">{{ $score }}%</span>
                                                </td>
                                                <td>
                                                    @php
                                                        $statusBadges = [
                                                            'applied' => 'secondary',
                                                            'eligible' => 'success',
                                                            'invited' => 'warning',
                                                            'screening' => 'info',
                                                            'interview' => 'primary',
                                                            'offered' => 'warning',
                                                            'hired' => 'success',
                                                            'rejected' => 'danger',
                                                        ];
                                                        $badge = $statusBadges[$applicant->status] ?? 'secondary';
                                                        $statusText = $applicant->status === 'eligible' ? 'Eligible' : ucfirst($applicant->status);
                                                    @endphp
                                                    <span class="badge bg-{{ $badge }}">{{ $statusText }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if($applicant->is_shortlisted)
                                                        <span class="text-success fs-4"><i class="bx bxs-check-circle"></i></span>
                                                    @else
                                                        <button type="button" class="btn btn-sm btn-outline-success shortlist-btn" data-id="{{ $applicant->hash_id }}">
                                                            <i class="bx bx-plus-circle"></i>
                                                        </button>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <a href="{{ route('hr.applicants.show', $applicant->hash_id) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($vacancyRequisition->applicants->count() > 10)
                                    <div class="text-center mt-2">
                                        <small class="text-muted italic">Showing top 10 candidates by score.</small>
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-4">
                                    <i class="bx bx-user-x fs-1 text-muted mb-3"></i>
                                    <h6 class="text-muted">No applicants found for this requisition yet.</h6>
                                    <p class="small text-muted mb-0">Once candidates apply through the portal, they will appear here automatically for ranking.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right Column - Sidebar -->
                <div class="col-lg-4">
                    <!-- Additional Information -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Additional Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <strong>Requested By:</strong><br>
                                    {{ $vacancyRequisition->requestedByUser->name ?? 'N/A' }}
                                    <small class="text-muted d-block">{{ $vacancyRequisition->created_at->format('M d, Y H:i') }}</small>
                                </div>
                            </div>

                            @if($vacancyRequisition->approved_by)
                            <hr>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <strong>Approved By:</strong><br>
                                    {{ $vacancyRequisition->approvedByUser->name ?? 'N/A' }}
                                    <small class="text-muted d-block">{{ $vacancyRequisition->approved_at?->format('M d, Y H:i') ?? 'N/A' }}</small>
                                </div>
                            </div>
                            @endif

                            @if($vacancyRequisition->rejection_reason)
                            <hr>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <strong class="text-danger">Rejection Reason:</strong><br>
                                    <div class="mt-1 p-2 bg-light rounded text-danger">
                                        {{ $vacancyRequisition->rejection_reason }}
                                    </div>
                                </div>
                            </div>
                            @endif

                            <hr>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <strong>Created:</strong><br>
                                    <small class="text-muted">{{ $vacancyRequisition->created_at->format('M d, Y H:i') }}</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <strong>Last Updated:</strong><br>
                                    <small class="text-muted">{{ $vacancyRequisition->updated_at->format('M d, Y H:i') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bx bx-link me-2"></i>Quick Links</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('hr.applicants.index', ['vacancy_requisition_id' => $vacancyRequisition->hash_id]) }}" class="btn btn-outline-info btn-sm">
                                    <i class="bx bx-user-plus me-1"></i>View Applicants
                                </a>
                                <a href="{{ route('hr.interview-records.index', ['vacancy_requisition_id' => $vacancyRequisition->hash_id]) }}" class="btn btn-outline-warning btn-sm">
                                    <i class="bx bx-conversation me-1"></i>View Interviews
                                </a>
                                @if($vacancyRequisition->status === 'approved')
                                    <a href="{{ route('hr.offer-letters.create', ['vacancy_requisition_id' => $vacancyRequisition->hash_id]) }}" class="btn btn-outline-success btn-sm">
                                        <i class="bx bx-envelope me-1"></i>Create Offer Letter
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Interview Invitation Wizard -->
    <div class="modal fade" id="bulkInvitationWizard" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="bulkInvitationForm">
                    @csrf
                    <input type="hidden" name="vacancy_requisition_id" value="{{ $vacancyRequisition->id }}">
                    
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bx bx-mail-send me-2"></i>Interview Invitation Wizard</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body">
                        <!-- Step 1: Summary -->
                        <div class="wizard-step" id="wizard-step-1">
                            <div class="alert alert-info border-0 bg-light-info">
                                <h6 class="mb-1 fw-bold">Vacancy & Applicant Summary</h6>
                                <p class="mb-0 small">Vacancy: {{ $vacancyRequisition->job_title }} ({{ $vacancyRequisition->requisition_number }})</p>
                                <p class="mb-0 small fw-bold">Eligible Applicants Found: {{ $eligibleCount }}</p>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="p-3 border rounded text-center bg-light">
                                        <h4 class="mb-0 text-primary">{{ $eligibleCount }}</h4>
                                        <small class="text-muted">Total Invited</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded text-center bg-light">
                                        <h4 class="mb-0 text-success">100%</h4>
                                        <small class="text-muted">Eligible Match</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded text-center bg-light">
                                        <h4 class="mb-0 text-info">Auto</h4>
                                        <small class="text-muted">Filtering Mode</small>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Interview Type <span class="text-danger">*</span></label>
                                    <select name="interview_type" class="form-select" required>
                                        <option value="in_person">Physical (Office)</option>
                                        <option value="video">Virtual (Online)</option>
                                        <option value="phone">Phone</option>
                                        <option value="panel">Hybrid/Panel</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Interview Stage <span class="text-danger">*</span></label>
                                    <select name="interview_stage" class="form-select" required>
                                        <option value="First Interview">First Interview</option>
                                        <option value="Technical Interview">Technical Interview</option>
                                        <option value="Panel Interview">Panel Interview</option>
                                        <option value="Final Interview">Final Interview</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Interview Method <span class="text-danger">*</span></label>
                                    <select name="interview_method" class="form-select" required>
                                        <option value="competency">Competency-based</option>
                                        <option value="technical">Technical assessment</option>
                                        <option value="mixed">Mixed Approach</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Schedule & Panel -->
                        <div class="wizard-step d-none" id="wizard-step-2">
                            <h6 class="fw-bold mb-3">Interview Scheduling & Panel Assignment</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Interview Date <span class="text-danger">*</span></label>
                                    <input type="date" name="interview_date" class="form-control" required min="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Start Time <span class="text-danger">*</span></label>
                                    <input type="time" name="interview_time" class="form-control" required>
                                </div>
                                <div class="col-md-12" id="location-field">
                                    <label class="form-label fw-bold">Location / Meeting Link</label>
                                    <input type="text" name="location" class="form-control" placeholder="e.g. Boardroom 1 or Zoom Link">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Assign Panel Members <span class="text-danger">*</span></label>
                                <select name="interviewers[]" class="form-select select2-panel" multiple required>
                                    @foreach(\App\Models\User::where('company_id', current_company_id())->active()->get() as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Selected members will be notified and granted access to interview forms.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-outline-primary" id="prevStep" disabled>Previous</button>
                        <button type="button" class="btn btn-primary" id="nextStep">Next: Schedule & Panel</button>
                        <button type="submit" class="btn btn-success d-none" id="submitInvitation">Confirm & Send Invitations</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .border-left-primary {
        border-left: 4px solid #0d6efd !important;
    }
    .border-left-danger {
        border-left: 4px solid #dc3545 !important;
    }
    .border-left-success {
        border-left: 4px solid #198754 !important;
    }
    .border-left-info {
        border-left: 4px solid #0dcaf0 !important;
    }
    .border-left-warning {
        border-left: 4px solid #ffc107 !important;
    }
    .text-xs {
        font-size: 0.7rem;
    }
    .text-gray-800 {
        color: #5a5c69 !important;
    }
    .timeline-item {
        position: relative;
        padding-left: 2rem;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0.5rem;
        width: 2px;
        height: calc(100% + 1rem);
        background: #dee2e6;
    }
    .timeline-item:last-child::before {
        display: none;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Submit for approval
    $('#submit-for-approval-btn, #resubmit-btn').on('click', function() {
        const form = $(this).closest('form');
        Swal.fire({
            title: 'Submit for Approval?',
            text: 'This will submit the vacancy requisition for approval. Continue?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, submit it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Submit form handler
    $('#submitApprovalForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Submit for Approval?',
            text: 'This will submit the vacancy requisition for approval. Continue?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, submit it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });
});

// Approve requisition function
function approveRequisition(id) {
    Swal.fire({
        title: 'Approve Requisition?',
        input: 'textarea',
        inputLabel: 'Comments (optional)',
        inputPlaceholder: 'Enter any comments for this approval...',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Approve',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (value && value.length > 500) {
                return 'Comments cannot exceed 500 characters';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Approving...',
                text: 'Please wait while we approve the requisition.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: `{{ url('hr-payroll/vacancy-requisitions') }}/${id}/approve`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    comments: result.value || ''
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Approved!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function (xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire('Error!', response.message || 'An error occurred while approving the requisition.', 'error');
                }
            });
        }
    });
}

// Reject requisition function
function rejectRequisition(id) {
    Swal.fire({
        title: 'Reject Requisition?',
        input: 'textarea',
        inputLabel: 'Rejection reason (required)',
        inputPlaceholder: 'Enter the reason for rejecting this requisition...',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Reject',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value || value.trim() === '') {
                return 'Please provide a reason for rejection';
            }
            if (value.length > 500) {
                return 'Reason cannot exceed 500 characters';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Rejecting...',
                text: 'Please wait while we reject the requisition.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: `{{ url('hr-payroll/vacancy-requisitions') }}/${id}/reject`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    reason: result.value
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Rejected!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function (xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire('Error!', response.message || 'An error occurred while rejecting the requisition.', 'error');
                }
            });
        }
    });
}

// Publish requisition function
function publishRequisition(id) {
    Swal.fire({
        title: 'Publish to Job Portal?',
        html: 'Are you sure you want to publish this vacancy to the job portal?<br><br><small class="text-muted">This will make the vacancy visible to external applicants.</small>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, publish it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Publishing...',
                text: 'Please wait while we publish the vacancy to the job portal.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: `{{ url('hr-payroll/vacancy-requisitions') }}/${id}/publish`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    Swal.fire({
                        title: 'Published!',
                        text: 'The vacancy has been successfully published to the job portal.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function (xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire('Error!', response.message || 'An error occurred while publishing the vacancy.', 'error');
                }
            });
        }
    });
}

// Unpublish requisition function
function unpublishRequisition(id) {
    Swal.fire({
        title: 'Remove from Job Portal?',
        html: 'Are you sure you want to remove this vacancy from the job portal?<br><br><small class="text-muted">The vacancy will no longer be visible to external applicants.</small>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f0ad4e',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, remove it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Removing...',
                text: 'Please wait while we remove the vacancy from the job portal.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: `{{ url('hr-payroll/vacancy-requisitions') }}/${id}/unpublish`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    Swal.fire({
                        title: 'Removed!',
                        text: 'The vacancy has been successfully removed from the job portal.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function (xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire('Error!', response.message || 'An error occurred while removing the vacancy.', 'error');
                }
            });
        }
    });
}

// Delete requisition function
function deleteRequisition(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete the vacancy requisition. This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the requisition.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: `{{ url('hr-payroll/vacancy-requisitions') }}/${id}`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = "{{ route('hr.vacancy-requisitions.index') }}";
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function (xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire('Error!', response.message || 'An error occurred while deleting the requisition.', 'error');
                }
            });
        }
    });
}

// Shortlist candidate function
$(document).on('click', '.shortlist-btn', function() {
    const applicantId = $(this).data('id');
    
    Swal.fire({
        title: 'Shortlist Candidate?',
        text: 'This will move the candidate to the shortlist for panel review.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, shortlist!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ url('hr-payroll/applicants') }}/${applicantId}/shortlist`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Shortlisted!',
                            text: response.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                }
            });
        }
    });
});

// Bulk Invitation Wizard Logic
function launchBulkInvitationWizard() {
    $('#bulkInvitationWizard').modal('show');
}

$(document).ready(function() {
    let currentStep = 1;
    
    $('.select2-panel').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#bulkInvitationWizard')
    });

    $('#nextStep').on('click', function() {
        if (currentStep === 1) {
            $('#wizard-step-1').addClass('d-none');
            $('#wizard-step-2').removeClass('d-none');
            $('#prevStep').prop('disabled', false);
            $(this).addClass('d-none');
            $('#submitInvitation').removeClass('d-none');
            currentStep = 2;
        }
    });

    $('#prevStep').on('click', function() {
        if (currentStep === 2) {
            $('#wizard-step-2').addClass('d-none');
            $('#wizard-step-1').removeClass('d-none');
            $(this).prop('disabled', true);
            $('#nextStep').removeClass('d-none');
            $('#submitInvitation').addClass('d-none');
            currentStep = 1;
        }
    });

    $('#bulkInvitationForm').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Confirm Bulk Invitations?',
            text: `You are about to invite {{ $eligibleCount }} candidates to the interview pipeline. Automated notifications will be sent immediately.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Send Invitations!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we generate interview records and send notifications.',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: "{{ route('hr.interview-records.bulk-store') }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 3000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', 'An unexpected error occurred during processing.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush