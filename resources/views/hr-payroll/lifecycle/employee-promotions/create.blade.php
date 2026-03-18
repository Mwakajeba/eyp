@extends('layouts.main')

@section('title', 'Create Employee Promotion')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Promotions', 'url' => '#', 'icon' => 'bx bx-trending-up'],
                ['label' => 'Employee Promotions', 'url' => route('hr.employee-promotions.index'), 'icon' => 'bx bx-file'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-trending-up me-1"></i>Create Employee Promotion</h6>
                <a href="{{ route('hr.employee-promotions.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr.employee-promotions.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="employee_id" class="form-label">Employee <span class="text-danger">*</span></label>
                                <select name="employee_id" id="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ old('employee_id', $employeeId) == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->full_name }} ({{ $employee->employee_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="from_job_grade_id" class="form-label">From Job Grade</label>
                                <select name="from_job_grade_id" id="from_job_grade_id" class="form-select @error('from_job_grade_id') is-invalid @enderror">
                                    <option value="">Select Job Grade</option>
                                    @foreach($jobGrades as $jobGrade)
                                        <option value="{{ $jobGrade->id }}" {{ old('from_job_grade_id') == $jobGrade->id ? 'selected' : '' }}>
                                            {{ $jobGrade->grade_code }} - {{ $jobGrade->grade_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('from_job_grade_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="to_job_grade_id" class="form-label">To Job Grade <span class="text-danger">*</span></label>
                                <select name="to_job_grade_id" id="to_job_grade_id" class="form-select @error('to_job_grade_id') is-invalid @enderror" required>
                                    <option value="">Select Job Grade</option>
                                    @foreach($jobGrades as $jobGrade)
                                        <option value="{{ $jobGrade->id }}" {{ old('to_job_grade_id') == $jobGrade->id ? 'selected' : '' }}>
                                            {{ $jobGrade->grade_code }} - {{ $jobGrade->grade_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('to_job_grade_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="from_position_id" class="form-label">From Position</label>
                                <select name="from_position_id" id="from_position_id" class="form-select @error('from_position_id') is-invalid @enderror">
                                    <option value="">Select Position</option>
                                    @foreach($positions as $position)
                                        <option value="{{ $position->id }}" {{ old('from_position_id') == $position->id ? 'selected' : '' }}>
                                            {{ $position->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('from_position_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="to_position_id" class="form-label">To Position</label>
                                <select name="to_position_id" id="to_position_id" class="form-select @error('to_position_id') is-invalid @enderror">
                                    <option value="">Select Position</option>
                                    @foreach($positions as $position)
                                        <option value="{{ $position->id }}" {{ old('to_position_id') == $position->id ? 'selected' : '' }}>
                                            {{ $position->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('to_position_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="promotion_date" class="form-label">Promotion Date <span class="text-danger">*</span></label>
                                <input type="date" name="promotion_date" id="promotion_date" class="form-control @error('promotion_date') is-invalid @enderror" value="{{ old('promotion_date') }}" required>
                                @error('promotion_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="salary_adjustment_amount" class="form-label">Salary Adjustment Amount</label>
                                <input type="number" name="salary_adjustment_amount" id="salary_adjustment_amount" class="form-control @error('salary_adjustment_amount') is-invalid @enderror" value="{{ old('salary_adjustment_amount') }}" step="0.01" min="0">
                                @error('salary_adjustment_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="reason" class="form-label">Reason</label>
                                <textarea name="reason" id="reason" class="form-control @error('reason') is-invalid @enderror" rows="3">{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('hr.employee-promotions.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Create Promotion
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

