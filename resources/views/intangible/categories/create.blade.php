@extends('layouts.main')

@section('title', 'New Intangible Category')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Intangible Assets', 'url' => route('assets.intangible.index'), 'icon' => 'bx bx-brain'],
            ['label' => 'New Category', 'url' => '#', 'icon' => 'bx bx-category-alt']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><i class="bx bx-category-alt me-2 text-primary"></i>New Intangible Asset Category</h5>
                        <p class="text-muted mb-0 small">Group your intangibles (software, licences, goodwill) and link them to the correct GL accounts.</p>
                    </div>
                    <span class="badge bg-light text-primary border small">
                        <i class="bx bx-check-shield me-1"></i>IAS 38 Structure
                    </span>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center mb-1">
                                <div class="avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-title bg-primary text-white rounded-2">
                                        <i class="bx bx-purchase-tag-alt"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="small fw-semibold text-dark">Purchased Intangibles</div>
                                    <div class="text-muted extra-small">Software licences, franchises, copyrights, patents, trademarks.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center mb-1">
                                <div class="avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-title bg-info text-white rounded-2">
                                        <i class="bx bx-code-alt"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="small fw-semibold text-dark">Internally Developed</div>
                                    <div class="text-muted extra-small">Development costs capitalised once IAS 38 criteria are met.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center mb-1">
                                <div class="avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-title bg-warning text-white rounded-2">
                                        <i class="bx bx-crown"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="small fw-semibold text-dark">Goodwill / Indefinite-life</div>
                                    <div class="text-muted extra-small">Non-amortising assets subject to annual impairment testing.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('assets.intangible.categories.store') }}">
                    @csrf

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <h6 class="text-muted text-uppercase small mb-2">Category Details</h6>
                            <div class="mb-3">
                                <label class="form-label small">Name<span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-sm @error('name') is-invalid @enderror" placeholder="e.g. Purchased Software">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Code</label>
                                <input type="text" name="code" value="{{ old('code') }}" class="form-control form-control-sm @error('code') is-invalid @enderror" placeholder="e.g. SW">
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Category Type<span class="text-danger">*</span></label>
                                <select name="type" id="categoryType" class="form-select form-select-sm @error('type') is-invalid @enderror">
                                    <option value="">-- Select Type --</option>
                                    <option value="purchased" {{ old('type') === 'purchased' ? 'selected' : '' }}>Purchased</option>
                                    <option value="internally_developed" {{ old('type') === 'internally_developed' ? 'selected' : '' }}>Internally Developed</option>
                                    <option value="goodwill" {{ old('type') === 'goodwill' ? 'selected' : '' }}>Goodwill</option>
                                    <option value="indefinite_life" {{ old('type') === 'indefinite_life' ? 'selected' : '' }}>Indefinite-life Intangible</option>
                                </select>
                                <div class="form-text small mt-1">
                                    <i class="bx bx-info-circle me-1"></i>
                                    <span id="typeDescription">This type will be applied to assets created under this category.</span>
                                </div>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <h6 class="text-muted text-uppercase small mb-2">GL Account Mapping</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Intangible Asset – Cost<span class="text-danger">*</span></label>
                                    <select name="cost_account_id" class="form-select form-select-sm select2-single @error('cost_account_id') is-invalid @enderror" data-placeholder="Select cost account">
                                        <option value=""></option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('cost_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('cost_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">Accumulated Amortisation</label>
                                    <select name="accumulated_amortisation_account_id" class="form-select form-select-sm select2-single @error('accumulated_amortisation_account_id') is-invalid @enderror" data-placeholder="Select accumulated amortisation">
                                        <option value=""></option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('accumulated_amortisation_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('accumulated_amortisation_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">Accumulated Impairment</label>
                                    <select name="accumulated_impairment_account_id" class="form-select form-select-sm select2-single @error('accumulated_impairment_account_id') is-invalid @enderror" data-placeholder="Select accumulated impairment">
                                        <option value=""></option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('accumulated_impairment_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('accumulated_impairment_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">Amortisation Expense</label>
                                    <select name="amortisation_expense_account_id" class="form-select form-select-sm select2-single @error('amortisation_expense_account_id') is-invalid @enderror" data-placeholder="Select amortisation expense">
                                        <option value=""></option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('amortisation_expense_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('amortisation_expense_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">Impairment Loss</label>
                                    <select name="impairment_loss_account_id" class="form-select form-select-sm select2-single @error('impairment_loss_account_id') is-invalid @enderror" data-placeholder="Select impairment loss">
                                        <option value=""></option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('impairment_loss_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('impairment_loss_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">Gain/Loss on Disposal</label>
                                    <select name="disposal_gain_loss_account_id" class="form-select form-select-sm select2-single @error('disposal_gain_loss_account_id') is-invalid @enderror" data-placeholder="Select gain/loss on disposal">
                                        <option value=""></option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('disposal_gain_loss_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('disposal_gain_loss_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('assets.intangible.categories.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-arrow-back me-1"></i>Back to Categories
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bx bx-save me-1"></i>Save Category
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
        width: '100%',
        placeholder: function(){
            return $(this).data('placeholder') || '';
        }
    });

    // Type description helper
    const typeDescriptions = {
        'purchased': 'Purchased intangibles (software licences, franchises, patents, trademarks)',
        'internally_developed': 'Internally developed intangibles (capitalised development costs)',
        'goodwill': 'Goodwill from business combinations (non-amortising, impairment only)',
        'indefinite_life': 'Indefinite-life intangibles (brands, trademarks with no expiry)'
    };

    $('#categoryType').on('change', function() {
        const selectedType = $(this).val();
        const description = typeDescriptions[selectedType] || 'This type will be applied to assets created under this category.';
        $('#typeDescription').text(description);
    });

    // Trigger on load if value exists
    if ($('#categoryType').val()) {
        $('#categoryType').trigger('change');
    }
});
</script>
@endpush


