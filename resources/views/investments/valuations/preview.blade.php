@extends('layouts.main')

@section('title', 'Preview Revaluation')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Investment Management', 'url' => route('investments.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Valuations', 'url' => route('investments.valuations.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Preview Revaluation', 'url' => '#', 'icon' => 'bx bx-search']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">PREVIEW REVALUATION JOURNAL</h6>
            <a href="{{ route('investments.valuations.show', $valuation->hash_id) }}" class="btn btn-secondary">
                <i class="bx bx-arrow-back"></i> Back to Valuation
            </a>
        </div>
        <hr />

        <!-- Revaluation Summary -->
        <div class="card mb-3">
            <div class="card-header bg-primary bg-gradient text-white">
                <h5 class="mb-0">
                    <i class="bx bx-calculator me-2"></i>Revaluation Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold text-muted">Investment</label>
                        <div class="fw-bold">{{ $preview['investment_code'] }}</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold text-muted">Valuation Date</label>
                        <div class="fw-bold">{{ $preview['valuation_date'] }}</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold text-muted">Carrying Amount (Before)</label>
                        <div class="fw-bold">TZS {{ number_format($preview['carrying_amount_before'], 2) }}</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold text-muted">Fair Value</label>
                        <div class="fw-bold text-success">TZS {{ number_format($preview['fair_value'], 2) }}</div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-semibold text-muted">Gain/Loss</label>
                        <div class="h4 fw-bold {{ $preview['gain_loss'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $preview['gain_loss'] >= 0 ? '+' : '' }}TZS {{ number_format($preview['gain_loss'], 2) }}
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-semibold text-muted">Accounting Classification</label>
                        <div>
                            <span class="badge bg-info">{{ $preview['accounting_class'] }}</span>
                            @if($preview['accounting_class'] == 'FVPL')
                                <small class="text-muted ms-2">Gains/losses go to P&L</small>
                            @else
                                <small class="text-muted ms-2">Gains/losses go to OCI Reserve</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Journal Preview -->
        <div class="card mb-3">
            <div class="card-header bg-success bg-gradient text-white">
                <h5 class="mb-0">
                    <i class="bx bx-book me-2"></i>Journal Entries Preview
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['journal_entries'] as $entry)
                            <tr>
                                <td>
                                    @php
                                        $account = \App\Models\ChartAccount::find($entry['account']);
                                    @endphp
                                    {{ $account ? $account->account_code . ' - ' . $account->account_name : 'N/A' }}
                                </td>
                                <td class="text-end">{{ $entry['debit'] > 0 ? 'TZS ' . number_format($entry['debit'], 2) : '-' }}</td>
                                <td class="text-end">{{ $entry['credit'] > 0 ? 'TZS ' . number_format($entry['credit'], 2) : '-' }}</td>
                                <td>{{ $entry['description'] }}</td>
                            </tr>
                            @endforeach
                            <tr class="table-info fw-bold">
                                <td colspan="1">Total</td>
                                <td class="text-end">TZS {{ number_format(collect($preview['journal_entries'])->sum('debit'), 2) }}</td>
                                <td class="text-end">TZS {{ number_format(collect($preview['journal_entries'])->sum('credit'), 2) }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('investments.valuations.show', $valuation->hash_id) }}" class="btn btn-secondary">
                        <i class="bx bx-x me-1"></i> Cancel
                    </a>
                    <form action="{{ route('investments.valuations.post', $valuation->hash_id) }}" method="POST" class="d-inline" id="post-form">
                        @csrf
                        <button type="button" class="btn btn-primary" onclick="confirmPost()">
                            <i class="bx bx-check me-1"></i> Post Revaluation
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function confirmPost() {
    Swal.fire({
        title: 'Post Revaluation?',
        text: 'This will create a journal entry and update the investment carrying amount. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, post it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('post-form').submit();
        }
    });
}
</script>
@endpush

