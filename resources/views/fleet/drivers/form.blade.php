<div class="driver-form">
    <!-- Basic Information -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light border-0 py-3">
            <h6 class="mb-0 text-primary"><i class="bx bx-user me-2"></i>Basic Information</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input name="full_name" class="form-control" value="{{ old('full_name', isset($driver) ? $driver->full_name : '') }}" required placeholder="e.g. John Mwita">
                    @error('full_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Phone Number</label>
                    <input name="phone_number" class="form-control" value="{{ old('phone_number', isset($driver) ? $driver->phone_number : '') }}" placeholder="e.g. 0712 345 678">
                    @error('phone_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', isset($driver) ? $driver->email : '') }}" placeholder="driver@example.com">
                    @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Street, city, region">{{ old('address', isset($driver) ? $driver->address : '') }}</textarea>
                    @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- License Information -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light border-0 py-3">
            <h6 class="mb-0 text-primary"><i class="bx bx-id-card me-2"></i>License Information</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">License Number <span class="text-danger">*</span></label>
                    <input name="license_number" class="form-control" value="{{ old('license_number', isset($driver) ? $driver->license_number : '') }}" required placeholder="e.g. DL-123456">
                    @error('license_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">License Class</label>
                    <input name="license_class" class="form-control" value="{{ old('license_class', isset($driver) ? $driver->license_class : '') }}" placeholder="e.g. B, C, E">
                    @error('license_class')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">License Expiry Date <span class="text-danger">*</span></label>
                    <input type="date" name="license_expiry_date" class="form-control" value="{{ old('license_expiry_date', isset($driver) && $driver->license_expiry_date ? $driver->license_expiry_date->format('Y-m-d') : '') }}" required>
                    @error('license_expiry_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Issuing Authority</label>
                    <input name="license_issuing_authority" class="form-control" value="{{ old('license_issuing_authority', isset($driver) ? $driver->license_issuing_authority : '') }}" placeholder="e.g. TRA">
                    @error('license_issuing_authority')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Employment Details -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light border-0 py-3">
            <h6 class="mb-0 text-primary"><i class="bx bx-briefcase me-2"></i>Employment Details</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Employment Type <span class="text-danger">*</span></label>
                    <select name="employment_type" class="form-select" required>
                        <option value="">Select type</option>
                        <option value="employee" {{ old('employment_type', isset($driver) ? $driver->employment_type : '') == 'employee' ? 'selected' : '' }}>Employee</option>
                        <option value="contractor" {{ old('employment_type', isset($driver) ? $driver->employment_type : '') == 'contractor' ? 'selected' : '' }}>Contractor</option>
                    </select>
                    @error('employment_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" {{ old('status', isset($driver) ? $driver->status : 'active') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $driver->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="suspended" {{ old('status', $driver->status ?? '') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="terminated" {{ old('status', $driver->status ?? '') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                    </select>
                    @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light border-0 py-3">
            <h6 class="mb-0 text-primary"><i class="bx bx-phone me-2"></i>Emergency Contact</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Contact Name</label>
                    <input name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', isset($driver) ? $driver->emergency_contact_name : '') }}" placeholder="Name">
                    @error('emergency_contact_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Phone</label>
                    <input name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', isset($driver) ? $driver->emergency_contact_phone : '') }}" placeholder="Phone number">
                    @error('emergency_contact_phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Relationship</label>
                    <input name="emergency_contact_relationship" class="form-control" value="{{ old('emergency_contact_relationship', isset($driver) ? $driver->emergency_contact_relationship : '') }}" placeholder="e.g. Spouse, Parent">
                    @error('emergency_contact_relationship')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle Assignment -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light border-0 py-3">
            <h6 class="mb-0 text-primary"><i class="bx bx-car me-2"></i>Vehicle Assignment <span class="text-muted fw-normal">(Optional)</span></h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Assigned Vehicle</label>
                    <select name="assigned_vehicle_id" class="form-select select2-single" id="assigned_vehicle_id">
                        <option value="">Not Assigned</option>
                        @foreach($vehicles as $v)
                            @php
                                $statusLabel = '';
                                $hasDriver = false;
                                $driverInfo = '';
                                if ($v->assignedDriver) {
                                    $hasDriver = true;
                                    $driverName = $v->assignedDriver->full_name;
                                    if ($v->assignedDriver->assignment_end_date) {
                                        $endDate = \Carbon\Carbon::parse($v->assignedDriver->assignment_end_date);
                                        if ($endDate->isPast()) {
                                            $hasDriver = false;
                                            $driverInfo = '(Assignment ended ' . $endDate->format('M d, Y') . ')';
                                        } else {
                                            $driverInfo = '(Until ' . $endDate->format('M d, Y') . ')';
                                        }
                                    } else {
                                        $driverInfo = '(Permanent)';
                                    }
                                }
                                if ($hasDriver) {
                                    $statusLabel = '👤 Has Driver: ' . $driverName . ' ' . $driverInfo;
                                } else {
                                    switch($v->operational_status) {
                                        case 'available': $statusLabel = '✓ Available'; break;
                                        case 'assigned': $statusLabel = $driverInfo ? '✓ Available ' . $driverInfo : '⚠ Assigned'; break;
                                        case 'in_repair': $statusLabel = '🔧 In Repair'; break;
                                        case 'retired': $statusLabel = '✖ Retired'; break;
                                        default: $statusLabel = $v->operational_status ?? 'Unknown';
                                    }
                                }
                            @endphp
                            <option value="{{ $v->id }}" data-status="{{ $v->operational_status }}" data-has-driver="{{ $hasDriver ? 'true' : 'false' }}" {{ (old('assigned_vehicle_id', isset($driver) ? $driver->assigned_vehicle_id : null) == $v->id) ? 'selected' : '' }}>
                                {{ $v->name }} ({{ $v->registration_number ?? 'N/A' }}) — {{ $statusLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_vehicle_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    <div class="form-text small mt-1">
                        <span class="text-success">✓ Available</span> ·
                        <span class="text-primary">👤 Has Driver</span> ·
                        <span class="text-danger">🔧 In Repair</span> ·
                        <span class="text-muted">✖ Retired</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Assignment Start Date</label>
                    <input type="date" name="assignment_start_date" class="form-control" value="{{ old('assignment_start_date', isset($driver) && $driver->assignment_start_date ? $driver->assignment_start_date->format('Y-m-d') : '') }}">
                    @error('assignment_start_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Assignment End Date</label>
                    <input type="date" name="assignment_end_date" class="form-control" value="{{ old('assignment_end_date', isset($driver) && $driver->assignment_end_date ? $driver->assignment_end_date->format('Y-m-d') : '') }}">
                    @error('assignment_end_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Notes -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light border-0 py-3">
            <h6 class="mb-0 text-primary"><i class="bx bx-note me-2"></i>Notes</h6>
        </div>
        <div class="card-body">
            <label class="form-label">Additional notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Any additional information about this driver">{{ old('notes', isset($driver) ? $driver->notes : '') }}</textarea>
            @error('notes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
