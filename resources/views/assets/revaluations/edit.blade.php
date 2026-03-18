@extends('layouts.main')

@section('title', 'Edit Revaluation')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Revaluations', 'url' => route('assets.revaluations.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Revaluation: {{ $revaluation->revaluation_number }}</h6>
            </div>
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger">
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('assets.revaluations.update', \Vinkla\Hashids\Facades\Hashids::encode($revaluation->id)) }}" 
                      enctype="multipart/form-data" id="revaluation-form">
                    @csrf
                    @method('PUT')

                    <!-- Asset Information (Read-only) -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Asset Information</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Asset</label>
                            <input type="text" class="form-control" 
                                value="{{ $revaluation->asset->code }} - {{ $revaluation->asset->name }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info mb-0">
                                <strong>Current Carrying Amount:</strong> 
                                {{ number_format($revaluation->carrying_amount_before ?? 0, 2) }}
                            </div>
                        </div>
                    </div>

                    <!-- Revaluation Details -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Revaluation Details</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Revaluation Date <span class="text-danger">*</span></label>
                            <input type="date" name="revaluation_date" class="form-control" 
                                value="{{ old('revaluation_date', $revaluation->revaluation_date->format('Y-m-d')) }}" required>
                            @error('revaluation_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fair Value <span class="text-danger">*</span></label>
                            <input type="number" name="fair_value" class="form-control" 
                                step="0.01" min="0" value="{{ old('fair_value', $revaluation->fair_value) }}" required>
                            @error('fair_value')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valuation Model</label>
                            <input type="text" class="form-control" 
                                value="{{ ucfirst($revaluation->valuation_model) }} Model" disabled>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Reason for Revaluation <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="2" required>{{ old('reason', $revaluation->reason) }}</textarea>
                            @error('reason')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Valuer Information -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Valuer Information</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valuer Name</label>
                            <input type="text" name="valuer_name" class="form-control" value="{{ old('valuer_name', $revaluation->valuer_name) }}">
                            @error('valuer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valuer License</label>
                            <input type="text" name="valuer_license" class="form-control" value="{{ old('valuer_license', $revaluation->valuer_license) }}">
                            @error('valuer_license')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valuer Company</label>
                            <input type="text" name="valuer_company" class="form-control" value="{{ old('valuer_company', $revaluation->valuer_company) }}">
                            @error('valuer_company')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valuation Report Reference</label>
                            <input type="text" name="valuation_report_ref" class="form-control" value="{{ old('valuation_report_ref', $revaluation->valuation_report_ref) }}">
                            @error('valuation_report_ref')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valuation Report (PDF/DOC)</label>
                            <input type="file" name="valuation_report" class="form-control" accept=".pdf,.doc,.docx">
                            @if($revaluation->valuation_report_path)
                                <div class="form-text">
                                    <a href="{{ Storage::url($revaluation->valuation_report_path) }}" target="_blank">
                                        <i class="bx bx-file me-1"></i>View Current Report
                                    </a>
                                </div>
                            @endif
                            @error('valuation_report')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Asset Adjustments -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Asset Adjustments (Optional)</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Useful Life After (Months)</label>
                            <input type="number" name="useful_life_after" class="form-control" 
                                min="1" value="{{ old('useful_life_after', $revaluation->useful_life_after) }}">
                            @error('useful_life_after')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Residual Value After</label>
                            <input type="number" name="residual_value_after" class="form-control" 
                                step="0.01" min="0" value="{{ old('residual_value_after', $revaluation->residual_value_after) }}">
                            @error('residual_value_after')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Revaluation Reserve Account</label>
                            <select name="revaluation_reserve_account_id" class="form-select select2-single">
                                <option value="">Use Category Default</option>
                                @foreach($reserveAccounts as $account)
                                    <option value="{{ $account->id }}" 
                                        {{ old('revaluation_reserve_account_id', $revaluation->revaluation_reserve_account_id) == $account->id ? 'selected' : '' }}>
                                        {{ $account->account_code ?? $account->code }} - {{ $account->account_name ?? $account->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('revaluation_reserve_account_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Attachments</h6>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Additional Documents</label>
                            <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            @if($revaluation->attachments && count($revaluation->attachments) > 0)
                                <div class="form-text mt-2">
                                    <strong>Current Attachments:</strong>
                                    <ul class="mb-0">
                                        @foreach($revaluation->attachments as $attachment)
                                            <li><a href="{{ Storage::url($attachment) }}" target="_blank">{{ basename($attachment) }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @error('attachments.*')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('assets.revaluations.show', \Vinkla\Hashids\Facades\Hashids::encode($revaluation->id)) }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Revaluation
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
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script>
@endpush

