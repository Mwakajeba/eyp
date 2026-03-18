@extends('layouts.main')

@section('title', 'Add New Tyre - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Tyre Master Register', 'url' => route('fleet.tyres.index'), 'icon' => 'bx bx-circle'],
            ['label' => 'Add New Tyre', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Register New Tyre</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.tyres.store') }}">
                    @csrf
                    <h6 class="text-primary mb-3"><i class="bx bx-detail me-2"></i>Tyre identity & purchase</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">DOT number (from sidewall)</label>
                            <input type="text" name="dot_number" class="form-control" value="{{ old('dot_number') }}" maxlength="100" placeholder="e.g. 4523">
                            @error('dot_number')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control" value="{{ old('brand') }}" maxlength="100" placeholder="e.g. Michelin">
                            @error('brand')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" class="form-control" value="{{ old('model') }}" maxlength="100">
                            @error('model')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tyre size</label>
                            <input type="text" name="tyre_size" class="form-control" value="{{ old('tyre_size') }}" maxlength="50" placeholder="e.g. 295/80R22.5">
                            @error('tyre_size')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <input type="text" name="supplier" class="form-control" value="{{ old('supplier') }}" maxlength="255">
                            @error('supplier')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Purchase date</label>
                            <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date') }}">
                            @error('purchase_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Purchase cost</label>
                            <input type="number" name="purchase_cost" class="form-control" value="{{ old('purchase_cost') }}" min="0" step="0.01" placeholder="0">
                            @error('purchase_cost')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <hr>
                    <h6 class="text-primary mb-3"><i class="bx bx-time me-2"></i>Warranty & lifespan</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Warranty type</label>
                            <select name="warranty_type" class="form-select select2-single">
                                <option value="">—</option>
                                <option value="distance" {{ old('warranty_type') === 'distance' ? 'selected' : '' }}>Distance (km)</option>
                                <option value="time" {{ old('warranty_type') === 'time' ? 'selected' : '' }}>Time (months)</option>
                            </select>
                            @error('warranty_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Warranty limit value (km or months)</label>
                            <input type="number" name="warranty_limit_value" class="form-control" value="{{ old('warranty_limit_value') }}" min="0" step="0.01" placeholder="e.g. 40000">
                            @error('warranty_limit_value')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expected lifespan (km)</label>
                            <input type="number" name="expected_lifespan_km" class="form-control" value="{{ old('expected_lifespan_km') }}" min="0" step="0.01" placeholder="e.g. 80000">
                            @error('expected_lifespan_km')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select select2-single" required>
                                <option value="new" {{ old('status', 'new') === 'new' ? 'selected' : '' }}>New</option>
                                <option value="in_use" {{ old('status') === 'in_use' ? 'selected' : '' }}>In use</option>
                                <option value="removed" {{ old('status') === 'removed' ? 'selected' : '' }}>Removed</option>
                                <option value="under_warranty_claim" {{ old('status') === 'under_warranty_claim' ? 'selected' : '' }}>Under warranty claim</option>
                                <option value="scrapped" {{ old('status') === 'scrapped' ? 'selected' : '' }}>Scrapped</option>
                            </select>
                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
                            @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Register Tyre</button>
                            <a href="{{ route('fleet.tyres.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-single').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Type to search...', allowClear: true, minimumResultsForSearch: 0 });
    }
});
</script>
@endpush
