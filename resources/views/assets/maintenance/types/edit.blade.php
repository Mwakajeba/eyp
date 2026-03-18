@extends('layouts.main')

@section('title', 'Edit Maintenance Type')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Maintenance', 'url' => route('assets.maintenance.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Types', 'url' => route('assets.maintenance.types.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Maintenance Type</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.maintenance.types.update', $encodedId) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $maintenanceType->code) }}" required>
                            <div class="form-text">Unique code for this maintenance type</div>
                            @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $maintenanceType->name) }}" required>
                            @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="preventive" {{ old('type', $maintenanceType->type) == 'preventive' ? 'selected' : '' }}>Preventive</option>
                                <option value="corrective" {{ old('type', $maintenanceType->type) == 'corrective' ? 'selected' : '' }}>Corrective</option>
                                <option value="major_overhaul" {{ old('type', $maintenanceType->type) == 'major_overhaul' ? 'selected' : '' }}>Major Overhaul</option>
                            </select>
                            @error('type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $maintenanceType->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $maintenanceType->description) }}</textarea>
                            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update
                        </button>
                        <a href="{{ route('assets.maintenance.types.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

