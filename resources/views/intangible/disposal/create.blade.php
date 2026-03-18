@extends('layouts.main')

@section('title', 'Intangible Disposal')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Intangible Assets', 'url' => route('assets.intangible.index'), 'icon' => 'bx bx-brain'],
            ['label' => 'Disposal', 'url' => '#', 'icon' => 'bx bx-transfer-alt']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><i class="bx bx-transfer-alt me-2 text-secondary"></i>Dispose Intangible Asset</h5>
                        <p class="text-muted mb-0 small">Record disposal, expiry or write-off and let the system compute gain or loss automatically.</p>
                    </div>
                    <span class="badge bg-light text-secondary border small">
                        <i class="bx bx-check-shield me-1"></i>IFRS Disposal Flow
                    </span>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center mb-1">
                                <div class="avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-title bg-secondary text-white rounded-2">
                                        <i class="bx bx-exit"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="small fw-semibold text-dark">Sale / Transfer</div>
                                    <div class="text-muted extra-small">Asset disposed with consideration – proceeds vs NBV determine gain or loss.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center mb-1">
                                <div class="avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-title bg-info text-white rounded-2">
                                        <i class="bx bx-time-five"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="small fw-semibold text-dark">Expiry</div>
                                    <div class="text-muted extra-small">Licence expired or asset abandoned – typically with zero proceeds.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center mb-1">
                                <div class="avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-title bg-danger text-white rounded-2">
                                        <i class="bx bx-trash-alt"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="small fw-semibold text-dark">Write-off</div>
                                    <div class="text-muted extra-small">No further economic benefit expected; remaining NBV expensed.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <div class="card-body">
                <form method="POST" action="{{ route('assets.intangible.disposals.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small">Intangible Asset<span class="text-danger">*</span></label>
                            <select name="intangible_asset_id" class="form-select form-select-sm select2-single @error('intangible_asset_id') is-invalid @enderror" data-placeholder="Select intangible asset">
                                <option value=""></option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" {{ (old('intangible_asset_id', $selectedAssetId) == $asset->id) ? 'selected' : '' }}>
                                        {{ $asset->code }} - {{ $asset->name }}
                                        (Cost: {{ number_format($asset->cost, 2) }},
                                        Acc Amort: {{ number_format($asset->accumulated_amortisation, 2) }},
                                        Acc Impairment: {{ number_format($asset->accumulated_impairment, 2) }},
                                        NBV: {{ number_format($asset->nbv, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('intangible_asset_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Disposal Date<span class="text-danger">*</span></label>
                            <input type="date" name="disposal_date" value="{{ old('disposal_date', now()->toDateString()) }}" class="form-control form-control-sm @error('disposal_date') is-invalid @enderror">
                            @error('disposal_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Proceeds<span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="proceeds" value="{{ old('proceeds', 0) }}" class="form-control form-control-sm @error('proceeds') is-invalid @enderror">
                            @error('proceeds')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text small">Enter 0 for expiry or full write-off.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Reason / Notes</label>
                            <textarea name="reason" rows="3" class="form-control form-control-sm @error('reason') is-invalid @enderror">{{ old('reason') }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('assets.intangible.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-arrow-back me-1"></i>Back to Register
                        </a>
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <i class="bx bx-save me-1"></i>Record Disposal
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
});
</script>
@endpush
