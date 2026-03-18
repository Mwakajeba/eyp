@extends('layouts.main')

@section('title', 'Tyre Abuse & Warranty Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Tyre Abuse & Warranty', 'url' => '#', 'icon' => 'bx bx-error-alt']
        ]" />

        <h6 class="mb-0 text-uppercase">TYRE ABUSE & WARRANTY REPORT</h6>
        <hr />

        <!-- Export Buttons (no date filter for this report) -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('fleet.reports.tyre-abuse-warranty.export-excel') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-success"><i class="bx bx-file me-1"></i> Export Excel</button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.tyre-abuse-warranty.export-pdf') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-danger"><i class="bx bx-file-blank me-1"></i> Export PDF</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary -->
        @if($abuseData->count() > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Premature Tyre Failures Detected</h6>
                        <h4 class="text-danger">{{ $abuseData->count() }}</h4>
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
                                <th>Tyre Serial</th>
                                <th>Vehicle</th>
                                <th>Requested By</th>
                                <th class="text-end">KM Covered</th>
                                <th class="text-end">Expected KM</th>
                                <th>Failure %</th>
                                <th>Reason</th>
                                <th>Request Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($abuseData as $index => $abuse)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $abuse->tyre_serial }}</td>
                                <td>{{ $abuse->vehicle }}</td>
                                <td>{{ $abuse->requested_by }}</td>
                                <td class="text-end">{{ number_format($abuse->km_covered, 0) }}</td>
                                <td class="text-end">{{ number_format($abuse->expected_km, 0) }}</td>
                                <td><span class="badge bg-danger">{{ $abuse->failure_pct }}%</span></td>
                                <td>{{ $abuse->reason }}</td>
                                <td>{{ $abuse->request_date }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">No premature tyre failures detected.</td>
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
