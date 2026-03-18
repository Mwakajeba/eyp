@extends('layouts.main')

@section('title', 'Add Trip Cost - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Cost Management', 'url' => route('fleet.trip-costs.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Add Cost', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Add Trip Cost</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.trip-costs.store') }}" id="cost-form" enctype="multipart/form-data">
                    @csrf

                    <h6 class="text-danger mb-3"><i class="bx bx-info-circle me-2"></i>Trip & Date</h6>
                    <div class="table-responsive mb-2">
                        <table class="table table-bordered" id="trip-lines-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50%;">Trip <span class="text-danger">*</span></th>
                                    <th style="width: 35%;">Date Incurred <span class="text-danger">*</span></th>
                                    <th style="width: 15%;"></th>
                                </tr>
                            </thead>
                            <tbody id="trip-lines-container">
                                <!-- Trip lines added dynamically -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-trip-line-btn">
                                            <i class="bx bx-plus me-1"></i>Add Trip Line
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="form-text">Add one or more trips. Cost total will be divided equally across all trips.</div>
                    @error('trip_lines')<div class="text-danger small">{{ $message }}</div>@enderror

                    <hr class="my-4">

                    <h6 class="text-danger mb-3"><i class="bx bx-money me-2"></i>Cost Lines</h6>
                    <div class="alert alert-light border small mb-3">
                        <i class="bx bx-info-circle me-1"></i><strong>Qty</strong> = number of trips (auto). <strong>Amount (TZS)</strong> = cost per trip. <strong>Total</strong> = Qty × Amount. On save, you will see cost for each trip.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="cost-lines-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 22%;">GL Account <span class="text-danger">*</span></th>
                                    <th style="width: 8%;">Qty</th>
                                    <th style="width: 12%;">Amount (TZS) per trip <span class="text-danger">*</span></th>
                                    <th style="width: 10%;">Total (Qty×Amount)</th>
                                    <th style="width: 18%;">Cost Category</th>
                                    <th style="width: 22%;">Description</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="cost-lines-container">
                                <!-- Cost lines added dynamically -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-line-btn">
                                            <i class="bx bx-plus me-1"></i>Add Cost Line
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Grand Total:</td>
                                    <td class="fw-bold" id="total-amount">0.00 TZS</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div id="per-trip-breakdown"></div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-danger mb-0"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-attachment-line">
                            <i class="bx bx-plus me-1"></i>Add Line
                        </button>
                    </div>
                    <div id="attachment-lines">
                        <div class="row g-3 mb-2 attachment-line">
                            <div class="col-md-11">
                                <div class="mb-3">
                                    <label class="form-label">Attach File</label>
                                    <input type="file" name="attachments[]" class="form-control" accept="image/*,.pdf,.doc,.docx">
                                    <div class="form-text">Images, PDF, Word documents (Max 2MB)</div>
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
                    <h6 class="text-danger mb-3"><i class="bx bx-wallet me-2"></i>Payment Information</h6>
                    <div class="row g-3">
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
                                <div class="form-text">Select the cash or bank account from which this cost was paid</div>
                                @error('paid_from_account_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Receipt Number</label>
                                <input type="text" name="receipt_number" class="form-control" value="{{ old('receipt_number') }}">
                                @error('receipt_number')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="text-danger mb-3"><i class="bx bx-receipt me-2"></i>Additional Information</h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="card border-warning mb-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Billable to Customer</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="is_billable_to_customer" name="is_billable_to_customer" value="1" {{ old('is_billable_to_customer') ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold" for="is_billable_to_customer">
                                            Yes, bill this cost to customer
                                        </label>
                                    </div>
                                    <div class="alert alert-info mb-0" id="billable-info" style="display: none;">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>Note:</strong> This cost will be marked as billable and can be included in customer invoices. 
                                        Make sure the trip is associated with a customer to enable billing.
                                    </div>
                                    <div class="alert alert-light border mb-0" id="billable-default" style="font-size: 0.875rem;">
                                        <i class="bx bx-bulb me-2"></i>
                                        Enable this option if you want to pass this cost to the customer. This is useful for:
                                        <ul class="mb-0 mt-2" style="font-size: 0.85rem;">
                                            <li>Toll charges and parking fees</li>
                                            <li>Loading/offloading charges</li>
                                            <li>Extra fuel costs for special requests</li>
                                            <li>Any other reimbursable expenses</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @error('is_billable_to_customer')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes about these costs...">{{ old('notes') }}</textarea>
                                <div class="form-text">Add any relevant information about these costs (e.g., reason, vendor details, etc.)</div>
                                @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.trip-costs.index', isset($trip) ? ['trip_id' => $trip->id] : []) }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-save me-1"></i>Add Cost
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
    let tripLineIndex = 0;
    let costLineIndex = 0;
    const glAccounts = @json($glAccounts);
    const costCategories = @json($costCategories);
    const trips = @json($trips);

    // Initialize Select2
    function initSelect2($element) {
        if ($element.length && !$element.hasClass('select2-hidden-accessible')) {
            $element.select2({ theme: 'bootstrap-5', width: '100%' });
        }
    }

    // Get trip line count
    function getTripLineCount() {
        return $('.trip-line-row').length;
    }

    // Update all Qty fields in cost lines
    function updateCostLineQtys() {
        const qty = getTripLineCount();
        $('.qty-display').text(qty);
        $('.qty-input').val(qty);
        calculateTotal();
    }

    // Add trip line (defaultTripId = pre-select when coming from trip link)
    function addTripLine(defaultTripId) {
        tripLineIndex++;
        const lineHtml = `
            <tr class="trip-line-row" data-index="${tripLineIndex}">
                <td>
                    <select name="trip_lines[${tripLineIndex}][trip_id]" class="form-select trip-select" required>
                        <option value="">Select Trip</option>
                        ${trips.map(t => `<option value="${t.id}" ${(defaultTripId && t.id == defaultTripId) ? 'selected' : ''}>${t.trip_number}${t.date ? ' (' + t.date + ')' : ''} - ${t.vehicle_name}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <input type="date" name="trip_lines[${tripLineIndex}][date_incurred]" class="form-control" value="{{ date('Y-m-d') }}" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-trip-line-btn" title="Remove">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#trip-lines-container').append(lineHtml);
        initSelect2($('#trip-lines-container tr').last().find('.trip-select'));
        updateCostLineQtys();
    }

    // Remove trip line
    $(document).on('click', '.remove-trip-line-btn', function() {
        if ($('.trip-line-row').length > 1) {
            $(this).closest('tr').remove();
            updateCostLineQtys();
        } else {
            if (typeof Swal !== 'undefined') Swal.fire('Info', 'At least one trip line is required.', 'info');
            else alert('At least one trip line is required.');
        }
    });

    $('#add-trip-line-btn').on('click', addTripLine);

    // Add cost line (Qty = trip count, read-only)
    function addCostLine() {
        costLineIndex++;
        const qty = getTripLineCount();
        const lineHtml = `
            <tr class="cost-line-row" data-index="${costLineIndex}">
                <td>
                    <select name="cost_lines[${costLineIndex}][gl_account_id]" class="form-select gl-account-select" required>
                        <option value="">Select GL Account</option>
                        ${glAccounts.map(acc => `<option value="${acc.id}">${acc.account_code} - ${acc.account_name}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <span class="qty-display">${qty}</span>
                    <input type="hidden" name="cost_lines[${costLineIndex}][qty]" class="qty-input" value="${qty}">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" class="form-control amount-per-trip-input" placeholder="Per trip" required>
                    <input type="hidden" name="cost_lines[${costLineIndex}][amount]" class="amount-total-input" value="0">
                </td>
                <td class="line-total-cell">
                    <span class="line-total-display">0.00</span> TZS
                </td>
                <td>
                    <select name="cost_lines[${costLineIndex}][cost_category_id]" class="form-select cost-category-select">
                        <option value="">Select Category</option>
                        ${costCategories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <input type="text" name="cost_lines[${costLineIndex}][description]" class="form-control" placeholder="Description">
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
        initSelect2($newRow.find('.cost-category-select'));
        calculateTotal();
    }

    // Remove cost line
    $(document).on('click', '.remove-line-btn', function() {
        if ($('.cost-line-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotal();
        } else {
            if (typeof Swal !== 'undefined') Swal.fire('Info', 'At least one cost line is required.', 'info');
            else alert('At least one cost line is required.');
        }
    });

    // Calculate total amount and update line totals (Amount = per trip, Total = Qty × Amount)
    function calculateTotal() {
        const qty = getTripLineCount();
        let grandTotal = 0;
        $('.cost-line-row').each(function() {
            const amountPerTrip = parseFloat($(this).find('.amount-per-trip-input').val()) || 0;
            const lineTotal = qty * amountPerTrip;
            $(this).find('.amount-total-input').val(lineTotal);
            grandTotal += lineTotal;
            $(this).find('.line-total-display').text(lineTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
        });
        $('#total-amount').text(grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' TZS');
        updatePerTripBreakdown();
    }

    // Show per-trip cost breakdown (Amount is per trip, so each trip gets that amount per line)
    function updatePerTripBreakdown() {
        const tripCount = getTripLineCount();
        if (tripCount === 0) return;
        let html = '<div class="alert alert-light border mt-3"><h6 class="mb-2"><i class="bx bx-list-ul me-2"></i>Cost per trip (after save)</h6>';
        const tripRows = $('.trip-line-row');
        tripRows.each(function(idx) {
            const tripSelect = $(this).find('.trip-select option:selected');
            const tripLabel = tripSelect.text() || 'Trip ' + (idx + 1);
            let totalForTrip = 0;
            $('.cost-line-row').each(function() {
                const amountPerTrip = parseFloat($(this).find('.amount-per-trip-input').val()) || 0;
                totalForTrip += amountPerTrip;
            });
            html += '<div class="mb-1"><strong>' + tripLabel + ':</strong> ' + totalForTrip.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' TZS</div>';
        });
        html += '</div>';
        $('#per-trip-breakdown').html(html);
    }

    $(document).on('input', '.amount-per-trip-input', function() { calculateTotal(); });

    // Add first trip line and first cost line by default (pre-fill trip if from trip link)
    addTripLine(@json(isset($trip) && $trip ? $trip->id : null));
    addCostLine();

    $('#add-line-btn').on('click', addCostLine);

    // Form submit: send Total (Qty × Amount per trip) as amount so backend divides across trips
    $('#cost-form').on('submit', function() {
        const tripCount = getTripLineCount();
        if (tripCount === 0) {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'Add at least one trip line.', 'error');
            else alert('Add at least one trip line.');
            return false;
        }
        // Update qty and set hidden amount = total (qty × amount per trip) for each cost line
        $('.cost-line-row').each(function() {
            $(this).find('.qty-input').val(tripCount);
            $(this).find('.qty-display').text(tripCount);
            const amountPerTrip = parseFloat($(this).find('.amount-per-trip-input').val()) || 0;
            $(this).find('.amount-total-input').val(tripCount * amountPerTrip);
        });
        return true;
    });

    // Initialize Select2 for existing elements
    initSelect2($('.gl-account-select'));
    initSelect2($('.cost-category-select'));

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
                        <div class="form-text">Images, PDF, Word documents (Max 2MB)</div>
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

    // Toggle billable to customer info
    $('#is_billable_to_customer').on('change', function() {
        if ($(this).is(':checked')) {
            $('#billable-info').slideDown();
            $('#billable-default').slideUp();
        } else {
            $('#billable-info').slideUp();
            $('#billable-default').slideDown();
        }
    });

    // Trigger change event on page load if checkbox is checked
    if ($('#is_billable_to_customer').is(':checked')) {
        $('#is_billable_to_customer').trigger('change');
    }
});
</script>
@endpush
