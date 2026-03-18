@extends('layouts.main')

@section('title', 'Edit Applicant')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Applicants', 'url' => route('hr.applicants.index'), 'icon' => 'bx bx-user-plus'],
                ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-edit me-1"></i>Edit Applicant</h6>
                <a href="{{ route('hr.applicants.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    @if($applicant->submission_source === 'portal')
                        <div class="alert alert-info border-0 bg-light-info alert-dismissible fade show">
                            <div class="d-flex align-items-center">
                                <div class="font-35 text-info"><i class='bx bx-info-circle'></i></div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-info">Electronic Submission Detected</h6>
                                    <div class="text-dark small">This application was submitted via the Job Portal. To maintain data integrity and audit compliance, candidate-provided details (Personal Info, Qualifications, Experience) cannot be modified. You may only update the <strong>Application Status</strong>.</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('hr.applicants.update', $applicant->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="vacancy_requisition_id" class="form-label">Vacancy Requisition</label>
                                <select name="vacancy_requisition_id" id="vacancy_requisition_id" class="form-select @error('vacancy_requisition_id') is-invalid @enderror" {{ $applicant->submission_source === 'portal' ? 'disabled' : '' }}>
                                    <option value="">Select Vacancy</option>
                                    @foreach($vacancies as $vacancy)
                                        <option value="{{ $vacancy->id }}" {{ old('vacancy_requisition_id', $applicant->vacancy_requisition_id) == $vacancy->id ? 'selected' : '' }}>
                                            {{ $vacancy->job_title }} ({{ $vacancy->requisition_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('vacancy_requisition_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="applied" {{ old('status', $applicant->status) == 'applied' ? 'selected' : '' }}>Applied</option>
                                    <option value="screening" {{ old('status', $applicant->status) == 'screening' ? 'selected' : '' }}>Screening</option>
                                    <option value="interview" {{ old('status', $applicant->status) == 'interview' ? 'selected' : '' }}>Interview</option>
                                    <option value="offered" {{ old('status', $applicant->status) == 'offered' ? 'selected' : '' }}>Offered</option>
                                    <option value="hired" {{ old('status', $applicant->status) == 'hired' ? 'selected' : '' }}>Hired</option>
                                    <option value="rejected" {{ old('status', $applicant->status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    <option value="withdrawn" {{ old('status', $applicant->status) == 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $applicant->first_name) }}" required {{ $applicant->submission_source === 'portal' ? 'readonly' : '' }}>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" id="middle_name" class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name', $applicant->middle_name) }}" {{ $applicant->submission_source === 'portal' ? 'readonly' : '' }}>
                                @error('middle_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $applicant->last_name) }}" required {{ $applicant->submission_source === 'portal' ? 'readonly' : '' }}>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $applicant->email) }}" required {{ $applicant->submission_source === 'portal' ? 'readonly' : '' }}>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <input type="text" name="phone_number" id="phone_number" class="form-control @error('phone_number') is-invalid @enderror" value="{{ old('phone_number', $applicant->phone_number) }}" {{ $applicant->submission_source === 'portal' ? 'readonly' : '' }}>
                                @error('phone_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $applicant->date_of_birth?->format('Y-m-d')) }}" {{ $applicant->submission_source === 'portal' ? 'readonly' : '' }}>
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror" {{ $applicant->submission_source === 'portal' ? 'disabled' : '' }}>
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender', $applicant->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $applicant->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender', $applicant->gender) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="2" {{ $applicant->submission_source === 'portal' ? 'readonly' : '' }}>{{ old('address', $applicant->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="qualification" class="form-label">Qualification</label>
                                <input type="text" name="qualification" id="qualification" class="form-control @error('qualification') is-invalid @enderror" value="{{ old('qualification', $applicant->qualification) }}" {{ $applicant->submission_source === 'portal' ? 'readonly' : '' }}>
                                @error('qualification')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="years_of_experience" class="form-label">Years of Experience</label>
                                <input type="number" name="years_of_experience" id="years_of_experience" class="form-control @error('years_of_experience') is-invalid @enderror" value="{{ old('years_of_experience', $applicant->years_of_experience) }}" min="0" {{ $applicant->submission_source === 'portal' ? 'readonly' : '' }}>
                                @error('years_of_experience')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="cover_letter" class="form-label">Cover Letter</label>
                                <textarea name="cover_letter" id="cover_letter" class="form-control @error('cover_letter') is-invalid @enderror" rows="4" {{ $applicant->submission_source === 'portal' ? 'readonly' : '' }}>{{ old('cover_letter', $applicant->cover_letter) }}</textarea>
                                @error('cover_letter')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="resume" class="form-label">Resume (PDF/DOC)</label>
                                <input type="file" name="resume" id="resume" class="form-control @error('resume') is-invalid @enderror" accept=".pdf,.doc,.docx" {{ $applicant->submission_source === 'portal' ? 'disabled' : '' }}>
                                @if($applicant->resume_path)
                                    <small class="text-muted">Current: <a href="{{ Storage::url($applicant->resume_path) }}" target="_blank">View Resume</a></small>
                                @endif
                                @error('resume')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cv" class="form-label">CV (PDF/DOC)</label>
                                <input type="file" name="cv" id="cv" class="form-control @error('cv') is-invalid @enderror" accept=".pdf,.doc,.docx" {{ $applicant->submission_source === 'portal' ? 'disabled' : '' }}>
                                @if($applicant->cv_path)
                                    <small class="text-muted">Current: <a href="{{ Storage::url($applicant->cv_path) }}" target="_blank">View CV</a></small>
                                @endif
                                @error('cv')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('hr.applicants.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Update Applicant
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

