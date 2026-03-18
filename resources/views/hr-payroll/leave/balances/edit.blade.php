@extends('layouts.main')

@section('title', 'Adjust Leave Balance')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Leave Management', 'url' => route('hr.leave.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Leave Balances', 'url' => route('hr.leave.balances.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => $employee->full_name, 'url' => route('hr.leave.balances.show', $employee->id), 'icon' => 'bx bx-user'],
            ['label' => 'Adjust Balance', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">ADJUST LEAVE BALANCE</h6>
        <hr />

        <form action="{{ route('hr.leave.balances.update', $employee->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Adjustment Form -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-edit me-2"></i>Balance Adjustment</h5>
                        </div>
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <h6 class="alert-heading">Please fix the following errors:</h6>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <!-- Employee Info -->
                            <div class="mb-4 p-3 bg-light rounded">
                                <h6>Employee: <strong>{{ $employee->full_name }}</strong></h6>
                                <p class="mb-0 text-muted">{{ $employee->employee_number }} | {{ $employee->department->name ?? 'N/A' }}</p>
                            </div>

                            <!-- Leave Type -->
                            <div class="mb-3">
                                <label for="leave_type_id" class="form-label">Leave Type <span class="text-danger">*</span></label>
                                <select name="leave_type_id" id="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" required>
                                    <option value="">Select Leave Type</option>
                                    @foreach($balances as $balanceInfo)
                                        <option value="{{ $balanceInfo['leave_type']->id }}"
                                                data-available="{{ $balanceInfo['available'] }}"
                                                {{ old('leave_type_id') == $balanceInfo['leave_type']->id ? 'selected' : '' }}>
                                            {{ $balanceInfo['leave_type']->name }}
                                            (Available: {{ number_format($balanceInfo['available'], 2) }} days)
                                        </option>
                                    @endforeach
                                </select>
                                @error('leave_type_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Adjustment Days -->
                            <div class="mb-3">
                                <label for="adjustment_days" class="form-label">
                                    Adjustment Days <span class="text-danger">*</span>
                                    <small class="text-muted">(Use positive number to add, negative to deduct)</small>
                                </label>
                                <input type="number"
                                       name="adjustment_days"
                                       id="adjustment_days"
                                       step="0.5"
                                       class="form-control @error('adjustment_days') is-invalid @enderror"
                                       value="{{ old('adjustment_days') }}"
                                       placeholder="e.g., 5 or -2.5"
                                       required>
                                @error('adjustment_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="bx bx-info-circle"></i>
                                    Examples: Enter <strong>5</strong> to add 5 days, or <strong>-3</strong> to deduct 3 days
                                </div>
                            </div>

                            <!-- Reason -->
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Adjustment</label>
                                <textarea name="reason"
                                          id="reason"
                                          rows="3"
                                          class="form-control @error('reason') is-invalid @enderror"
                                          placeholder="Explain why this adjustment is being made...">{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save"></i> Save Adjustment
                                </button>
                                <a href="{{ route('hr.leave.balances.show', $employee->id) }}" class="btn btn-secondary">
                                    <i class="bx bx-x"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Balances Sidebar -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-bar-chart me-2"></i>Current Balances</h5>
                        </div>
                        <div class="card-body">
                            @foreach($balances as $balanceInfo)
                            <div class="mb-3 p-3 border rounded">
                                <h6>{{ $balanceInfo['leave_type']->name }}</h6>
                                <div class="small">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Available:</span>
                                        <strong class="text-{{ $balanceInfo['available'] > 0 ? 'success' : 'danger' }}">
                                            {{ number_format($balanceInfo['available'], 2) }}
                                        </strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Taken:</span>
                                        <span>{{ number_format($balanceInfo['balance']->taken_days, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Pending:</span>
                                        <span>{{ number_format($balanceInfo['balance']->pending_hold_days, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Adjusted:</span>
                                        <span>{{ number_format($balanceInfo['balance']->adjusted_days, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Warning Notice -->
                    <div class="card mt-3 border-warning">
                        <div class="card-body">
                            <div class="alert alert-warning mb-0">
                                <h6 class="alert-heading">
                                    <i class="bx bx-error me-2"></i>Important
                                </h6>
                                <p class="small mb-0">
                                    Balance adjustments are permanent and will be recorded in the system.
                                    Please ensure the adjustment is accurate before saving.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

