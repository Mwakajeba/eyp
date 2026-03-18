@extends('layouts.main')

@section('title', 'Edit Grievance')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Discipline', 'url' => '#', 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'Grievances', 'url' => route('hr.grievances.index'), 'icon' => 'bx bx-error'],
                ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit'],
            ]" />

            <h6 class="mb-0 text-uppercase">
                <i class="bx bx-error me-1"></i>Edit Grievance - {{ $grievance->grievance_number }}
            </h6>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('hr.grievances.update', $grievance->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="employee_id" class="form-label">Employee<span class="text-danger">*</span></label>
                                <select name="employee_id" id="employee_id" class="form-select select2-single @error('employee_id') is-invalid @enderror" required data-placeholder="-- Select Employee --">
                                    <option value="">-- Select Employee --</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            {{ old('employee_id', $grievance->employee_id) == $employee->id ? 'selected' : '' }}>
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
                                    <option value="harassment" {{ old('complaint_type', $grievance->complaint_type) == 'harassment' ? 'selected' : '' }}>Harassment</option>
                                    <option value="discrimination" {{ old('complaint_type', $grievance->complaint_type) == 'discrimination' ? 'selected' : '' }}>Discrimination</option>
                                    <option value="workplace" {{ old('complaint_type', $grievance->complaint_type) == 'workplace' ? 'selected' : '' }}>Workplace</option>
                                    <option value="salary" {{ old('complaint_type', $grievance->complaint_type) == 'salary' ? 'selected' : '' }}>Salary</option>
                                    <option value="other" {{ old('complaint_type', $grievance->complaint_type) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('complaint_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="priority" class="form-label">Priority<span class="text-danger">*</span></label>
                                <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                    <option value="">-- Select Priority --</option>
                                    <option value="low" {{ old('priority', $grievance->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', $grievance->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority', $grievance->priority) == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ old('priority', $grievance->priority) == 'urgent' ? 'selected' : '' }}>Urgent</option>
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
                                      required>{{ old('description', $grievance->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Status<span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="">-- Select Status --</option>
                                    <option value="open" {{ old('status', $grievance->status) == 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="investigating" {{ old('status', $grievance->status) == 'investigating' ? 'selected' : '' }}>Investigating</option>
                                    <option value="resolved" {{ old('status', $grievance->status) == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="closed" {{ old('status', $grievance->status) == 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="assigned_to" class="form-label">Assigned To (User ID)</label>
                                <input type="number"
                                       name="assigned_to"
                                       id="assigned_to"
                                       class="form-control @error('assigned_to') is-invalid @enderror"
                                       value="{{ old('assigned_to', $grievance->assigned_to) }}"
                                       placeholder="User ID (optional)">
                                @error('assigned_to')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="investigation_notes" class="form-label">Investigation Notes</label>
                            <textarea name="investigation_notes"
                                      id="investigation_notes"
                                      rows="3"
                                      class="form-control @error('investigation_notes') is-invalid @enderror">{{ old('investigation_notes', $grievance->investigation_notes) }}</textarea>
                            @error('investigation_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="resolution" class="form-label">Resolution</label>
                            <textarea name="resolution"
                                      id="resolution"
                                      rows="3"
                                      class="form-control @error('resolution') is-invalid @enderror">{{ old('resolution', $grievance->resolution) }}</textarea>
                            @error('resolution')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Update
                            </button>
                            <a href="{{ route('hr.grievances.show', $grievance->id) }}" class="btn btn-info">
                                <i class="bx bx-show me-1"></i>View
                            </a>
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

