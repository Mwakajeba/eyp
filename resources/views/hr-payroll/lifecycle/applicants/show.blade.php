@extends('layouts.main')

@section('title', 'Applicant Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Applicants', 'url' => route('hr.applicants.index'), 'icon' => 'bx bx-user-plus'],
                ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <!-- Header Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h4 class="mb-1">
                                <i class="bx bx-user-circle me-2"></i>
                                Applicant: {{ $applicant->full_name }}
                            </h4>
                            <p class="text-muted mb-0">
                                <i class="bx bx-hash me-1"></i>
                                Application Number: {{ $applicant->application_number }}
                            </p>
                            <p class="text-muted mb-0">
                                <i class="bx bx-calendar me-1"></i>
                                Applied: {{ $applicant->created_at->format('M d, Y h:i A') }}
                                @if($applicant->vacancyRequisition)
                                    for <strong>{{ $applicant->vacancyRequisition->job_title }}</strong>
                                @endif
                            </p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap mt-2">
                            @if(!$applicant->isConverted() && $applicant->status !== 'rejected')
                                <a href="{{ route('hr.applicants.convert-to-employee', $applicant->hash_id) }}" class="btn btn-success">
                                    <i class="bx bx-user-check me-1"></i>Convert to Employee
                                </a>
                            @endif
                            <a href="{{ route('hr.applicants.edit', $applicant->hash_id) }}" class="btn btn-warning">
                                <i class="bx bx-edit me-1"></i>Edit
                            </a>
                            <a href="{{ route('hr.applicants.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i>Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Badge Section -->
            <div class="row mb-4">
                <div class="col-12">
                    @php
                        $statusConfig = match ($applicant->status) {
                            'applied' => ['class' => 'bg-secondary', 'icon' => 'bx bx-send', 'text' => 'Applied'],
                            'screening' => ['class' => 'bg-info', 'icon' => 'bx bx-search-alt', 'text' => 'Screening'],
                            'interview' => ['class' => 'bg-primary', 'icon' => 'bx bx-conversation', 'text' => 'Interview'],
                            'offered' => ['class' => 'bg-warning', 'icon' => 'bx bx-badge-check', 'text' => 'Offered'],
                            'hired' => ['class' => 'bg-success', 'icon' => 'bx bx-check-double', 'text' => 'Hired'],
                            'rejected' => ['class' => 'bg-danger', 'icon' => 'bx bx-x-circle', 'text' => 'Rejected'],
                            'withdrawn' => ['class' => 'bg-dark', 'icon' => 'bx bx-user-minus', 'text' => 'Withdrawn'],
                            default => ['class' => 'bg-secondary', 'icon' => 'bx bx-question-mark', 'text' => 'Unknown']
                        };
                    @endphp
                    <div class="card border-{{ str_replace('bg-', '', $statusConfig['class']) }}">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="badge {{ $statusConfig['class'] }} fs-6 px-3 py-2 me-3">
                                        <i class="{{ $statusConfig['icon'] }} me-1"></i>
                                        {{ $statusConfig['text'] }}
                                    </span>
                                    @if($applicant->isConverted())
                                        <span class="badge bg-success fs-6 px-3 py-2">
                                            <i class="bx bx-check me-1"></i>Converted to Employee
                                        </span>
                                    @endif
                                </div>
                                <div class="text-end">
                                    @if($applicant->submission_source === 'portal')
                                        <span class="badge bg-light-info text-info border-info px-3 py-2">
                                            <i class="bx bx-globe me-1"></i>Job Portal Submission
                                        </span>
                                    @else
                                        <span class="badge bg-light-secondary text-secondary border-secondary px-3 py-2">
                                            <i class="bx bx-edit me-1"></i>Manual Entry
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Experience
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $applicant->years_of_experience ?? 0 }} Year(s)
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bx bx-briefcase-alt-2 bx-lg text-primary opacity-50"></i>
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
                                        Qualifications
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        @php
                                            $quals = is_array($applicant->qualifications) ? count($applicant->qualifications) : ($applicant->qualification ? 1 : 0);
                                        @endphp
                                        {{ $quals }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bx bx-graduation bx-lg text-info opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Age
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        @if($applicant->date_of_birth)
                                            {{ \Carbon\Carbon::parse($applicant->date_of_birth)->age }} Years
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bx bx-user bx-lg text-success opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Eligibility Score
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ number_format($applicant->total_eligibility_score ?? 0, 1) }}%
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bx bx-task bx-lg text-warning opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Eligibility Engine Report -->
            @if($applicant->eligibilityChecks->isNotEmpty())
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow border-info">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bx bx-check-shield me-2"></i>Eligibility Engine Report</h6>
                            <span class="badge bg-white text-info fw-bold">Score: {{ number_format($applicant->total_eligibility_score ?? 0, 0) }}%</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Criteria Type</th>
                                            <th>Requirement</th>
                                            <th>Applicant's Data</th>
                                            <th>Mandatory</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($applicant->eligibilityChecks->sortByDesc('eligibilityRule.is_mandatory') as $check)
                                            <tr class="{{ $check->passed ? 'table-success-light' : ($check->eligibilityRule->is_mandatory ? 'table-danger-light' : 'table-warning-light') }}">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bx {{ $check->passed ? 'bx-check-circle text-success' : 'bx-x-circle text-danger' }} me-2 fs-5"></i>
                                                        <span class="fw-bold">{{ ucfirst(str_replace('_', ' ', $check->eligibilityRule->rule_type ?? 'Other')) }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    @php
                                                        $ruleVal = is_array($check->expected_value) ? implode(', ', $check->expected_value) : $check->expected_value;
                                                        $operatorText = match($check->eligibilityRule->rule_operator ?? 'equals') {
                                                            'equals' => 'Must be',
                                                            'greater_than' => 'Greater than',
                                                            'less_than' => 'Less than',
                                                            'contains' => 'Must include',
                                                            'in' => 'One of',
                                                            'not_in' => 'None of',
                                                            'between' => 'Between',
                                                            default => $check->eligibilityRule->rule_operator
                                                        };
                                                    @endphp
                                                    <small class="text-muted d-block">{{ $operatorText }}:</small>
                                                    <span class="fw-bold">{{ $ruleVal }}</span>
                                                </td>
                                                <td>
                                                    @php
                                                        $appVal = is_array($check->checked_value) ? implode(', ', $check->checked_value) : $check->checked_value;
                                                    @endphp
                                                    <span class="{{ $check->passed ? 'text-success' : 'text-danger' }} fw-bold">
                                                        {{ $appVal ?: 'Not Provided' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($check->eligibilityRule->is_mandatory ?? false)
                                                        <span class="badge bg-danger"><i class="bx bx-lock-alt me-1"></i>Yes</span>
                                                    @else
                                                        <span class="badge bg-secondary">No</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($check->passed)
                                                        <span class="badge bg-success px-3"><i class="bx bx-check me-1"></i>MET</span>
                                                    @else
                                                        @if($check->eligibilityRule->is_mandatory ?? false)
                                                            <span class="badge bg-danger px-3"><i class="bx bx-x me-1"></i>FAILED</span>
                                                        @else
                                                            <span class="badge bg-warning text-dark px-3"><i class="bx bx-info-circle me-1"></i>PARTIAL</span>
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($applicant->status === 'rejected')
                                <div class="alert alert-danger mt-3 mb-0 py-2">
                                    <i class="bx bx-info-circle me-1"></i> <strong>Rejection Reason:</strong> This application was automatically rejected because it failed one or more <strong>mandatory</strong> criteria.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Hybrid Normalization Report -->
            @if($applicant->normalizedProfile)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow border-primary">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bx bx-repost me-2"></i>Profile Normalization (Standardized Data)</h6>
                            <div class="d-flex align-items-center">
                                @if($applicant->normalizedProfile->ai_confidence_score >= 90)
                                    <span class="badge bg-success me-2">AI Confidence: {{ number_format($applicant->normalizedProfile->ai_confidence_score, 0) }}%</span>
                                @elseif($applicant->normalizedProfile->ai_confidence_score >= 70)
                                    <span class="badge bg-warning text-dark me-2">AI Confidence: {{ number_format($applicant->normalizedProfile->ai_confidence_score, 0) }}%</span>
                                @else
                                    <span class="badge bg-danger me-2">AI Confidence: {{ number_format($applicant->normalizedProfile->ai_confidence_score, 0) }}%</span>
                                @endif

                                @if($applicant->normalizedProfile->is_manually_overridden)
                                    <span class="badge bg-info"><i class="bx bx-user-check me-1"></i>Manually Overridden</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 border-end">
                                    <label class="text-muted small d-block">Education Level</label>
                                    <h6 class="fw-bold text-capitalize">{{ $applicant->normalizedProfile->education_level ?: 'Not Extracted' }}</h6>
                                </div>
                                <div class="col-md-3 border-end">
                                    <label class="text-muted small d-block">Experience (Years)</label>
                                    <h6 class="fw-bold">{{ number_format($applicant->normalizedProfile->years_of_experience, 1) }} Yrs</h6>
                                </div>
                                <div class="col-md-3 border-end">
                                    <label class="text-muted small d-block">Inferred Role</label>
                                    <h6 class="fw-bold">{{ $applicant->normalizedProfile->current_role ?: 'N/A' }}</h6>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small d-block">Status</label>
                                    @if($applicant->normalizedProfile->requires_hr_review && !$applicant->normalizedProfile->is_manually_overridden)
                                        <span class="text-warning fw-bold"><i class="bx bx-error me-1"></i>Awaiting Review</span>
                                    @else
                                        <span class="text-success fw-bold"><i class="bx bx-check-double me-1"></i>Standardized</span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($applicant->normalizedProfile->requires_hr_review && !$applicant->normalizedProfile->is_manually_overridden)
                                <div class="alert alert-warning mt-3 mb-0 py-2 border-0 bg-light-warning">
                                    <div class="d-flex align-items-center">
                                        <div class="font-24 text-warning"><i class='bx bx-info-circle'></i></div>
                                        <div class="ms-3">
                                            <div class="small fw-bold">Human-in-the-Loop Review Required</div>
                                            <div class="text-dark extra-small">The AI extraction confidence is below 90%. HR review is recommended to ensure data accuracy for scoring.</div>
                                        </div>
                                        <button type="button" class="btn btn-warning btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#reviewNormalizationModal">
                                            Review & Standardize
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Main Content Row -->
            <div class="row mb-4">
                <!-- Left Column - Main Details -->
                <div class="col-lg-8">
                    <!-- Personal Information -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bx bx-user me-2"></i>Personal Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row mb-3">
                                        <div class="col-5 text-muted">Full Name:</div>
                                        <div class="col-7 fw-bold">{{ $applicant->full_name }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-5 text-muted">Gender:</div>
                                        <div class="col-7 text-capitalize">{{ $applicant->gender ?? 'N/A' }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-5 text-muted">Date of Birth:</div>
                                        <div class="col-7">{{ $applicant->date_of_birth ? $applicant->date_of_birth->format('d M Y') : 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row mb-3">
                                        <div class="col-5 text-muted">Email:</div>
                                        <div class="col-7 fw-bold">{{ $applicant->email }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-5 text-muted">Phone:</div>
                                        <div class="col-7 fw-bold">{{ $applicant->phone_number ?? 'N/A' }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-5 text-muted">Address:</div>
                                        <div class="col-7">{{ $applicant->address ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Qualifications -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bx bx-graduation me-2"></i>Qualifications & Education</h6>
                        </div>
                        <div class="card-body">
                            @if($applicant->qualifications && is_array($applicant->qualifications))
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Qualification</th>
                                                <th>Level</th>
                                                <th>Documents</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($applicant->qualifications as $qual)
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold">{{ $qual['qualification_name'] ?? 'N/A' }}</div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border">{{ ucfirst($qual['qualification_level'] ?? 'N/A') }}</span>
                                                    </td>
                                                    <td>
                                                        @if(isset($applicant->qualification_documents) && is_array($applicant->qualification_documents))
                                                            @foreach($applicant->qualification_documents as $doc)
                                                                @if($doc['qualification_id'] == ($qual['qualification_id'] ?? null))
                                                                    <a href="{{ Storage::url($doc['document_path']) }}" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-1 mb-1" title="{{ $doc['document_name'] }}">
                                                                        <i class="bx bx-file small"></i> {{ Str::limit($doc['document_name'], 15) }}
                                                                    </a>
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted small">No documents</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @elseif($applicant->qualification)
                                <div class="p-3 bg-light rounded">
                                    <strong>Qualification (Legacy):</strong><br>
                                    {{ $applicant->qualification }}
                                </div>
                            @else
                                <p class="text-muted mb-0 italic">No qualifications provided.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Professional Experience & Letter -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="bx bx-pencil me-2"></i>Professional Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6 class="fw-bold text-primary mb-2">Years of Experience:</h6>
                                <p class="fs-5">{{ $applicant->years_of_experience ?? 0 }} Year(s)</p>
                            </div>
                            
                            <hr>
                            
                            <h6 class="fw-bold text-primary mb-2">Cover Letter / Statement:</h6>
                            <div class="p-3 bg-light rounded" style="min-height: 100px; line-height: 1.8;">
                                @if($applicant->cover_letter)
                                    {!! nl2br(e($applicant->cover_letter)) !!}
                                @else
                                    <span class="text-muted italic">No cover letter provided.</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Interview History -->
                    @if($applicant->interviews->count() > 0)
                    <div class="card shadow mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="bx bx-conversation me-2"></i>Interview History</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Round</th>
                                            <th>Score</th>
                                            <th>Result</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($applicant->interviews as $interview)
                                        <tr>
                                            <td>{{ $interview->interview_date->format('d M Y') }}</td>
                                            <td><span class="text-capitalize">{{ str_replace('_', ' ', $interview->interview_type) }}</span></td>
                                            <td>Round {{ $interview->round_number }}</td>
                                            <td>
                                                <div class="progress" style="height: 8px; width: 60px;">
                                                    <div class="progress-bar bg-info" style="width: {{ $interview->overall_score ?? 0 }}%"></div>
                                                </div>
                                                <small>{{ $interview->overall_score ?? 0 }}/100</small>
                                            </td>
                                            <td>
                                                @php
                                                    $recBadge = match($interview->recommendation) {
                                                        'recommend' => 'success',
                                                        'not_recommend' => 'danger',
                                                        'on_hold' => 'warning',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $recBadge }} text-capitalize">
                                                    {{ str_replace('_', ' ', $interview->recommendation ?? 'Pending') }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right Column - Sidebar -->
                <div class="col-lg-4">
                    <!-- Contact Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="bx bx-phone me-2"></i>Contact Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="avatar-sm bg-light rounded text-primary p-2 me-3">
                                    <i class="bx bx-envelope fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Email Address</small>
                                    <a href="mailto:{{ $applicant->email }}" class="fw-bold text-decoration-none">{{ $applicant->email }}</a>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-3">
                                <div class="avatar-sm bg-light rounded text-success p-2 me-3">
                                    <i class="bx bx-phone fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Phone Number</small>
                                    <a href="tel:{{ $applicant->phone_number }}" class="fw-bold text-decoration-none">{{ $applicant->phone_number ?? 'N/A' }}</a>
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <div class="avatar-sm bg-light rounded text-warning p-2 me-3">
                                    <i class="bx bx-map fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Location/Address</small>
                                    <span class="fw-bold">{{ $applicant->address ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Files & Documents -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bx bx-file me-2"></i>Attachments</h6>
                        </div>
                        <div class="card-body">
                            @if($applicant->resume_path)
                                <div class="mb-3">
                                    <strong>Primary Resume:</strong><br>
                                    <a href="{{ Storage::url($applicant->resume_path) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100 mt-2">
                                        <i class="bx bx-show me-1"></i>View Resume
                                    </a>
                                </div>
                            @endif

                            @if($applicant->cv_path)
                                <div class="mb-3">
                                    <strong>Additional CV:</strong><br>
                                    <a href="{{ Storage::url($applicant->cv_path) }}" target="_blank" class="btn btn-outline-info btn-sm w-100 mt-2">
                                        <i class="bx bx-file me-1"></i>View Additional CV
                                    </a>
                                </div>
                            @endif

                            @if(!$applicant->resume_path && !$applicant->cv_path && !count($applicant->qualification_documents ?? []))
                                <p class="text-muted small italic">No attachments found.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Vacancy Context -->
                    @if($applicant->vacancyRequisition)
                    <div class="card shadow mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 text-dark"><i class="bx bx-link-external me-2"></i>Applied Vacancy</h6>
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold">{{ $applicant->vacancyRequisition->job_title }}</h6>
                            <p class="small text-muted mb-3">{{ $applicant->vacancyRequisition->requisition_number }}</p>
                            <div class="d-grid">
                                <a href="{{ route('hr.vacancy-requisitions.show', $applicant->vacancyRequisition->hash_id) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-show-alt me-1"></i>View Vacancy Details
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Conversion Status -->
                    @if($applicant->isConverted())
                    <div class="card shadow border-success">
                        <div class="card-body">
                            <h6 class="text-success fw-bold mb-2">
                                <i class="bx bx-check-circle me-1"></i>Hired & Converted
                            </h6>
                            <p class="small mb-3">This applicant has been hired and converted to an employee master record.</p>
                            <div class="d-grid">
                                <a href="{{ route('hr.employees.show', $applicant->converted_to_employee_id) }}" class="btn btn-success btn-sm">
                                    <i class="bx bx-user me-1"></i>View Employee Profile
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- Normalization Review Modal -->
    @if($applicant->normalizedProfile)
    <div class="modal fade" id="reviewNormalizationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('hr.applicants.override-normalization', $applicant->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Standardize Profile Data</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Standardized Education Level</label>
                                <select name="education_level" class="form-select">
                                    <option value="phd" {{ $applicant->normalizedProfile->education_level === 'phd' ? 'selected' : '' }}>PhD / Doctorate</option>
                                    <option value="masters" {{ $applicant->normalizedProfile->education_level === 'masters' ? 'selected' : '' }}>Master's Degree</option>
                                    <option value="degree" {{ $applicant->normalizedProfile->education_level === 'degree' ? 'selected' : '' }}>Bachelor's Degree</option>
                                    <option value="diploma" {{ $applicant->normalizedProfile->education_level === 'diploma' ? 'selected' : '' }}>Diploma</option>
                                    <option value="certificate" {{ $applicant->normalizedProfile->education_level === 'certificate' ? 'selected' : '' }}>Certificate</option>
                                    <option value="other" {{ $applicant->normalizedProfile->education_level === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Experience (Years)</label>
                                <input type="number" name="years_of_experience" class="form-control" step="0.5" value="{{ $applicant->normalizedProfile->years_of_experience }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Identified Job Role</label>
                                <input type="text" name="current_role" class="form-control" value="{{ $applicant->normalizedProfile->current_role }}" placeholder="e.g. Software Engineer, Finance Manager">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Override Reason (Auditable)</label>
                                <textarea name="override_reason" class="form-control" rows="2" required placeholder="Explain why you are modifying the system-generated data..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Standardized Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('styles')
<style>
    .border-left-primary { border-left: 4px solid #0d6efd !important; }
    .border-left-danger { border-left: 4px solid #dc3545 !important; }
    .border-left-success { border-left: 4px solid #198754 !important; }
    .border-left-info { border-left: 4px solid #0dcaf0 !important; }
    .border-left-warning { border-left: 4px solid #ffc107 !important; }
    .text-xs { font-size: 0.7rem; }
    .text-gray-800 { color: #5a5c69 !important; }
    .italic { font-style: italic; }
    .btn-xs { padding: 0.125rem 0.25rem; font-size: 0.75rem; line-height: 1.5; border-radius: 0.15rem; }
    .avatar-sm { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; }
    .bg-light-danger { background-color: rgba(220, 53, 69, 0.1) !important; }
    .table-success-light { background-color: rgba(25, 135, 84, 0.05) !important; }
    .table-danger-light { background-color: rgba(220, 53, 69, 0.05) !important; }
    .table-warning-light { background-color: rgba(255, 193, 7, 0.05) !important; }
</style>
@endpush