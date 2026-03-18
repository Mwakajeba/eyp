@extends('layouts.main')

@section('title', 'Create Interview Record')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Interview Records', 'url' => route('hr.interview-records.index'), 'icon' => 'bx bx-conversation'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-conversation me-1"></i>Create Interview Record</h6>
                <a href="{{ route('hr.interview-records.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr.interview-records.store') }}" method="POST">
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
                                        <option value="{{ $vacancy->id }}" {{ old('vacancy_requisition_id', $vacancyId) == $vacancy->id ? 'selected' : '' }}>
                                            {{ $vacancy->job_title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vacancy_requisition_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="interview_type" class="form-label">Interview Type <span class="text-danger">*</span></label>
                                <select name="interview_type" id="interview_type" class="form-select @error('interview_type') is-invalid @enderror" required>
                                    <option value="phone" {{ old('interview_type') == 'phone' ? 'selected' : '' }}>Phone</option>
                                    <option value="video" {{ old('interview_type') == 'video' ? 'selected' : '' }}>Video</option>
                                    <option value="in_person" {{ old('interview_type') == 'in_person' ? 'selected' : '' }}>In Person</option>
                                    <option value="panel" {{ old('interview_type') == 'panel' ? 'selected' : '' }}>Panel</option>
                                </select>
                                @error('interview_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="round_number" class="form-label">Round Number</label>
                                <input type="text" name="round_number" id="round_number" class="form-control @error('round_number') is-invalid @enderror" value="{{ old('round_number', '1') }}" placeholder="1, 2, 3, Final">
                                @error('round_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="interview_date" class="form-label">Interview Date <span class="text-danger">*</span></label>
                                <input type="date" name="interview_date" id="interview_date" class="form-control @error('interview_date') is-invalid @enderror" value="{{ old('interview_date') }}" required>
                                @error('interview_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="interview_time" class="form-label">Interview Time <span class="text-danger">*</span></label>
                                <input type="time" name="interview_time" id="interview_time" class="form-control @error('interview_time') is-invalid @enderror" value="{{ old('interview_time') }}" required>
                                @error('interview_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" name="location" id="location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location') }}" placeholder="Office address or room">
                                @error('location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="meeting_link" class="form-label">Meeting Link</label>
                                <input type="url" name="meeting_link" id="meeting_link" class="form-control @error('meeting_link') is-invalid @enderror" value="{{ old('meeting_link') }}" placeholder="https://...">
                                @error('meeting_link')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="overall_score" class="form-label">Overall Score (%)</label>
                                <input type="number" name="overall_score" id="overall_score" class="form-control @error('overall_score') is-invalid @enderror" value="{{ old('overall_score') }}" min="0" max="100" step="0.01" placeholder="Auto-calculated from criteria" readonly>
                                @error('overall_score')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-4">
                                <div class="card bg-light border-0">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="bx bx-list-check me-2"></i>Structured Scoring Criteria</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered bg-white" id="scoringTable">
                                                <thead>
                                                    <tr class="table-light">
                                                        <th style="width: 30%">Criteria</th>
                                                        <th style="width: 15%">Score (0-10)</th>
                                                        <th style="width: 15%">Weight (%)</th>
                                                        <th>Comments</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $defaultCriteria = [
                                                            'Technical Skills' => 40,
                                                            'Communication' => 20,
                                                            'Cultural Fit' => 20,
                                                            'Problem Solving' => 20
                                                        ];
                                                    @endphp
                                                    @foreach($defaultCriteria as $name => $weight)
                                                        <tr>
                                                            <td>
                                                                <input type="text" name="detailed_scores[{{ $loop->index }}][name]" class="form-control form-control-sm" value="{{ $name }}" readonly>
                                                            </td>
                                                            <td>
                                                                <input type="number" name="detailed_scores[{{ $loop->index }}][score]" class="form-control form-control-sm criteria-score" min="0" max="10" step="0.5" value="0">
                                                            </td>
                                                            <td>
                                                                <input type="number" name="detailed_scores[{{ $loop->index }}][weight]" class="form-control form-control-sm criteria-weight" min="0" max="100" value="{{ $weight }}">
                                                            </td>
                                                            <td>
                                                                <input type="text" name="detailed_scores[{{ $loop->index }}][comment]" class="form-control form-control-sm" placeholder="Optional notes...">
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-light fw-bold">
                                                        <td class="text-end">Weighted Total:</td>
                                                        <td id="weightedTotalDisplay">0.0</td>
                                                        <td id="weightSumDisplay">100%</td>
                                                        <td class="text-muted small">Scale: 0-10 (Normalized to % for Overall Score)</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="recommendation" class="form-label">Recommendation</label>
                                <select name="recommendation" id="recommendation" class="form-select @error('recommendation') is-invalid @enderror">
                                    <option value="">Select Recommendation</option>
                                    <option value="hire" {{ old('recommendation') == 'hire' ? 'selected' : '' }}>Hire</option>
                                    <option value="maybe" {{ old('recommendation') == 'maybe' ? 'selected' : '' }}>Maybe</option>
                                    <option value="reject" {{ old('recommendation') == 'reject' ? 'selected' : '' }}>Reject</option>
                                    <option value="next_round" {{ old('recommendation') == 'next_round' ? 'selected' : '' }}>Next Round</option>
                                </select>
                                @error('recommendation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="feedback" class="form-label">Feedback</label>
                                <textarea name="feedback" id="feedback" class="form-control @error('feedback') is-invalid @enderror" rows="4">{{ old('feedback') }}</textarea>
                                @error('feedback')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="strengths" class="form-label">Strengths</label>
                                <textarea name="strengths" id="strengths" class="form-control @error('strengths') is-invalid @enderror" rows="3">{{ old('strengths') }}</textarea>
                                @error('strengths')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="weaknesses" class="form-label">Weaknesses</label>
                                <textarea name="weaknesses" id="weaknesses" class="form-control @error('weaknesses') is-invalid @enderror" rows="3">{{ old('weaknesses') }}</textarea>
                                @error('weaknesses')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('hr.interview-records.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Create Interview Record
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
    function calculateOverallScore() {
        let totalWeightedScore = 0;
        let totalWeight = 0;

        $('.criteria-score').each(function(index) {
            const score = parseFloat($(this).val()) || 0;
            const weight = parseFloat($('.criteria-weight').eq(index).val()) || 0;
            
            totalWeightedScore += (score * weight);
            totalWeight += weight;
        });

        // Update display
        $('#weightSumDisplay').text(totalWeight + '%');
        
        if (totalWeight > 0) {
            // Normalize 0-10 score to percentage (score * 10)
            const overall = (totalWeightedScore / totalWeight) * 10;
            $('#overall_score').val(overall.toFixed(2));
            $('#weightedTotalDisplay').text((totalWeightedScore / totalWeight).toFixed(1));
        } else {
            $('#overall_score').val(0);
            $('#weightedTotalDisplay').text('0.0');
        }
    }

    $(document).on('input', '.criteria-score, .criteria-weight', function() {
        calculateOverallScore();
    });

    // Initial calculation
    calculateOverallScore();
});
</script>
@endpush
