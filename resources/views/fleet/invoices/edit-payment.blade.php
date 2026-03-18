@extends('layouts.main')

@section('title', 'Edit Payment - Fleet Invoice')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Revenue & Billing', 'url' => route('fleet.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Invoice ' . $invoice->invoice_number, 'url' => route('fleet.invoices.show', $invoice->hash_id), 'icon' => 'bx bx-show'],
            ['label' => 'Edit Payment', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Payment - Invoice {{ $invoice->invoice_number }}</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.invoices.payments.update', [$invoice->hash_id, $payment->id]) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Balance due:</strong> {{ number_format($invoice->balance_due, 2) }} TZS (Total: {{ number_format($invoice->total_amount, 2) }} TZS, Paid: {{ number_format($invoice->paid_amount, 2) }} TZS)
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Amount (TZS) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount', $payment->amount) }}" required>
                                @error('amount')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', $payment->payment_date ? $payment->payment_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                                @error('payment_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bank / Cash Account <span class="text-danger">*</span></label>
                                <select name="bank_account_id" class="form-select" required>
                                    <option value="">Select Account</option>
                                    @foreach($bankAccounts as $ba)
                                    <option value="{{ $ba->id }}" {{ old('bank_account_id', $payment->bank_account_id) == $ba->id ? 'selected' : '' }}>{{ $ba->name }}@if($ba->account_number) - {{ $ba->account_number }}@endif</option>
                                    @endforeach
                                </select>
                                @error('bank_account_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number', $payment->reference_number) }}" placeholder="e.g. deposit slip #">
                                @error('reference_number')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes">{{ old('notes', $payment->notes) }}</textarea>
                                @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Attachment</label>
                                @if($payment->attachments && count($payment->attachments) > 0)
                                    <div class="small text-muted mb-1">Current: {{ $payment->attachments[0]['original_name'] ?? 'file' }}</div>
                                @endif
                                <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                                <div class="form-text">Optional. Leave blank to keep existing. (jpg, png, pdf, doc, docx, max 10MB)</div>
                                @error('attachment')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.invoices.show', $invoice->hash_id) }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-save me-1"></i>Update Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
