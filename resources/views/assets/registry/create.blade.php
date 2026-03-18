@extends('layouts.main')

@section('title', 'Add Asset')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.registry.index'), 'icon' => 'bx bx-clipboard'],
            ['label' => 'Add Asset', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Add Asset</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.registry.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="asset_category_id" class="form-select select2-single" required>
                                    <option value="">Select</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}" {{ old('asset_category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Choose the IFRS/IPSAS-aligned category to inherit defaults.</div>
                                @error('asset_category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tax Depreciation Class (TRA)</label>
                                <select name="tax_class_id" class="form-select select2-single">
                                    <option value="">Select TRA Class</option>
                                    @foreach(($taxClasses ?? []) as $taxClass)
                                        <option value="{{ $taxClass->id }}" {{ old('tax_class_id') == $taxClass->id ? 'selected' : '' }}>
                                            {{ $taxClass->class_code }} — {{ $taxClass->description }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Assign the TRA tax depreciation class for tax computation.</div>
                                @if(($taxClasses ?? collect())->isEmpty())
                                    <div class="form-text text-warning">No TRA classes loaded. Run: <code>php artisan db:seed --class=TaxDepreciationClassSeeder</code> then refresh this page.</div>
                                @endif
                                @error('tax_class_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Asset Code / Tag No.</label>
                                <input name="code" class="form-control" value="{{ old('code') }}" placeholder="Auto if blank">
                                <div class="form-text">Leave blank to auto-generate (e.g., AST-000001). Used for barcode/QR.</div>
                                @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input name="name" class="form-control" value="{{ old('name') }}" required>
                                <div class="form-text">Short description, e.g., “HP ProBook 450 G8”.</div>
                                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Capitalization / Opening Balance Date</label>
                                <input type="date" name="capitalization_date" class="form-control" value="{{ old('capitalization_date') }}">
                                <div class="form-text">Date the asset is placed in service or recognised as opening balance.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Serial Number</label>
                                <input name="serial_number" class="form-control" value="{{ old('serial_number') }}">
                                <div class="form-text">Manufacturer serial number, if available.</div>
                                @error('serial_number')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Model</label>
                                <input name="model" class="form-control" value="{{ old('model') }}">
                                <div class="form-text">e.g., “ProBook 450 G8”.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Manufacturer</label>
                                <input name="manufacturer" class="form-control" value="{{ old('manufacturer') }}">
                                <div class="form-text">e.g., “HP”, “Caterpillar”.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Residual Value (TZS)</label>
                                <input type="number" step="0.01" min="0" name="salvage_value" class="form-control" value="{{ old('salvage_value', 0) }}">
                                <div class="form-text">Expected value at the end of useful life.</div>
                                @error('salvage_value')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Current NBV (TZS)</label>
                                <input type="number" step="0.01" min="0" name="current_nbv" class="form-control" value="{{ old('current_nbv') }}" placeholder="Auto by system if blank">
                                <div class="form-text">Net book value now; leave blank to let the system compute.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-select select2-single">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Owning or custodial department.</div>
                                @error('department_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Warranty (months)</label>
                                <input type="number" min="0" name="warranty_months" class="form-control" value="{{ old('warranty_months') }}">
                                <div class="form-text">Enter warranty period in months.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Warranty Expiry</label>
                                <input type="date" name="warranty_expiry_date" class="form-control" value="{{ old('warranty_expiry_date') }}">
                                <div class="form-text">If known; otherwise the system may derive from purchase date + months.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Insurance Policy No.</label>
                                <input name="insurance_policy_no" class="form-control" value="{{ old('insurance_policy_no') }}">
                                <div class="form-text">Optional. Fill if insured.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Insured Value (TZS)</label>
                                <input type="number" step="0.01" min="0" name="insured_value" class="form-control" value="{{ old('insured_value') }}">
                                <div class="form-text">Sum insured for this asset.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Insurance Expiry</label>
                                <input type="date" name="insurance_expiry_date" class="form-control" value="{{ old('insurance_expiry_date') }}">
                                <div class="form-text">Policy expiry date.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Physical Location</label>
                                <input name="location" class="form-control" value="{{ old('location') }}" placeholder="Site / Room / Area">
                                <div class="form-text">Where the asset is kept or used.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Building Ref.</label>
                                <input name="building_reference" class="form-control" value="{{ old('building_reference') }}">
                                <div class="form-text">Optional building/room reference.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">GPS (lat, lng)</label>
                                <div class="d-flex gap-2">
                                    <input type="number" step="0.0000001" name="gps_lat" class="form-control" placeholder="Lat" value="{{ old('gps_lat') }}">
                                    <input type="number" step="0.0000001" name="gps_lng" class="form-control" placeholder="Lng" value="{{ old('gps_lng') }}">
                                </div>
                                <div class="form-text">Optional coordinates for mapping.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Asset Tag / RFID</label>
                                <input name="tag" class="form-control" value="{{ old('tag') }}" placeholder="e.g. RFID-0001">
                                <div class="form-text">Printed tag/sticker code applied to the asset.</div>
                                @error('tag')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                                <div class="form-text">Additional details or notes about this asset.</div>
                                @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Attachments (Invoice, Warranty, Photo, Insurance, Valuation)</label>
                                <input type="file" name="attachments[]" class="form-control" multiple>
                                <div class="form-text">Upload supporting documents and images (max 5MB each).</div>
                            </div>
                        </div>
                    </div>

                    <!-- Fleet Management - Vehicle Specific Fields (shown only for Motor Vehicles category) -->
                    <div id="vehicle-fields" style="display: none;">
                        <hr class="my-4">
                        <h6 class="text-primary mb-3"><i class="bx bx-car me-2"></i>Vehicle Specifications</h6>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Registration Number <span class="text-danger">*</span></label>
                                    <input name="registration_number" class="form-control" value="{{ old('registration_number') }}" placeholder="T 123 ABC">
                                    <div class="form-text">Vehicle registration/license plate number.</div>
                                    @error('registration_number')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Ownership Type</label>
                                    <select name="ownership_type" class="form-select">
                                        <option value="">Select Ownership</option>
                                        <option value="owned" {{ old('ownership_type') == 'owned' ? 'selected' : '' }}>Owned</option>
                                        <option value="leased" {{ old('ownership_type') == 'leased' ? 'selected' : '' }}>Leased</option>
                                        <option value="rented" {{ old('ownership_type') == 'rented' ? 'selected' : '' }}>Rented</option>
                                    </select>
                                    <div class="form-text">How the vehicle is owned or acquired.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Fuel Type</label>
                                    <select name="fuel_type" class="form-select">
                                        <option value="">Select Fuel Type</option>
                                        <option value="petrol" {{ old('fuel_type') == 'petrol' ? 'selected' : '' }}>Petrol</option>
                                        <option value="diesel" {{ old('fuel_type') == 'diesel' ? 'selected' : '' }}>Diesel</option>
                                        <option value="electric" {{ old('fuel_type') == 'electric' ? 'selected' : '' }}>Electric</option>
                                        <option value="hybrid" {{ old('fuel_type') == 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                        <option value="lpg" {{ old('fuel_type') == 'lpg' ? 'selected' : '' }}>LPG</option>
                                        <option value="cng" {{ old('fuel_type') == 'cng' ? 'selected' : '' }}>CNG</option>
                                    </select>
                                    <div class="form-text">Primary fuel type used by the vehicle.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Capacity (Tons)</label>
                                    <input type="number" step="0.01" min="0" name="capacity_tons" class="form-control" value="{{ old('capacity_tons') }}" placeholder="2.5">
                                    <div class="form-text">Load capacity in tons.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Capacity (Volume)</label>
                                    <input type="number" step="0.01" min="0" name="capacity_volume" class="form-control" value="{{ old('capacity_volume') }}" placeholder="5000">
                                    <div class="form-text">Volume capacity in liters.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Capacity (Passengers)</label>
                                    <input type="number" min="0" name="capacity_passengers" class="form-control" value="{{ old('capacity_passengers') }}" placeholder="5">
                                    <div class="form-text">Passenger capacity (excluding driver).</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">License Expiry Date</label>
                                    <input type="date" name="license_expiry_date" class="form-control" value="{{ old('license_expiry_date') }}">
                                    <div class="form-text">Vehicle license/registration expiry date.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Inspection Expiry Date</label>
                                    <input type="date" name="inspection_expiry_date" class="form-control" value="{{ old('inspection_expiry_date') }}">
                                    <div class="form-text">Next inspection/roadworthiness expiry date.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Operational Status</label>
                                    <select name="operational_status" class="form-select">
                                        <option value="available" {{ old('operational_status', 'available') == 'available' ? 'selected' : '' }}>Available</option>
                                        <option value="assigned" {{ old('operational_status') == 'assigned' ? 'selected' : '' }}>Assigned</option>
                                        <option value="in_repair" {{ old('operational_status') == 'in_repair' ? 'selected' : '' }}>In Repair</option>
                                        <option value="retired" {{ old('operational_status') == 'retired' ? 'selected' : '' }}>Retired</option>
                                    </select>
                                    <div class="form-text">Current operational status of the vehicle.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">GPS Device ID</label>
                                    <input name="gps_device_id" class="form-control" value="{{ old('gps_device_id') }}" placeholder="GPS001">
                                    <div class="form-text">GPS tracking device identifier (if installed).</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Current Location</label>
                                    <input name="current_location" class="form-control" value="{{ old('current_location') }}" placeholder="Warehouse A">
                                    <div class="form-text">Current location or base station of the vehicle.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('assets.registry.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>Back</a>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i>Save Asset</button>
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

    // Show/hide vehicle fields based on category selection
    function toggleVehicleFields() {
        const selectedCategoryId = $('select[name="asset_category_id"]').val();
        // FA04 is the Motor Vehicles category code
        const isVehicleCategory = selectedCategoryId && (
            @json($categories->pluck('id', 'code')['FA04'] ?? null) == selectedCategoryId
        );

        if (isVehicleCategory) {
            $('#vehicle-fields').slideDown();
            // Make registration number required for vehicles
            $('input[name="registration_number"]').attr('required', true);
        } else {
            $('#vehicle-fields').slideUp();
            // Remove required attribute for non-vehicles
            $('input[name="registration_number"]').attr('required', false);
        }
    }

    // Check on page load
    toggleVehicleFields();

    // Check on category change
    $('select[name="asset_category_id"]').on('change', toggleVehicleFields);
});
</script>
@endpush


