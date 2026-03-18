@extends('layouts.main')

@section('title', 'Edit Disposal Reason Code')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Disposals', 'url' => route('assets.disposals.index'), 'icon' => 'bx bx-trash'],
            ['label' => 'Reason Codes', 'url' => route('assets.disposals.reason-codes.index'), 'icon' => 'bx bx-list-ul'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Disposal Reason Code</h6>
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

                <form method="POST" action="{{ route('assets.disposals.reason-codes.update', $encodedId) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $reasonCode->code) }}" required>
                            <div class="form-text">Unique code for this reason</div>
                            @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $reasonCode->name) }}" required>
                            @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Disposal Type</label>
                            <select name="disposal_type" class="form-select">
                                <option value="">-- Any Type --</option>
                                <option value="sale" {{ old('disposal_type', $reasonCode->disposal_type) == 'sale' ? 'selected' : '' }}>Sale</option>
                                <option value="scrap" {{ old('disposal_type', $reasonCode->disposal_type) == 'scrap' ? 'selected' : '' }}>Scrap</option>
                                <option value="write_off" {{ old('disposal_type', $reasonCode->disposal_type) == 'write_off' ? 'selected' : '' }}>Write-off</option>
                                <option value="donation" {{ old('disposal_type', $reasonCode->disposal_type) == 'donation' ? 'selected' : '' }}>Donation</option>
                                <option value="loss" {{ old('disposal_type', $reasonCode->disposal_type) == 'loss' ? 'selected' : '' }}>Loss/Theft</option>
                            </select>
                            <div class="form-text">Optional: Link to specific disposal type</div>
                            @error('disposal_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $reasonCode->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $reasonCode->description) }}</textarea>
                            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Reason Code
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

