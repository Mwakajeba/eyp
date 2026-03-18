@extends('layouts.main')

@section('title', 'Edit Work Order - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Maintenance Work Orders', 'url' => route('fleet.maintenance.work-orders.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Edit Work Order', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-purple text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Work Order - {{ $workOrder->wo_number }}</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.maintenance.work-orders.update', $workOrder->hash_id) }}" id="work-order-form">
                    @csrf
                    @method('PUT')

                    <!-- Vehicle & Maintenance Details -->
                    <h6 class="text-purple mb-3"><i class="bx bx-car me-2"></i>Vehicle & Maintenance Details</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" value="{{ $workOrder->vehicle->name ?? 'N/A' }}" disabled>
                                <input type="hidden" name="vehicle_id" value="{{ $workOrder->vehicle_id }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                                <select name="maintenance_type" class="form-select" required>
                                    <option value="preventive" {{ old('maintenance_type', $workOrder->maintenance_type) == 'preventive' ? 'selected' : '' }}>Preventive</option>
                                    <option value="corrective" {{ old('maintenance_type', $workOrder->maintenance_type) == 'corrective' ? 'selected' : '' }}>Corrective</option>
                                    <option value="major_overhaul" {{ old('maintenance_type', $workOrder->maintenance_type) == 'major_overhaul' ? 'selected' : '' }}>Major Overhaul</option>
                                </select>
                                @error('maintenance_type')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Maintenance Category</label>
                                <select name="maintenance_category" class="form-select select2-single">
                                    <option value="">Select Category</option>
                                    @foreach($costCategories ?? [] as $cat)
                                        <option value="{{ $cat->name }}" {{ old('maintenance_category', $workOrder->maintenance_category) == $cat->name ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select from cost categories</div>
                                @error('maintenance_category')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select" required>
                                    <option value="low" {{ old('priority', $workOrder->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', $workOrder->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority', $workOrder->priority) == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ old('priority', $workOrder->priority) == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                                @error('priority')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="draft" {{ old('status', $workOrder->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="scheduled" {{ old('status', $workOrder->status) == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                    <option value="in_progress" {{ old('status', $workOrder->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="on_hold" {{ old('status', $workOrder->status) == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                                    <option value="completed" {{ old('status', $workOrder->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ old('status', $workOrder->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Work Description</label>
                                <textarea name="work_description" class="form-control" rows="3">{{ old('work_description', $workOrder->work_description) }}</textarea>
                                @error('work_description')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Execution Details -->
                    <h6 class="text-purple mb-3"><i class="bx bx-user-check me-2"></i>Execution Details</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Execution Type <span class="text-danger">*</span></label>
                                <select name="execution_type" class="form-select" id="execution_type" required>
                                    <option value="in_house" {{ old('execution_type', $workOrder->execution_type) == 'in_house' ? 'selected' : '' }}>In House</option>
                                    <option value="external_vendor" {{ old('execution_type', $workOrder->execution_type) == 'external_vendor' ? 'selected' : '' }}>External Vendor</option>
                                    <option value="mixed" {{ old('execution_type', $workOrder->execution_type) == 'mixed' ? 'selected' : '' }}>Mixed</option>
                                </select>
                                @error('execution_type')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4" id="vendor-field" style="display: {{ in_array($workOrder->execution_type, ['external_vendor', 'mixed']) ? 'block' : 'none' }};">
                            <div class="mb-3">
                                <label class="form-label">Vendor</label>
                                <select name="vendor_id" class="form-select select2-single">
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $v)
                                        <option value="{{ $v->id }}" {{ old('vendor_id', $workOrder->vendor_id) == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                                    @endforeach
                                </select>
                                @error('vendor_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4" id="technician-field" style="display: {{ in_array($workOrder->execution_type, ['in_house', 'mixed']) ? 'block' : 'none' }};">
                            <div class="mb-3">
                                <label class="form-label">Assigned Technician</label>
                                <select name="assigned_technician_id" class="form-select select2-single">
                                    <option value="">Select Technician</option>
                                    @foreach($technicians as $t)
                                        <option value="{{ $t->id }}" {{ old('assigned_technician_id', $workOrder->assigned_technician_id) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                    @endforeach
                                </select>
                                @error('assigned_technician_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Scheduling -->
                    <h6 class="text-purple mb-3"><i class="bx bx-calendar me-2"></i>Scheduling</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Scheduled Date</label>
                                <input type="datetime-local" name="scheduled_date" class="form-control" value="{{ old('scheduled_date', $workOrder->scheduled_date ? $workOrder->scheduled_date->format('Y-m-d\TH:i') : '') }}">
                                @error('scheduled_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Estimated Start Date</label>
                                <input type="datetime-local" name="estimated_start_date" class="form-control" value="{{ old('estimated_start_date', $workOrder->estimated_start_date ? $workOrder->estimated_start_date->format('Y-m-d\TH:i') : '') }}">
                                @error('estimated_start_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Estimated Completion Date</label>
                                <input type="datetime-local" name="estimated_completion_date" class="form-control" value="{{ old('estimated_completion_date', $workOrder->estimated_completion_date ? $workOrder->estimated_completion_date->format('Y-m-d\TH:i') : '') }}">
                                @error('estimated_completion_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Cost Information -->
                    <h6 class="text-purple mb-3"><i class="bx bx-money me-2"></i>Cost Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Estimated Cost (TZS)</label>
                                <input type="number" step="0.01" min="0" name="estimated_cost" class="form-control" value="{{ old('estimated_cost', $workOrder->estimated_cost) }}">
                                @error('estimated_cost')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Actual Cost (TZS)</label>
                                <input type="number" step="0.01" min="0" name="actual_cost" class="form-control" value="{{ old('actual_cost', $workOrder->actual_cost) }}">
                                @error('actual_cost')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    @if($workOrder->status == 'in_progress' || $workOrder->status == 'completed')
                    <hr class="my-4">
                    <h6 class="text-purple mb-3"><i class="bx bx-check-circle me-2"></i>Work Completion Details</h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Work Performed</label>
                                <textarea name="work_performed" class="form-control" rows="4">{{ old('work_performed', $workOrder->work_performed) }}</textarea>
                                @error('work_performed')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Technician Notes</label>
                                <textarea name="technician_notes" class="form-control" rows="3">{{ old('technician_notes', $workOrder->technician_notes) }}</textarea>
                                @error('technician_notes')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    @endif

                    <hr class="my-4">

                    <!-- Notes -->
                    <h6 class="text-purple mb-3"><i class="bx bx-note me-2"></i>Additional Notes</h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $workOrder->notes) }}</textarea>
                                @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.maintenance.work-orders.show', $workOrder->hash_id) }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-purple" style="background-color: #6f42c1; border-color: #6f42c1; color: #fff;">
                            <i class="bx bx-save me-1"></i>Update Work Order
                        </button>
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
    // Show/hide vendor and technician fields based on execution type
    $('#execution_type').on('change', function() {
        const executionType = $(this).val();
        
        if (executionType === 'external_vendor') {
            $('#vendor-field').show();
            $('#technician-field').hide();
        } else if (executionType === 'in_house') {
            $('#vendor-field').hide();
            $('#technician-field').show();
        } else if (executionType === 'mixed') {
            $('#vendor-field').show();
            $('#technician-field').show();
        } else {
            $('#vendor-field').hide();
            $('#technician-field').hide();
        }
    });
});
</script>
@endpush
