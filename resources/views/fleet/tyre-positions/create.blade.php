@extends('layouts.main')

@section('title', 'Add Tyre Position - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Truck Tyre Configuration', 'url' => route('fleet.tyre-positions.index'), 'icon' => 'bx bx-grid-alt'],
            ['label' => 'Add Position', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Add Tyre Position</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.tyre-positions.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Position code</label>
                            <input type="text" name="position_code" class="form-control" value="{{ old('position_code') }}" maxlength="50" placeholder="e.g. FL, FR, R1LI">
                            @error('position_code')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Position name <span class="text-danger">*</span></label>
                            <input type="text" name="position_name" class="form-control" value="{{ old('position_name') }}" required maxlength="100" placeholder="e.g. Front Left">
                            @error('position_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sort order</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                            @error('sort_order')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-info"><i class="bx bx-save me-1"></i>Create Position</button>
                            <a href="{{ route('fleet.tyre-positions.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
