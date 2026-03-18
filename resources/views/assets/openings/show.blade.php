@extends('layouts.main')

@section('title', 'Opening Asset Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Opening Assets', 'url' => route('assets.openings.index'), 'icon' => 'bx bx-book-open'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Opening Asset Details</h4>
                    <div class="page-title-right d-flex gap-2">
                        <a href="{{ route('assets.openings.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back
                        </a>
                        <form id="delete-opening-form" method="POST" action="{{ route('assets.openings.destroy', Vinkla\Hashids\Facades\Hashids::encode($opening->id)) }}">
                            @csrf
                            @method('DELETE')
                            <button type="button" id="btn-delete-opening" class="btn btn-danger"><i class="bx bx-trash me-1"></i>Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Header Summary -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Asset Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="170"><strong>Asset Name:</strong></td>
                                        <td>{{ $opening->asset_name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Asset Code:</strong></td>
                                        <td>{{ $opening->asset_code ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Category:</strong></td>
                                        <td>{{ $category->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tax Pool Class:</strong></td>
                                        <td>
                                            @if($opening->tax_pool_class)
                                                <span class="badge bg-info text-dark">{{ $opening->tax_pool_class }}</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Opening Date:</strong></td>
                                        <td>{{ \Carbon\Carbon::parse($opening->opening_date)->format('d M Y') }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Opening Summary</h5>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted small">Opening Cost</div>
                                            <div class="fs-5 fw-semibold">TZS {{ number_format($opening->opening_cost, 2) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted small">Accumulated Depreciation</div>
                                            <div class="fs-5 fw-semibold">TZS {{ number_format($opening->opening_accum_depr, 2) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted small">Opening NBV</div>
                                            <div class="fs-5 fw-semibold text-primary">TZS {{ number_format($opening->opening_nbv, 2) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted small">GL Status</div>
                                            <div>
                                                @if($opening->gl_posted)
                                                    <span class="badge bg-success">Posted</span>
                                                @elseif($opening->gl_post)
                                                    <span class="badge bg-warning text-dark">To Post</span>
                                                @else
                                                    <span class="badge bg-secondary">N/A</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if($opening->notes)
                                <div class="mt-3">
                                    <div class="text-muted small">Notes</div>
                                    <div class="border rounded p-2 bg-light">{{ $opening->notes }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GL Double Entry Transactions -->
        @if($opening->gl_posted && $glTransactions->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-book-open me-2"></i>General Ledger Double Entry Transactions
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            $debitTotal = $glTransactions->where('nature', 'debit')->sum('amount');
                            $creditTotal = $glTransactions->where('nature', 'credit')->sum('amount');
                            $balance = $debitTotal - $creditTotal;
                        @endphp
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40%">Account</th>
                                        <th width="20%" class="text-center">Type</th>
                                        <th width="20%" class="text-end">Debit</th>
                                        <th width="20%" class="text-end">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($glTransactions as $t)
                                    <tr>
                                        <td>
                                            @if($t->chartAccount)
                                            <div class="fw-bold">{{ $t->chartAccount->account_name }}</div>
                                            <small class="text-muted">{{ $t->chartAccount->account_code }}</small>
                                            @else
                                            <div class="fw-bold text-warning">Account Not Found</div>
                                            <small class="text-muted">ID: {{ $t->chart_account_id }}</small>
                                            @endif
                                            @if($t->description)
                                            <br><small class="text-info">{{ $t->description }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $t->nature == 'debit' ? 'danger' : 'success' }} fs-6">{{ ucfirst($t->nature) }}</span>
                                        </td>
                                        <td class="text-end">
                                            @if($t->nature == 'debit')
                                                <span class="fw-bold text-danger">TZS {{ number_format($t->amount, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($t->nature == 'credit')
                                                <span class="fw-bold text-success">TZS {{ number_format($t->amount, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2" class="text-end">Totals:</th>
                                        <th class="text-end text-danger">TZS {{ number_format($debitTotal, 2) }}</th>
                                        <th class="text-end text-success">TZS {{ number_format($creditTotal, 2) }}</th>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="text-end">Balance:</th>
                                        <th colspan="2" class="text-end {{ $balance == 0 ? 'text-success' : 'text-danger' }}">
                                            TZS {{ number_format(abs($balance), 2) }}
                                            @if($balance == 0)
                                                <span class="badge bg-success ms-2">Balanced</span>
                                            @else
                                                <span class="badge bg-danger ms-2">Unbalanced</span>
                                            @endif
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="bx bx-info-circle me-1"></i>
                            <strong>Double Entry Logic:</strong> Opening entries are posted as: 
                            <strong>DR Asset (Cost)</strong>, <strong>CR Opening Equity (Cost)</strong>; 
                            <strong>DR Opening Equity (Accum. Depr)</strong>, <strong>CR Accum. Depr</strong>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @elseif($opening->gl_post && !$opening->gl_posted)
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="bx bx-info-circle me-1"></i>
                    This opening asset is marked for GL posting but has not been posted yet. Please review and post to GL.
                </div>
            </div>
        </div>
        @else
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="bx bx-info-circle me-1"></i>
                    Opening entries are posted as: DR Asset (Cost), CR Opening Equity (Cost); DR Opening Equity (Accum. Depr), CR Accum. Depr.
                    @if(!$opening->gl_post)
                    <br><small>This opening asset was not posted to GL. To post, edit the opening asset and enable "Post Opening to GL".</small>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.getElementById('btn-delete-opening')?.addEventListener('click', function(){
    const form = document.getElementById('delete-opening-form');
    if (!form) return;
    Swal.fire({
        title: 'Delete this opening?',
        text: 'This will also remove related GL entries.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});
</script>
@endpush


