@extends('layouts.main')

@section('title', 'Amortization Schedule')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Investment Management', 'url' => route('investments.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Investment Master', 'url' => route('investments.master.index'), 'icon' => 'bx bx-package'],
            ['label' => $master->instrument_code, 'url' => route('investments.master.show', \Vinkla\Hashids\Facades\Hashids::encode($master->id)), 'icon' => 'bx bx-show'],
            ['label' => 'Amortization Schedule', 'url' => '#', 'icon' => 'bx bx-table']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">AMORTIZATION SCHEDULE: {{ $master->instrument_code }}</h6>
            <div class="btn-group">
                <a href="{{ route('investments.master.show', \Vinkla\Hashids\Facades\Hashids::encode($master->id)) }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back"></i> Back to Investment
                </a>
                <form action="{{ route('investments.master.generate-amortization', \Vinkla\Hashids\Facades\Hashids::encode($master->id)) }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="recompute" value="1">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Recompute amortization schedule? This will regenerate all future lines.')">
                        <i class="bx bx-refresh"></i> Recompute Schedule
                    </button>
                </form>
            </div>
        </div>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Investment Summary -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Instrument:</strong> {{ $master->instrument_code }}
                    </div>
                    <div class="col-md-3">
                        <strong>EIR Rate:</strong> {{ $master->eir_rate ? number_format($master->eir_rate, 4) . '%' : 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Nominal Amount:</strong> TZS {{ number_format($master->nominal_amount, 2) }}
                    </div>
                    <div class="col-md-3">
                        <strong>Day Count:</strong> {{ $master->day_count ?? 'ACT/365' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Amortization Schedule Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Amortization Schedule</h5>
            </div>
            <div class="card-body">
                @if($amortizationLines->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Period Start</th>
                                <th>Period End</th>
                                <th>Days</th>
                                <th class="text-end">Opening Carrying Amount</th>
                                <th class="text-end">Interest Income</th>
                                <th class="text-end">Cash Flow</th>
                                <th class="text-end">Amortization</th>
                                <th class="text-end">Closing Carrying Amount</th>
                                <th>EIR Rate</th>
                                <th>Status</th>
                                <th>Journal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($amortizationLines as $line)
                            <tr class="{{ $line->posted ? 'table-success' : '' }}">
                                <td>{{ $line->period_start->format('M d, Y') }}</td>
                                <td>{{ $line->period_end->format('M d, Y') }}</td>
                                <td>{{ $line->days }}</td>
                                <td class="text-end">TZS {{ number_format($line->opening_carrying_amount, 2) }}</td>
                                <td class="text-end">TZS {{ number_format($line->interest_income, 2) }}</td>
                                <td class="text-end">TZS {{ number_format($line->cash_flow, 2) }}</td>
                                <td class="text-end">TZS {{ number_format($line->amortization, 2) }}</td>
                                <td class="text-end"><strong>TZS {{ number_format($line->closing_carrying_amount, 2) }}</strong></td>
                                <td>{{ number_format($line->eir_rate, 4) }}%</td>
                                <td>
                                    @if($line->posted)
                                        <span class="badge bg-success">Posted</span><br>
                                        <small class="text-muted">{{ $line->posted_at->format('M d, Y') }}</small>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($line->journal_id)
                                        <a href="{{ route('accounting.journals.show', \Vinkla\Hashids\Facades\Hashids::encode($line->journal_id)) }}" target="_blank">
                                            Journal #{{ $line->journal_id }}
                                            <i class="bx bx-external-link"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Totals</th>
                                <th class="text-end">-</th>
                                <th class="text-end">TZS {{ number_format($amortizationLines->sum('interest_income'), 2) }}</th>
                                <th class="text-end">TZS {{ number_format($amortizationLines->sum('cash_flow'), 2) }}</th>
                                <th class="text-end">TZS {{ number_format($amortizationLines->sum('amortization'), 2) }}</th>
                                <th class="text-end">-</th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Summary Statistics -->
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6>Total Interest Income</h6>
                                <h4>TZS {{ number_format($amortizationLines->sum('interest_income'), 2) }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6>Posted Lines</h6>
                                <h4>{{ $amortizationLines->where('posted', true)->count() }} / {{ $amortizationLines->count() }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h6>Pending Lines</h6>
                                <h4>{{ $amortizationLines->where('posted', false)->count() }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="alert alert-info">
                    <i class="bx bx-info-circle me-2"></i>
                    No amortization schedule found. 
                    <a href="{{ route('investments.master.show', \Vinkla\Hashids\Facades\Hashids::encode($master->id)) }}" class="alert-link">
                        Generate amortization schedule
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

