@extends('layouts.main')

@section('title', 'Invoice ' . $invoice->invoice_number . ' - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Revenue & Billing', 'url' => route('fleet.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Invoice ' . $invoice->invoice_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bx bx-receipt me-2"></i>Invoice {{ $invoice->invoice_number }}</h6>
                <div>
                    <a href="{{ route('fleet.invoices.export-pdf', $invoice->hash_id) }}" class="btn btn-light btn-sm" target="_blank">
                        <i class="bx bx-download me-1"></i>Export PDF
                    </a>
                    @if(in_array($invoice->status, ['draft', 'sent', 'overdue', 'partially_paid']))
                    <a href="{{ route('fleet.invoices.edit', $invoice->hash_id) }}" class="btn btn-light btn-sm"><i class="bx bx-edit me-1"></i>Edit</a>
                    @endif
                    @if($invoice->status === 'draft')
                    <form method="POST" action="{{ route('fleet.invoices.send', $invoice->hash_id) }}" class="d-inline" id="send-invoice-form">
                        @csrf
                        <button type="button" class="btn btn-light btn-sm" id="send-invoice-btn">
                            <i class="bx bx-send me-1"></i>Send Invoice
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('fleet.invoices.index') }}" class="btn btn-light btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
                </div>
            </div>
            <div class="card-body">

                <!-- Customer, Vehicle, Driver & Trip -->
                <h6 class="text-success mb-3"><i class="bx bx-car me-2"></i>Customer, Vehicle, Driver & Trip</h6>
                <div class="row g-3">
                    @php
                        $displayVehicle = $invoice->vehicle;
                        $displayDriver = $invoice->driver;
                        $displayTrip = $invoice->trip;
                        $displayCustomer = $invoice->customer;
                        if (!$displayVehicle || !$displayDriver || !$displayTrip || !$displayCustomer) {
                            $firstItem = $invoice->items->first();
                            if ($firstItem && $firstItem->trip) {
                                $displayVehicle = $displayVehicle ?? $firstItem->trip->vehicle;
                                $displayDriver = $displayDriver ?? $firstItem->trip->driver;
                                $displayTrip = $displayTrip ?? $firstItem->trip;
                                $displayCustomer = $displayCustomer ?? $firstItem->trip->customer;
                            }
                        }
                        $tripDisplay = 'N/A';
                        if ($displayTrip) {
                            $tripDate = $displayTrip->actual_start_date ?? $displayTrip->planned_start_date ?? $displayTrip->created_at;
                            $tripDateFormatted = $tripDate ? $tripDate->format('d/m/Y') : 'N/A';
                            $tripDisplay = ($displayTrip->trip_number ?? 'N/A') . ' (' . $tripDateFormatted . ')';
                        }
                        $customerName = $displayCustomer ? ($displayCustomer->name ?? $displayCustomer->company_name ?? 'N/A') : 'N/A';
                    @endphp
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label fw-bold">Customer (Billed To)</label>
                        <p class="mb-0">{{ $customerName }}</p>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label fw-bold">Vehicle</label>
                        <p class="mb-0">{{ $displayVehicle->name ?? 'N/A' }} ({{ $displayVehicle->registration_number ?? 'N/A' }})</p>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label fw-bold">Driver</label>
                        <p class="mb-0">{{ $displayDriver->full_name ?? $displayDriver->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label fw-bold">Trip</label>
                        <p class="mb-0">{{ $tripDisplay }}</p>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Invoice Details -->
                <h6 class="text-success mb-3"><i class="bx bx-calendar me-2"></i>Invoice Details</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Invoice Date</label>
                        <p class="mb-0">{{ $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : 'N/A' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Due Date</label>
                        <p class="mb-0">{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'N/A' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Status</label>
                        <p class="mb-0">
                            @php $c = ['draft'=>'secondary','sent'=>'info','paid'=>'success','partially_paid'=>'warning','overdue'=>'danger','cancelled'=>'dark']; @endphp
                            <span class="badge bg-{{ $c[$invoice->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$invoice->status)) }}</span>
                        </p>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Invoice Items -->
                <h6 class="text-success mb-3"><i class="bx bx-list-ul me-2"></i>Invoice Items</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Trip</th>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Unit Rate (TZS)</th>
                                <th>Amount (TZS)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                            @php
                                $tripDisplay = 'N/A';
                                if ($item->trip) {
                                    $tripDate = $item->trip->actual_start_date ?? $item->trip->planned_start_date ?? $item->trip->created_at;
                                    $tripDateFormatted = $tripDate ? $tripDate->format('d/m/Y') : 'N/A';
                                    $tripDisplay = ($item->trip->trip_number ?? 'N/A') . ' (' . $tripDateFormatted . ')';
                                }
                            @endphp
                            <tr>
                                <td>{{ $tripDisplay }}</td>
                                <td>{{ $item->description }}</td>
                                <td>{{ number_format($item->quantity, 2) }}</td>
                                <td>{{ $item->unit ?? '-' }}</td>
                                <td>{{ number_format($item->unit_rate, 2) }}</td>
                                <td>{{ number_format($item->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr><td colspan="5" class="text-end fw-bold">Subtotal</td><td>{{ number_format($invoice->subtotal, 2) }} TZS</td></tr>
                            <tr><td colspan="5" class="text-end fw-bold">Tax</td><td>{{ number_format($invoice->tax_amount ?? 0, 2) }} TZS</td></tr>
                            <tr><td colspan="5" class="text-end fw-bold">Total</td><td>{{ number_format($invoice->total_amount, 2) }} TZS</td></tr>
                        </tfoot>
                    </table>
                </div>

                <hr class="my-4">

                <!-- Payments & Record Payment -->
                <h6 class="text-success mb-3"><i class="bx bx-wallet me-2"></i>Payments</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Paid:</strong> {{ number_format($invoice->paid_amount ?? 0, 2) }} TZS</p>
                        <p class="mb-1"><strong>Balance Due:</strong> {{ number_format($invoice->balance_due ?? 0, 2) }} TZS</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        @if(($invoice->balance_due ?? 0) > 0 || $invoice->status === 'draft')
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#recordPaymentModal" style="background-color: #198754; border-color: #198754;">
                            <i class="bx bx-plus me-1"></i>Record Payment
                        </button>
                        @endif
                    </div>
                </div>

                @if($invoice->payments && $invoice->payments->count() > 0)
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Amount (TZS)</th>
                                <th>Bank/Cash Account</th>
                                <th>Reference</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->payments as $pmt)
                            <tr>
                                <td>{{ $pmt->payment_date ? $pmt->payment_date->format('Y-m-d') : '-' }}</td>
                                <td>{{ number_format($pmt->amount, 2) }}</td>
                                <td>{{ $pmt->bankAccount->name ?? 'N/A' }}</td>
                                <td>{{ $pmt->reference_number ?? '-' }}</td>
                                <td>{{ $pmt->notes ?? '-' }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('fleet.invoices.payments.export-pdf', [$invoice->hash_id, $pmt->id]) }}" class="btn btn-sm btn-outline-info" title="Export Receipt PDF" target="_blank">
                                            <i class="bx bx-download"></i>
                                        </a>
                                        <a href="{{ route('fleet.invoices.payments.edit', [$invoice->hash_id, $pmt->id]) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('fleet.invoices.payments.destroy', [$invoice->hash_id, $pmt->id]) }}" class="d-inline delete-payment-form" data-amount="{{ number_format($pmt->amount, 2) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-payment-btn" title="Delete">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <!-- Attachments -->
                @php
                    $attachments = is_array($invoice->attachments) ? $invoice->attachments : (is_string($invoice->attachments) ? json_decode($invoice->attachments, true) : []);
                @endphp
                @if(!empty($attachments) && count($attachments) > 0)
                <hr class="my-4">
                <h6 class="text-success mb-3"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                <div class="row g-3">
                    @foreach($attachments as $attachment)
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
                @endif

                @if($invoice->notes)
                <hr class="my-4">
                <h6 class="text-success mb-2"><i class="bx bx-note me-2"></i>Notes</h6>
                <p class="mb-0">{{ $invoice->notes }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Send invoice with SweetAlert
    $('#send-invoice-btn').on('click', function(e) {
        e.preventDefault();
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Send Invoice?',
                text: 'Are you sure you want to send this invoice? This will change the status from draft to sent.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Send Invoice',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#send-invoice-form').submit();
                }
            });
        } else {
            if (confirm('Are you sure you want to send this invoice? This will change the status from draft to sent.')) {
                $('#send-invoice-form').submit();
            }
        }
    });

    // Delete payment with SweetAlert
    $(document).on('click', '.delete-payment-btn', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const amount = form.data('amount');
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete Payment?',
                text: 'Are you sure you want to delete this payment of ' + amount + ' TZS? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        } else {
            if (confirm('Are you sure you want to delete this payment?')) {
                form.submit();
            }
        }
    });
});
</script>
@endpush

<!-- Record Payment Modal -->
@if(($invoice->balance_due ?? 0) > 0 || $invoice->status === 'draft')
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('fleet.invoices.payments.store', $invoice->hash_id) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Balance due: <strong>{{ number_format($invoice->balance_due, 2) }} TZS</strong>. Driver revenue collected will be recorded against this invoice and posted to GL (Debit: Bank/Cash, Credit: Revenue).</p>
                    <div class="mb-3">
                        <label class="form-label">Amount (TZS) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" max="{{ $invoice->balance_due }}" name="amount" class="form-control" value="{{ number_format($invoice->balance_due, 2, '.','') }}" required>
                        @error('amount')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        @error('payment_date')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bank / Cash Account <span class="text-danger">*</span></label>
                        <select name="bank_account_id" class="form-select" required>
                            <option value="">Select Account</option>
                            @foreach($bankAccounts as $ba)
                            <option value="{{ $ba->id }}">{{ $ba->name }}@if($ba->account_number) - {{ $ba->account_number }}@endif</option>
                            @endforeach
                        </select>
                        <div class="form-text">Where the collected money is deposited</div>
                        @error('bank_account_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference Number</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="e.g. deposit slip #">
                        @error('reference_number')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Evening collection from driver"></textarea>
                        @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attachment</label>
                        <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <div class="form-text">Optional: receipt, deposit slip, etc. (jpg, png, pdf, doc, docx, max 10MB)</div>
                        @error('attachment')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" style="background-color: #198754; border-color: #198754;">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
