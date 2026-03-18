@extends('layouts.main')

@section('title', 'Tyre Installation - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Tyre Installation', 'url' => route('fleet.tyre-installations.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Installation #' . $installation->id, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bx bx-wrench me-2"></i>Installation: {{ $installation->tyre?->tyre_serial ?? 'Tyre #' . $installation->tyre_id }} on {{ $installation->vehicle?->name ?? $installation->vehicle_id }}</h6>
                <div>
                    <a href="{{ route('fleet.tyre-installations.edit', $installation) }}" class="btn btn-light btn-sm"><i class="bx bx-edit me-1"></i>Edit</a>
                    <form action="{{ route('fleet.tyre-installations.destroy', $installation) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this installation record?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm"><i class="bx bx-trash me-1"></i>Delete</button>
                    </form>
                    <a href="{{ route('fleet.tyre-installations.index') }}" class="btn btn-light btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tyre</label>
                        <p class="mb-0">{{ $installation->tyre?->tyre_serial ?? $installation->tyre_id }} — {{ $installation->tyre?->brand ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Vehicle</label>
                        <p class="mb-0">{{ $installation->vehicle?->name ?? $installation->vehicle_id }} ({{ $installation->vehicle?->registration_number ?? '—' }})</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Position</label>
                        <p class="mb-0">{{ $installation->tyrePosition?->position_name ?? $installation->tyre_position_id }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Installed at</label>
                        <p class="mb-0">{{ $installation->installed_at?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Odometer at install</label>
                        <p class="mb-0">{{ $installation->odometer_at_install ? number_format($installation->odometer_at_install) . ' km' : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Installer</label>
                        <p class="mb-0">{{ $installation->installer_name ?? ($installation->installer_type ? ucfirst($installation->installer_type) : '—') }}</p>
                    </div>
                    @if($installation->notes)
                    <div class="col-12">
                        <label class="form-label fw-bold">Notes</label>
                        <p class="mb-0">{{ $installation->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
