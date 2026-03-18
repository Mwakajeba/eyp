@extends('layouts.main')

@section('title', 'Edit Asset')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.registry.index'), 'icon' => 'bx bx-clipboard'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Asset</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.registry.update', $asset->id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="asset_category_id" class="form-select select2-single" required>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}" {{ old('asset_category_id', $asset->asset_category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tax Depreciation Class (TRA)</label>
                                <select name="tax_class_id" class="form-select select2-single">
                                    <option value="">Select TRA Class</option>
                                    @foreach(($taxClasses ?? []) as $taxClass)
                                        <option value="{{ $taxClass->id }}" {{ old('tax_class_id', $asset->tax_class_id) == $taxClass->id ? 'selected' : '' }}>
                                            {{ $taxClass->class_code }} — {{ $taxClass->description }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Assign the TRA tax depreciation class for tax computation.</div>
                                @error('tax_class_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Code</label>
                                <input name="code" class="form-control" value="{{ old('code', $asset->code) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input name="name" class="form-control" value="{{ old('name', $asset->name) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', optional($asset->purchase_date)->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Capitalization Date</label>
                                <input type="date" name="capitalization_date" class="form-control" value="{{ old('capitalization_date', optional($asset->capitalization_date)->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Purchase Cost (TZS)</label>
                                <input type="number" step="0.01" min="0" name="purchase_cost" class="form-control" value="{{ old('purchase_cost', $asset->purchase_cost) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Serial Number</label>
                                <input name="serial_number" class="form-control" value="{{ old('serial_number', $asset->serial_number) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Model</label>
                                <input name="model" class="form-control" value="{{ old('model', $asset->model) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Manufacturer</label>
                                <input name="manufacturer" class="form-control" value="{{ old('manufacturer', $asset->manufacturer) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Residual Value (TZS)</label>
                                <input type="number" step="0.01" min="0" name="salvage_value" class="form-control" value="{{ old('salvage_value', $asset->salvage_value) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Current NBV (TZS)</label>
                                <input type="number" step="0.01" min="0" name="current_nbv" class="form-control" value="{{ old('current_nbv', $asset->current_nbv) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-select select2-single">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id', $asset->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Warranty (months)</label>
                                <input type="number" min="0" name="warranty_months" class="form-control" value="{{ old('warranty_months', $asset->warranty_months) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Warranty Expiry</label>
                                <input type="date" name="warranty_expiry_date" class="form-control" value="{{ old('warranty_expiry_date', $asset->warranty_expiry_date) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Insurance Policy No.</label>
                                <input name="insurance_policy_no" class="form-control" value="{{ old('insurance_policy_no', $asset->insurance_policy_no) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Insured Value (TZS)</label>
                                <input type="number" step="0.01" min="0" name="insured_value" class="form-control" value="{{ old('insured_value', $asset->insured_value) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Insurance Expiry</label>
                                <input type="date" name="insurance_expiry_date" class="form-control" value="{{ old('insurance_expiry_date', $asset->insurance_expiry_date) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Physical Location</label>
                                <input name="location" class="form-control" value="{{ old('location', $asset->location) }}" placeholder="Site / Room / Area">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Building Ref.</label>
                                <input name="building_reference" class="form-control" value="{{ old('building_reference', $asset->building_reference) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">GPS (lat, lng)</label>
                                <div class="d-flex gap-2">
                                    <input type="number" step="0.0000001" name="gps_lat" class="form-control" placeholder="Lat" value="{{ old('gps_lat', $asset->gps_lat) }}">
                                    <input type="number" step="0.0000001" name="gps_lng" class="form-control" placeholder="Lng" value="{{ old('gps_lng', $asset->gps_lng) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Asset Tag / RFID</label>
                                <input name="tag" class="form-control" value="{{ old('tag', $asset->tag) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Barcode</label>
                                <input name="barcode" class="form-control" value="{{ old('barcode', $asset->barcode) }}">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-control">{{ old('description', $asset->description) }}</textarea>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Attachments</label>
                                <input type="file" name="attachments[]" class="form-control" multiple>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('assets.registry.show', $asset->id) }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>Back</a>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Update Asset</button>
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
    // Initialize Select2 for dropdowns (matching sales invoice style)
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select',
        allowClear: true,
        width: '100%'
    });
});
</script>
@endpush

