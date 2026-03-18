@extends('layouts.main')

@section('title', 'Payroll Calendar Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Payroll Calendars', 'url' => route('hr.payroll-calendars.index'), 'icon' => 'bx bx-calendar'],
                ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-calendar me-2"></i>Payroll Calendar Details</h5>
                    <p class="mb-0 text-muted">{{ $payrollCalendar->period_label }}</p>
                </div>
                <div class="d-flex gap-2">
                    @if(!$payrollCalendar->is_locked)
                        <a href="{{ route('hr.payroll-calendars.edit', $payrollCalendar->id) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i>Edit
                        </a>
                    @endif
                    <a href="{{ route('hr.payroll-calendars.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Calendar Information</h6>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Period:</strong>
                                    <p class="text-muted">{{ $payrollCalendar->period_label }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Status:</strong>
                                    <p>
                                        @if($payrollCalendar->is_locked)
                                            <span class="badge bg-danger">Locked</span>
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Cut-off Date:</strong>
                                    <p class="text-muted">{{ $payrollCalendar->cut_off_date->format('d M Y') }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Pay Date:</strong>
                                    <p class="text-muted">{{ $payrollCalendar->pay_date->format('d M Y') }}</p>
                                </div>
                            </div>

                            @if($payrollCalendar->is_locked)
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>Locked By:</strong>
                                        <p class="text-muted">{{ $payrollCalendar->lockedBy ? $payrollCalendar->lockedBy->name : 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Locked At:</strong>
                                        <p class="text-muted">{{ $payrollCalendar->locked_at ? $payrollCalendar->locked_at->format('d M Y H:i') : 'N/A' }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($payrollCalendar->notes)
                                <div class="mb-3">
                                    <strong>Notes:</strong>
                                    <p class="text-muted">{{ $payrollCalendar->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Quick Actions</h6>
                            
                            @if(!$payrollCalendar->is_locked && $payrollCalendar->canBeLocked())
                                <button type="button" class="btn btn-warning w-100 mb-2" onclick="lockCalendar({{ $payrollCalendar->id }})">
                                    <i class="bx bx-lock me-1"></i>Lock Calendar
                                </button>
                            @endif

                            @if($payrollCalendar->is_locked)
                                <button type="button" class="btn btn-success w-100 mb-2" onclick="unlockCalendar({{ $payrollCalendar->id }})">
                                    <i class="bx bx-lock-open me-1"></i>Unlock Calendar
                                </button>
                            @endif

                            <a href="{{ route('hr.payroll-calendars.index') }}" class="btn btn-secondary w-100">
                                <i class="bx bx-arrow-back me-1"></i>Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    function lockCalendar(id) {
        Swal.fire({
            title: 'Lock Payroll Calendar?',
            text: "Once locked, the calendar cannot be edited. Continue?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, lock it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('hr.payroll-calendars.index') }}/" + id + "/lock",
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Locked!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        Swal.fire('Error!', response.message || 'An error occurred.', 'error');
                    }
                });
            }
        });
    }

    function unlockCalendar(id) {
        Swal.fire({
            title: 'Unlock Payroll Calendar?',
            text: "This will allow editing of the calendar. Continue?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, unlock it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('hr.payroll-calendars.index') }}/" + id + "/unlock",
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Unlocked!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        Swal.fire('Error!', response.message || 'An error occurred.', 'error');
                    }
                });
            }
        });
    }
</script>
@endpush

