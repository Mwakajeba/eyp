@extends('layouts.main')

@section('title', 'Create Disposal Reason Code')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Disposals', 'url' => route('assets.disposals.index'), 'icon' => 'bx bx-trash'],
            ['label' => 'Reason Codes', 'url' => route('assets.disposals.reason-codes.index'), 'icon' => 'bx bx-list-ul'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Create Disposal Reason Code</h6>
            </div>
            <div class="card-body">
                @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('assets.disposals.reason-codes.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Enter a unique alphanumeric code to identify this reason code. Use a consistent format like OBS-001 (Obsolescence), DAM-001 (Damage), or RET-001 (Retirement). This code will be displayed in disposal forms and reports.
                                </small>
                            </div>
                            @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Enter a descriptive name for this reason code (e.g., "Obsolescence", "Physical Damage", "End of Useful Life"). This name will be shown to users when selecting a reason for asset disposal.
                                </small>
                            </div>
                            @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Disposal Type</label>
                            <select name="disposal_type" class="form-select">
                                <option value="">-- Any Type --</option>
                                <option value="sale" {{ old('disposal_type') == 'sale' ? 'selected' : '' }}>Sale</option>
                                <option value="scrap" {{ old('disposal_type') == 'scrap' ? 'selected' : '' }}>Scrap</option>
                                <option value="write_off" {{ old('disposal_type') == 'write_off' ? 'selected' : '' }}>Write-off</option>
                                <option value="donation" {{ old('disposal_type') == 'donation' ? 'selected' : '' }}>Donation</option>
                                <option value="loss" {{ old('disposal_type') == 'loss' ? 'selected' : '' }}>Loss/Theft</option>
                            </select>
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Optionally link this reason code to a specific disposal type. If set, this reason will only be available when creating disposals of that type. Leave blank to allow this reason for all disposal types.
                                </small>
                            </div>
                            @error('disposal_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Toggle this switch to activate or deactivate this reason code. Inactive reason codes will not appear in disposal forms but existing disposals using this code will remain unchanged.
                                </small>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Enter additional details about this reason code...">{{ old('description') }}</textarea>
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Provide additional context or guidelines for when this reason code should be used. This description helps users understand the appropriate circumstances for selecting this reason during asset disposal.
                                </small>
                            </div>
                            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Create Reason Code
                        </button>
                        <a href="{{ route('assets.disposals.reason-codes.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

