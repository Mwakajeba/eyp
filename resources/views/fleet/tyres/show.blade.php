@extends('layouts.main')

@section('title', 'Tyre: ' . ($tyre->tyre_serial ?? $tyre->id) . ' - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Tyre Master Register', 'url' => route('fleet.tyres.index'), 'icon' => 'bx bx-circle'],
            ['label' => $tyre->tyre_serial ?? 'Tyre #' . $tyre->id, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bx bx-circle me-2"></i>Tyre: {{ $tyre->tyre_serial ?? 'ID ' . $tyre->id }}</h6>
                <div>
                    <a href="{{ route('fleet.tyres.edit', $tyre) }}" class="btn btn-light btn-sm"><i class="bx bx-edit me-1"></i>Edit</a>
                    <a href="{{ route('fleet.tyres.index') }}" class="btn btn-light btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
                </div>
            </div>
            <div class="card-body">
                <h6 class="text-primary mb-3"><i class="bx bx-detail me-2"></i>Identity & purchase</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tyre serial (ID)</label>
                        <p class="mb-0">{{ $tyre->tyre_serial ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">DOT number</label>
                        <p class="mb-0">{{ $tyre->dot_number ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Brand / Model</label>
                        <p class="mb-0">{{ $tyre->brand ?? '—' }} {{ $tyre->model ? ' / ' . $tyre->model : '' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tyre size</label>
                        <p class="mb-0">{{ $tyre->tyre_size ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Supplier</label>
                        <p class="mb-0">{{ $tyre->supplier ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Purchase date / cost</label>
                        <p class="mb-0">{{ $tyre->purchase_date?->format('d/m/Y') ?? '—' }} — {{ $tyre->purchase_cost ? number_format($tyre->purchase_cost) : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Warranty</label>
                        <p class="mb-0">{{ $tyre->warranty_type ? ucfirst($tyre->warranty_type) . ' ' . ($tyre->warranty_limit_value ?? '') : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Expected lifespan (km)</label>
                        <p class="mb-0">{{ $tyre->expected_lifespan_km ? number_format($tyre->expected_lifespan_km) : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status</label>
                        <p class="mb-0"><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $tyre->status)) }}</span></p>
                    </div>
                    @if($tyre->notes)
                    <div class="col-12">
                        <label class="form-label fw-bold">Notes</label>
                        <p class="mb-0">{{ $tyre->notes }}</p>
                    </div>
                    @endif
                </div>
                @if($tyre->installations->isNotEmpty())
                    <hr>
                    <h6 class="text-primary mb-3"><i class="bx bx-wrench me-2"></i>Installation history</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Vehicle</th>
                                    <th>Position</th>
                                    <th>Installed at</th>
                                    <th>Odometer</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tyre->installations as $inst)
                                    <tr>
                                        <td>{{ $inst->vehicle?->name ?? $inst->vehicle_id }}</td>
                                        <td>{{ $inst->tyrePosition?->position_name ?? $inst->tyre_position_id }}</td>
                                        <td>{{ $inst->installed_at?->format('d/m/Y') }}</td>
                                        <td>{{ $inst->odometer_at_install ? number_format($inst->odometer_at_install) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
