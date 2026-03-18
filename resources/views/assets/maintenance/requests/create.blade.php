@extends('layouts.main')

@section('title', 'Create Maintenance Request')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Maintenance', 'url' => route('assets.maintenance.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Requests', 'url' => route('assets.maintenance.requests.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Create Maintenance Request</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.maintenance.requests.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Asset <span class="text-danger">*</span></label>
                            <select name="asset_id" id="asset_id" class="form-select select2-single" required>
                                <option value="">Select Asset</option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" {{ (old('asset_id') == $asset->id || $selectedAssetId == $asset->id) ? 'selected' : '' }}>
                                        {{ $asset->name }} ({{ $asset->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('asset_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                            <select name="maintenance_type_id" id="maintenance_type_id" class="form-select select2-single" required>
                                <option value="">Select Type</option>
                                @foreach($maintenanceTypes as $type)
                                    @php
                                        $triggerValue = $type->type === 'major_overhaul' ? 'planned_improvement' : $type->type;
                                    @endphp
                                    <option value="{{ $type->id }}" data-trigger-type="{{ $triggerValue }}" {{ old('maintenance_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }} ({{ ucfirst(str_replace('_', ' ', $type->type)) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('maintenance_type_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Trigger Type <span class="text-danger">*</span></label>
                            <select name="trigger_type" id="trigger_type" class="form-select" required>
                                <option value="">Select</option>
                                <option value="preventive" {{ old('trigger_type') == 'preventive' ? 'selected' : '' }}>Preventive</option>
                                <option value="corrective" {{ old('trigger_type') == 'corrective' ? 'selected' : '' }}>Corrective</option>
                                <option value="planned_improvement" {{ old('trigger_type') == 'planned_improvement' ? 'selected' : '' }}>Planned Improvement</option>
                            </select>
                            @error('trigger_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>
                                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                            @error('priority')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Requested Date <span class="text-danger">*</span></label>
                            <input type="date" name="requested_date" class="form-control" value="{{ old('requested_date', $defaultRequestedDate ?? now()->format('Y-m-d')) }}" required>
                            @error('requested_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Preferred Start Date</label>
                            <input type="date" name="preferred_start_date" class="form-control" value="{{ old('preferred_start_date') }}">
                            <div class="form-text">When would you like the maintenance to start?</div>
                            @error('preferred_start_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" required>{{ old('description') }}</textarea>
                            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Issue Details</label>
                            <textarea name="issue_details" class="form-control" rows="3">{{ old('issue_details') }}</textarea>
                            <div class="form-text">Provide detailed information about the issue or maintenance needed</div>
                            @error('issue_details')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Attachments</label>
                            <input type="file" name="attachments[]" class="form-control" multiple accept="image/*,.pdf">
                            <div class="form-text">Upload images or documents related to this request (max 5MB each)</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Submit Request
                        </button>
                        <a href="{{ route('assets.maintenance.requests.index') }}" class="btn btn-secondary">
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

    var $maintenanceType = $('#maintenance_type_id');
    var $triggerType = $('#trigger_type');

    function setTriggerTypeFromMaintenanceType() {
        var option = $maintenanceType.find('option:selected');
        var triggerValue = option.data('trigger-type');
        if (triggerValue) {
            $triggerType.val(triggerValue);
        }
    }

    $maintenanceType.on('change', setTriggerTypeFromMaintenanceType);

    // Default Trigger Type from selected Maintenance Type on load
    setTriggerTypeFromMaintenanceType();
});
</script>
@endpush

