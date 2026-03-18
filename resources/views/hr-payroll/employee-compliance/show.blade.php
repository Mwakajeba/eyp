@extends('layouts.main')

@section('title', 'Compliance Record Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumbs -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Employee Compliance', 'url' => route('hr.employee-compliance.index'), 'icon' => 'bx bx-check-circle'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">
                    <i class="bx bx-check-circle me-2"></i>Compliance Record Details
                </h5>
                <p class="text-muted mb-0">View and manage employee compliance information</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('hr.employee-compliance.edit', $employeeCompliance->id) }}" class="btn btn-primary">
                    <i class="bx bx-edit me-1"></i>Edit Record
                </a>
                <a href="{{ route('hr.employee-compliance.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to List
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Main Information Cards -->
            <div class="col-lg-8">
                <!-- Employee & Compliance Type Card -->
                <div class="card border-top border-0 border-4 border-primary mb-3">
                    <div class="card-header bg-transparent border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-3">
                                <div class="avatar-title bg-primary bg-opacity-10 rounded">
                                    <i class="bx bx-user text-primary fs-4"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-0">Employee Information</h6>
                                <small class="text-muted">Employee details and compliance type</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <!-- Employee -->
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-xs">
                                            <div class="avatar-title bg-light rounded-circle">
                                                <i class="bx bx-user text-primary"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <label class="form-label text-muted mb-1 small">Employee</label>
                                        <h6 class="mb-1">
                                            <a href="{{ route('hr.employees.show', $employeeCompliance->employee_id) }}" 
                                               class="text-decoration-none text-dark">
                                                {{ $employeeCompliance->employee->full_name }}
                                            </a>
                                        </h6>
                                        <p class="text-muted mb-0 small">
                                            <i class="bx bx-id-card me-1"></i>{{ $employeeCompliance->employee->employee_number }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Compliance Type -->
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-xs">
                                            <div class="avatar-title bg-light rounded-circle">
                                                <i class="bx bx-file text-info"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <label class="form-label text-muted mb-1 small">Compliance Type</label>
                                        <div class="mb-1">
                                            <span class="badge bg-info fs-6 px-3 py-2">
                                                {{ strtoupper($employeeCompliance->compliance_type) }}
                                            </span>
                                        </div>
                                        <p class="text-muted mb-0 small">
                                            @if($employeeCompliance->compliance_type == 'paye')
                                                <i class="bx bx-info-circle me-1"></i>Pay As You Earn - Tax Identification Number
                                            @elseif($employeeCompliance->compliance_type == 'pension')
                                                <i class="bx bx-info-circle me-1"></i>Pension - Social Security Fund
                                            @elseif($employeeCompliance->compliance_type == 'nhif')
                                                <i class="bx bx-info-circle me-1"></i>National Health Insurance Fund
                                            @elseif($employeeCompliance->compliance_type == 'wcf')
                                                <i class="bx bx-info-circle me-1"></i>Workers Compensation Fund
                                            @elseif($employeeCompliance->compliance_type == 'sdl')
                                                <i class="bx bx-info-circle me-1"></i>Skills Development Levy
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compliance Details Card -->
                <div class="card border-top border-0 border-4 border-success mb-3">
                    <div class="card-header bg-transparent border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-3">
                                <div class="avatar-title bg-success bg-opacity-10 rounded">
                                    <i class="bx bx-detail text-success fs-4"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-0">Compliance Details</h6>
                                <small class="text-muted">Registration number and verification information</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <!-- Compliance Number -->
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-xs">
                                            <div class="avatar-title bg-light rounded-circle">
                                                <i class="bx bx-barcode text-primary"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <label class="form-label text-muted mb-1 small">Compliance Number</label>
                                        @if($employeeCompliance->compliance_number)
                                            <h6 class="mb-0 text-dark">{{ $employeeCompliance->compliance_number }}</h6>
                                        @else
                                            <p class="mb-0 text-muted fst-italic">
                                                <i class="bx bx-minus-circle me-1"></i>Not provided
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Last Verified -->
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-xs">
                                            <div class="avatar-title bg-light rounded-circle">
                                                <i class="bx bx-check-circle text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <label class="form-label text-muted mb-1 small">Last Verified</label>
                                        @if($employeeCompliance->last_verified_at)
                                            <h6 class="mb-1 text-dark">
                                                {{ $employeeCompliance->last_verified_at->format('d M Y') }}
                                            </h6>
                                            <p class="text-muted mb-0 small">
                                                <i class="bx bx-time me-1" style="font-size: 0.875rem;"></i>{{ $employeeCompliance->last_verified_at->format('h:i A') }}
                                            </p>
                                        @else
                                            <p class="mb-0 text-muted fst-italic">
                                                <i class="bx bx-x-circle me-1" style="font-size: 0.875rem;"></i>Never verified
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status & Validity Card -->
                <div class="card border-top border-0 border-4 border-{{ $employeeCompliance->status_badge_color == 'success' ? 'success' : ($employeeCompliance->status_badge_color == 'warning' ? 'warning' : 'danger') }} mb-3">
                    <div class="card-header bg-transparent border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-3">
                                <div class="avatar-title bg-{{ $employeeCompliance->status_badge_color }} bg-opacity-10 rounded">
                                    <i class="bx bx-{{ $employeeCompliance->isValid() ? 'check-circle' : 'x-circle' }} text-{{ $employeeCompliance->status_badge_color }} fs-4"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-0">Status & Validity</h6>
                                <small class="text-muted">Current compliance status and expiry information</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <!-- Status -->
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-xs">
                                            <div class="avatar-title bg-{{ $employeeCompliance->status_badge_color }} bg-opacity-10 rounded-circle">
                                                <i class="bx bx-{{ $employeeCompliance->isValid() ? 'check' : 'x' }} text-{{ $employeeCompliance->status_badge_color }}"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <label class="form-label text-muted mb-1 small">Current Status</label>
                                        <div class="mb-2">
                                            @php
                                                $isValid = $employeeCompliance->isValid();
                                                $badgeColor = $employeeCompliance->status_badge_color;
                                            @endphp
                                            <span class="badge bg-{{ $badgeColor }} fs-6 px-3 py-2">
                                                <i class="bx bx-{{ $isValid ? 'check' : 'x' }}-circle me-1"></i>
                                                {{ $isValid ? 'Valid & Active' : 'Invalid or Expired' }}
                                            </span>
                                        </div>
                                        @if(!$isValid)
                                            <p class="text-danger mb-0 small">
                                                @if($employeeCompliance->expiry_date && $employeeCompliance->expiry_date < now())
                                                    <i class="bx bx-calendar-x me-1" style="font-size: 0.875rem;"></i>Expired on {{ $employeeCompliance->expiry_date->format('d M Y') }}
                                                @elseif(!$employeeCompliance->is_valid)
                                                    <i class="bx bx-error me-1" style="font-size: 0.875rem;"></i>Marked as invalid
                                                @endif
                                            </p>
                                        @else
                                            <p class="text-success mb-0 small">
                                                <i class="bx bx-check me-1" style="font-size: 0.875rem;"></i>Compliance is active and valid
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Expiry Date -->
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-xs">
                                            <div class="avatar-title bg-light rounded-circle">
                                                <i class="bx bx-calendar text-warning"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <label class="form-label text-muted mb-1 small">Expiry Date</label>
                                        @if($employeeCompliance->expiry_date)
                                            <h6 class="mb-1 text-dark">
                                                {{ $employeeCompliance->expiry_date->format('d M Y') }}
                                            </h6>
                                            @php
                                                // Calculate days remaining: expiry_date - now
                                                // Positive = future date, Negative = past date
                                                $daysRemaining = round(now()->diffInDays($employeeCompliance->expiry_date, false));
                                            @endphp
                                            @if($employeeCompliance->expiry_date < now())
                                                <span class="badge bg-danger">
                                                    <i class="bx bx-error me-1" style="font-size: 0.875rem;"></i>Expired {{ abs($daysRemaining) }} {{ abs($daysRemaining) == 1 ? 'day' : 'days' }} ago
                                                </span>
                                            @elseif($employeeCompliance->expiry_date->isToday())
                                                <span class="badge bg-danger">
                                                    <i class="bx bx-error me-1" style="font-size: 0.875rem;"></i>Expires Today
                                                </span>
                                            @elseif($daysRemaining <= 30)
                                                <span class="badge bg-warning">
                                                    <i class="bx bx-time me-1" style="font-size: 0.875rem;"></i>Expiring in {{ $daysRemaining }} {{ $daysRemaining == 1 ? 'day' : 'days' }}
                                                </span>
                                            @else
                                                <span class="badge bg-success">
                                                    <i class="bx bx-check me-1" style="font-size: 0.875rem;"></i>Valid for {{ $daysRemaining }} more {{ $daysRemaining == 1 ? 'day' : 'days' }}
                                                </span>
                                            @endif
                                        @else
                                            <p class="mb-0 text-muted fst-italic">
                                                <i class="bx bx-infinite me-1" style="font-size: 0.875rem;"></i>No expiry date (Permanent)
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Record History Card -->
                <div class="card mb-3">
                    <div class="card-header bg-transparent border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-3">
                                <div class="avatar-title bg-secondary bg-opacity-10 rounded">
                                    <i class="bx bx-time text-secondary fs-4"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-0">Record History</h6>
                                <small class="text-muted">Creation and modification timestamps</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-xs">
                                            <div class="avatar-title bg-light rounded-circle">
                                                <i class="bx bx-plus-circle text-primary"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <label class="form-label text-muted mb-1 small">Created At</label>
                                        <h6 class="mb-1 text-dark">
                                            {{ $employeeCompliance->created_at->format('d M Y') }}
                                        </h6>
                                        <p class="text-muted mb-0 small">
                                            <i class="bx bx-time me-1"></i>{{ $employeeCompliance->created_at->format('h:i A') }}
                                            <span class="ms-2">({{ $employeeCompliance->created_at->diffForHumans() }})</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-xs">
                                            <div class="avatar-title bg-light rounded-circle">
                                                <i class="bx bx-edit text-info"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <label class="form-label text-muted mb-1 small">Last Updated</label>
                                        <h6 class="mb-1 text-dark">
                                            {{ $employeeCompliance->updated_at->format('d M Y') }}
                                        </h6>
                                        <p class="text-muted mb-0 small">
                                            <i class="bx bx-time me-1"></i>{{ $employeeCompliance->updated_at->format('h:i A') }}
                                            <span class="ms-2">({{ $employeeCompliance->updated_at->diffForHumans() }})</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Information -->
            <div class="col-lg-4">
                <!-- Status Overview Card -->
                <div class="card border-top border-0 border-4 border-{{ $employeeCompliance->status_badge_color }} mb-3">
                    <div class="card-body text-center p-4">
                        <div class="mb-3 position-relative">
                            @if($employeeCompliance->isValid())
                                <div class="status-icon-wrapper mx-auto mb-3">
                                    <div class="status-icon-oval bg-success">
                                        <i class="bx bx-check-circle text-white"></i>
                                    </div>
                                </div>
                            @else
                                <div class="status-icon-wrapper mx-auto mb-3">
                                    <div class="status-icon-oval bg-danger">
                                        <i class="bx bx-x-circle text-white"></i>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <span class="badge bg-{{ $employeeCompliance->status_badge_color }} fs-6 px-4 py-2 rounded-pill">
                                {{ $employeeCompliance->isValid() ? 'Compliant' : 'Non-Compliant' }}
                            </span>
                        </div>
                        <p class="text-muted mb-0 small lh-base">
                            @if($employeeCompliance->isValid())
                                This compliance record is valid and the employee can be included in payroll processing.
                            @else
                                This compliance record is invalid or expired. Please update it to include the employee in payroll.
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="card mb-3">
                    <div class="card-header bg-transparent border-bottom">
                        <h6 class="mb-0">
                            <i class="bx bx-cog me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('hr.employee-compliance.edit', $employeeCompliance->id) }}" class="btn btn-outline-primary">
                                <i class="bx bx-edit me-1"></i>Edit Record
                            </a>
                            <a href="{{ route('hr.employees.show', $employeeCompliance->employee_id) }}" class="btn btn-outline-info">
                                <i class="bx bx-user me-1"></i>View Employee Profile
                            </a>
                            <button type="button" class="btn btn-outline-danger delete-btn" data-id="{{ $employeeCompliance->id }}">
                                <i class="bx bx-trash me-1"></i>Delete Record
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Payroll Impact Card -->
                <div class="card border-top border-0 border-4 border-{{ $employeeCompliance->isValid() ? 'success' : 'danger' }}">
                    <div class="card-header bg-transparent border-bottom">
                        <h6 class="mb-0">
                            <i class="bx bx-money me-2"></i>Payroll Impact
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-{{ $employeeCompliance->isValid() ? 'success' : 'danger' }} border-0 mb-0">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <i class="bx bx-{{ $employeeCompliance->isValid() ? 'check' : 'x' }}-circle fs-4"></i>
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <strong>
                                        {{ $employeeCompliance->isValid() ? 'Can be included' : 'Cannot be included' }}
                                    </strong>
                                    <p class="mb-0 small mt-1">
                                        {{ $employeeCompliance->isValid() 
                                            ? 'This employee meets all compliance requirements and can be processed in payroll.' 
                                            : 'This employee must have valid compliance records to be included in payroll processing.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
        transition: all 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .card-header {
        padding: 1rem 1.25rem;
    }

    .avatar-sm {
        width: 2.5rem;
        height: 2.5rem;
    }

    .avatar-lg {
        width: 5rem;
        height: 5rem;
    }

    .avatar-xs {
        width: 2rem;
        height: 2rem;
    }

    .avatar-title {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }

    .border-primary {
        border-color: #0d6efd !important;
    }

    .border-success {
        border-color: #198754 !important;
    }

    .border-warning {
        border-color: #ffc107 !important;
    }

    .border-danger {
        border-color: #dc3545 !important;
    }

    .form-label {
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }

    .badge {
        font-weight: 500;
    }

    .status-icon-wrapper {
        width: 120px;
        height: 80px;
        position: relative;
    }

    .status-icon-oval {
        width: 100%;
        height: 100%;
        border-radius: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .status-icon-oval i {
        font-size: 2.5rem;
        position: relative;
        z-index: 2;
    }

    .status-icon-oval::after {
        content: '';
        position: absolute;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.3);
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1;
    }

    /* Ensure icons are visible */
    .avatar-title i,
    .status-icon-oval i,
    .badge i,
    i[class^="bx"] {
        display: inline-block !important;
        line-height: 1;
        vertical-align: middle;
        font-size: inherit;
    }

    /* Fix icon sizing */
    .avatar-xs .avatar-title i {
        font-size: 1rem !important;
    }

    .avatar-sm .avatar-title i {
        font-size: 1.25rem !important;
    }

    .avatar-lg .avatar-title i {
        font-size: 2rem !important;
    }

    /* Ensure badge icons are visible */
    .badge i {
        font-size: 0.875rem !important;
        margin-right: 0.25rem;
        display: inline-block !important;
    }

    /* Ensure small text icons are visible */
    .small i,
    small i {
        font-size: 0.875rem !important;
        display: inline-block !important;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Delete confirmation
    $(document).on('click', '.delete-btn', function() {
        const complianceId = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("hr.employee-compliance.index") }}/' + complianceId,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire(
                            'Deleted!',
                            response.message || 'Compliance record has been deleted.',
                            'success'
                        ).then(() => {
                            window.location.href = '{{ route("hr.employee-compliance.index") }}';
                        });
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'Error!',
                            xhr.responseJSON?.message || 'Failed to delete compliance record.',
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>
@endpush
