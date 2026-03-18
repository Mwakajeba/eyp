@extends('layouts.main')

@section('title', 'Edit Confirmation Request')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Confirmation', 'url' => '#', 'icon' => 'bx bx-check-circle'],
                ['label' => 'Confirmation Requests', 'url' => route('hr.confirmation-requests.index'), 'icon' => 'bx bx-file'],
                ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-edit me-1"></i>Edit Confirmation Request</h6>
                <a href="{{ route('hr.confirmation-requests.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr.confirmation-requests.update', $confirmationRequest->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="probation_start_date" class="form-label">Probation Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="probation_start_date" id="probation_start_date" class="form-control @error('probation_start_date') is-invalid @enderror" value="{{ old('probation_start_date', $confirmationRequest->probation_start_date->format('Y-m-d')) }}" required>
                                @error('probation_start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="probation_end_date" class="form-label">Probation End Date <span class="text-danger">*</span></label>
                                <input type="date" name="probation_end_date" id="probation_end_date" class="form-control @error('probation_end_date') is-invalid @enderror" value="{{ old('probation_end_date', $confirmationRequest->probation_end_date->format('Y-m-d')) }}" required>
                                @error('probation_end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="review_date" class="form-label">Review Date</label>
                                <input type="date" name="review_date" id="review_date" class="form-control @error('review_date') is-invalid @enderror" value="{{ old('review_date', $confirmationRequest->review_date?->format('Y-m-d')) }}">
                                @error('review_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="pending" {{ old('status', $confirmationRequest->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="manager_review" {{ old('status', $confirmationRequest->status) == 'manager_review' ? 'selected' : '' }}>Manager Review</option>
                                    <option value="hr_review" {{ old('status', $confirmationRequest->status) == 'hr_review' ? 'selected' : '' }}>HR Review</option>
                                    <option value="approved" {{ old('status', $confirmationRequest->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ old('status', $confirmationRequest->status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    <option value="extended" {{ old('status', $confirmationRequest->status) == 'extended' ? 'selected' : '' }}>Extended</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="recommendation_type" class="form-label">Recommendation Type</label>
                                <select name="recommendation_type" id="recommendation_type" class="form-select @error('recommendation_type') is-invalid @enderror">
                                    <option value="">Select Recommendation</option>
                                    <option value="confirm" {{ old('recommendation_type', $confirmationRequest->recommendation_type) == 'confirm' ? 'selected' : '' }}>Confirm</option>
                                    <option value="extend" {{ old('recommendation_type', $confirmationRequest->recommendation_type) == 'extend' ? 'selected' : '' }}>Extend</option>
                                    <option value="terminate" {{ old('recommendation_type', $confirmationRequest->recommendation_type) == 'terminate' ? 'selected' : '' }}>Terminate</option>
                                </select>
                                @error('recommendation_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3" id="extension_months_div" style="display: {{ old('recommendation_type', $confirmationRequest->recommendation_type) == 'extend' ? 'block' : 'none' }};">
                                <label for="extension_months" class="form-label">Extension Months</label>
                                <input type="number" name="extension_months" id="extension_months" class="form-control @error('extension_months') is-invalid @enderror" value="{{ old('extension_months', $confirmationRequest->extension_months) }}" min="1">
                                @error('extension_months')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="salary_adjustment_amount" class="form-label">Salary Adjustment Amount</label>
                                <input type="number" name="salary_adjustment_amount" id="salary_adjustment_amount" class="form-control @error('salary_adjustment_amount') is-invalid @enderror" value="{{ old('salary_adjustment_amount', $confirmationRequest->salary_adjustment_amount) }}" step="0.01" min="0">
                                @error('salary_adjustment_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="confirmation_bonus" class="form-label">Confirmation Bonus</label>
                                <input type="number" name="confirmation_bonus" id="confirmation_bonus" class="form-control @error('confirmation_bonus') is-invalid @enderror" value="{{ old('confirmation_bonus', $confirmationRequest->confirmation_bonus) }}" step="0.01" min="0">
                                @error('confirmation_bonus')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="performance_summary" class="form-label">Performance Summary</label>
                                <textarea name="performance_summary" id="performance_summary" class="form-control @error('performance_summary') is-invalid @enderror" rows="4">{{ old('performance_summary', $confirmationRequest->performance_summary) }}</textarea>
                                @error('performance_summary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="recommendation" class="form-label">Recommendation</label>
                                <textarea name="recommendation" id="recommendation" class="form-control @error('recommendation') is-invalid @enderror" rows="4">{{ old('recommendation', $confirmationRequest->recommendation) }}</textarea>
                                @error('recommendation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('hr.confirmation-requests.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Update Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('#recommendation_type').on('change', function() {
        if ($(this).val() === 'extend') {
            $('#extension_months_div').show();
            $('#extension_months').prop('required', true);
        } else {
            $('#extension_months_div').hide();
            $('#extension_months').prop('required', false);
        }
    });

    $('#probation_start_date').on('change', function() {
        if ($(this).val()) {
            $('#probation_end_date').attr('min', $(this).val());
        }
    });
});
</script>
@endpush

