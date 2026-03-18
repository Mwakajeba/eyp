@extends('layouts.main')

@section('title', 'Edit Offer Letter')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Offer Letters', 'url' => route('hr.offer-letters.index'), 'icon' => 'bx bx-envelope'],
                ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-edit me-1"></i>Edit Offer Letter</h6>
                <a href="{{ route('hr.offer-letters.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr.offer-letters.update', $offerLetter->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="applicant_id" class="form-label">Applicant <span class="text-danger">*</span></label>
                                <select name="applicant_id" id="applicant_id" class="form-select @error('applicant_id') is-invalid @enderror" required>
                                    <option value="">Select Applicant</option>
                                    @foreach($applicants as $applicant)
                                        <option value="{{ $applicant->id }}" {{ old('applicant_id', $offerLetter->applicant_id) == $applicant->id ? 'selected' : '' }}>
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
                                        <option value="{{ $vacancy->id }}" {{ old('vacancy_requisition_id', $offerLetter->vacancy_requisition_id) == $vacancy->id ? 'selected' : '' }}>
                                            {{ $vacancy->job_title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vacancy_requisition_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="offered_salary" class="form-label">Offered Salary <span class="text-danger">*</span></label>
                                <input type="number" name="offered_salary" id="offered_salary" class="form-control @error('offered_salary') is-invalid @enderror" value="{{ old('offered_salary', $offerLetter->offered_salary) }}" step="0.01" min="0" required>
                                @error('offered_salary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="draft" {{ old('status', $offerLetter->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="pending_approval" {{ old('status', $offerLetter->status) == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                                    <option value="approved" {{ old('status', $offerLetter->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="sent" {{ old('status', $offerLetter->status) == 'sent' ? 'selected' : '' }}>Sent</option>
                                    <option value="accepted" {{ old('status', $offerLetter->status) == 'accepted' ? 'selected' : '' }}>Accepted</option>
                                    <option value="rejected" {{ old('status', $offerLetter->status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    <option value="expired" {{ old('status', $offerLetter->status) == 'expired' ? 'selected' : '' }}>Expired</option>
                                    <option value="withdrawn" {{ old('status', $offerLetter->status) == 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="offer_date" class="form-label">Offer Date <span class="text-danger">*</span></label>
                                <input type="date" name="offer_date" id="offer_date" class="form-control @error('offer_date') is-invalid @enderror" value="{{ old('offer_date', $offerLetter->offer_date->format('Y-m-d')) }}" required>
                                @error('offer_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="expiry_date" class="form-label">Expiry Date <span class="text-danger">*</span></label>
                                <input type="date" name="expiry_date" id="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror" value="{{ old('expiry_date', $offerLetter->expiry_date->format('Y-m-d')) }}" required>
                                @error('expiry_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="proposed_start_date" class="form-label">Proposed Start Date</label>
                                <input type="date" name="proposed_start_date" id="proposed_start_date" class="form-control @error('proposed_start_date') is-invalid @enderror" value="{{ old('proposed_start_date', $offerLetter->proposed_start_date?->format('Y-m-d')) }}">
                                @error('proposed_start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="terms_and_conditions" class="form-label">Terms and Conditions</label>
                                <textarea name="terms_and_conditions" id="terms_and_conditions" class="form-control @error('terms_and_conditions') is-invalid @enderror" rows="6">{{ old('terms_and_conditions', $offerLetter->terms_and_conditions) }}</textarea>
                                @error('terms_and_conditions')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if(in_array($offerLetter->status, ['accepted', 'rejected']))
                            <div class="col-md-12 mb-3">
                                <label for="response_notes" class="form-label">Response Notes</label>
                                <textarea name="response_notes" id="response_notes" class="form-control @error('response_notes') is-invalid @enderror" rows="3">{{ old('response_notes', $offerLetter->response_notes) }}</textarea>
                                @error('response_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('hr.offer-letters.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Update Offer Letter
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
});
</script>
@endpush

