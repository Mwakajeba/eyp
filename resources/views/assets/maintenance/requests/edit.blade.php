@extends('layouts.main')

@section('title', 'Edit Maintenance Request')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Maintenance', 'url' => route('assets.maintenance.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Requests', 'url' => route('assets.maintenance.requests.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Maintenance Request</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.maintenance.requests.update', $encodedId) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Asset <span class="text-danger">*</span></label>
                            <select name="asset_id" class="form-select select2-single" required>
                                <option value="">Select Asset</option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" {{ old('asset_id', $maintenanceRequest->asset_id) == $asset->id ? 'selected' : '' }}>
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
                                    <option value="{{ $type->id }}" {{ old('maintenance_type_id', $maintenanceRequest->maintenance_type_id) == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }} ({{ ucfirst($type->type) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('maintenance_type_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Trigger Type <span class="text-danger">*</span></label>
                            <select name="trigger_type" class="form-select" required>
                                <option value="preventive" {{ old('trigger_type', $maintenanceRequest->trigger_type) == 'preventive' ? 'selected' : '' }}>Preventive</option>
                                <option value="corrective" {{ old('trigger_type', $maintenanceRequest->trigger_type) == 'corrective' ? 'selected' : '' }}>Corrective</option>
                                <option value="planned_improvement" {{ old('trigger_type', $maintenanceRequest->trigger_type) == 'planned_improvement' ? 'selected' : '' }}>Planned Improvement</option>
                            </select>
                            @error('trigger_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>
                                <option value="low" {{ old('priority', $maintenanceRequest->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('priority', $maintenanceRequest->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('priority', $maintenanceRequest->priority) == 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ old('priority', $maintenanceRequest->priority) == 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                            @error('priority')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Requested Date <span class="text-danger">*</span></label>
                            <input type="date" name="requested_date" class="form-control" value="{{ old('requested_date', $maintenanceRequest->requested_date->format('Y-m-d')) }}" required>
                            @error('requested_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Preferred Start Date</label>
                            <input type="date" name="preferred_start_date" class="form-control" value="{{ old('preferred_start_date', $maintenanceRequest->preferred_start_date?->format('Y-m-d')) }}">
                            @error('preferred_start_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" required>{{ old('description', $maintenanceRequest->description) }}</textarea>
                            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Issue Details</label>
                            <textarea name="issue_details" class="form-control" rows="3">{{ old('issue_details', $maintenanceRequest->issue_details) }}</textarea>
                            @error('issue_details')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Additional Attachments</label>
                            <input type="file" name="attachments[]" class="form-control" multiple accept="image/*,.pdf">
                            <div class="form-text">Upload additional files (existing attachments are preserved)</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $maintenanceRequest->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Request
                        </button>
                        <a href="{{ route('assets.maintenance.requests.show', $encodedId) }}" class="btn btn-secondary">
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
});
</script>
@endpush

