@extends('layouts.main')

@section('title', 'Edit Invoice ' . $invoice->invoice_number . ' - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Revenue & Billing', 'url' => route('fleet.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Edit Invoice', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Invoice {{ $invoice->invoice_number }}</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.invoices.update', $invoice->hash_id) }}" id="invoice-form" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <h6 class="text-success mb-3"><i class="bx bx-car me-2"></i>Vehicle, Driver, Route & Trip (from invoice items)</h6>
                    <div class="row g-3 mb-3">
                        @php
                            $displayVehicle = $invoice->vehicle;
                            $displayDriver = $invoice->driver;
                            $displayRoute = $invoice->route;
                            $firstItem = $invoice->items->first();
                            if ($firstItem && $firstItem->trip) {
                                $displayVehicle = $displayVehicle ?? $firstItem->trip->vehicle;
                                $displayDriver = $displayDriver ?? $firstItem->trip->driver;
                                $displayRoute = $displayRoute ?? $firstItem->trip->route;
                            }
                        @endphp
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Vehicle</label>
                            <p class="mb-0">{{ $displayVehicle->name ?? 'N/A' }} ({{ $displayVehicle->registration_number ?? 'N/A' }})</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Driver</label>
                            <p class="mb-0">{{ $displayDriver->full_name ?? $displayDriver->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Route</label>
                            <p class="mb-0">@if($displayRoute){{ $displayRoute->origin_location ?? '' }} → {{ $displayRoute->destination_location ?? '' }}@else N/A @endif</p>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="text-success mb-3"><i class="bx bx-calendar me-2"></i>Invoice Dates</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                                @error('invoice_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}" required>
                                @error('due_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Payment Terms <span class="text-danger">*</span></label>
                                <select name="payment_terms" class="form-select" id="payment_terms" required>
                                    <option value="immediate" {{ old('payment_terms', $invoice->payment_terms) == 'immediate' ? 'selected' : '' }}>Immediate</option>
                                    <option value="net_15" {{ old('payment_terms', $invoice->payment_terms) == 'net_15' ? 'selected' : '' }}>Net 15</option>
                                    <option value="net_30" {{ old('payment_terms', $invoice->payment_terms) == 'net_30' ? 'selected' : '' }}>Net 30</option>
                                    <option value="net_45" {{ old('payment_terms', $invoice->payment_terms) == 'net_45' ? 'selected' : '' }}>Net 45</option>
                                    <option value="net_60" {{ old('payment_terms', $invoice->payment_terms) == 'net_60' ? 'selected' : '' }}>Net 60</option>
                                    <option value="custom" {{ old('payment_terms', $invoice->payment_terms) == 'custom' ? 'selected' : '' }}>Custom</option>
                                </select>
                                @error('payment_terms')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4" id="custom-payment-days" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Payment Days</label>
                                <input type="number" min="0" name="payment_days" class="form-control" value="{{ old('payment_days', $invoice->payment_days ?? 30) }}">
                                @error('payment_days')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tax Rate (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="tax_rate" class="form-control" value="{{ old('tax_rate', $invoice->tax_rate ?? 0) }}">
                                @error('tax_rate')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="text-success mb-3"><i class="bx bx-list-ul me-2"></i>Invoice Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="invoice-items-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Trip <span class="text-danger">*</span></th>
                                    <th>Description <span class="text-danger">*</span></th>
                                    <th>Quantity <span class="text-danger">*</span></th>
                                    <th>Unit</th>
                                    <th>Unit Rate (TZS) <span class="text-danger">*</span></th>
                                    <th>Amount (TZS)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="invoice-items-container">
                                @foreach($invoice->items as $idx => $item)
                                <tr class="invoice-item-row">
                                    <td>
                                        <select name="items[{{ $idx }}][trip_id]" class="form-select trip-select" required>
                                            <option value="">Select Trip</option>
                                            @foreach($trips as $trip)
                                            <option value="{{ $trip->id }}" {{ old('items.'.$idx.'.trip_id', $item->trip_id) == $trip->id ? 'selected' : '' }}>
                                                {{ $trip->trip_number }}{{ $trip->formatted_date ? ' (' . $trip->formatted_date . ')' : '' }} - {{ $trip->vehicle ? $trip->vehicle->name : 'N/A' }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[{{ $idx }}][description]" class="form-control" value="{{ old('items.'.$idx.'.description', $item->description) }}" required></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $idx }}][quantity]" class="form-control quantity-input" value="{{ old('items.'.$idx.'.quantity', $item->quantity) }}" required></td>
                                    <td><input type="text" name="items[{{ $idx }}][unit]" class="form-control" value="{{ old('items.'.$idx.'.unit', $item->unit) }}"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $idx }}][unit_rate]" class="form-control unit-rate-input" value="{{ old('items.'.$idx.'.unit_rate', $item->unit_rate) }}" required></td>
                                    <td><input type="text" class="form-control item-amount" readonly value="{{ number_format($item->quantity * $item->unit_rate, 2) }}"></td>
                                    <td>
                                        <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn"><i class="bx bx-trash"></i></button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr><td colspan="6" class="text-end"><button type="button" class="btn btn-sm btn-outline-success" id="add-item-btn"><i class="bx bx-plus me-1"></i>Add Item</button></td><td></td></tr>
                                <tr><td colspan="5" class="text-end fw-bold">Subtotal:</td><td class="fw-bold" id="subtotal-amount">0.00 TZS</td><td></td></tr>
                                <tr><td colspan="5" class="text-end fw-bold">Tax:</td><td class="fw-bold" id="tax-amount">0.00 TZS</td><td></td></tr>
                                <tr><td colspan="5" class="text-end fw-bold">Total:</td><td class="fw-bold" id="total-amount">0.00 TZS</td><td></td></tr>
                            </tfoot>
                        </table>
                    </div>

                    <hr class="my-4">

                    <!-- Attachments -->
                    <h6 class="text-success mb-3"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                    <div class="mb-3">
                        <label class="form-label">Attachments</label>
                        <div id="attachments-container">
                            <div class="mb-2 d-flex gap-2 align-items-end">
                                <div class="flex-grow-1">
                                    <input type="file" name="attachments[]" class="form-control attachment-file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                                </div>
                                <button type="button" class="btn btn-outline-danger remove-attachment-btn" style="display: none;">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success" id="add-attachment-btn">
                            <i class="bx bx-plus me-1"></i>Add Attachment
                        </button>
                        <div class="form-text">You can upload multiple files (JPG, PNG, PDF, DOC, DOCX). Max 10MB per file.</div>
                    </div>

                    @php
                        $attachments = is_array($invoice->attachments) ? $invoice->attachments : (is_string($invoice->attachments) ? json_decode($invoice->attachments, true) : []);
                    @endphp
                    @if(!empty($attachments) && count($attachments) > 0)
                    <div class="mb-3">
                        <label class="form-label">Current Attachments</label>
                        <div class="row g-2">
                            @foreach($attachments as $idx => $attachment)
                            @if(!empty($attachment['path']))
                            <div class="col-md-3">
                                <div class="card border">
                                    <div class="card-body p-2">
                                        <div class="d-flex align-items-center mb-2">
                                            @php
                                                $fileExt = strtolower(pathinfo($attachment['path'] ?? '', PATHINFO_EXTENSION));
                                                $isImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif']);
                                                $isPdf = $fileExt === 'pdf';
                                            @endphp
                                            @if($isImage)
                                                <i class="bx bx-image fs-3 text-primary me-2"></i>
                                            @elseif($isPdf)
                                                <i class="bx bx-file-blank fs-3 text-danger me-2"></i>
                                            @else
                                                <i class="bx bx-file fs-3 text-secondary me-2"></i>
                                            @endif
                                        </div>
                                        <div class="text-truncate mb-2" title="{{ $attachment['original_name'] ?? 'Attachment' }}">
                                            <small class="text-muted d-block">{{ $attachment['original_name'] ?? basename($attachment['path']) }}</small>
                                            @if(isset($attachment['size']))
                                            <small class="text-muted">{{ number_format($attachment['size'] / 1024, 2) }} KB</small>
                                            @endif
                                        </div>
                                        <a href="{{ asset('storage/' . $attachment['path']) }}" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                                            <i class="bx bx-show me-1"></i>View
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.invoices.show', $invoice->hash_id) }}" class="btn btn-secondary"><i class="bx bx-x me-1"></i>Cancel</a>
                        <button type="submit" class="btn btn-success" style="background-color: #198754; border-color: #198754;"><i class="bx bx-save me-1"></i>Update Invoice</button>
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
    let itemIndex = {{ $invoice->items->count() }};
    const trips = @json($trips);

    if (typeof $ !== 'undefined' && $.fn.select2) { 
        $('.select2-single, .trip-select').select2({ theme: 'bootstrap-5', width: '100%' }); 
    }

    $('#payment_terms').on('change', function() { $('#custom-payment-days').toggle($(this).val() === 'custom'); }); $('#payment_terms').trigger('change');

    function addItem() {
        const tripOptions = trips.map(trip => {
            const dateDisplay = trip.formatted_date ? ` (${trip.formatted_date})` : '';
            const vehicleName = trip.vehicle ? trip.vehicle.name : 'N/A';
            return `<option value="${trip.id}">${trip.trip_number}${dateDisplay} - ${vehicleName}</option>`;
        }).join('');
        const row = `<tr class="invoice-item-row"><td><select name="items[${itemIndex}][trip_id]" class="form-select trip-select" required><option value="">Select Trip</option>${tripOptions}</select></td><td><input type="text" name="items[${itemIndex}][description]" class="form-control" required></td><td><input type="number" step="0.01" min="0" name="items[${itemIndex}][quantity]" class="form-control quantity-input" value="1" required></td><td><input type="text" name="items[${itemIndex}][unit]" class="form-control"></td><td><input type="number" step="0.01" min="0" name="items[${itemIndex}][unit_rate]" class="form-control unit-rate-input" required></td><td><input type="text" class="form-control item-amount" readonly value="0.00"></td><td><button type="button" class="btn btn-sm btn-outline-danger remove-item-btn"><i class="bx bx-trash"></i></button></td></tr>`;
        $('#invoice-items-container').append(row);
        itemIndex++;
        if ($.fn.select2) $('#invoice-items-container tr').last().find('.trip-select').select2({ theme: 'bootstrap-5', width: '100%' });
        calculateTotals();
    }
    $('#add-item-btn').on('click', addItem);
    $(document).on('click', '.remove-item-btn', function() { if ($('.invoice-item-row').length > 1) { $(this).closest('tr').remove(); calculateTotals(); } else { alert('At least one item required.'); } });
    $(document).on('input', '.quantity-input, .unit-rate-input', function() { const r=$(this).closest('tr'); const q=parseFloat(r.find('.quantity-input').val())||0; const u=parseFloat(r.find('.unit-rate-input').val())||0; r.find('.item-amount').val((q*u).toFixed(2)); calculateTotals(); });
    function calculateTotals() { let s=0; $('.invoice-item-row').each(function(){ s += parseFloat($(this).find('.item-amount').val())||0; }); const tr=parseFloat($('input[name="tax_rate"]').val())||0; $('#subtotal-amount').text(s.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,",")+' TZS'); $('#tax-amount').text((s*tr/100).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,",")+' TZS'); $('#total-amount').text((s+s*tr/100).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,",")+' TZS'); }
    $('input[name="tax_rate"]').on('input', calculateTotals);
    calculateTotals();

    // Attachment management
    let attachmentIndex = 1;
    $('#add-attachment-btn').on('click', function() {
        const attachmentHtml = `
            <div class="mb-2 d-flex gap-2 align-items-end">
                <div class="flex-grow-1">
                    <input type="file" name="attachments[]" class="form-control attachment-file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                </div>
                <button type="button" class="btn btn-outline-danger remove-attachment-btn">
                    <i class="bx bx-trash"></i>
                </button>
            </div>
        `;
        $('#attachments-container').append(attachmentHtml);
        attachmentIndex++;
    });

    $(document).on('click', '.remove-attachment-btn', function() {
        $(this).closest('.mb-2').remove();
    });
});
</script>
@endpush
