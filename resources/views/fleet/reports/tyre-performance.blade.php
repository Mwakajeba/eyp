@extends('layouts.main')

@section('title', 'Tyre Performance & Lifespan Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Tyre Performance & Lifespan', 'url' => '#', 'icon' => 'bx bx-circle']
        ]" />

        <h6 class="mb-0 text-uppercase">TYRE PERFORMANCE & LIFESPAN REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.tyre-performance') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from', \Carbon\Carbon::now()->subMonths(12)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to', \Carbon\Carbon::now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-search me-1"></i> Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('fleet.reports.tyre-performance.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.tyre-performance.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-file-blank me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary -->
        @if($tyres->count() > 0)
        <div class="row">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Tyres</h6>
                        <h4 class="text-primary">{{ $tyres->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Purchase Cost (TZS)</h6>
                        <h4 class="text-success">{{ number_format($totalCost ?? 0, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Report Data -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Serial Number</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Size</th>
                                <th>Purchase Date</th>
                                <th class="text-end">Purchase Cost</th>
                                <th class="text-end">Expected KM</th>
                                <th class="text-end">KM Covered</th>
                                <th class="text-end">Remaining KM</th>
                                <th>Status</th>
                                <th>Current Vehicle</th>
                                <th>Position</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tyres as $index => $tyre)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $tyre->serial_number }}</td>
                                <td>{{ $tyre->brand }}</td>
                                <td>{{ $tyre->model }}</td>
                                <td>{{ $tyre->size }}</td>
                                <td>{{ $tyre->purchase_date }}</td>
                                <td class="text-end">{{ number_format($tyre->purchase_cost, 0) }}</td>
                                <td class="text-end">{{ number_format($tyre->expected_lifespan_km, 0) }}</td>
                                <td class="text-end">{{ number_format($tyre->km_covered, 0) }}</td>
                                <td class="text-end">{{ number_format($tyre->remaining_km, 0) }}</td>
                                <td><span class="badge bg-{{ $tyre->status === 'in_use' ? 'success' : 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $tyre->status ?? 'N/A')) }}</span></td>
                                <td>{{ $tyre->vehicle }}</td>
                                <td>{{ $tyre->position }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="13" class="text-center">No tyre data available for the selected period.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($tyres->count() > 0)
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-end">TOTAL:</th>
                                <th class="text-end">{{ number_format($tyres->sum('purchase_cost'), 0) }}</th>
                                <th colspan="7"></th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
