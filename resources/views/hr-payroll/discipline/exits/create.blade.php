@extends('layouts.main')

@section('title', 'New Exit')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Discipline', 'url' => '#', 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'Exit Management', 'url' => route('hr.exits.index'), 'icon' => 'bx bx-log-out'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus'],
            ]" />

            <h6 class="mb-0 text-uppercase"><i class="bx bx-log-out me-1"></i>Create Exit Record</h6>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('hr.exits.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="employee_id" class="form-label">Employee<span class="text-danger">*</span></label>
                                <select name="employee_id" id="employee_id" class="form-select select2-single @error('employee_id') is-invalid @enderror" required data-placeholder="-- Select Employee --">
                                    <option value="">-- Select Employee --</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            {{ old('employee_id', $employeeId) == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->employee_number }} - {{ $employee->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="exit_type" class="form-label">Exit Type<span class="text-danger">*</span></label>
                                <select name="exit_type" id="exit_type" class="form-select @error('exit_type') is-invalid @enderror" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="resignation" {{ old('exit_type') == 'resignation' ? 'selected' : '' }}>Resignation</option>
                                    <option value="termination" {{ old('exit_type') == 'termination' ? 'selected' : '' }}>Termination</option>
                                    <option value="retirement" {{ old('exit_type') == 'retirement' ? 'selected' : '' }}>Retirement</option>
                                    <option value="contract_expiry" {{ old('exit_type') == 'contract_expiry' ? 'selected' : '' }}>Contract Expiry</option>
                                    <option value="redundancy" {{ old('exit_type') == 'redundancy' ? 'selected' : '' }}>Redundancy</option>
                                </select>
                                @error('exit_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="effective_date" class="form-label">Effective Date<span class="text-danger">*</span></label>
                                <input type="date"
                                       name="effective_date"
                                       id="effective_date"
                                       class="form-control @error('effective_date') is-invalid @enderror"
                                       value="{{ old('effective_date') }}"
                                       required>
                                @error('effective_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="resignation_date" class="form-label">Resignation Date</label>
                                <input type="date"
                                       name="resignation_date"
                                       id="resignation_date"
                                       class="form-control @error('resignation_date') is-invalid @enderror"
                                       value="{{ old('resignation_date') }}">
                                @error('resignation_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Required when exit type is resignation.</small>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="notice_period_days" class="form-label">Notice Period (Days)</label>
                                <input type="number"
                                       name="notice_period_days"
                                       id="notice_period_days"
                                       class="form-control @error('notice_period_days') is-invalid @enderror"
                                       value="{{ old('notice_period_days') }}"
                                       min="0">
                                @error('notice_period_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="exit_reason" class="form-label">Exit Reason</label>
                            <textarea name="exit_reason"
                                      id="exit_reason"
                                      rows="3"
                                      class="form-control @error('exit_reason') is-invalid @enderror">{{ old('exit_reason') }}</textarea>
                            @error('exit_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Save
                            </button>
                            <a href="{{ route('hr.exits.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

