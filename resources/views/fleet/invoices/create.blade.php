@extends('layouts.main')

@section('title', 'Create Invoice - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Revenue & Billing', 'url' => route('fleet.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Create Invoice', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Create Invoice</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.invoices.store') }}" id="invoice-form" enctype="multipart/form-data">
                    @csrf

                    <!-- Invoice Type -->
                    <h6 class="text-success mb-3"><i class="bx bx-car me-2"></i>Invoice Information</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Invoice Type <span class="text-danger">*</span></label>
                                <select name="invoice_type" class="form-select" id="invoice_type" required>
                                    <option value="">Select Type</option>
                                    <option value="trip_based" {{ old('invoice_type') == 'trip_based' ? 'selected' : '' }}>Trip Based</option>
                                    <option value="period_based" {{ old('invoice_type') == 'period_based' ? 'selected' : '' }}>Period Based</option>
                                    <option value="contract" {{ old('invoice_type') == 'contract' ? 'selected' : '' }}>Contract</option>
                                </select>
                                @error('invoice_type')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Invoice Dates -->
                    <h6 class="text-success mb-3"><i class="bx bx-calendar me-2"></i>Invoice Dates</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                <input type="date" name="invoice_date" id="invoice_date" class="form-control" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                                @error('invoice_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" id="due_date" class="form-control" value="{{ old('due_date') }}" required>
                                @error('due_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Payment Terms <span class="text-danger">*</span></label>
                                <select name="payment_terms" class="form-select" id="payment_terms" required>
                                    <option value="">Select Terms</option>
                                    <option value="immediate" {{ old('payment_terms') == 'immediate' ? 'selected' : '' }}>Immediate</option>
                                    <option value="net_15" {{ old('payment_terms') == 'net_15' ? 'selected' : '' }}>Net 15</option>
                                    <option value="net_30" {{ old('payment_terms') == 'net_30' ? 'selected' : '' }}>Net 30</option>
                                    <option value="net_45" {{ old('payment_terms') == 'net_45' ? 'selected' : '' }}>Net 45</option>
                                    <option value="net_60" {{ old('payment_terms') == 'net_60' ? 'selected' : '' }}>Net 60</option>
                                    <option value="custom" {{ old('payment_terms') == 'custom' ? 'selected' : '' }}>Custom</option>
                                </select>
                                @error('payment_terms')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4" id="custom-payment-days" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Payment Days</label>
                                <input type="number" min="0" name="payment_days" id="payment_days" class="form-control" value="{{ old('payment_days', 30) }}" placeholder="Days">
                                @error('payment_days')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tax Rate (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="tax_rate" class="form-control" value="{{ old('tax_rate', 0) }}">
                                @error('tax_rate')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div class="row g-3 mt-2">
                        <div class="col-12">
                            <label class="form-label">Attachments</label>
                            <div id="attachments-container">
                                <div class="input-group mb-2">
                                    <input type="file" name="attachments[]" class="form-control attachment-file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                                    <button type="button" class="btn btn-outline-danger remove-attachment-btn" style="display: none;">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-success" id="add-attachment-btn">
                                <i class="bx bx-plus me-1"></i>Add File
                            </button>
                            <div class="form-text">Supported formats: JPG, PNG, PDF, DOC, DOCX (Max 10MB per file)</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Invoice Items -->
                    <h6 class="text-success mb-3"><i class="bx bx-list-ul me-2"></i>Invoice Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="invoice-items-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 25%;">Trip <span class="text-danger">*</span></th>
                                    <th style="width: 25%;">Description <span class="text-danger">*</span></th>
                                    <th style="width: 10%;">Quantity <span class="text-danger">*</span></th>
                                    <th style="width: 10%;">Unit of Measure</th>
                                    <th style="width: 15%;">Unit Rate (TZS) <span class="text-danger">*</span></th>
                                    <th style="width: 15%;">Amount (TZS)</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="invoice-items-container">
                                <!-- First line will be added by default -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-success" id="add-item-btn">
                                            <i class="bx bx-plus me-1"></i>Add Item
                                        </button>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Subtotal:</td>
                                    <td class="fw-bold" id="subtotal-amount">0.00 TZS</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Tax:</td>
                                    <td class="fw-bold" id="tax-amount">0.00 TZS</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Total:</td>
                                    <td class="fw-bold" id="total-amount">0.00 TZS</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.invoices.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success" style="background-color: #198754; border-color: #198754; color: #fff;">
                            <i class="bx bx-save me-1"></i>Create Invoice
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
    let itemIndex = 0;

    // Initialize Select2 if available
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: 'Select an option...'
        });
        
    }

    // Auto-calculate due date based on payment terms and invoice date
    function updateDueDate() {
        const invoiceDate = $('#invoice_date').val();
        if (!invoiceDate) return;

        const paymentTerms = $('#payment_terms').val();
        let days = 0;
        if (paymentTerms === 'immediate') days = 0;
        else if (paymentTerms === 'net_15') days = 15;
        else if (paymentTerms === 'net_30') days = 30;
        else if (paymentTerms === 'net_45') days = 45;
        else if (paymentTerms === 'net_60') days = 60;
        else if (paymentTerms === 'custom') days = parseInt($('#payment_days').val(), 10) || 30;

        if (days === 0) {
            $('#due_date').val(invoiceDate);
        } else {
            const date = new Date(invoiceDate + 'T12:00:00'); // avoid timezone shift
            date.setDate(date.getDate() + days);
            $('#due_date').val(date.toISOString().split('T')[0]);
        }
    }

    // Show/hide custom payment days and update due date when payment terms change
    $('#payment_terms').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#custom-payment-days').show();
        } else {
            $('#custom-payment-days').hide();
        }
        updateDueDate();
    });
    $('#payment_terms').trigger('change');

    $('#invoice_date').on('change input', updateDueDate);
    $('input[name="payment_days"]').on('change input', updateDueDate);
    // Run on load so due date is set when invoice date and payment terms are already filled
    updateDueDate();

    // Add invoice item
    function addInvoiceItem() {
        itemIndex++;
        const trips = @json($trips);
        const itemHtml = `
            <tr class="invoice-item-row" data-index="${itemIndex}">
                <td>
                    <select name="items[${itemIndex}][trip_id]" class="form-select trip-select" required>
                        <option value="">Select Trip</option>
                        ${trips.map(trip => {
                            const tripDisplay = trip.trip_number || 'N/A';
                            const vehicleName = trip.vehicle ? trip.vehicle.name : 'N/A';
                            const dateDisplay = trip.formatted_date ? ` (${trip.formatted_date})` : '';
                            return `<option value="${trip.id}" data-vehicle="${trip.vehicle_id || ''}" data-driver="${trip.driver_id || ''}" data-route="${trip.route_id || ''}">${tripDisplay}${dateDisplay} - ${vehicleName}</option>`;
                        }).join('')}
                    </select>
                </td>
                <td>
                    <input type="text" name="items[${itemIndex}][description]" class="form-control" placeholder="Item description" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${itemIndex}][quantity]" class="form-control quantity-input" placeholder="1.00" value="1" required>
                </td>
                <td>
                    <input type="text" name="items[${itemIndex}][unit]" class="form-control" placeholder="e.g., km, trip, hour">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${itemIndex}][unit_rate]" class="form-control unit-rate-input" placeholder="0.00" required>
                </td>
                <td>
                    <input type="text" class="form-control item-amount" readonly value="0.00">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" title="Remove Item">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#invoice-items-container').append(itemHtml);
        
        // Initialize Select2 for new trip select
        if ($.fn.select2) {
            $('#invoice-items-container tr').last().find('.trip-select').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        }
        
        calculateTotals();
    }

    // Remove invoice item
    $(document).on('click', '.remove-item-btn', function() {
        if ($('.invoice-item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        } else {
            alert('At least one invoice item is required.');
        }
    });

    // Calculate item amount
    $(document).on('input', '.quantity-input, .unit-rate-input', function() {
        const row = $(this).closest('tr');
        const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
        const unitRate = parseFloat(row.find('.unit-rate-input').val()) || 0;
        const amount = quantity * unitRate;
        row.find('.item-amount').val(amount.toFixed(2));
        calculateTotals();
    });

    // Calculate totals
    function calculateTotals() {
        let subtotal = 0;
        $('.invoice-item-row').each(function() {
            const amount = parseFloat($(this).find('.item-amount').val()) || 0;
            subtotal += amount;
        });

        const taxRate = parseFloat($('input[name="tax_rate"]').val()) || 0;
        const tax = subtotal * (taxRate / 100);
        const total = subtotal + tax;

        $('#subtotal-amount').text(subtotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' TZS');
        $('#tax-amount').text(tax.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' TZS');
        $('#total-amount').text(total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' TZS');
    }

    // Recalculate when tax rate changes
    $('input[name="tax_rate"]').on('input', function() {
        calculateTotals();
    });

    // Add first item by default
    addInvoiceItem();
    
    // Bind Add Item button
    $('#add-item-btn').on('click', function() {
        addInvoiceItem();
    });

    // Attachment management
    let attachmentIndex = 1;
    $('#add-attachment-btn').on('click', function() {
        const attachmentHtml = `
            <div class="input-group mb-2">
                <input type="file" name="attachments[]" class="form-control attachment-file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                <button type="button" class="btn btn-outline-danger remove-attachment-btn">
                    <i class="bx bx-trash"></i>
                </button>
            </div>
        `;
        $('#attachments-container').append(attachmentHtml);
        attachmentIndex++;
    });

    $(document).on('click', '.remove-attachment-btn', function() {
        $(this).closest('.input-group').remove();
    });
});
</script>
@endpush
