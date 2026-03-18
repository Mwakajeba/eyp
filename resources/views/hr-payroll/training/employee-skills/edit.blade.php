@extends('layouts.main')

@section('title', 'Edit Employee Skill')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Training', 'url' => '#', 'icon' => 'bx bx-book'],
            ['label' => 'Employee Skills', 'url' => route('hr.employee-skills.index'), 'icon' => 'bx bx-certification'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">Edit Employee Skill</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.employee-skills.update', $employeeSkill->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select select2-single @error('employee_id') is-invalid @enderror" required>
                                <option value="">-- Select Employee --</option>
                                @foreach($employees ?? [] as $employee)
                                    <option value="{{ $employee->id }}" {{ old('employee_id', $employeeSkill->employee_id) == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }} ({{ $employee->employee_number }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Skill Name <span class="text-danger">*</span></label>
                            <input type="text" name="skill_name" class="form-control @error('skill_name') is-invalid @enderror" 
                                   value="{{ old('skill_name', $employeeSkill->skill_name) }}" required />
                            @error('skill_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Skill Level</label>
                            <select name="skill_level" class="form-select @error('skill_level') is-invalid @enderror">
                                <option value="">-- Select Level --</option>
                                <option value="beginner" {{ old('skill_level', $employeeSkill->skill_level) == 'beginner' ? 'selected' : '' }}>Beginner</option>
                                <option value="intermediate" {{ old('skill_level', $employeeSkill->skill_level) == 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                                <option value="advanced" {{ old('skill_level', $employeeSkill->skill_level) == 'advanced' ? 'selected' : '' }}>Advanced</option>
                                <option value="expert" {{ old('skill_level', $employeeSkill->skill_level) == 'expert' ? 'selected' : '' }}>Expert</option>
                            </select>
                            @error('skill_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Certification Name</label>
                            <input type="text" name="certification_name" class="form-control @error('certification_name') is-invalid @enderror" 
                                   value="{{ old('certification_name', $employeeSkill->certification_name) }}" />
                            @error('certification_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Certification Expiry</label>
                            <input type="date" name="certification_expiry" class="form-control @error('certification_expiry') is-invalid @enderror" 
                                   value="{{ old('certification_expiry', $employeeSkill->certification_expiry ? $employeeSkill->certification_expiry->format('Y-m-d') : '') }}" />
                            @error('certification_expiry')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Verified By</label>
                            <select name="verified_by" class="form-select select2-single @error('verified_by') is-invalid @enderror">
                                <option value="">-- Select Verifier --</option>
                                @foreach(\App\Models\User::where('company_id', current_company_id())->get() as $user)
                                    <option value="{{ $user->id }}" {{ old('verified_by', $employeeSkill->verified_by) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('verified_by')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Skill
                        </button>
                        <a href="{{ route('hr.employee-skills.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

