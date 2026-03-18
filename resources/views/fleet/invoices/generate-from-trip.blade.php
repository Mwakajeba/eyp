@extends('layouts.main')

@section('title', 'Generate Invoice from Trip - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Revenue & Billing', 'url' => route('fleet.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Generate from Trip: ' . $trip->trip_number, 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Generate Invoice from Trip: {{ $trip->trip_number }}</h6>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Vehicle</label>
                        <p class="mb-0">{{ $trip->vehicle->name ?? 'N/A' }} ({{ $trip->vehicle->registration_number ?? 'N/A' }})</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Driver</label>
                        <p class="mb-0">{{ $trip->driver->full_name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Route</label>
                        <p class="mb-0">@if($trip->route){{ $trip->route->origin_location ?? '' }} → {{ $trip->route->destination_location ?? '' }}@else N/A @endif</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('fleet.invoices.store-from-trip', $trip->hash_id) }}">
                    @csrf

                    <h6 class="text-success mb-3"><i class="bx bx-calendar me-2"></i>Invoice Details</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                                @error('invoice_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" class="form-control" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" required>
                                @error('due_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Payment Terms <span class="text-danger">*</span></label>
                                <select name="payment_terms" class="form-select" required>
                                    <option value="immediate" {{ old('payment_terms') == 'immediate' ? 'selected' : '' }}>Immediate</option>
                                    <option value="net_15" {{ old('payment_terms') == 'net_15' ? 'selected' : '' }}>Net 15</option>
                                    <option value="net_30" {{ old('payment_terms', 'net_30') == 'net_30' ? 'selected' : '' }}>Net 30</option>
                                    <option value="net_45" {{ old('payment_terms') == 'net_45' ? 'selected' : '' }}>Net 45</option>
                                    <option value="net_60" {{ old('payment_terms') == 'net_60' ? 'selected' : '' }}>Net 60</option>
                                    <option value="custom" {{ old('payment_terms') == 'custom' ? 'selected' : '' }}>Custom</option>
                                </select>
                                @error('payment_terms')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Payment Days</label>
                                <input type="number" min="0" name="payment_days" class="form-control" value="{{ old('payment_days', 30) }}">
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
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Discount Amount</label>
                                <input type="number" step="0.01" min="0" name="discount_amount" class="form-control" value="{{ old('discount_amount', 0) }}">
                                @error('discount_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Discount Type</label>
                                <select name="discount_type" class="form-select">
                                    <option value="">None</option>
                                    <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                    <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>Fixed</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <p class="text-muted small">Revenue will be calculated from the trip's revenue model (per_trip, per_km, or per_hour) and rate. The invoice will be linked to Vehicle, Driver, Route, and Trip.</p>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.invoices.index') }}" class="btn btn-secondary"><i class="bx bx-x me-1"></i>Cancel</a>
                        <button type="submit" class="btn btn-success" style="background-color: #198754; border-color: #198754; color: #fff;">
                            <i class="bx bx-save me-1"></i>Generate Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
