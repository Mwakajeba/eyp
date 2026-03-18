@extends('layouts.main')

@section('title', 'New Grievance')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Discipline', 'url' => '#', 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'Grievances', 'url' => route('hr.grievances.index'), 'icon' => 'bx bx-error'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus'],
            ]" />

            <h6 class="mb-0 text-uppercase"><i class="bx bx-error me-1"></i>Create Grievance</h6>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('hr.grievances.store') }}">
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
                                <label for="complaint_type" class="form-label">Complaint Type<span class="text-danger">*</span></label>
                                <select name="complaint_type" id="complaint_type" class="form-select @error('complaint_type') is-invalid @enderror" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="harassment" {{ old('complaint_type') == 'harassment' ? 'selected' : '' }}>Harassment</option>
                                    <option value="discrimination" {{ old('complaint_type') == 'discrimination' ? 'selected' : '' }}>Discrimination</option>
                                    <option value="workplace" {{ old('complaint_type') == 'workplace' ? 'selected' : '' }}>Workplace</option>
                                    <option value="salary" {{ old('complaint_type') == 'salary' ? 'selected' : '' }}>Salary</option>
                                    <option value="other" {{ old('complaint_type') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('complaint_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="priority" class="form-label">Priority<span class="text-danger">*</span></label>
                                <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                    <option value="">-- Select Priority --</option>
                                    <option value="low" {{ old('priority', 'medium') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                                @error('priority')
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

                            <div class="col-md-4 mb-3">
                                <label for="assigned_to" class="form-label">Assigned To</label>
                                <input type="number"
                                       name="assigned_to"
                                       id="assigned_to"
                                       class="form-control @error('assigned_to') is-invalid @enderror"
                                       value="{{ old('assigned_to') }}"
                                       placeholder="User ID (optional)">
                                @error('assigned_to')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Save
                            </button>
                            <a href="{{ route('hr.grievances.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

