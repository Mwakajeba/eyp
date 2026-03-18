@extends('layouts.main')

@section('title', 'Investment Trade Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Investment Management', 'url' => route('investments.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Trades', 'url' => route('investments.trades.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Trade Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">TRADE DETAILS</h6>
            <a href="{{ route('investments.trades.index') }}" class="btn btn-secondary">
                <i class="bx bx-arrow-back"></i> Back to List
            </a>
        </div>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <!-- Trade Details -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Trade Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Trade Type:</strong><br>
                                <span class="badge bg-{{ $trade->trade_type == 'PURCHASE' ? 'success' : ($trade->trade_type == 'SALE' ? 'danger' : 'info') }}">
                                    {{ $trade->trade_type }}
                                </span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Trade Date:</strong><br>
                                {{ $trade->trade_date->format('Y-m-d') }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Settlement Date:</strong><br>
                                {{ $trade->settlement_date->format('Y-m-d') }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Settlement Status:</strong><br>
                                <span class="badge bg-{{ $trade->settlement_status == 'SETTLED' ? 'success' : ($trade->settlement_status == 'FAILED' ? 'danger' : 'warning') }}">
                                    {{ $trade->settlement_status }}
                                </span>
                            </div>
                            @if($trade->investment)
                            <div class="col-md-6 mb-3">
                                <strong>Investment:</strong><br>
                                <a href="{{ route('investments.master.show', \Vinkla\Hashids\Facades\Hashids::encode($trade->investment_id)) }}">
                                    {{ $trade->investment->instrument_code }} - {{ $trade->investment->issuer }}
                                </a>
                            </div>
                            @endif
                            <div class="col-md-6 mb-3">
                                <strong>Trade Price:</strong><br>
                                {{ number_format($trade->trade_price, 6) }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Trade Units:</strong><br>
                                {{ number_format($trade->trade_units, 6) }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Gross Amount:</strong><br>
                                TZS {{ number_format($trade->gross_amount, 2) }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Fees:</strong><br>
                                TZS {{ number_format($trade->fees ?? 0, 2) }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Tax Withheld:</strong><br>
                                TZS {{ number_format($trade->tax_withheld ?? 0, 2) }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Net Amount:</strong><br>
                                <strong class="text-primary">TZS {{ number_format($trade->net_amount, 2) }}</strong>
                            </div>
                            @if($trade->bank_ref)
                            <div class="col-md-6 mb-3">
                                <strong>Bank Reference:</strong><br>
                                {{ $trade->bank_ref }}
                            </div>
                            @endif
                            <div class="col-md-6 mb-3">
                                <strong>Created By:</strong><br>
                                {{ $trade->creator->name ?? 'N/A' }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Created At:</strong><br>
                                {{ $trade->created_at->format('Y-m-d H:i:s') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Journal Entry -->
                @if($trade->posted_journal_id)
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Posted Journal Entry</h5>
                    </div>
                    <div class="card-body">
                        <p>
                            <strong>Journal:</strong>
                            <a href="{{ route('accounting.journals.show', \Vinkla\Hashids\Facades\Hashids::encode($trade->posted_journal_id)) }}" target="_blank">
                                View Journal #{{ $trade->posted_journal_id }}
                                <i class="bx bx-external-link"></i>
                            </a>
                        </p>
                        @if($trade->journal && $trade->journal->items)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trade->journal->items as $item)
                                    <tr>
                                        <td>{{ $item->chartAccount->account_name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ $item->nature == 'debit' ? number_format($item->amount, 2) : '-' }}</td>
                                        <td class="text-end">{{ $item->nature == 'credit' ? number_format($item->amount, 2) : '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
                @elseif($journalPreview && $trade->trade_type === 'PURCHASE')
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Journal Preview (Not Posted)</h5>
                    </div>
                    <div class="card-body">
                        @if($journalPreview['balanced'])
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            Journal is balanced. Ready to post.
                        </div>
                        @else
                        <div class="alert alert-warning">
                            <i class="bx bx-error-circle me-2"></i>
                            Journal is not balanced! Debit: {{ number_format($journalPreview['total_debit'], 2) }}, 
                            Credit: {{ number_format($journalPreview['total_credit'], 2) }}
                        </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($journalPreview['entries'] as $entry)
                                    <tr>
                                        <td>{{ \App\Models\ChartAccount::find($entry['chart_account_id'])->account_name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ $entry['nature'] == 'debit' ? number_format($entry['amount'], 2) : '-' }}</td>
                                        <td class="text-end">{{ $entry['nature'] == 'credit' ? number_format($entry['amount'], 2) : '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-end">{{ number_format($journalPreview['total_debit'], 2) }}</th>
                                        <th class="text-end">{{ number_format($journalPreview['total_credit'], 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @if($trade->investment_id)
                        <form action="{{ route('investments.trades.post-journal', \Vinkla\Hashids\Facades\Hashids::encode($trade->trade_id)) }}" method="POST" class="mt-3">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Bank Account (for payment)</label>
                                <select name="bank_account_id" class="form-select">
                                    <option value="">Auto-select</option>
                                    @php
                                        $bankAccounts = \App\Models\BankAccount::with('chartAccount')
                                            ->whereHas('chartAccount.accountClassGroup', function ($q) {
                                                $q->where('company_id', auth()->user()->company_id);
                                            })
                                            ->where('is_active', true)
                                            ->get();
                                    @endphp
                                    @foreach($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}">
                                        {{ $bankAccount->name }}
                                        @if($bankAccount->chartAccount)
                                            - {{ $bankAccount->chartAccount->account_name }}
                                        @endif
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success" onclick="return confirm('Post this journal entry?')">
                                <i class="bx bx-check"></i> Post Journal Entry
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <div class="col-md-4">
                <!-- Settlement Actions -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Settlement Management</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('investments.trades.update-settlement', \Vinkla\Hashids\Facades\Hashids::encode($trade->trade_id)) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Settlement Status</label>
                                <select name="settlement_status" class="form-select" required>
                                    <option value="PENDING" {{ $trade->settlement_status == 'PENDING' ? 'selected' : '' }}>Pending</option>
                                    <option value="INSTRUCTED" {{ $trade->settlement_status == 'INSTRUCTED' ? 'selected' : '' }}>Instructed</option>
                                    <option value="SETTLED" {{ $trade->settlement_status == 'SETTLED' ? 'selected' : '' }}>Settled</option>
                                    <option value="FAILED" {{ $trade->settlement_status == 'FAILED' ? 'selected' : '' }}>Failed</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bank Reference</label>
                                <input type="text" name="bank_ref" class="form-control" value="{{ $trade->bank_ref }}" placeholder="Bank transaction reference">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bx bx-save"></i> Update Settlement
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

