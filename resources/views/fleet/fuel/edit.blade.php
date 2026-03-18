@extends('layouts.main')

@section('title', 'Edit Fuel Log - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fuel Management', 'url' => route('fleet.fuel.index'), 'icon' => 'bx bx-gas-pump'],
            ['label' => 'Edit Fuel Log', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-orange text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Fuel Log</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.fuel.update', $fuelLog->hash_id) }}" id="fuel-log-form" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Vehicle & Trip Information -->
                    <h6 class="text-orange mb-3"><i class="bx bx-car me-2"></i>Vehicle & Trip Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                                <select name="vehicle_id" class="form-select select2-single" required id="vehicle_id">
                                    <option value="">Select Vehicle</option>
                                    @foreach($vehicles as $v)
                                        <option value="{{ $v->id }}" {{ old('vehicle_id', $fuelLog->vehicle_id) == $v->id ? 'selected' : '' }}>
                                            {{ $v->name }} ({{ $v->registration_number ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Trip (Optional)</label>
                                <select name="trip_id" class="form-select select2-single" id="trip_id">
                                    <option value="">Select Trip</option>
                                    @foreach($trips as $t)
                                        @php
                                            $tripDate = $t->actual_start_date ?? $t->planned_start_date;
                                            $dateStr = $tripDate ? $tripDate->format('d-M-Y') : '';
                                        @endphp
                                        <option value="{{ $t->id }}" {{ old('trip_id', $fuelLog->trip_id) == $t->id ? 'selected' : '' }}>
                                            {{ $t->trip_number }}@if($dateStr) ({{ $dateStr }})@endif - {{ $t->vehicle->name ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
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
                                    <option value="petrol" {{ old('fuel_type', $fuelLog->fuel_type) == 'petrol' ? 'selected' : '' }}>Petrol</option>
                                    <option value="diesel" {{ old('fuel_type', $fuelLog->fuel_type) == 'diesel' ? 'selected' : '' }}>Diesel</option>
                                    <option value="premium" {{ old('fuel_type', $fuelLog->fuel_type) == 'premium' ? 'selected' : '' }}>Premium</option>
                                    <option value="super" {{ old('fuel_type', $fuelLog->fuel_type) == 'super' ? 'selected' : '' }}>Super</option>
                                </select>
                                @error('fuel_type')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Date Filled <span class="text-danger">*</span></label>
                                <input type="date" name="date_filled" class="form-control" value="{{ old('date_filled', $fuelLog->date_filled ? $fuelLog->date_filled->format('Y-m-d') : date('Y-m-d')) }}" required>
                                @error('date_filled')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Time Filled</label>
                                <input type="time" name="time_filled" class="form-control" value="{{ old('time_filled', $fuelLog->time_filled ? \Carbon\Carbon::parse($fuelLog->time_filled)->format('H:i') : '') }}">
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
                                <input type="number" step="0.01" min="0" name="odometer_reading" class="form-control" value="{{ old('odometer_reading', $fuelLog->odometer_reading) }}" placeholder="0.00" required id="odometer_reading">
                                @error('odometer_reading')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Previous Odometer Reading</label>
                                <input type="number" step="0.01" min="0" name="previous_odometer" class="form-control" value="{{ old('previous_odometer', $fuelLog->previous_odometer) }}" placeholder="Auto-filled from last log" id="previous_odometer">
                                <div class="form-text">Leave blank to auto-fill from last fuel log</div>
                                @error('previous_odometer')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Fuel Card Information -->
                    <h6 class="text-orange mb-3"><i class="bx bx-credit-card me-2"></i>Fuel Card Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fuel_card_used" value="1" id="fuel_card_used" {{ old('fuel_card_used', $fuelLog->fuel_card_used) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="fuel_card_used">
                                        Fuel Card Used
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" id="fuel-card-details" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Fuel Card Number</label>
                                <input type="text" name="fuel_card_number" class="form-control" value="{{ old('fuel_card_number', $fuelLog->fuel_card_number) }}" placeholder="Card number">
                                @error('fuel_card_number')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6" id="fuel-card-type-field" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Fuel Card Type</label>
                                <input type="text" name="fuel_card_type" class="form-control" value="{{ old('fuel_card_type', $fuelLog->fuel_card_type) }}" placeholder="e.g., Shell Card, Total Card">
                                @error('fuel_card_type')<div class="text-danger small">{{ $message }}</div>@enderror
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
                                @foreach($costLines as $idx => $line)
                                <tr class="cost-line-row" data-index="{{ $idx + 1 }}">
                                    <td>
                                        <select name="cost_lines[{{ $idx + 1 }}][gl_account_id]" class="form-select gl-account-select" required>
                                            <option value="">Select GL Account</option>
                                            @foreach($glAccounts as $acc)
                                                <option value="{{ $acc->id }}" {{ old('cost_lines.'.($idx+1).'.gl_account_id', $line['gl_account_id']) == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="cost_lines[{{ $idx + 1 }}][liters_filled]" class="form-control liters-filled-input" placeholder="0.00" value="{{ old('cost_lines.'.($idx+1).'.liters_filled', $line['liters_filled']) }}" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="cost_lines[{{ $idx + 1 }}][cost_per_liter]" class="form-control cost-per-liter-input" placeholder="0.00" value="{{ old('cost_lines.'.($idx+1).'.cost_per_liter', $line['cost_per_liter']) }}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="cost_lines[{{ $idx + 1 }}][fuel_station]" class="form-control" placeholder="e.g., Shell, Total" value="{{ old('cost_lines.'.($idx+1).'.fuel_station', $line['fuel_station']) }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="cost_lines[{{ $idx + 1 }}][amount]" class="form-control amount-input" placeholder="0.00" value="{{ old('cost_lines.'.($idx+1).'.amount', $line['amount']) }}" required readonly>
                                    </td>
                                    <td>
                                        <input type="text" name="cost_lines[{{ $idx + 1 }}][description]" class="form-control" placeholder="Description" value="{{ old('cost_lines.'.($idx+1).'.description', $line['description']) }}">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn" title="Remove Line">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
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
                    @if($fuelLog->attachments && count($fuelLog->attachments) > 0)
                    <p class="small text-muted mb-2">Existing: @foreach($fuelLog->attachments as $a) <a href="{{ asset('storage/'.($a['path'] ?? '')) }}" target="_blank">{{ $a['original_name'] ?? 'File' }}</a>@if(!$loop->last), @endif @endforeach</p>
                    @endif
                    <div id="attachment-lines">
                        <div class="row g-3 mb-2 attachment-line">
                            <div class="col-md-11">
                                <div class="mb-3">
                                    <label class="form-label">Attach File</label>
                                    <input type="file" name="attachments[]" class="form-control" accept="image/*,.pdf,.doc,.docx">
                                    <div class="form-text">Images, PDF, Word documents (Max 10MB). Add new files only; existing are kept.</div>
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
                                <input type="text" name="receipt_number" class="form-control" value="{{ old('receipt_number', $fuelLog->receipt_number) }}" placeholder="Receipt number">
                                @error('receipt_number')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Paid From (Bank Account) <span class="text-danger">*</span></label>
                                <select name="paid_from_account_id" id="paid_from_account_id" class="form-select select2-single" required>
                                    <option value="">Select Bank Account</option>
                                    @foreach($bankAccounts as $bankAccount)
                                        <option value="{{ $bankAccount->id }}" {{ old('paid_from_account_id', $paidFromAccountId) == $bankAccount->id ? 'selected' : '' }}>
                                            {{ $bankAccount->name }}@if($bankAccount->account_number) - {{ $bankAccount->account_number }}@endif@if($bankAccount->currency) ({{ $bankAccount->currency }})@endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select the cash or bank account from which this fuel was paid</div>
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
                                <textarea name="notes" class="form-control" rows="3" placeholder="Any additional notes or comments">{{ old('notes', $fuelLog->notes) }}</textarea>
                                @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.fuel.show', $fuelLog->hash_id) }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-orange" style="background-color: #fd7e14; border-color: #fd7e14; color: #fff;">
                            <i class="bx bx-save me-1"></i>Update Fuel Log
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
    let lineIndex = {{ count($costLines) }};
    const glAccounts = @json($glAccounts);

    // Initialize Select2
    function initSelect2($element) {
        if ($element.length && !$element.hasClass('select2-hidden-accessible')) {
            $element.select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        }
    }

    initSelect2($('.select2-single'));
    initSelect2($('.gl-account-select'));

    // Add cost line
    function addCostLine() {
        lineIndex++;
        const lineHtml = `
            <tr class="cost-line-row" data-index="${lineIndex}">
                <td>
                    <select name="cost_lines[${lineIndex}][gl_account_id]" class="form-select gl-account-select" required>
                        <option value="">Select GL Account</option>
                        ${glAccounts.map(acc => `<option value="${acc.id}">${acc.account_code} - ${acc.account_name}</option>`).join('')}
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
        const $newRow = $('#cost-lines-container tr').last();
        initSelect2($newRow.find('.gl-account-select'));
        calculateTotal();
    }

    $(document).on('click', '.remove-line-btn', function() {
        if ($('.cost-line-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotal();
        } else {
            alert('At least one cost line is required.');
        }
    });

    function calculateTotal() {
        let total = 0;
        $('.amount-input').each(function() {
            const amount = parseFloat($(this).val()) || 0;
            total += amount;
        });
        $('#total-amount').text(total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' TZS');
    }

    function calculateLineAmount($row) {
        const liters = parseFloat($row.find('.liters-filled-input').val()) || 0;
        const costPerLiter = parseFloat($row.find('.cost-per-liter-input').val()) || 0;
        const amount = liters * costPerLiter;
        $row.find('.amount-input').val(amount.toFixed(2));
        calculateTotal();
    }

    $(document).on('input', '.liters-filled-input, .cost-per-liter-input', function() {
        const $row = $(this).closest('tr');
        calculateLineAmount($row);
    });

    $('#add-line-btn').on('click', function() {
        addCostLine();
    });

    // Initial total
    calculateTotal();

    // Show/hide fuel card fields
    $('#fuel_card_used').on('change', function() {
        if ($(this).is(':checked')) {
            $('#fuel-card-details, #fuel-card-type-field').show();
        } else {
            $('#fuel-card-details, #fuel-card-type-field').hide();
        }
    });
    $('#fuel_card_used').trigger('change');

    // Attachment lines management
    let attachmentLineIndex = 0;
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

    $(document).on('click', '.remove-attachment-line', function() {
        if ($('.attachment-line').length > 1) {
            $(this).closest('.attachment-line').remove();
            updateAttachmentRemoveButtons();
        } else {
            alert('At least one attachment line is required.');
        }
    });

    function updateAttachmentRemoveButtons() {
        if ($('.attachment-line').length > 1) {
            $('.remove-attachment-line').show();
        } else {
            $('.remove-attachment-line').hide();
        }
    }
    updateAttachmentRemoveButtons();
});
</script>
@endpush
