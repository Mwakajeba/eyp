@extends('layouts.main')

@section('title', 'Tyre Installation & Removal Log Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Tyre Installation Log', 'url' => '#', 'icon' => 'bx bx-transfer']
        ]" />

        <h6 class="mb-0 text-uppercase">TYRE INSTALLATION & REMOVAL LOG REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.tyre-installation-log') }}" class="row g-3">
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
                    <form method="POST" action="{{ route('fleet.reports.tyre-installation-log.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-success"><i class="bx bx-file me-1"></i> Export Excel</button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.tyre-installation-log.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-danger"><i class="bx bx-file-blank me-1"></i> Export PDF</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary -->
        @if($installations->count() > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Installations</h6>
                        <h4 class="text-primary">{{ $installations->count() }}</h4>
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
                                <th>Installation Date</th>
                                <th>Tyre Serial</th>
                                <th>Vehicle</th>
                                <th>Position</th>
                                <th class="text-end">Odometer at Install</th>
                                <th>Installer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($installations as $index => $i)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $i->installed_at ? $i->installed_at->format('Y-m-d') : 'N/A' }}</td>
                                <td>{{ $i->tyre ? $i->tyre->tyre_serial : 'N/A' }}</td>
                                <td>{{ $i->vehicle ? $i->vehicle->name : 'N/A' }}</td>
                                <td>{{ $i->tyrePosition ? $i->tyrePosition->name : 'N/A' }}</td>
                                <td class="text-end">{{ $i->odometer_at_install ? number_format($i->odometer_at_install, 0) : 'N/A' }}</td>
                                <td>{{ $i->installer_name ?? 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">No tyre installation records found for the selected period.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
