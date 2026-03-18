@extends('layouts.main')

@section('title', 'Edit Maintenance Schedule - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Maintenance Schedules', 'url' => route('fleet.maintenance.schedules.index'), 'icon' => 'bx bx-calendar'],
            ['label' => 'Edit Schedule', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-purple text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Maintenance Schedule - {{ $schedule->schedule_name }}</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.maintenance.schedules.update', $schedule->hash_id) }}" id="schedule-form">
                    @csrf
                    @method('PUT')

                    <!-- Vehicle Information -->
                    <h6 class="text-purple mb-3"><i class="bx bx-car me-2"></i>Vehicle Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Vehicle</label>
                                <input type="text" class="form-control" value="{{ $schedule->vehicle->name ?? 'N/A' }}" disabled>
                                <input type="hidden" name="vehicle_id" value="{{ $schedule->vehicle_id }}">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Schedule Details -->
                    <h6 class="text-purple mb-3"><i class="bx bx-calendar me-2"></i>Schedule Details</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Schedule Name <span class="text-danger">*</span></label>
                                <input type="text" name="schedule_name" class="form-control" value="{{ old('schedule_name', $schedule->schedule_name) }}" placeholder="e.g., Oil Change Schedule" required>
                                @error('schedule_name')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Maintenance Category <span class="text-danger">*</span></label>
                                <select name="maintenance_category" class="form-select select2-single" required>
                                    <option value="">Select Category</option>
                                    @foreach($costCategories ?? [] as $cat)
                                        <option value="{{ $cat->name }}" {{ old('maintenance_category', $schedule->maintenance_category) == $cat->name ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select from cost categories (manage categories in Fleet → Cost Categories)</div>
                                @error('maintenance_category')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Describe the maintenance schedule">{{ old('description', $schedule->description) }}</textarea>
                                @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Schedule Type -->
                    <h6 class="text-purple mb-3"><i class="bx bx-cog me-2"></i>Schedule Type</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Schedule Type <span class="text-danger">*</span></label>
                                <select name="schedule_type" class="form-select" id="schedule_type" required>
                                    <option value="time_based" {{ old('schedule_type', $schedule->schedule_type) == 'time_based' ? 'selected' : '' }}>Time Based</option>
                                    <option value="mileage_based" {{ old('schedule_type', $schedule->schedule_type) == 'mileage_based' ? 'selected' : '' }}>Mileage Based</option>
                                    <option value="both" {{ old('schedule_type', $schedule->schedule_type) == 'both' ? 'selected' : '' }}>Both (Time & Mileage)</option>
                                </select>
                                @error('schedule_type')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" {{ old('is_active', $schedule->is_active) ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ !old('is_active', $schedule->is_active) ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('is_active')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <!-- Time-Based Schedule -->
                    <div id="time-based-fields" style="display: none;">
                        <h6 class="text-purple mb-3"><i class="bx bx-time me-2"></i>Time-Based Interval</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Interval (Days)</label>
                                    <input type="number" min="1" name="interval_days" class="form-control" value="{{ old('interval_days', $schedule->interval_days) }}" placeholder="e.g., 30, 60, 90">
                                    <div class="form-text">Days between maintenance</div>
                                    @error('interval_days')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Interval (Months)</label>
                                    <input type="number" min="1" name="interval_months" class="form-control" value="{{ old('interval_months', $schedule->interval_months) }}" placeholder="e.g., 3, 6, 12">
                                    <div class="form-text">Months between maintenance</div>
                                    @error('interval_months')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Alert Days Before</label>
                                    <input type="number" min="0" name="alert_days_before" class="form-control" value="{{ old('alert_days_before', $schedule->alert_days_before ?? 7) }}" placeholder="7">
                                    <div class="form-text">Days before due date to alert</div>
                                    @error('alert_days_before')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mileage-Based Schedule -->
                    <div id="mileage-based-fields" style="display: none;">
                        <h6 class="text-purple mb-3"><i class="bx bx-tachometer me-2"></i>Mileage-Based Interval</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Interval (Kilometers)</label>
                                    <input type="number" step="0.01" min="0" name="interval_km" class="form-control" value="{{ old('interval_km', $schedule->interval_km) }}" placeholder="e.g., 5000, 10000, 20000" id="interval_km">
                                    <div class="form-text">Kilometers between maintenance</div>
                                    @error('interval_km')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Alert Kilometers Before</label>
                                    <input type="number" step="0.01" min="0" name="alert_km_before" class="form-control" value="{{ old('alert_km_before', $schedule->alert_km_before ?? 500) }}" placeholder="500">
                                    <div class="form-text">KM before due to alert</div>
                                    @error('alert_km_before')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Cost & Settings -->
                    <h6 class="text-purple mb-3"><i class="bx bx-money me-2"></i>Cost & Settings</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Estimated Cost (TZS)</label>
                                <input type="number" step="0.01" min="0" name="estimated_cost" class="form-control" value="{{ old('estimated_cost', $schedule->estimated_cost) }}" placeholder="0.00">
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
                                <textarea name="notes" class="form-control" rows="3" placeholder="Any additional notes or comments">{{ old('notes', $schedule->notes) }}</textarea>
                                @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.maintenance.schedules.show', $schedule->hash_id) }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-purple" style="background-color: #6f42c1; border-color: #6f42c1; color: #fff;">
                            <i class="bx bx-save me-1"></i>Update Schedule
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
    // Show/hide fields based on schedule type
    function toggleScheduleFields() {
        const scheduleType = $('#schedule_type').val();
        
        if (scheduleType === 'time_based' || scheduleType === 'both') {
            $('#time-based-fields').show();
        } else {
            $('#time-based-fields').hide();
        }
        
        if (scheduleType === 'mileage_based' || scheduleType === 'both') {
            $('#mileage-based-fields').show();
        } else {
            $('#mileage-based-fields').hide();
        }
    }

    $('#schedule_type').on('change', toggleScheduleFields);
    
    // Trigger on page load
    toggleScheduleFields();
});
</script>
@endpush
