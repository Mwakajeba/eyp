@extends('layouts.main')

@section('title', 'Create Offer Letter')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Offer Letters', 'url' => route('hr.offer-letters.index'), 'icon' => 'bx bx-envelope'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-envelope me-1"></i>Create Offer Letter</h6>
                <a href="{{ route('hr.offer-letters.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr.offer-letters.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="applicant_id" class="form-label">Applicant <span class="text-danger">*</span></label>
                                <select name="applicant_id" id="applicant_id" class="form-select @error('applicant_id') is-invalid @enderror" required>
                                    <option value="">Select Applicant</option>
                                    @foreach($applicants as $applicant)
                                        <option value="{{ $applicant->id }}" {{ old('applicant_id', $applicantId) == $applicant->id ? 'selected' : '' }}>
                                            {{ $applicant->full_name }} ({{ $applicant->application_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('applicant_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="vacancy_requisition_id" class="form-label">Vacancy Requisition</label>
                                <select name="vacancy_requisition_id" id="vacancy_requisition_id" class="form-select @error('vacancy_requisition_id') is-invalid @enderror">
                                    <option value="">Select Vacancy</option>
                                    @foreach($vacancies as $vacancy)
                                        <option value="{{ $vacancy->id }}" 
                                            {{ old('vacancy_requisition_id', $vacancyId) == $vacancy->id ? 'selected' : '' }}
                                            data-salary-min="{{ $vacancy->budgeted_salary_min }}"
                                            data-salary-max="{{ $vacancy->budgeted_salary_max }}"
                                        >
                                            {{ $vacancy->job_title }} ({{ $vacancy->requisition_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('vacancy_requisition_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="offered_salary" class="form-label">Offered Salary <span class="text-danger">*</span></label>
                                <input type="number" name="offered_salary" id="offered_salary" class="form-control @error('offered_salary') is-invalid @enderror" value="{{ old('offered_salary') }}" step="0.01" min="0" required>
                                @error('offered_salary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="pending_approval" {{ old('status') == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                                    <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="offer_date" class="form-label">Offer Date <span class="text-danger">*</span></label>
                                <input type="date" name="offer_date" id="offer_date" class="form-control @error('offer_date') is-invalid @enderror" value="{{ old('offer_date') }}" required>
                                @error('offer_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="expiry_date" class="form-label">Expiry Date <span class="text-danger">*</span></label>
                                <input type="date" name="expiry_date" id="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror" value="{{ old('expiry_date') }}" required>
                                @error('expiry_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="proposed_start_date" class="form-label">Proposed Start Date</label>
                                <input type="date" name="proposed_start_date" id="proposed_start_date" class="form-control @error('proposed_start_date') is-invalid @enderror" value="{{ old('proposed_start_date') }}">
                                @error('proposed_start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="terms_and_conditions" class="form-label">Terms and Conditions</label>
                                <textarea name="terms_and_conditions" id="terms_and_conditions" class="form-control @error('terms_and_conditions') is-invalid @enderror" rows="6">{{ old('terms_and_conditions') }}</textarea>
                                @error('terms_and_conditions')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('hr.offer-letters.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Create Offer Letter
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
    $('#offer_date').on('change', function() {
        if ($(this).val()) {
            $('#expiry_date').attr('min', $(this).val());
        }
    });

    $('#vacancy_requisition_id').on('change', function() {
        const selected = $(this).find('option:selected');
        const min = selected.data('salary-min');
        const max = selected.data('salary-max');
        
        if (max > 0) {
            $('#offered_salary').val(max);
            toastr.info(`Pre-filled with budgeted salary maximum: TZS ${parseFloat(max).toLocaleString()}`);
        } else if (min > 0) {
            $('#offered_salary').val(min);
            toastr.info(`Pre-filled with budgeted salary minimum: TZS ${parseFloat(min).toLocaleString()}`);
        }
    });
});
</script>
@endpush

