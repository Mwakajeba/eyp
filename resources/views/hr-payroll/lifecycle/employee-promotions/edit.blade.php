@extends('layouts.main')

@section('title', 'Edit Employee Promotion')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Promotions', 'url' => '#', 'icon' => 'bx bx-trending-up'],
                ['label' => 'Employee Promotions', 'url' => route('hr.employee-promotions.index'), 'icon' => 'bx bx-file'],
                ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-edit me-1"></i>Edit Employee Promotion</h6>
                <a href="{{ route('hr.employee-promotions.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr.employee-promotions.update', $employeePromotion->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="pending" {{ old('status', $employeePromotion->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ old('status', $employeePromotion->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ old('status', $employeePromotion->status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    <option value="completed" {{ old('status', $employeePromotion->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="effective_date" class="form-label">Effective Date</label>
                                <input type="date" name="effective_date" id="effective_date" class="form-control @error('effective_date') is-invalid @enderror" value="{{ old('effective_date', $employeePromotion->effective_date?->format('Y-m-d')) }}">
                                @error('effective_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="salary_adjustment_amount" class="form-label">Salary Adjustment Amount</label>
                                <input type="number" name="salary_adjustment_amount" id="salary_adjustment_amount" class="form-control @error('salary_adjustment_amount') is-invalid @enderror" value="{{ old('salary_adjustment_amount', $employeePromotion->salary_adjustment_amount) }}" step="0.01" min="0">
                                @error('salary_adjustment_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="reason" class="form-label">Reason</label>
                                <textarea name="reason" id="reason" class="form-control @error('reason') is-invalid @enderror" rows="3">{{ old('reason', $employeePromotion->reason) }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $employeePromotion->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('hr.employee-promotions.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Update Promotion
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

