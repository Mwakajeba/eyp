@extends('layouts.main')

@section('title', 'Edit Appraisal Cycle')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Performance', 'url' => '#', 'icon' => 'bx bx-trophy'],
            ['label' => 'Appraisal Cycles', 'url' => route('hr.appraisal-cycles.index'), 'icon' => 'bx bx-calendar'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">Edit Appraisal Cycle</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.appraisal-cycles.update', $appraisalCycle->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cycle Name <span class="text-danger">*</span></label>
                            <input type="text" name="cycle_name" class="form-control @error('cycle_name') is-invalid @enderror" 
                                   value="{{ old('cycle_name', $appraisalCycle->cycle_name) }}" required />
                            @error('cycle_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Cycle Type <span class="text-danger">*</span></label>
                            <select name="cycle_type" class="form-select @error('cycle_type') is-invalid @enderror" required>
                                <option value="annual" {{ old('cycle_type', $appraisalCycle->cycle_type) == 'annual' ? 'selected' : '' }}>Annual</option>
                                <option value="semi_annual" {{ old('cycle_type', $appraisalCycle->cycle_type) == 'semi_annual' ? 'selected' : '' }}>Semi-Annual</option>
                                <option value="quarterly" {{ old('cycle_type', $appraisalCycle->cycle_type) == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="probation" {{ old('cycle_type', $appraisalCycle->cycle_type) == 'probation' ? 'selected' : '' }}>Probation</option>
                            </select>
                            @error('cycle_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" 
                                   value="{{ old('start_date', $appraisalCycle->start_date->format('Y-m-d')) }}" required />
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                                   value="{{ old('end_date', $appraisalCycle->end_date->format('Y-m-d')) }}" required />
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="draft" {{ old('status', $appraisalCycle->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="active" {{ old('status', $appraisalCycle->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="completed" {{ old('status', $appraisalCycle->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ old('status', $appraisalCycle->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Cycle
                        </button>
                        <a href="{{ route('hr.appraisal-cycles.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

