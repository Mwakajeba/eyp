@extends('layouts.main')

@section('title', 'New Opening Asset')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Opening Assets', 'url' => route('assets.openings.index'), 'icon' => 'bx bx-book-open'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Create Opening Asset</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.openings.store') }}">
                    @csrf

                    <div class="mb-2">
                        <span class="badge bg-secondary me-2">Step 1</span>
                        <strong>Select an existing asset</strong> or leave empty to enter minimal details.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Link Existing Asset</label>
                            <select name="asset_id" id="asset_id" class="form-select select2-single">
                                <option value="">-- None (enter details below) --</option>
                                @foreach($assets as $a)
                                <option value="{{ $a->id }}" data-tax-pool-class="{{ $a->taxClass?->class_code ?? '' }}" data-purchase-cost="{{ $a->purchase_cost ?? 0 }}">{{ $a->name }} ({{ $a->code }})</option>
                                @endforeach
                            </select>
                            <div class="form-text">If selected, we will use its name, category, and tax pool class.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tax Pool Class (TRA)</label>
                            <select name="tax_pool_class" id="tax_pool_class" class="form-select select2-single">
                                <option value="">Select Class</option>
                                @foreach(($taxPools ?? []) as $pool)
                                    <option value="{{ $pool['class'] ?? '' }}">{{ $pool['class'] ?? '' }} — {{ $pool['name'] ?? '' }}</option>
                                @endforeach
                            </select>
                            <div class="form-text" id="tax_class_help">Defaults from linked asset; editable only for manual entries.</div>
                        </div>
                    </div>

                    <hr class="my-3">
                    <div class="mb-2">
                        <span class="badge bg-secondary me-2">Step 2</span>
                        <strong>Asset details</strong> (required only when not linking an asset).
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4 when-manual">
                            <label class="form-label">Asset Code</label>
                            <input name="asset_code" class="form-control" placeholder="Optional if linked">
                        </div>
                        <div class="col-md-4 when-manual">
                            <label class="form-label">Asset Name <span class="text-danger">*</span></label>
                            <input name="asset_name" id="asset_name" class="form-control" placeholder="Required if not linked">
                            <div class="form-text">Enter a clear name if you didn't link an existing asset.</div>
                        </div>
                        <div class="col-md-4 when-manual">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="asset_category_id" id="asset_category_id" class="form-select select2-single">
                                <option value="">Select</option>
                                @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Required for manual entries.</div>
                        </div>
                    </div>

                    <hr class="my-3">
                    <div class="mb-2">
                        <span class="badge bg-secondary me-2">Step 3</span>
                        <strong>Opening balances</strong>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-3">
                            <label class="form-label">Opening Date <span class="text-danger">*</span></label>
                            <input type="date" name="opening_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Opening Cost (TZS) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="opening_cost" id="opening_cost" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Accumulated Depreciation (TZS)</label>
                            <input type="number" step="0.01" min="0" name="opening_accum_depr" id="opening_accum_depr" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Opening NBV (TZS)</span>
                                <a href="#" id="reset_nbv_auto" class="small">Reset to Auto</a>
                            </label>
                            <input type="number" step="0.01" min="0" name="opening_nbv" id="opening_nbv" class="form-control" placeholder="Auto = Cost - Acc. Depr">
                            <div class="form-text">Auto = Cost - Acc. Depr (you can edit this).</div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-8">
                            <label class="form-label">Notes</label>
                            <input name="notes" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="gl_post" name="gl_post" value="1" checked>
                                <label class="form-check-label" for="gl_post">Post Opening to GL</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('assets.openings.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>Back</a>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i>Save Opening</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(function(){
    $('.select2-single').select2({ theme:'bootstrap-5', width:'100%', allowClear:true, placeholder:'Select' });

    let nbvManual = false;

    function toNumber(val){
        const n = parseFloat(val);
        return isNaN(n) ? 0 : n;
    }

    function updateAutoNbv(){
        if (nbvManual) return;
        const cost = toNumber($('#opening_cost').val());
        const acc = toNumber($('#opening_accum_depr').val());
        const nbv = Math.max(cost - acc, 0);
        $('#opening_nbv').val(nbv.toFixed(2));
    }

    // Auto-calc on cost/acc changes if not manually overridden
    $('#opening_cost, #opening_accum_depr').on('input', updateAutoNbv);

    // If user types in NBV, mark as manual
    $('#opening_nbv').on('input', function(){ nbvManual = true; });

    // Reset to auto
    $('#reset_nbv_auto').on('click', function(e){
        e.preventDefault();
        nbvManual = false;
        updateAutoNbv();
    });

    // Initialize once
    updateAutoNbv();

    function applyAssetTaxClass(){
        const $asset = $('#asset_id');
        const $tax = $('#tax_pool_class');
        const selected = $asset.find(':selected');
        const taxClass = selected.data('tax-pool-class');
        const purchaseCost = parseFloat(selected.data('purchase-cost')) || 0;
        if (selected.val()) {
            if (taxClass) {
                $tax.val(taxClass).trigger('change');
            }
            $tax.prop('disabled', true);
            // Default opening cost from asset
            $('#opening_cost').val(purchaseCost.toFixed(2));
        } else {
            $tax.prop('disabled', false);
        }
    }

    function toggleManualFields(){
        const linked = !!$('#asset_id').val();
        if (linked) {
            $('.when-manual').hide();
            $('#asset_name').prop('required', false);
            $('#asset_category_id').prop('required', false);
        } else {
            $('.when-manual').show();
            $('#asset_name').prop('required', true);
            $('#asset_category_id').prop('required', true);
        }
    }

    $('#asset_id').on('change', function(){
        applyAssetTaxClass();
        toggleManualFields();
    });
    // Run once in case of preselected
    applyAssetTaxClass();
    toggleManualFields();
});
</script>
@endpush


