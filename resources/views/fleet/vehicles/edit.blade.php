@extends('layouts.main')

@section('title', 'Edit Vehicle: ' . $vehicle->name . ' - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Vehicle Master', 'url' => route('fleet.vehicles.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Edit: ' . $vehicle->name, 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Vehicle: {{ $vehicle->name }}</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.vehicles.update', $vehicle->hash_id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="asset_category_id" class="form-select select2-single" required>
                                    <option value="">Select</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}" {{ ($vehicle->asset_category_id ?? old('asset_category_id')) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Choose the Motor Vehicles category.</div>
                                @error('asset_category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tax Depreciation Class (TRA)</label>
                                <select name="tax_class_id" class="form-select select2-single">
                                    <option value="">Select TRA Class</option>
                                    @foreach(($taxClasses ?? []) as $taxClass)
                                        <option value="{{ $taxClass->id }}" {{ ($vehicle->tax_class_id ?? old('tax_class_id')) == $taxClass->id ? 'selected' : '' }}>
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
                                <label class="form-label">Asset Code / Tag No.</label>
                                <input name="code" class="form-control" value="{{ $vehicle->code ?? old('code') }}" placeholder="Auto if blank">
                                <div class="form-text">Leave blank to auto-generate (e.g., AST-000001). Used for barcode/QR.</div>
                                @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Vehicle Name <span class="text-danger">*</span></label>
                                <input name="name" class="form-control" value="{{ $vehicle->name ?? old('name') }}" required placeholder="e.g., Toyota Hilux 2023">
                                <div class="form-text">Short description, e.g., "Toyota Hilux Double Cab".</div>
                                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Model</label>
                                <input name="model" class="form-control" value="{{ $vehicle->model ?? old('model') }}" placeholder="Hilux">
                                <div class="form-text">e.g., "Hilux".</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Manufacturer</label>
                                <input name="manufacturer" class="form-control" value="{{ $vehicle->manufacturer ?? old('manufacturer') }}" placeholder="Toyota">
                                <div class="form-text">e.g., "Toyota".</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Capitalization / Opening Balance Date</label>
                                <input type="date" name="capitalization_date" class="form-control" value="{{ $vehicle->capitalization_date ? $vehicle->capitalization_date->format('Y-m-d') : old('capitalization_date') }}">
                                <div class="form-text">Date the vehicle is placed in service or recognised as opening balance.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Purchase Cost (TZS)</label>
                                <input type="number" step="0.01" min="0" name="purchase_cost" class="form-control" value="{{ $vehicle->purchase_cost ?? old('purchase_cost', 0) }}" placeholder="0.00">
                                <div class="form-text">Original purchase cost or fair value (optional, defaults to 0).</div>
                                @error('purchase_cost')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Serial Number/VIN</label>
                                <input name="serial_number" class="form-control" value="{{ $vehicle->serial_number ?? old('serial_number') }}" placeholder="VIN or Chassis Number">
                                <div class="form-text">Vehicle Identification Number (VIN) or chassis number.</div>
                                @error('serial_number')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Residual Value (TZS)</label>
                                <input type="number" step="0.01" min="0" name="salvage_value" class="form-control" value="{{ $vehicle->salvage_value ?? old('salvage_value', 0) }}">
                                <div class="form-text">Expected value at the end of useful life.</div>
                                @error('salvage_value')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-select select2-single">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ ($vehicle->department_id ?? old('department_id')) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Owning or custodial department.</div>
                                @error('department_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <!-- Fleet Management - Vehicle Specific Fields -->
                    <div id="vehicle-fields">
                        <hr class="my-4">
                        <h6 class="text-primary mb-3"><i class="bx bx-car me-2"></i>Vehicle Specifications</h6>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Registration Number <span class="text-danger">*</span></label>
                                    <input name="registration_number" class="form-control" value="{{ $vehicle->registration_number ?? old('registration_number') }}" placeholder="T 123 ABC" required>
                                    <div class="form-text">Vehicle registration/license plate number.</div>
                                    @error('registration_number')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Ownership Type</label>
                                    <select name="ownership_type" class="form-select">
                                        <option value="">Select Ownership</option>
                                        <option value="owned" {{ ($vehicle->ownership_type ?? old('ownership_type')) == 'owned' ? 'selected' : '' }}>Owned</option>
                                        <option value="leased" {{ ($vehicle->ownership_type ?? old('ownership_type')) == 'leased' ? 'selected' : '' }}>Leased</option>
                                        <option value="rented" {{ ($vehicle->ownership_type ?? old('ownership_type')) == 'rented' ? 'selected' : '' }}>Rented</option>
                                    </select>
                                    <div class="form-text">How the vehicle is owned or acquired.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Fuel Type</label>
                                    <select name="fuel_type" class="form-select">
                                        <option value="">Select Fuel Type</option>
                                        <option value="petrol" {{ ($vehicle->fuel_type ?? old('fuel_type')) == 'petrol' ? 'selected' : '' }}>Petrol</option>
                                        <option value="diesel" {{ ($vehicle->fuel_type ?? old('fuel_type')) == 'diesel' ? 'selected' : '' }}>Diesel</option>
                                        <option value="electric" {{ ($vehicle->fuel_type ?? old('fuel_type')) == 'electric' ? 'selected' : '' }}>Electric</option>
                                        <option value="hybrid" {{ ($vehicle->fuel_type ?? old('fuel_type')) == 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                        <option value="lpg" {{ ($vehicle->fuel_type ?? old('fuel_type')) == 'lpg' ? 'selected' : '' }}>LPG</option>
                                        <option value="cng" {{ ($vehicle->fuel_type ?? old('fuel_type')) == 'cng' ? 'selected' : '' }}>CNG</option>
                                    </select>
                                    <div class="form-text">Primary fuel type used by the vehicle.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Capacity (Tons)</label>
                                    <input type="number" step="0.01" min="0" name="capacity_tons" class="form-control" value="{{ $vehicle->capacity_tons ?? old('capacity_tons') }}" placeholder="2.5">
                                    <div class="form-text">Load capacity in tons.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Capacity (Volume)/km</label>
                                    <input type="number" step="0.01" min="0" name="capacity_volume" class="form-control" value="{{ $vehicle->capacity_volume ?? old('capacity_volume') }}" placeholder="0.15">
                                    <div class="form-text">Liters per km (e.g. 0.15 for 15 L/100km). Used to estimate trip fuel.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Capacity (Passengers)</label>
                                    <input type="number" min="0" name="capacity_passengers" class="form-control" value="{{ $vehicle->capacity_passengers ?? old('capacity_passengers') }}" placeholder="5">
                                    <div class="form-text">Passenger capacity (excluding driver).</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">License Expiry Date</label>
                                    <input type="date" name="license_expiry_date" class="form-control" value="{{ $vehicle->license_expiry_date ? $vehicle->license_expiry_date->format('Y-m-d') : old('license_expiry_date') }}">
                                    <div class="form-text">Vehicle license/registration expiry date.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Inspection Expiry Date</label>
                                    <input type="date" name="inspection_expiry_date" class="form-control" value="{{ $vehicle->inspection_expiry_date ? $vehicle->inspection_expiry_date->format('Y-m-d') : old('inspection_expiry_date') }}">
                                    <div class="form-text">Next inspection/roadworthiness expiry date.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Operational Status</label>
                                    <select name="operational_status" class="form-select">
                                        <option value="available" {{ ($vehicle->operational_status ?? old('operational_status', 'available')) == 'available' ? 'selected' : '' }}>Available</option>
                                        <option value="assigned" {{ ($vehicle->operational_status ?? old('operational_status')) == 'assigned' ? 'selected' : '' }}>Assigned</option>
                                        <option value="in_repair" {{ ($vehicle->operational_status ?? old('operational_status')) == 'in_repair' ? 'selected' : '' }}>In Repair</option>
                                        <option value="retired" {{ ($vehicle->operational_status ?? old('operational_status')) == 'retired' ? 'selected' : '' }}>Retired</option>
                                    </select>
                                    <div class="form-text">Current operational status of the vehicle.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">GPS Device ID</label>
                                    <input name="gps_device_id" class="form-control" value="{{ $vehicle->gps_device_id ?? old('gps_device_id') }}" placeholder="GPS001">
                                    <div class="form-text">GPS tracking device identifier (if installed).</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Current Location</label>
                                    <input name="current_location" class="form-control" value="{{ $vehicle->current_location ?? old('current_location') }}" placeholder="Warehouse A">
                                    <div class="form-text">Current location or base station of the vehicle.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Physical Location</label>
                                <input name="location" class="form-control" value="{{ $vehicle->location ?? old('location') }}" placeholder="Site / Room / Area">
                                <div class="form-text">Where the vehicle is kept or used.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Building Ref.</label>
                                <input name="building_reference" class="form-control" value="{{ $vehicle->building_reference ?? old('building_reference') }}">
                                <div class="form-text">Optional building/room reference.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">GPS (lat, lng)</label>
                                <div class="d-flex gap-2">
                                    <input type="number" step="0.0000001" name="gps_lat" class="form-control" placeholder="Lat" value="{{ $vehicle->gps_lat ?? old('gps_lat') }}">
                                    <input type="number" step="0.0000001" name="gps_lng" class="form-control" placeholder="Lng" value="{{ $vehicle->gps_lng ?? old('gps_lng') }}">
                                </div>
                                <div class="form-text">Optional coordinates for mapping.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Asset Tag / RFID</label>
                                <input name="tag" class="form-control" value="{{ $vehicle->tag ?? old('tag') }}" placeholder="e.g. RFID-0001">
                                <div class="form-text">Printed tag/sticker code applied to the vehicle.</div>
                                @error('tag')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-control">{{ $vehicle->description ?? old('description') }}</textarea>
                                <div class="form-text">Additional details or notes about this vehicle.</div>
                                @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Attachments (Purchase Invoice, Photos, Insurance, Inspection)</label>
                                <input type="file" name="attachments[]" class="form-control" multiple>
                                <div class="form-text">Upload supporting documents and images (max 5MB each).</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('fleet.vehicles.show', $vehicle->hash_id) }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>Back</a>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i>Update Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card-header {
        border-radius: 0.375rem 0.375rem 0 0 !important;
    }

    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for dropdowns
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select',
        allowClear: true,
        width: '100%'
    });

    // Auto-set category to Motor Vehicles if not set
    const categorySelect = $('select[name="asset_category_id"]');
    if (!categorySelect.val() && {{ $categories->count() }} === 1) {
        categorySelect.val({{ $categories->first()->id }}).trigger('change');
    }
});
</script>
@endpush