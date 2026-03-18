@extends('layouts.main')

@section('title', 'Add Cost Component - ' . $asset->name)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Intangible Assets', 'url' => route('assets.intangible.index'), 'icon' => 'bx bx-brain'],
            ['label' => 'Cost Components', 'url' => route('assets.intangible.cost-components.index', $encodedId), 'icon' => 'bx bx-list-ul'],
            ['label' => 'Add Component', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><i class="bx bx-plus me-2 text-primary"></i>Add Cost Component</h5>
                        <p class="text-muted mb-0 small">{{ $asset->name }} ({{ $asset->code }})</p>
                    </div>
                    <span class="badge bg-light text-primary border small">
                        <i class="bx bx-check-shield me-1"></i>IAS 38 Compliant
                    </span>
                </div>
            </div>
            <div class="card-body border-top">
                <!-- Cost Summary -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3">
                            <div class="small text-muted mb-1">Asset Cost</div>
                            <div class="h5 mb-0">TZS {{ number_format($asset->cost, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3">
                            <div class="small text-muted mb-1">Total Components</div>
                            <div class="h5 mb-0">TZS {{ number_format($totalCostComponents, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-{{ $remainingAmount > 0 ? 'info' : 'warning' }} rounded-3 p-3 text-white">
                            <div class="small mb-1">Remaining Amount</div>
                            <div class="h5 mb-0">TZS {{ number_format($remainingAmount, 2) }}</div>
                        </div>
                    </div>
                </div>

                @if($remainingAmount <= 0)
                <div class="alert alert-warning">
                    <i class="bx bx-info-circle me-2"></i>
                    <strong>Warning:</strong> All cost components have been allocated. Adding more components will exceed the asset cost.
                </div>
                @endif

                <form method="POST" action="{{ route('assets.intangible.cost-components.store', $encodedId) }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small">Date<span class="text-danger">*</span></label>
                            <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="form-control form-control-sm @error('date') is-invalid @enderror" required>
                            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Type<span class="text-danger">*</span></label>
                            <select name="type" class="form-select form-select-sm @error('type') is-invalid @enderror" required>
                                <option value="">-- Select Type --</option>
                                <option value="purchase_price" {{ old('type') === 'purchase_price' ? 'selected' : '' }}>Purchase Price</option>
                                <option value="legal_fees" {{ old('type') === 'legal_fees' ? 'selected' : '' }}>Legal Fees</option>
                                <option value="registration_fees" {{ old('type') === 'registration_fees' ? 'selected' : '' }}>Registration Fees</option>
                                <option value="valuation_fees" {{ old('type') === 'valuation_fees' ? 'selected' : '' }}>Valuation Fees</option>
                                <option value="import_duties" {{ old('type') === 'import_duties' ? 'selected' : '' }}>Import Duties & Taxes</option>
                                <option value="testing_costs" {{ old('type') === 'testing_costs' ? 'selected' : '' }}>Testing Costs</option>
                                <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Other Directly Attributable Costs</option>
                            </select>
                            <div class="form-text small mt-1">
                                <i class="bx bx-info-circle me-1"></i>
                                Select the type of cost component per IAS 38 requirements.
                            </div>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Description<span class="text-danger">*</span></label>
                            <textarea name="description" rows="3" class="form-control form-control-sm @error('description') is-invalid @enderror" placeholder="Enter detailed description of the cost component..." required>{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Amount<span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="{{ $remainingAmount }}" name="amount" value="{{ old('amount') }}" class="form-control form-control-sm @error('amount') is-invalid @enderror" placeholder="0.00" required>
                            <div class="form-text small mt-1">
                                <i class="bx bx-info-circle me-1"></i>
                                Maximum: TZS {{ number_format($remainingAmount, 2) }}
                            </div>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Source Document Type (Optional)</label>
                            <input type="text" name="source_document_type" value="{{ old('source_document_type') }}" class="form-control form-control-sm @error('source_document_type') is-invalid @enderror" placeholder="e.g., Purchase Invoice, Receipt">
                            <div class="form-text small mt-1">
                                <i class="bx bx-info-circle me-1"></i>
                                Type of source document (e.g., Invoice, Receipt, Contract)
                            </div>
                            @error('source_document_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Source Document ID (Optional)</label>
                            <input type="number" name="source_document_id" value="{{ old('source_document_id') }}" class="form-control form-control-sm @error('source_document_id') is-invalid @enderror" placeholder="Document ID">
                            <div class="form-text small mt-1">
                                <i class="bx bx-info-circle me-1"></i>
                                Reference ID to source document (e.g., Invoice ID, Receipt ID)
                            </div>
                            @error('source_document_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('assets.intangible.cost-components.index', $encodedId) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-arrow-back me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bx bx-save me-1"></i>Save Cost Component
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

