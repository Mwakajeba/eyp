@extends('layouts.main')

@section('title', 'Edit Training Program')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Training', 'url' => '#', 'icon' => 'bx bx-book'],
            ['label' => 'Programs', 'url' => route('hr.training-programs.index'), 'icon' => 'bx bx-book-open'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">Edit Training Program</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.training-programs.update', $trainingProgram->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Program Code <span class="text-danger">*</span></label>
                            <input type="text" name="program_code" class="form-control @error('program_code') is-invalid @enderror" 
                                   value="{{ old('program_code', $trainingProgram->program_code) }}" required />
                            @error('program_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Program Name <span class="text-danger">*</span></label>
                            <input type="text" name="program_name" class="form-control @error('program_name') is-invalid @enderror" 
                                   value="{{ old('program_name', $trainingProgram->program_name) }}" required />
                            @error('program_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Provider</label>
                            <select name="provider" class="form-select @error('provider') is-invalid @enderror">
                                <option value="">-- Select Provider --</option>
                                <option value="internal" {{ old('provider', $trainingProgram->provider) == 'internal' ? 'selected' : '' }}>Internal</option>
                                <option value="external" {{ old('provider', $trainingProgram->provider) == 'external' ? 'selected' : '' }}>External</option>
                            </select>
                            @error('provider')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Funding Source</label>
                            <select name="funding_source" class="form-select @error('funding_source') is-invalid @enderror">
                                <option value="">-- Select Source --</option>
                                <option value="sdl" {{ old('funding_source', $trainingProgram->funding_source) == 'sdl' ? 'selected' : '' }}>SDL</option>
                                <option value="internal" {{ old('funding_source', $trainingProgram->funding_source) == 'internal' ? 'selected' : '' }}>Internal</option>
                                <option value="donor" {{ old('funding_source', $trainingProgram->funding_source) == 'donor' ? 'selected' : '' }}>Donor</option>
                            </select>
                            @error('funding_source')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Cost</label>
                            <input type="number" name="cost" step="0.01" min="0" 
                                   class="form-control @error('cost') is-invalid @enderror" 
                                   value="{{ old('cost', $trainingProgram->cost) }}" />
                            @error('cost')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Duration (Days)</label>
                            <input type="number" name="duration_days" min="1" 
                                   class="form-control @error('duration_days') is-invalid @enderror" 
                                   value="{{ old('duration_days', $trainingProgram->duration_days) }}" />
                            @error('duration_days')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                       value="1" {{ old('is_active', $trainingProgram->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="4">{{ old('description', $trainingProgram->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Program
                        </button>
                        <a href="{{ route('hr.training-programs.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

