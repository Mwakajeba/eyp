@extends('layouts.main')

@section('title', 'New Disciplinary Case')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Discipline', 'url' => '#', 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'Disciplinary Cases', 'url' => route('hr.disciplinary-cases.index'), 'icon' => 'bx bx-file'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus'],
            ]" />

            <h6 class="mb-0 text-uppercase"><i class="bx bx-file me-1"></i>Create Disciplinary Case</h6>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('hr.disciplinary-cases.store') }}">
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
                                <label for="case_category" class="form-label">Case Category<span class="text-danger">*</span></label>
                                <select name="case_category" id="case_category" class="form-select @error('case_category') is-invalid @enderror" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="misconduct" {{ old('case_category') == 'misconduct' ? 'selected' : '' }}>Misconduct</option>
                                    <option value="absenteeism" {{ old('case_category') == 'absenteeism' ? 'selected' : '' }}>Absenteeism</option>
                                    <option value="performance" {{ old('case_category') == 'performance' ? 'selected' : '' }}>Performance</option>
                                </select>
                                @error('case_category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="incident_date" class="form-label">Incident Date<span class="text-danger">*</span></label>
                                <input type="date"
                                       name="incident_date"
                                       id="incident_date"
                                       class="form-control @error('incident_date') is-invalid @enderror"
                                       value="{{ old('incident_date') }}"
                                       required>
                                @error('incident_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description<span class="text-danger">*</span></label>
                            <textarea name="description"
                                      id="description"
                                      rows="4"
                                      class="form-control @error('description') is-invalid @enderror"
                                      required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Status<span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="">-- Select Status --</option>
                                    <option value="open" {{ old('status', 'open') == 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="investigating" {{ old('status') == 'investigating' ? 'selected' : '' }}>Investigating</option>
                                    <option value="resolved" {{ old('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="closed" {{ old('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Save
                            </button>
                            <a href="{{ route('hr.disciplinary-cases.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

