@extends('layouts.main')

@section('title', 'Edit Interview Record')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Interview Records', 'url' => route('hr.interview-records.index'), 'icon' => 'bx bx-conversation'],
                ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-edit me-1"></i>Edit Interview Record</h6>
                <a href="{{ route('hr.interview-records.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr.interview-records.update', $interviewRecord->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="applicant_id" class="form-label">Applicant <span class="text-danger">*</span></label>
                                <select name="applicant_id" id="applicant_id" class="form-select @error('applicant_id') is-invalid @enderror" required>
                                    <option value="">Select Applicant</option>
                                    @foreach($applicants as $applicant)
                                        <option value="{{ $applicant->id }}" {{ old('applicant_id', $interviewRecord->applicant_id) == $applicant->id ? 'selected' : '' }}>
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
                                        <option value="{{ $vacancy->id }}" {{ old('vacancy_requisition_id', $interviewRecord->vacancy_requisition_id) == $vacancy->id ? 'selected' : '' }}>
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
                                    <option value="phone" {{ old('interview_type', $interviewRecord->interview_type) == 'phone' ? 'selected' : '' }}>Phone</option>
                                    <option value="video" {{ old('interview_type', $interviewRecord->interview_type) == 'video' ? 'selected' : '' }}>Video</option>
                                    <option value="in_person" {{ old('interview_type', $interviewRecord->interview_type) == 'in_person' ? 'selected' : '' }}>In Person</option>
                                    <option value="panel" {{ old('interview_type', $interviewRecord->interview_type) == 'panel' ? 'selected' : '' }}>Panel</option>
                                </select>
                                @error('interview_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="round_number" class="form-label">Round Number</label>
                                <input type="text" name="round_number" id="round_number" class="form-control @error('round_number') is-invalid @enderror" value="{{ old('round_number', $interviewRecord->round_number) }}">
                                @error('round_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="interview_date" class="form-label">Interview Date <span class="text-danger">*</span></label>
                                <input type="date" name="interview_date" id="interview_date" class="form-control @error('interview_date') is-invalid @enderror" value="{{ old('interview_date', $interviewRecord->interview_date->format('Y-m-d')) }}" required>
                                @error('interview_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="interview_time" class="form-label">Interview Time <span class="text-danger">*</span></label>
                                <input type="time" name="interview_time" id="interview_time" class="form-control @error('interview_time') is-invalid @enderror" value="{{ old('interview_time', date('H:i', strtotime($interviewRecord->interview_time))) }}" required>
                                @error('interview_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" name="location" id="location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location', $interviewRecord->location) }}">
                                @error('location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="meeting_link" class="form-label">Meeting Link</label>
                                <input type="url" name="meeting_link" id="meeting_link" class="form-control @error('meeting_link') is-invalid @enderror" value="{{ old('meeting_link', $interviewRecord->meeting_link) }}">
                                @error('meeting_link')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="overall_score" class="form-label">Overall Score</label>
                                <input type="number" name="overall_score" id="overall_score" class="form-control @error('overall_score') is-invalid @enderror" value="{{ old('overall_score', $interviewRecord->overall_score) }}" min="0" max="100" step="0.01">
                                @error('overall_score')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="recommendation" class="form-label">Recommendation</label>
                                <select name="recommendation" id="recommendation" class="form-select @error('recommendation') is-invalid @enderror">
                                    <option value="">Select Recommendation</option>
                                    <option value="hire" {{ old('recommendation', $interviewRecord->recommendation) == 'hire' ? 'selected' : '' }}>Hire</option>
                                    <option value="maybe" {{ old('recommendation', $interviewRecord->recommendation) == 'maybe' ? 'selected' : '' }}>Maybe</option>
                                    <option value="reject" {{ old('recommendation', $interviewRecord->recommendation) == 'reject' ? 'selected' : '' }}>Reject</option>
                                    <option value="next_round" {{ old('recommendation', $interviewRecord->recommendation) == 'next_round' ? 'selected' : '' }}>Next Round</option>
                                </select>
                                @error('recommendation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="feedback" class="form-label">Feedback</label>
                                <textarea name="feedback" id="feedback" class="form-control @error('feedback') is-invalid @enderror" rows="4">{{ old('feedback', $interviewRecord->feedback) }}</textarea>
                                @error('feedback')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="strengths" class="form-label">Strengths</label>
                                <textarea name="strengths" id="strengths" class="form-control @error('strengths') is-invalid @enderror" rows="3">{{ old('strengths', $interviewRecord->strengths) }}</textarea>
                                @error('strengths')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="weaknesses" class="form-label">Weaknesses</label>
                                <textarea name="weaknesses" id="weaknesses" class="form-control @error('weaknesses') is-invalid @enderror" rows="3">{{ old('weaknesses', $interviewRecord->weaknesses) }}</textarea>
                                @error('weaknesses')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('hr.interview-records.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Update Interview Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

