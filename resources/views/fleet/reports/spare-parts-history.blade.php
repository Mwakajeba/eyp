@extends('layouts.main')

@section('title', 'Spare Parts Replacement History Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Spare Parts Replacement History', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />

        <h6 class="mb-0 text-uppercase">SPARE PARTS REPLACEMENT HISTORY REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.spare-parts-history') }}" class="row g-3">
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
                    <form method="POST" action="{{ route('fleet.reports.spare-parts-history.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-success"><i class="bx bx-file me-1"></i> Export Excel</button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.spare-parts-history.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-danger"><i class="bx bx-file-blank me-1"></i> Export PDF</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary -->
        @if($replacements->count() > 0)
        <div class="row">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Replacements</h6>
                        <h4 class="text-primary">{{ $replacements->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Cost (TZS)</h6>
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
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Spare Part</th>
                                <th class="text-end">Cost (TZS)</th>
                                <th class="text-end">Odometer</th>
                                <th>Reason</th>
                                <th>Approved By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($replacements as $index => $r)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $r->replaced_at ? $r->replaced_at->format('Y-m-d') : 'N/A' }}</td>
                                <td>{{ $r->vehicle ? $r->vehicle->name : 'N/A' }}</td>
                                <td>{{ $r->sparePartCategory ? $r->sparePartCategory->name : 'N/A' }}</td>
                                <td class="text-end">{{ number_format($r->cost ?? 0, 0) }}</td>
                                <td class="text-end">{{ $r->odometer_at_replacement ? number_format($r->odometer_at_replacement, 0) : 'N/A' }}</td>
                                <td>{{ Str::limit($r->reason ?? 'N/A', 40) }}</td>
                                <td>{{ $r->approvedBy ? $r->approvedBy->name : 'N/A' }}</td>
                                <td><span class="badge bg-{{ $r->status === 'approved' ? 'success' : ($r->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($r->status ?? 'N/A') }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">No spare parts replacements found for the selected period.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($replacements->count() > 0)
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">TOTAL:</th>
                                <th class="text-end">{{ number_format($replacements->sum('cost'), 0) }}</th>
                                <th colspan="4"></th>
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
