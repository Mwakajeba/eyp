@extends('layouts.main')

@section('title', 'Create Work Order - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Maintenance Work Orders', 'url' => route('fleet.maintenance.work-orders.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Create Work Order', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-purple text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Create Work Order</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.maintenance.work-orders.store') }}" id="work-order-form">
                    @csrf

                    <!-- Vehicle & Schedule Information -->
                    <h6 class="text-purple mb-3"><i class="bx bx-car me-2"></i>Vehicle & Schedule Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                                <select name="vehicle_id" class="form-select select2-single" required>
                                    <option value="">Select Vehicle</option>
                                    @foreach($vehicles as $v)
                                        <option value="{{ $v->id }}" {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>
                                            {{ $v->name }} ({{ $v->registration_number ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Maintenance Schedule (Optional)</label>
                                <select name="maintenance_schedule_id" id="maintenance_schedule_id" class="form-select select2-single">
                                    <option value="">Select Schedule</option>
                                    @foreach($schedules as $s)
                                        <option value="{{ $s->id }}" {{ old('maintenance_schedule_id') == $s->id ? 'selected' : '' }}>
                                            {{ $s->schedule_name }} - {{ $s->vehicle->name ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Selecting a schedule will auto-populate vehicle, category, description, and estimated cost</div>
                                @error('maintenance_schedule_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Maintenance Details -->
                    <h6 class="text-purple mb-3"><i class="bx bx-wrench me-2"></i>Maintenance Details</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                                <select name="maintenance_type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="preventive" {{ old('maintenance_type') == 'preventive' ? 'selected' : '' }}>Preventive</option>
                                    <option value="corrective" {{ old('maintenance_type') == 'corrective' ? 'selected' : '' }}>Corrective</option>
                                    <option value="major_overhaul" {{ old('maintenance_type') == 'major_overhaul' ? 'selected' : '' }}>Major Overhaul</option>
                                </select>
                                @error('maintenance_type')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Maintenance Category</label>
                                <select name="maintenance_category" id="maintenance_category" class="form-select select2-single">
                                    <option value="">Select Category</option>
                                    @foreach($costCategories ?? [] as $cat)
                                        <option value="{{ $cat->name }}" {{ old('maintenance_category') == $cat->name ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select from cost categories (manage categories in Fleet → Cost Categories)</div>
                                @error('maintenance_category')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select" required>
                                    <option value="low" {{ old('priority', 'medium') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                                @error('priority')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Work Description</label>
                                <textarea name="work_description" class="form-control" rows="3" placeholder="Describe the work to be performed">{{ old('work_description') }}</textarea>
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
                                    <option value="">Select Type</option>
                                    <option value="in_house" {{ old('execution_type') == 'in_house' ? 'selected' : '' }}>In House</option>
                                    <option value="external_vendor" {{ old('execution_type') == 'external_vendor' ? 'selected' : '' }}>External Vendor</option>
                                    <option value="mixed" {{ old('execution_type') == 'mixed' ? 'selected' : '' }}>Mixed</option>
                                </select>
                                @error('execution_type')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4" id="vendor-field" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Vendor</label>
                                <select name="vendor_id" class="form-select select2-single">
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $v)
                                        <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                                    @endforeach
                                </select>
                                @error('vendor_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4" id="technician-field" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Assigned Technician</label>
                                <select name="assigned_technician_id" class="form-select select2-single">
                                    <option value="">Select Technician</option>
                                    @foreach($technicians as $t)
                                        <option value="{{ $t->id }}" {{ old('assigned_technician_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
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
                                <input type="datetime-local" name="scheduled_date" class="form-control" value="{{ old('scheduled_date') }}">
                                @error('scheduled_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Estimated Start Date</label>
                                <input type="datetime-local" name="estimated_start_date" class="form-control" value="{{ old('estimated_start_date') }}">
                                @error('estimated_start_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Estimated Completion Date</label>
                                <input type="datetime-local" name="estimated_completion_date" class="form-control" value="{{ old('estimated_completion_date') }}">
                                @error('estimated_completion_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Cost Estimates -->
                    <h6 class="text-purple mb-3"><i class="bx bx-money me-2"></i>Cost Estimates</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Estimated Total Cost (TZS)</label>
                                <input type="number" step="0.01" min="0" name="estimated_cost" class="form-control" value="{{ old('estimated_cost', 0) }}">
                                @error('estimated_cost')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Notes -->
                    <h6 class="text-purple mb-3"><i class="bx bx-note me-2"></i>Additional Notes</h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Any additional notes or comments">{{ old('notes') }}</textarea>
                                @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.maintenance.work-orders.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-purple" style="background-color: #6f42c1; border-color: #6f42c1; color: #fff;">
                            <i class="bx bx-save me-1"></i>Create Work Order
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
    // Initialize Select2 if available
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    // Show/hide vendor and technician fields based on execution type
    $('#execution_type').on('change', function() {
        const executionType = $(this).val();
        
        if (executionType === 'external_vendor') {
            $('#vendor-field').show();
            $('#technician-field').hide();
            $('#vendor-field select').prop('required', true);
            $('#technician-field select').prop('required', false);
        } else if (executionType === 'in_house') {
            $('#vendor-field').hide();
            $('#technician-field').show();
            $('#vendor-field select').prop('required', false);
            $('#technician-field select').prop('required', false);
        } else if (executionType === 'mixed') {
            $('#vendor-field').show();
            $('#technician-field').show();
            $('#vendor-field select').prop('required', true);
            $('#technician-field select').prop('required', false);
        } else {
            $('#vendor-field').hide();
            $('#technician-field').hide();
            $('#vendor-field select').prop('required', false);
            $('#technician-field select').prop('required', false);
        }
    });

    // Trigger on page load if value exists
    $('#execution_type').trigger('change');

    // Auto-populate fields when maintenance schedule is selected
    $('#maintenance_schedule_id').on('change', function() {
        const scheduleId = $(this).val();
        
        if (scheduleId) {
            // Show loading indicator
            $(this).prop('disabled', true);
            
            // Fetch schedule details via AJAX
            $.ajax({
                url: '{{ route('fleet.maintenance.work-orders.schedule-details') }}',
                method: 'GET',
                data: { schedule_id: scheduleId },
                success: function(response) {
                    // Auto-populate vehicle
                    if (response.vehicle_id) {
                        $('select[name="vehicle_id"]').val(response.vehicle_id).trigger('change');
                    }
                    
                    // Auto-populate maintenance category
                    if (response.maintenance_category) {
                        $('select[name="maintenance_category"]').val(response.maintenance_category).trigger('change');
                    }
                    
                    // Auto-populate work description
                    if (response.description) {
                        const scheduleDesc = 'Maintenance Schedule: ' + response.schedule_name + '\n\n' + response.description;
                        $('textarea[name="work_description"]').val(scheduleDesc);
                    }
                    
                    // Auto-populate estimated cost
                    if (response.estimated_cost) {
                        $('input[name="estimated_cost"]').val(response.estimated_cost);
                    }
                    
                    // Auto-populate scheduled date (next due date)
                    if (response.next_due_date) {
                        $('input[name="scheduled_date"]').val(response.next_due_date);
                    }
                    
                    // Set maintenance type to preventive by default
                    $('select[name="maintenance_type"]').val('preventive').trigger('change');
                    
                    // Show success message
                    toastr.success('Work order details populated from maintenance schedule');
                },
                error: function(xhr) {
                    console.error('Error fetching schedule details:', xhr);
                    toastr.error('Failed to load schedule details');
                },
                complete: function() {
                    $('#maintenance_schedule_id').prop('disabled', false);
                }
            });
        } else {
            // Clear fields if no schedule selected
            $('textarea[name="work_description"]').val('');
            $('input[name="estimated_cost"]').val('');
            $('input[name="scheduled_date"]').val('');
        }
    });
});
</script>
@endpush
