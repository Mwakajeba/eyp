@extends('layouts.main')

@section('title', 'Offer Letter Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Applicants', 'url' => route('hr.applicants.index'), 'icon' => 'bx bx-user-plus'],
                ['label' => 'Offer Letters', 'url' => route('hr.offer-letters.index'), 'icon' => 'bx bx-envelope'],
                ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <!-- Header Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h4 class="mb-1">
                                <i class="bx bx-envelope me-2"></i>
                                Offer Letter: {{ $offerLetter->offer_number }}
                            </h4>
                            <p class="text-muted mb-0">
                                <i class="bx bx-user me-1"></i>
                                Candidate: <strong>{{ $offerLetter->applicant->full_name }}</strong> ({{ $offerLetter->applicant->application_number }})
                            </p>
                            @if($offerLetter->vacancyRequisition)
                                <p class="text-muted mb-0">
                                    <i class="bx bx-briefcase me-1"></i>
                                    Position: {{ $offerLetter->vacancyRequisition->job_title }}
                                </p>
                            @endif
                        </div>
                        <div class="d-flex gap-2 flex-wrap mt-2">
                            @if($offerLetter->status === 'draft')
                                <button type="button" class="btn btn-primary" onclick="submitForApproval('{{ $offerLetter->id }}')">
                                    <i class="bx bx-send me-1"></i>Submit for Approval
                                </button>
                            @endif

                            @if($offerLetter->status === 'pending_approval' && auth()->user()->hasPermission('approve offer letters'))
                                <button type="button" class="btn btn-success" onclick="approveOffer('{{ $offerLetter->id }}')">
                                    <i class="bx bx-check-double me-1"></i>Approve Offer
                                </button>
                            @endif

                            @if($offerLetter->status === 'approved')
                                <button type="button" class="btn btn-info" onclick="sendOffer('{{ $offerLetter->id }}')">
                                    <i class="bx bx-mail-send me-1"></i>Send to Candidate
                                </button>
                            @endif

                            @if($offerLetter->status === 'sent')
                                <button type="button" class="btn btn-success" onclick="acceptOffer('{{ $offerLetter->id }}')">
                                    <i class="bx bx-badge-check me-1"></i>Mark Accepted
                                </button>
                                <button type="button" class="btn btn-danger" onclick="rejectOffer('{{ $offerLetter->id }}')">
                                    <i class="bx bx-x-circle me-1"></i>Mark Rejected
                                </button>
                            @endif

                            @if($offerLetter->status === 'accepted' && !$offerLetter->applicant->isConverted())
                                <a href="{{ route('hr.applicants.convert-to-employee', $offerLetter->applicant_id) }}" class="btn btn-success">
                                    <i class="bx bx-user-plus me-1"></i>Convert to Employee
                                </a>
                            @endif

                            <a href="{{ route('hr.offer-letters.edit', $offerLetter->id) }}" class="btn btn-warning">
                                <i class="bx bx-edit me-1"></i>Edit
                            </a>
                            <a href="{{ route('hr.offer-letters.index') }}" class="btn btn-secondary">
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
                        $statusConfig = match ($offerLetter->status) {
                            'draft' => ['class' => 'bg-secondary', 'icon' => 'bx bx-edit-alt', 'text' => 'Draft'],
                            'pending_approval' => ['class' => 'bg-warning', 'icon' => 'bx bx-time-five', 'text' => 'Pending Approval'],
                            'approved' => ['class' => 'bg-success', 'icon' => 'bx bx-check-circle', 'text' => 'Approved'],
                            'sent' => ['class' => 'bg-info', 'icon' => 'bx bx-paper-plane', 'text' => 'Sent to Candidate'],
                            'accepted' => ['class' => 'bg-success', 'icon' => 'bx bx-badge-check', 'text' => 'Accepted'],
                            'rejected' => ['class' => 'bg-danger', 'icon' => 'bx bx-x-circle', 'text' => 'Rejected'],
                            'expired' => ['class' => 'bg-dark', 'icon' => 'bx bx-timer', 'text' => 'Expired'],
                            'withdrawn' => ['class' => 'bg-dark', 'icon' => 'bx bx-undo', 'text' => 'Withdrawn'],
                            default => ['class' => 'bg-secondary', 'icon' => 'bx bx-question-mark', 'text' => 'Unknown']
                        };
                    @endphp
                    <div class="card border-{{ str_replace('bg-', '', $statusConfig['class']) }}">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="badge {{ $statusConfig['class'] }} fs-6 px-3 py-2 me-3">
                                        <i class="{{ $statusConfig['icon'] }} me-1"></i>
                                        {{ $statusConfig['text'] }}
                                    </span>
                                    @if($offerLetter->isExpired() && $offerLetter->status !== 'expired')
                                        <span class="badge bg-danger fs-6 px-3 py-2">
                                            <i class="bx bx-error me-1"></i>Offer Expired
                                        </span>
                                    @endif
                                </div>
                                <div class="text-end text-muted small">
                                    Last Updated: {{ $offerLetter->updated_at->format('M d, Y h:i A') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column - Offer Details -->
                <div class="col-lg-8">
                    <!-- Compensation & Terms -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0 fw-bold"><i class="bx bx-coin-stack me-2"></i>Compensation & Terms</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6 border-end">
                                    <label class="text-muted small d-block">Offered Gross Salary</label>
                                    <h4 class="fw-bold text-success mb-0">TZS {{ number_format($offerLetter->offered_salary, 2) }}</h4>
                                    <small class="text-muted italic">Per Month</small>
                                </div>
                                <div class="col-md-6 ps-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small d-block">Proposed Start Date</label>
                                        <h6 class="fw-bold">{{ $offerLetter->proposed_start_date ? $offerLetter->proposed_start_date->format('d M Y') : 'To be confirmed' }}</h6>
                                    </div>
                                    <div>
                                        <label class="text-muted small d-block">Offer Expiry Date</label>
                                        <h6 class="fw-bold {{ $offerLetter->isExpired() ? 'text-danger' : '' }}">
                                            {{ $offerLetter->expiry_date->format('d M Y') }}
                                            @if(!$offerLetter->isExpired())
                                                <small class="text-muted fw-normal ms-1">({{ now()->diffInDays($offerLetter->expiry_date) }} days remaining)</small>
                                            @endif
                                        </h6>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-4">
                                <h6 class="fw-bold mb-2"><i class="bx bx-list-check me-2"></i>Terms & Conditions</h6>
                                <div class="p-3 bg-light rounded" style="min-height: 150px; white-space: pre-wrap;">{{ $offerLetter->terms_and_conditions ?: 'Standard organization terms and conditions apply.' }}</div>
                            </div>

                            @if($offerLetter->response_notes)
                                <div class="alert alert-info border-0 mb-0">
                                    <h6 class="fw-bold"><i class="bx bx-message-detail me-2"></i>Candidate Response Notes</h6>
                                    <p class="mb-0 small">{{ $offerLetter->response_notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Offer Workflow Timeline -->
                    <div class="card shadow">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0 fw-bold"><i class="bx bx-history me-2"></i>Offer Lifecycle History</h6>
                        </div>
                        <div class="card-body">
                            <div class="timeline-compact">
                                <div class="timeline-item-compact pb-3">
                                    <div class="d-flex">
                                        <div class="timeline-icon bg-secondary text-white">
                                            <i class="bx bx-edit"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="mb-0 fw-bold">Offer Prepared</h6>
                                            <p class="mb-0 small text-muted">By: {{ $offerLetter->preparedByUser->name ?? 'System' }} on {{ $offerLetter->created_at->format('M d, Y h:i A') }}</p>
                                        </div>
                                    </div>
                                </div>

                                @if($offerLetter->approved_at)
                                <div class="timeline-item-compact pb-3">
                                    <div class="d-flex">
                                        <div class="timeline-icon bg-success text-white">
                                            <i class="bx bx-check-double"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="mb-0 fw-bold">Offer Approved</h6>
                                            <p class="mb-0 small text-muted">By: {{ $offerLetter->approvedByUser->name ?? 'N/A' }} on {{ $offerLetter->approved_at->format('M d, Y h:i A') }}</p>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if($offerLetter->sent_at)
                                <div class="timeline-item-compact pb-3">
                                    <div class="d-flex">
                                        <div class="timeline-icon bg-info text-white">
                                            <i class="bx bx-paper-plane"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="mb-0 fw-bold">Digital Offer Sent</h6>
                                            <p class="mb-0 small text-muted">Email dispatched to candidate on {{ $offerLetter->sent_at->format('M d, Y h:i A') }}</p>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if($offerLetter->responded_at)
                                <div class="timeline-item-compact">
                                    <div class="d-flex">
                                        <div class="timeline-icon {{ $offerLetter->status === 'accepted' ? 'bg-success' : 'bg-danger' }} text-white">
                                            <i class="bx {{ $offerLetter->status === 'accepted' ? 'bx-badge-check' : 'bx-x-circle' }}"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="mb-0 fw-bold">Candidate {{ ucfirst($offerLetter->status) }}</h6>
                                            <p class="mb-0 small text-muted">Response received on {{ $offerLetter->responded_at->format('M d, Y h:i A') }}</p>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Sidebar -->
                <div class="col-lg-4">
                    <!-- Candidate Quick Profile -->
                    <div class="card shadow mb-4 border-top border-4 border-primary">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="avatar-lg bg-light text-primary rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center">
                                    <i class="bx bxs-user fs-1"></i>
                                </div>
                                <h5 class="fw-bold mb-0">{{ $offerLetter->applicant->full_name }}</h5>
                                <p class="text-muted small">{{ $offerLetter->applicant->email }}</p>
                            </div>
                            <hr>
                            <div class="mb-2">
                                <label class="text-muted extra-small d-block text-uppercase fw-bold">Education Level</label>
                                <h6 class="fw-bold small text-capitalize">{{ $offerLetter->applicant->normalizedProfile->education_level ?? 'N/A' }}</h6>
                            </div>
                            <div class="mb-2">
                                <label class="text-muted extra-small d-block text-uppercase fw-bold">Experience</label>
                                <h6 class="fw-bold small">{{ $offerLetter->applicant->years_of_experience }} Years</h6>
                            </div>
                            <div class="mb-0">
                                <label class="text-muted extra-small d-block text-uppercase fw-bold">Eligibility Score</label>
                                @php
                                    $score = $offerLetter->applicant->total_eligibility_score;
                                    $class = $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
                                @endphp
                                <h6 class="fw-bold small text-{{ $class }}">{{ number_format($score, 0) }}% Match</h6>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions & Links -->
                    <div class="card shadow">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0 fw-bold"><i class="bx bx-link me-2"></i>Navigation</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('hr.applicants.show', $offerLetter->applicant_id) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bx bx-user-circle me-1"></i>View Full Profile
                                </a>
                                @if($offerLetter->vacancyRequisition)
                                    <a href="{{ route('hr.vacancy-requisitions.show', $offerLetter->vacancyRequisition->hash_id) }}" class="btn btn-outline-info btn-sm">
                                        <i class="bx bx-briefcase me-1"></i>View Vacancy
                                    </a>
                                @endif
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                                    <i class="bx bx-printer me-1"></i>Print Offer Letter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals for Workflow Actions -->
    @include('hr-payroll.lifecycle.offer-letters.modals')

@endsection

@push('styles')
<style>
    .avatar-lg { width: 80px; height: 80px; }
    .extra-small { font-size: 0.65rem; }
    .timeline-compact { position: relative; padding-left: 1.5rem; }
    .timeline-compact::before {
        content: '';
        position: absolute;
        left: 7px;
        top: 5px;
        width: 2px;
        height: 100%;
        background: #e9ecef;
    }
    .timeline-item-compact { position: relative; }
    .timeline-icon {
        position: absolute;
        left: -1.5rem;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        z-index: 1;
    }
    .card { border-radius: 8px; border: none; }
    .shadow { box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    function updateOfferStatus(id, status, title, text, icon, confirmColor) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, proceed!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processing...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: `{{ url('hr-payroll/offer-letters') }}/${id}`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'PUT',
                        status: status,
                        // Maintain existing values
                        applicant_id: '{{ $offerLetter->applicant_id }}',
                        offered_salary: '{{ $offerLetter->offered_salary }}',
                        offer_date: '{{ $offerLetter->offer_date->format('Y-m-d') }}',
                        expiry_date: '{{ $offerLetter->expiry_date->format('Y-m-d') }}'
                    },
                    success: function(response) {
                        Swal.fire('Success!', response.message, 'success').then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', 'Action failed.', 'error');
                    }
                });
            }
        });
    }

    function approveOffer(id) {
        updateOfferStatus(id, 'approved', 'Approve Offer?', 'This will authorize the offer for sending.', 'question', '#198754');
    }

    function sendOffer(id) {
        updateOfferStatus(id, 'sent', 'Send Offer to Candidate?', 'The candidate will be notified via email.', 'info', '#0dcaf0');
    }

    function acceptOffer(id) {
        updateOfferStatus(id, 'accepted', 'Mark Offer as Accepted?', 'This will change the applicant status to Hired.', 'success', '#198754');
    }

    function rejectOffer(id) {
        Swal.fire({
            title: 'Candidate Rejected Offer?',
            input: 'textarea',
            inputLabel: 'Reason for Rejection',
            inputPlaceholder: 'Enter any notes provided by the candidate...',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Confirm Rejection'
        }).then((result) => {
            if (result.isConfirmed) {
                // Similar AJAX as above but with response_notes
                $.ajax({
                    url: `{{ url('hr-payroll/offer-letters') }}/${id}`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'PUT',
                        status: 'rejected',
                        response_notes: result.value,
                        applicant_id: '{{ $offerLetter->applicant_id }}',
                        offered_salary: '{{ $offerLetter->offered_salary }}',
                        offer_date: '{{ $offerLetter->offer_date->format('Y-m-d') }}',
                        expiry_date: '{{ $offerLetter->expiry_date->format('Y-m-d') }}'
                    },
                    success: function() { window.location.reload(); }
                });
            }
        });
    }
</script>
@endpush
