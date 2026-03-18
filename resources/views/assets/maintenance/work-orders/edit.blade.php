@extends('layouts.main')

@section('title', 'Edit Work Order')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Maintenance', 'url' => route('assets.maintenance.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Work Orders', 'url' => route('assets.maintenance.work-orders.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Work Order: {{ $workOrder->wo_number }}</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.maintenance.work-orders.update', $encodedId) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Asset <span class="text-danger">*</span></label>
                            <select name="asset_id" class="form-select select2-single" required>
                                <option value="">Select Asset</option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" {{ old('asset_id', $workOrder->asset_id) == $asset->id ? 'selected' : '' }}>
                                        {{ $asset->name }} ({{ $asset->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('asset_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                            <select name="maintenance_type_id" class="form-select select2-single" required>
                                <option value="">Select Type</option>
                                @foreach($maintenanceTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('maintenance_type_id', $workOrder->maintenance_type_id) == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }} ({{ ucfirst($type->type) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('maintenance_type_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maintenance Type Category <span class="text-danger">*</span></label>
                            <select name="maintenance_type" class="form-select" required>
                                <option value="preventive" {{ old('maintenance_type', $workOrder->maintenance_type) == 'preventive' ? 'selected' : '' }}>Preventive</option>
                                <option value="corrective" {{ old('maintenance_type', $workOrder->maintenance_type) == 'corrective' ? 'selected' : '' }}>Corrective</option>
                                <option value="major_overhaul" {{ old('maintenance_type', $workOrder->maintenance_type) == 'major_overhaul' ? 'selected' : '' }}>Major Overhaul</option>
                            </select>
                            @error('maintenance_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Execution Type <span class="text-danger">*</span></label>
                            <select name="execution_type" id="execution_type" class="form-select" required>
                                <option value="in_house" {{ old('execution_type', $workOrder->execution_type) == 'in_house' ? 'selected' : '' }}>In-House</option>
                                <option value="external_vendor" {{ old('execution_type', $workOrder->execution_type) == 'external_vendor' ? 'selected' : '' }}>External Vendor</option>
                                <option value="mixed" {{ old('execution_type', $workOrder->execution_type) == 'mixed' ? 'selected' : '' }}>Mixed</option>
                            </select>
                            @error('execution_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" id="vendor_field" style="display: {{ in_array($workOrder->execution_type, ['external_vendor', 'mixed']) ? 'block' : 'none' }};">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" class="form-select select2-single">
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ old('vendor_id', $workOrder->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                        {{ $vendor->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vendor_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" id="technician_field" style="display: {{ in_array($workOrder->execution_type, ['in_house', 'mixed']) ? 'block' : 'none' }};">
                            <label class="form-label">Assigned Technician</label>
                            <select name="assigned_technician_id" class="form-select select2-single">
                                <option value="">Select Technician</option>
                                @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}" {{ old('assigned_technician_id', $workOrder->assigned_technician_id) == $tech->id ? 'selected' : '' }}>
                                        {{ $tech->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_technician_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estimated Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="estimated_start_date" class="form-control" value="{{ old('estimated_start_date', $workOrder->estimated_start_date->format('Y-m-d')) }}" required>
                            @error('estimated_start_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estimated Completion Date <span class="text-danger">*</span></label>
                            <input type="date" name="estimated_completion_date" class="form-control" value="{{ old('estimated_completion_date', $workOrder->estimated_completion_date->format('Y-m-d')) }}" required>
                            @error('estimated_completion_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estimated Labor Cost (TZS)</label>
                            <input type="number" step="0.01" min="0" name="estimated_labor_cost" class="form-control" value="{{ old('estimated_labor_cost', $workOrder->estimated_labor_cost) }}">
                            @error('estimated_labor_cost')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estimated Material Cost (TZS)</label>
                            <input type="number" step="0.01" min="0" name="estimated_material_cost" class="form-control" value="{{ old('estimated_material_cost', $workOrder->estimated_material_cost) }}">
                            @error('estimated_material_cost')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estimated Other Cost (TZS)</label>
                            <input type="number" step="0.01" min="0" name="estimated_other_cost" class="form-control" value="{{ old('estimated_other_cost', $workOrder->estimated_other_cost) }}">
                            @error('estimated_other_cost')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estimated Downtime (Hours)</label>
                            <input type="number" min="0" name="estimated_downtime_hours" class="form-control" value="{{ old('estimated_downtime_hours', $workOrder->estimated_downtime_hours) }}">
                            @error('estimated_downtime_hours')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Work Description</label>
                            <textarea name="work_description" class="form-control" rows="3">{{ old('work_description', $workOrder->work_description) }}</textarea>
                            @error('work_description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $workOrder->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Work Order
                        </button>
                        <a href="{{ route('assets.maintenance.work-orders.show', $encodedId) }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
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
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Show/hide fields based on execution type
    $('#execution_type').on('change', function() {
        const type = $(this).val();
        if (type === 'external_vendor') {
            $('#vendor_field').show();
            $('#technician_field').hide();
        } else if (type === 'in_house') {
            $('#vendor_field').hide();
            $('#technician_field').show();
        } else if (type === 'mixed') {
            $('#vendor_field').show();
            $('#technician_field').show();
        }
    });
});
</script>
@endpush

