<h6 class="text-warning mb-3"><i class="bx bx-car me-2"></i>Trip Assignment</h6>
<div class="row g-3">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Vehicle <span class="text-danger">*</span></label>
            <select name="vehicle_id" id="vehicle_id" class="form-select select2-single" required>
                <option value="">Select Vehicle</option>
                @foreach($vehicles as $v)
                    <option value="{{ $v->id }}" 
                            data-driver-id="{{ $v->assignedDriver ? $v->assignedDriver->id : '' }}"
                            data-driver-name="{{ $v->assignedDriver ? $v->assignedDriver->full_name : '' }}"
                            data-capacity-volume="{{ $v->capacity_volume ?? '' }}"
                            {{ (old('vehicle_id', isset($trip) ? $trip->vehicle_id : null) == $v->id) ? 'selected' : '' }}>
                        {{ $v->name }} ({{ $v->registration_number ?? 'N/A' }})
                    </option>
                @endforeach
            </select>
            @error('vehicle_id')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Driver</label>
            <select name="driver_id" id="driver_id" class="form-select select2-single">
                <option value="">Select Driver</option>
                @foreach($drivers as $d)
                    <option value="{{ $d->id }}" {{ (old('driver_id', isset($trip) ? $trip->driver_id : null) == $d->id) ? 'selected' : '' }}>
                        {{ $d->full_name }} ({{ $d->license_number }})
                    </option>
                @endforeach
            </select>
            @error('driver_id')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Trip Type <span class="text-danger">*</span></label>
            <select name="trip_type" class="form-select" required>
                <option value="">Select</option>
                <option value="delivery" {{ old('trip_type', isset($trip) ? $trip->trip_type : '') == 'delivery' ? 'selected' : '' }}>Delivery</option>
                <option value="pickup" {{ old('trip_type', isset($trip) ? $trip->trip_type : '') == 'pickup' ? 'selected' : '' }}>Pickup</option>
                <option value="service" {{ old('trip_type', isset($trip) ? $trip->trip_type : '') == 'service' ? 'selected' : '' }}>Service</option>
                <option value="transport" {{ old('trip_type', isset($trip) ? $trip->trip_type : '') == 'transport' ? 'selected' : '' }}>Transport</option>
                <option value="other" {{ old('trip_type', isset($trip) ? $trip->trip_type : '') == 'other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('trip_type')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<hr class="my-4">

<h6 class="text-warning mb-3"><i class="bx bx-map me-2"></i>Route Details</h6>
<div class="row g-3">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Origin Location</label>
            <input name="origin_location" id="origin_location" class="form-control" value="{{ old('origin_location', isset($trip) ? $trip->origin_location : '') }}" placeholder="e.g. Dar es Salaam, Tanzania">
            @error('origin_location')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Destination Location</label>
            <input name="destination_location" id="destination_location" class="form-control" value="{{ old('destination_location', isset($trip) ? $trip->destination_location : '') }}" placeholder="e.g. Mwanza, Tanzania">
            @error('destination_location')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12">
        <div class="mb-3 d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-primary btn-sm" id="calculate-distance-btn" title="Search on map and fill planned distance (uses roads/driving route when available)">
                <i class="bx bx-map-pin me-1"></i>Get distance from map
            </button>
            <span id="distance-calc-status" class="small text-muted"></span>
        </div>
        <div id="distance-route-districts" class="small mb-2" style="display: none;">
            <strong>Districts / regions along route:</strong> <span id="route-districts-list" class="text-primary"></span>
        </div>
        <div id="distance-route-roads" class="small text-muted mb-2" style="display: none;">
            <strong>Roads:</strong> <span id="route-roads-list"></span>
        </div>
    </div>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Cargo Description</label>
            <textarea name="cargo_description" class="form-control" rows="2" placeholder="Describe the cargo or service...">{{ old('cargo_description', isset($trip) ? $trip->cargo_description : '') }}</textarea>
            @error('cargo_description')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<hr class="my-4">

<h6 class="text-warning mb-3"><i class="bx bx-calendar me-2"></i>Planning</h6>
<div class="row g-3">
    <div class="col-md-4">
        <div class="mb-3">
            <label class="form-label">Planned Start Date & Time</label>
            <input type="datetime-local" name="planned_start_date" class="form-control" value="{{ old('planned_start_date', isset($trip) && $trip->planned_start_date ? $trip->planned_start_date->format('Y-m-d\TH:i') : '') }}">
            @error('planned_start_date')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label class="form-label">Planned End Date & Time</label>
            <input type="datetime-local" name="planned_end_date" class="form-control" value="{{ old('planned_end_date', isset($trip) && $trip->planned_end_date ? $trip->planned_end_date->format('Y-m-d\TH:i') : '') }}">
            @error('planned_end_date')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label class="form-label">Planned Distance (km)</label>
            <input type="number" step="0.01" min="0" name="planned_distance_km" id="planned_distance_km" class="form-control" value="{{ old('planned_distance_km', isset($trip) ? $trip->planned_distance_km : '') }}" placeholder="Auto from map">
            <div class="form-text small">Use "Get distance from map" in Route Details to fill automatically.</div>
            @error('planned_distance_km')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label class="form-label">Planned Fuel Consumption (Liters)</label>
            <input type="number" step="0.01" min="0" name="planned_fuel_consumption_liters" id="planned_fuel_consumption_liters" class="form-control" value="{{ old('planned_fuel_consumption_liters', isset($trip) ? $trip->planned_fuel_consumption_liters : '') }}" placeholder="Estimated from vehicle L/km × distance">
            <div class="form-text small">Auto-estimated when vehicle (with Capacity L/km) and Planned Distance are set.</div>
            @error('planned_fuel_consumption_liters')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<hr class="my-4">

<h6 class="text-warning mb-3"><i class="bx bx-user me-2"></i>Customer (Optional)</h6>
<div class="row g-3">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Customer</label>
            <div class="input-group">
                <select name="customer_id" id="customer_id" class="form-select select2-single">
                    <option value="">Select Customer</option>
                    @foreach($customers ?? [] as $c)
                        <option value="{{ $c->id }}" data-phone="{{ $c->phone ?? '' }}" {{ (old('customer_id', isset($trip) ? $trip->customer_id : null) == $c->id) ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-primary" id="add-customer-btn" title="Create new customer">
                    <i class="bx bx-plus"></i> Add
                </button>
            </div>
            @error('customer_id')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Phone number</label>
            <input type="text" id="customer_phone_display" class="form-control" readonly placeholder="Select a customer to show phone">
        </div>
    </div>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes...">{{ old('notes', isset($trip) ? $trip->notes : '') }}</textarea>
            @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="tripCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="trip-add-customer-errors" class="alert alert-danger d-none"></div>
                <div class="mb-3">
                    <label for="trip_customer_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="trip_customer_name" placeholder="e.g. John Mwita">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                    <label for="trip_customer_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="trip_customer_phone" placeholder="e.g. 0712 345 678">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                    <label for="trip_customer_email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="trip_customer_email" placeholder="optional@example.com">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="trip-save-customer-btn">
                    <i class="bx bx-save me-1"></i>Save Customer
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // ---- Map distance calculation (Nominatim + OSRM) ----
    var distanceCalcInProgress = false;
    function setStatus(msg, isError) {
        var el = $('#distance-calc-status');
        el.removeClass('text-muted text-danger text-success').addClass(isError ? 'text-danger' : (msg.indexOf('km') !== -1 ? 'text-success' : 'text-muted')).text(msg);
    }
    var calculateDistanceUrl = '{{ route("fleet.trips.calculate-distance") }}';
    function runDistanceCalculation() {
        var origin = $('#origin_location').val().trim();
        var dest = $('#destination_location').val().trim();
        if (!origin || !dest) {
            setStatus('Enter both Origin and Destination to calculate distance.', true);
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'info', title: 'Missing addresses', text: 'Please enter both Origin and Destination location.' });
            return;
        }
        if (distanceCalcInProgress) return;
        distanceCalcInProgress = true;
        var btn = $('#calculate-distance-btn');
        btn.prop('disabled', true);
        setStatus('Searching map and calculating distance and districts...');

        $.ajax({
            url: calculateDistanceUrl,
            method: 'GET',
            data: { origin: origin, destination: dest },
            dataType: 'json'
        }).done(function(data) {
            if (data && typeof data.distance_km === 'number') {
                var km = data.distance_km;
                $('#planned_distance_km').val(km);
                estimatePlannedFuel();
                var label = data.approximate ? 'Distance: ' + km + ' km (straight-line, approximate)' : 'Distance: ' + km + ' km (driving)';
                setStatus(label, false);
                var districts = data.route_via_districts || [];
                if (districts.length > 0) {
                    $('#route-districts-list').text(districts.join(' → '));
                    $('#distance-route-districts').show();
                } else {
                    $('#distance-route-districts').hide();
                    if (data.source === 'straight_line') {
                        $('#route-districts-list').text('—');
                    }
                }
                var roads = data.route_via_roads || [];
                if (roads.length > 0) {
                    $('#route-roads-list').text(roads.join(', '));
                    $('#distance-route-roads').show();
                } else {
                    if (data.source === 'straight_line') {
                        $('#route-roads-list').text('(straight-line distance)');
                        $('#distance-route-roads').show();
                    } else if (data.source === 'driving') {
                        $('#distance-route-roads').hide();
                    } else {
                        $('#distance-route-roads').hide();
                    }
                }
                var msg = km + ' km' + (data.approximate ? ' (approximate)' : '');
                if (districts.length) msg += ' — ' + districts.length + ' districts/regions';
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Distance calculated', text: msg, timer: 2000, showConfirmButton: false });
            } else {
                $('#distance-route-districts').hide();
                $('#distance-route-roads').hide();
                setStatus(data && data.error ? data.error : 'Invalid response.', true);
            }
        }).fail(function(xhr) {
            $('#distance-route-districts').hide();
            $('#distance-route-roads').hide();
            var msg = 'Could not calculate distance.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            } else if (xhr.responseText) {
                try {
                    var j = JSON.parse(xhr.responseText);
                    if (j && j.error) msg = j.error;
                } catch (e) {}
            }
            if (msg === 'Could not calculate distance.' && (xhr.status === 500 || xhr.status === 502)) {
                msg = 'Server or map service error. Check storage/logs/laravel.log for details.';
            }
            if (xhr.status === 0) msg = 'Network error. Check your connection and that the app is running.';
            else if (xhr.status === 403) msg = 'Session expired. Please refresh the page.';
            else if (xhr.status === 404) msg = 'Route not found. Clear cache: php artisan route:clear';
            setStatus(msg, true);
        }).always(function() {
            distanceCalcInProgress = false;
            btn.prop('disabled', false);
        });
    }

    $('#calculate-distance-btn').on('click', function() {
        runDistanceCalculation();
    });

    // Optional: auto-calculate when both fields are filled and user leaves destination (with debounce)
    var distanceDebounce;
    $('#destination_location').on('blur', function() {
        var origin = $('#origin_location').val().trim();
        var dest = $(this).val().trim();
        if (origin && dest && origin.length >= 3 && dest.length >= 3) {
            clearTimeout(distanceDebounce);
            distanceDebounce = setTimeout(function() { runDistanceCalculation(); }, 600);
        }
    });

    function estimatePlannedFuel() {
        var opt = $('#vehicle_id option:selected');
        var lPerKm = parseFloat(opt.data('capacity-volume')) || 0;
        var km = parseFloat($('#planned_distance_km').val()) || 0;
        if (lPerKm > 0 && km > 0) {
            var liters = Math.round(km * lPerKm * 100) / 100;
            $('#planned_fuel_consumption_liters').val(liters);
        }
    }
    $('#vehicle_id').on('change', estimatePlannedFuel);
    $('#planned_distance_km').on('input change', estimatePlannedFuel);

    function updateCustomerPhone() {
        var opt = $('#customer_id option:selected');
        var phone = opt.data('phone') || '';
        $('#customer_phone_display').val(phone || '—');
    }
    $('#customer_id').on('change', updateCustomerPhone);
    updateCustomerPhone();

    // Auto-select driver when vehicle is selected
    $('#vehicle_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var driverId = selectedOption.data('driver-id');
        if (driverId) {
            $('#driver_id').val(driverId).trigger('change');
        }
        estimatePlannedFuel();
    });

    // Add customer button - open modal
    $('#add-customer-btn').on('click', function() {
        $('#trip-add-customer-errors').addClass('d-none').empty();
        $('#trip_customer_name, #trip_customer_phone, #trip_customer_email').val('').removeClass('is-invalid');
        $('#tripCustomerModal').modal('show');
    });

    // Save customer from modal
    $('#trip-save-customer-btn').on('click', function() {
        var name = $('#trip_customer_name').val().trim();
        var rawPhone = $('#trip_customer_phone').val().trim();
        var email = $('#trip_customer_email').val().trim();
        function normalizePhone(phone) {
            var p = (phone || '').replace(/[^0-9+]/g, '');
            if (p.startsWith('+255')) p = '255' + p.slice(4);
            else if (p.startsWith('0')) p = '255' + p.slice(1);
            else if (/^\d{9}$/.test(p)) p = '255' + p;
            return p;
        }
        var phone = normalizePhone(rawPhone);
        $('#trip-add-customer-errors').addClass('d-none').empty();
        $('#trip_customer_name, #trip_customer_phone').removeClass('is-invalid');
        if (!name) { $('#trip_customer_name').addClass('is-invalid').siblings('.invalid-feedback').text('Customer name is required'); return; }
        if (!phone || phone.length !== 12) { $('#trip_customer_phone').addClass('is-invalid').siblings('.invalid-feedback').text('Valid phone required (e.g. 0712345678 or 255712345678)'); return; }

        var btn = $('#trip-save-customer-btn');
        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');
        $.ajax({
            url: '{{ route("customers.store") }}',
            method: 'POST',
            data: { name: name, phone: phone, email: email || '', status: 'active', _token: '{{ csrf_token() }}' },
            headers: { 'Accept': 'application/json' }
        }).done(function(res) {
            var c = res.customer;
            if (c && c.id) {
                var opt = $('<option></option>').val(c.id).text(c.name).attr('data-phone', c.phone || '').prop('selected', true);
                $('#customer_id').append(opt).trigger('change');
                updateCustomerPhone();
            }
            $('#tripCustomerModal').modal('hide');
            if (typeof Swal !== 'undefined') Swal.fire('Success', 'Customer created', 'success');
            else alert('Customer created');
        }).fail(function(xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var err = xhr.responseJSON.errors;
                $.each(err, function(f, msgs) {
                    var inp = $('#trip_customer_' + f);
                    if (inp.length) inp.addClass('is-invalid').siblings('.invalid-feedback').text(msgs[0]);
                });
            } else {
                $('#trip-add-customer-errors').removeClass('d-none').text((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to create customer');
            }
        }).always(function() {
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Customer');
        });
    });
});
</script>
@endpush
