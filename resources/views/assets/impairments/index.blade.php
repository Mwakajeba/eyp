@extends('layouts.main')

@section('title', 'Asset Impairments')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Impairments', 'url' => '#', 'icon' => 'bx bx-error-circle']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-error-circle me-2"></i>Asset Impairments</h5>
                    <div class="text-muted">Manage asset impairment losses and reversals</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('assets.revaluations.settings') }}" class="btn btn-info">
                        <i class="bx bx-cog me-1"></i>Settings
                    </a>
                    <a href="{{ route('assets.impairments.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>New Impairment
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <!-- Filters -->
                <form method="GET" action="{{ route('assets.impairments.index') }}" class="mb-3">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Asset</label>
                            <select name="asset_id" class="form-select form-select-sm select2-single">
                                <option value="">All Assets</option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" {{ request('asset_id') == $asset->id ? 'selected' : '' }}>
                                        {{ $asset->code }} - {{ $asset->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="posted" {{ request('status') == 'posted' ? 'selected' : '' }}>Posted</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Type</label>
                            <select name="is_reversal" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="0" {{ request('is_reversal') === '0' ? 'selected' : '' }}>Impairments</option>
                                <option value="1" {{ request('is_reversal') === '1' ? 'selected' : '' }}>Reversals</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Date From</label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Date To</label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-1 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bx bx-search"></i>
                            </button>
                            <a href="{{ route('assets.impairments.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-refresh"></i>
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Impairments Table -->
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Impairment #</th>
                                <th>Asset</th>
                                <th>Date</th>
                                <th>Carrying Amount</th>
                                <th>Recoverable Amount</th>
                                <th>Impairment Loss</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($impairments as $impairment)
                            <tr>
                                <td>
                                    <strong>{{ $impairment->impairment_number }}</strong>
                                    @if($impairment->is_reversal)
                                        <span class="badge bg-info ms-1">Reversal</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $impairment->asset->code ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $impairment->asset->name ?? 'N/A' }}</small>
                                </td>
                                <td>{{ $impairment->impairment_date->format('d M Y') }}</td>
                                <td class="text-end">{{ number_format($impairment->carrying_amount ?? 0, 2) }}</td>
                                <td class="text-end">{{ number_format($impairment->recoverable_amount ?? 0, 2) }}</td>
                                <td class="text-end text-danger">
                                    @if($impairment->impairment_loss > 0)
                                        {{ number_format($impairment->impairment_loss, 2) }}
                                    @elseif($impairment->reversal_amount > 0)
                                        <span class="text-success">+{{ number_format($impairment->reversal_amount, 2) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($impairment->is_reversal)
                                        <span class="badge bg-info">Reversal</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($impairment->impairment_type) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'pending_approval' => 'warning',
                                            'approved' => 'info',
                                            'posted' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $color = $statusColors[$impairment->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">
                                        {{ ucfirst(str_replace('_', ' ', $impairment->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('assets.impairments.show', \Vinkla\Hashids\Facades\Hashids::encode($impairment->id)) }}" 
                                           class="btn btn-outline-primary" title="View">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        @if(!$impairment->is_reversal && $impairment->status == 'posted' && $impairment->canBeReversed())
                                            <a href="{{ route('assets.impairments.create-reversal', \Vinkla\Hashids\Facades\Hashids::encode($impairment->id)) }}" 
                                               class="btn btn-outline-success" title="Create Reversal">
                                                <i class="bx bx-undo"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bx bx-info-circle me-2"></i>No impairments found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($impairments->hasPages())
                <div class="mt-3">
                    {{ $impairments->links() }}
                </div>
                @endif
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

