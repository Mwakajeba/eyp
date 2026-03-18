@extends('layouts.main')

@section('title', 'Edit Exit')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Discipline', 'url' => '#', 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'Exit Management', 'url' => route('hr.exits.index'), 'icon' => 'bx bx-log-out'],
                ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit'],
            ]" />

            <h6 class="mb-0 text-uppercase">
                <i class="bx bx-log-out me-1"></i>Edit Exit - {{ $exit->exit_number }}
            </h6>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('hr.exits.update', $exit->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="employee_id" class="form-label">Employee<span class="text-danger">*</span></label>
                                <select name="employee_id" id="employee_id" class="form-select select2-single @error('employee_id') is-invalid @enderror" required data-placeholder="-- Select Employee --">
                                    <option value="">-- Select Employee --</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            {{ old('employee_id', $exit->employee_id) == $employee->id ? 'selected' : '' }}>
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
                                    <option value="resignation" {{ old('exit_type', $exit->exit_type) == 'resignation' ? 'selected' : '' }}>Resignation</option>
                                    <option value="termination" {{ old('exit_type', $exit->exit_type) == 'termination' ? 'selected' : '' }}>Termination</option>
                                    <option value="retirement" {{ old('exit_type', $exit->exit_type) == 'retirement' ? 'selected' : '' }}>Retirement</option>
                                    <option value="contract_expiry" {{ old('exit_type', $exit->exit_type) == 'contract_expiry' ? 'selected' : '' }}>Contract Expiry</option>
                                    <option value="redundancy" {{ old('exit_type', $exit->exit_type) == 'redundancy' ? 'selected' : '' }}>Redundancy</option>
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
                                       value="{{ old('effective_date', optional($exit->effective_date)->format('Y-m-d')) }}"
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
                                       value="{{ old('resignation_date', optional($exit->resignation_date)->format('Y-m-d')) }}">
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
                                       value="{{ old('notice_period_days', $exit->notice_period_days) }}"
                                       min="0">
                                @error('notice_period_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="clearance_status" class="form-label">Clearance Status<span class="text-danger">*</span></label>
                                <select name="clearance_status" id="clearance_status" class="form-select @error('clearance_status') is-invalid @enderror" required>
                                    <option value="pending" {{ old('clearance_status', $exit->clearance_status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="in_progress" {{ old('clearance_status', $exit->clearance_status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ old('clearance_status', $exit->clearance_status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                                @error('clearance_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="final_pay_status" class="form-label">Final Pay Status<span class="text-danger">*</span></label>
                                <select name="final_pay_status" id="final_pay_status" class="form-select @error('final_pay_status') is-invalid @enderror" required>
                                    <option value="pending" {{ old('final_pay_status', $exit->final_pay_status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="calculated" {{ old('final_pay_status', $exit->final_pay_status) == 'calculated' ? 'selected' : '' }}>Calculated</option>
                                    <option value="approved" {{ old('final_pay_status', $exit->final_pay_status) == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="paid" {{ old('final_pay_status', $exit->final_pay_status) == 'paid' ? 'selected' : '' }}>Paid</option>
                                </select>
                                @error('final_pay_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="final_pay_amount" class="form-label">Final Pay Amount</label>
                                <input type="number"
                                       step="0.01"
                                       name="final_pay_amount"
                                       id="final_pay_amount"
                                       class="form-control @error('final_pay_amount') is-invalid @enderror"
                                       value="{{ old('final_pay_amount', $exit->final_pay_amount) }}">
                                @error('final_pay_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Exit Interview Conducted</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="exit_interview_conducted" name="exit_interview_conducted" value="1"
                                           {{ old('exit_interview_conducted', $exit->exit_interview_conducted) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="exit_interview_conducted">Yes</label>
                                </div>
                                @error('exit_interview_conducted')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="exit_reason" class="form-label">Exit Reason</label>
                            <textarea name="exit_reason"
                                      id="exit_reason"
                                      rows="3"
                                      class="form-control @error('exit_reason') is-invalid @enderror">{{ old('exit_reason', $exit->exit_reason) }}</textarea>
                            @error('exit_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="exit_interview_notes" class="form-label">Exit Interview Notes</label>
                            <textarea name="exit_interview_notes"
                                      id="exit_interview_notes"
                                      rows="3"
                                      class="form-control @error('exit_interview_notes') is-invalid @enderror">{{ old('exit_interview_notes', $exit->exit_interview_notes) }}</textarea>
                            @error('exit_interview_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="final_pay_notes" class="form-label">Final Pay Notes</label>
                            <textarea name="final_pay_notes"
                                      id="final_pay_notes"
                                      rows="3"
                                      class="form-control @error('final_pay_notes') is-invalid @enderror">{{ old('final_pay_notes', $exit->final_pay_notes) }}</textarea>
                            @error('final_pay_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Clearance items are managed inline via the controller; a more complex UI can be added later if needed --}}

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Update
                            </button>
                            <a href="{{ route('hr.exits.show', $exit->id) }}" class="btn btn-info">
                                <i class="bx bx-show me-1"></i>View
                            </a>
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

