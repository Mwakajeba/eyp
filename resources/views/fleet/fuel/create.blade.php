@extends('layouts.main')

@section('title', 'Add Fuel Log - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fuel Management', 'url' => route('fleet.fuel.index'), 'icon' => 'bx bx-gas-pump'],
            ['label' => 'Add Fuel Log', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-orange text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Add Fuel Log</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.fuel.store') }}" id="fuel-log-form" enctype="multipart/form-data">
                    @csrf

                    <!-- Vehicle & Trip Information -->
                    <h6 class="text-orange mb-3"><i class="bx bx-car me-2"></i>Vehicle & Trip Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                                <select name="vehicle_id" class="form-select select2-single" required id="vehicle_id">
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
                            <div class="mb-3" id="trip-field-wrapper">
                                <label class="form-label">Trip <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <select name="trip_id" class="form-select select2-single" id="trip_id" required>
                                        <option value="">Select vehicle first</option>
                                    </select>
                                    <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted d-none" id="trip-loading-spinner" style="pointer-events: none;">
                                        <i class="bx bx-loader-alt bx-spin bx-sm"></i>
                                    </span>
                                </div>
                                <div class="form-text" id="trip-loading-text">Select a vehicle to load trips (planned / in progress).</div>
                                @error('trip_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Fuel Details -->
                    <h6 class="text-orange mb-3"><i class="bx bx-gas-pump me-2"></i>Fuel Details</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Fuel Type</label>
                                <select name="fuel_type" class="form-select">
                                    <option value="">Select Type</option>
                                    <option value="petrol" {{ old('fuel_type') == 'petrol' ? 'selected' : '' }}>Petrol</option>
                                    <option value="diesel" {{ old('fuel_type') == 'diesel' ? 'selected' : '' }}>Diesel</option>
                                    <option value="premium" {{ old('fuel_type') == 'premium' ? 'selected' : '' }}>Premium</option>
                                    <option value="super" {{ old('fuel_type') == 'super' ? 'selected' : '' }}>Super</option>
                                </select>
                                @error('fuel_type')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Date Filled <span class="text-danger">*</span></label>
                                <input type="date" name="date_filled" class="form-control" value="{{ old('date_filled', date('Y-m-d')) }}" required>
                                @error('date_filled')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Time Filled</label>
                                <input type="time" name="time_filled" class="form-control" value="{{ old('time_filled') }}">
                                @error('time_filled')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Odometer Information -->
                    <h6 class="text-orange mb-3"><i class="bx bx-tachometer me-2"></i>Odometer Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Current Odometer Reading <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="odometer_reading" class="form-control" value="{{ old('odometer_reading') }}" placeholder="0.00" required id="odometer_reading">
                                @error('odometer_reading')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Previous Odometer Reading</label>
                                <input type="number" step="0.01" min="0" name="previous_odometer" class="form-control bg-light" value="{{ old('previous_odometer') }}" placeholder="Select vehicle to load" id="previous_odometer" readonly>
                                <div class="form-text">Displayed from last fuel log or trip start; not editable.</div>
                                @error('previous_odometer')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Fuel Card Information -->
                    <h6 class="text-orange mb-3"><i class="bx bx-credit-card me-2"></i>Fuel Card Information</h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fuel_card_used" value="1" id="fuel_card_used" {{ old('fuel_card_used') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="fuel_card_used">
                                        Fuel Card Used
                                    </label>
                                </div>
                                <div class="form-text" id="fuel-card-hint">When checked, Paid From (below) will show only the driver's assigned card for the selected trip.</div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Cost Lines -->
                    <h6 class="text-orange mb-3"><i class="bx bx-money me-2"></i>Cost Lines</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="cost-lines-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20%;">GL Account <span class="text-danger">*</span></th>
                                    <th style="width: 12%;">Liters Filled <span class="text-danger">*</span></th>
                                    <th style="width: 12%;">Cost Per Liter <span class="text-danger">*</span></th>
                                    <th style="width: 15%;">Fuel Station</th>
                                    <th style="width: 15%;">Amount (TZS) <span class="text-danger">*</span></th>
                                    <th style="width: 20%;">Description</th>
                                    <th style="width: 6%;"></th>
                                </tr>
                            </thead>
                            <tbody id="cost-lines-container">
                                <!-- First line will be added by default -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-orange" id="add-line-btn" style="background-color: #fd7e14; border-color: #fd7e14; color: #fff;">
                                            <i class="bx bx-plus me-1"></i>Add Line
                                        </button>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Total Amount:</td>
                                    <td class="fw-bold" id="total-amount">0.00 TZS</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <hr class="my-4">

                    <!-- Attachments -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-orange mb-0"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                        <button type="button" class="btn btn-sm btn-outline-orange" id="add-attachment-line">
                            <i class="bx bx-plus me-1"></i>Add Line
                        </button>
                    </div>
                    <div id="attachment-lines">
                        <div class="row g-3 mb-2 attachment-line">
                            <div class="col-md-11">
                                <div class="mb-3">
                                    <label class="form-label">Attach File</label>
                                    <input type="file" name="attachments[]" class="form-control" accept="image/*,.pdf,.doc,.docx">
                                    <div class="form-text">Images, PDF, Word documents (Max 10MB)</div>
                                    @error('attachments.*')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-attachment-line" style="display: none;">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Receipt & Payment Information -->
                    <h6 class="text-orange mb-3"><i class="bx bx-wallet me-2"></i>Receipt & Payment Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Receipt Number</label>
                                <input type="text" name="receipt_number" class="form-control" value="{{ old('receipt_number') }}" placeholder="Receipt number">
                                @error('receipt_number')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Paid From (Bank Account) <span class="text-danger">*</span></label>
                                <select name="paid_from_account_id" id="paid_from_account_id" class="form-select select2-single" required>
                                    <option value="">Select Bank Account</option>
                                    @foreach($bankAccounts as $bankAccount)
                                        <option value="{{ $bankAccount->id }}" {{ old('paid_from_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                            {{ $bankAccount->name }}@if($bankAccount->account_number) - {{ $bankAccount->account_number }}@endif@if($bankAccount->currency) ({{ $bankAccount->currency }})@endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">When "Fuel Card Used" is checked, only the driver's assigned card (for the selected trip) is shown here.</div>
                                @error('paid_from_account_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Notes -->
                    <h6 class="text-orange mb-3"><i class="bx bx-note me-2"></i>Additional Notes</h6>
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
                        <a href="{{ route('fleet.fuel.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-orange" style="background-color: #fd7e14; border-color: #fd7e14; color: #fff;">
                            <i class="bx bx-save me-1"></i>Save Fuel Log
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
    let lineIndex = 0;
    const glAccountsDiesel = @json($glAccountsDiesel);
    const glAccountsPetrol = @json($glAccountsPetrol);
    const bankAccounts = @json($bankAccountsForScript);
    let trips = [];

    function getGlAccountsForFuelType() {
        const ft = $('select[name="fuel_type"]').val();
        if (ft === 'diesel') return glAccountsDiesel;
        if (ft === 'petrol' || ft === 'premium' || ft === 'super') return glAccountsPetrol;
        return glAccountsDiesel.length ? glAccountsDiesel : glAccountsPetrol;
    }

    // Initialize Select2
    function initSelect2($element) {
        if ($element.length && !$element.hasClass('select2-hidden-accessible')) {
            $element.select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        }
    }

    // Initialize Select2 for existing elements (trip_id gets re-inited after vehicle load)
    initSelect2($('#vehicle_id'));
    initSelect2($('#paid_from_account_id'));
    initSelect2($('#trip_id'));

    // Previous odometer: fetch when vehicle or trip changes
    function fetchPreviousOdometer() {
        const vehicleId = $('#vehicle_id').val();
        const tripId = $('#trip_id').val();
        if (!vehicleId) {
            $('#previous_odometer').val('');
            return;
        }
        $('#previous_odometer').attr('placeholder', 'Loading…');
        $.get('{{ route("fleet.fuel.previous-odometer") }}', { vehicle_id: vehicleId, trip_id: tripId || '' }, function(data) {
            const val = data.previous_odometer != null ? String(data.previous_odometer) : '';
            $('#previous_odometer').val(val).attr('placeholder', 'Auto-filled');
        }).fail(function() {
            $('#previous_odometer').val('').attr('placeholder', 'Select vehicle to load');
        });
    }
    $('#vehicle_id, #trip_id').on('change', fetchPreviousOdometer);

    // Load trips for selected vehicle (planned / dispatched / in progress)
    function loadTripsForVehicle(vehicleId) {
        const $tripSel = $('#trip_id');
        const $spinner = $('#trip-loading-spinner');
        const $loadingText = $('#trip-loading-text');

        if (!$tripSel.length) return;
        if ($tripSel.hasClass('select2-hidden-accessible')) {
            $tripSel.select2('destroy');
        }
        $tripSel.prop('disabled', true).empty().append($('<option value="">Loading trips…</option>'));
        $spinner.removeClass('d-none');
        $loadingText.text('Loading trips…');
        trips = [];
        refreshPaidFrom();

        if (!vehicleId) {
            $tripSel.prop('disabled', true).empty().append($('<option value="">Select vehicle first</option>'));
            $spinner.addClass('d-none');
            $loadingText.text('Select a vehicle to load trips (planned / in progress).');
            initSelect2($tripSel);
            return;
        }

        $.get('{{ route("fleet.fuel.trips-by-vehicle") }}', { vehicle_id: vehicleId }, function(data) {
            trips = data.trips || [];
            $tripSel.empty().append($('<option value="">' + (trips.length ? 'Select Trip' : 'No incomplete trips for this vehicle') + '</option>'));
            trips.forEach(function(t) {
                $tripSel.append($('<option></option>').attr('value', t.id).text(t.trip_number));
            });
            $tripSel.prop('disabled', false);
            $spinner.addClass('d-none');
            $loadingText.text(trips.length ? trips.length + ' trip(s) loaded. Select one.' : 'No incomplete trips for this vehicle.');
            initSelect2($tripSel);
            fetchPreviousOdometer();
        }).fail(function() {
            $tripSel.empty().append($('<option value="">Error loading trips</option>')).prop('disabled', true);
            $spinner.addClass('d-none');
            $loadingText.text('Failed to load trips. Try again.');
            initSelect2($tripSel);
        });
    }

    $('#vehicle_id').on('change', function() {
        loadTripsForVehicle($(this).val());
    });

    // When trip is selected, auto-fill estimated liters and refresh Paid From if fuel card used
    $('#trip_id').on('change', function() {
        const tripId = $(this).val();
        const trip = trips.find(t => String(t.id) === String(tripId));
        const firstLitersInput = $('.liters-filled-input').first();
        if (trip && trip.planned_fuel_liters != null && firstLitersInput.length) {
            firstLitersInput.val(parseFloat(trip.planned_fuel_liters).toFixed(2));
            calculateLineAmount(firstLitersInput.closest('tr'));
        }
        refreshPaidFrom();
    });

    function refreshPaidFrom() {
        const fuelCardUsed = $('#fuel_card_used').is(':checked');
        const tripId = $('#trip_id').val();
        const trip = tripId ? trips.find(t => String(t.id) === String(tripId)) : null;
        const driverCardId = fuelCardUsed && trip && trip.driver_fuel_card_bank_account_id ? trip.driver_fuel_card_bank_account_id : null;
        const $sel = $('#paid_from_account_id');
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.select2('destroy');
        }
        $sel.find('option:not(:first)').remove();
        if (driverCardId) {
            var card = bankAccounts.find(function(b) { return String(b.id) === String(driverCardId); });
            if (card) {
                var text = card.account_number ? card.name + ' - ' + card.account_number : card.name;
                if (card.currency) text += ' (' + card.currency + ')';
                $sel.append($('<option></option>').attr('value', card.id).text(text));
            } else if (trip && (trip.driver_fuel_card_name || trip.driver_fuel_card_bank_account_id)) {
                var label = trip.driver_fuel_card_name || ('Card #' + driverCardId);
                if (trip.driver_fuel_card_account_number) label += ' - ' + trip.driver_fuel_card_account_number;
                $sel.append($('<option></option>').attr('value', driverCardId).text(label));
            }
            if ($sel.find('option[value="' + driverCardId + '"]').length) {
                $sel.val(driverCardId);
            }
        } else {
            bankAccounts.forEach(function(b) {
                var text = b.account_number ? b.name + ' - ' + b.account_number : b.name;
                if (b.currency) text += ' (' + b.currency + ')';
                $sel.append($('<option></option>').attr('value', b.id).text(text));
            });
        }
        initSelect2($sel);
    }

    // When fuel type changes, refresh GL account dropdowns to show only diesel or petrol accounts
    $('select[name="fuel_type"]').on('change', function() {
        const accounts = getGlAccountsForFuelType();
        $('.gl-account-select').each(function() {
            const current = $(this).val();
            const $sel = $(this);
            $sel.find('option:not(:first)').remove();
            accounts.forEach(acc => {
                $sel.append($('<option></option>').attr('value', acc.id).text(acc.account_code + ' - ' + acc.account_name));
            });
            if (current && accounts.some(a => String(a.id) === String(current))) {
                $sel.val(current);
            } else {
                $sel.val('');
            }
        });
    });

    // Add cost line
    function addCostLine() {
        lineIndex++;
        const accounts = getGlAccountsForFuelType();
        const accountsOptions = accounts.map(acc => `<option value="${acc.id}">${acc.account_code} - ${acc.account_name}</option>`).join('');
        const lineHtml = `
            <tr class="cost-line-row" data-index="${lineIndex}">
                <td>
                    <select name="cost_lines[${lineIndex}][gl_account_id]" class="form-select gl-account-select" required>
                        <option value="">Select GL Account</option>
                        ${accountsOptions}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="cost_lines[${lineIndex}][liters_filled]" class="form-control liters-filled-input" placeholder="0.00" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="cost_lines[${lineIndex}][cost_per_liter]" class="form-control cost-per-liter-input" placeholder="0.00" required>
                </td>
                <td>
                    <input type="text" name="cost_lines[${lineIndex}][fuel_station]" class="form-control" placeholder="e.g., Shell, Total">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="cost_lines[${lineIndex}][amount]" class="form-control amount-input" placeholder="0.00" required readonly>
                </td>
                <td>
                    <input type="text" name="cost_lines[${lineIndex}][description]" class="form-control" placeholder="Description">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn" title="Remove Line">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#cost-lines-container').append(lineHtml);
        
        // Initialize Select2 for new line
        const $newRow = $('#cost-lines-container tr').last();
        initSelect2($newRow.find('.gl-account-select'));
        
        // Calculate total
        calculateTotal();
    }

    // Remove cost line
    $(document).on('click', '.remove-line-btn', function() {
        if ($('.cost-line-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotal();
        } else {
            alert('At least one cost line is required.');
        }
    });

    // Calculate total amount
    function calculateTotal() {
        let total = 0;
        $('.amount-input').each(function() {
            const amount = parseFloat($(this).val()) || 0;
            total += amount;
        });
        $('#total-amount').text(total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' TZS');
    }

    // Calculate amount for a specific line
    function calculateLineAmount($row) {
        const liters = parseFloat($row.find('.liters-filled-input').val()) || 0;
        const costPerLiter = parseFloat($row.find('.cost-per-liter-input').val()) || 0;
        const amount = liters * costPerLiter;
        $row.find('.amount-input').val(amount.toFixed(2));
        calculateTotal();
    }

    // Recalculate amount when liters or cost per liter changes
    $(document).on('input', '.liters-filled-input, .cost-per-liter-input', function() {
        const $row = $(this).closest('tr');
        calculateLineAmount($row);
    });

    // Add first line by default
    addCostLine();

    // Add line button
    $('#add-line-btn').on('click', function() {
        addCostLine();
    });


    // When Fuel Card Used is checked, Paid From shows only the selected trip driver's card; unchecked = all banks
    $('#fuel_card_used').on('change', function() {
        refreshPaidFrom();
    });

    // Attachment lines management
    let attachmentLineIndex = 0;

    // Add attachment line
    $('#add-attachment-line').on('click', function() {
        attachmentLineIndex++;
        const lineHtml = `
            <div class="row g-3 mb-2 attachment-line" data-index="${attachmentLineIndex}">
                <div class="col-md-11">
                    <div class="mb-3">
                        <label class="form-label">Attach File</label>
                        <input type="file" name="attachments[]" class="form-control" accept="image/*,.pdf,.doc,.docx">
                        <div class="form-text">Images, PDF, Word documents (Max 10MB)</div>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-attachment-line">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#attachment-lines').append(lineHtml);
        updateAttachmentRemoveButtons();
    });

    // Remove attachment line
    $(document).on('click', '.remove-attachment-line', function() {
        if ($('.attachment-line').length > 1) {
            $(this).closest('.attachment-line').remove();
            updateAttachmentRemoveButtons();
        } else {
            alert('At least one attachment line is required.');
        }
    });

    // Update remove buttons visibility
    function updateAttachmentRemoveButtons() {
        if ($('.attachment-line').length > 1) {
            $('.remove-attachment-line').show();
        } else {
            $('.remove-attachment-line').hide();
        }
    }

    // Initialize remove buttons
    updateAttachmentRemoveButtons();
});
</script>
@endpush
