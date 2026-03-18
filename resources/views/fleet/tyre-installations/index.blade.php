@extends('layouts.main')

@section('title', 'Tyre Installation - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Tyre Installation', 'url' => route('fleet.tyre-installations.index'), 'icon' => 'bx bx-wrench']
        ]" />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-success text-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-wrench me-2"></i>Tyre Installation</h5>
                    <div class="text-white-50">Assign tyres to trucks and positions. Installation date, odometer, and installer. Cool-down rules apply.</div>
                </div>
                <div>
                    <a href="{{ route('fleet.tyre-installations.create') }}" class="btn btn-light"><i class="bx bx-plus me-1"></i>Add New Installation</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Tyre</th>
                                <th>Vehicle</th>
                                <th>Position</th>
                                <th>Installed at</th>
                                <th>Odometer</th>
                                <th>Installer</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($installations as $inst)
                                <tr>
                                    <td>{{ $inst->tyre?->tyre_serial ?? $inst->tyre_id }}</td>
                                    <td>{{ $inst->vehicle?->name ?? $inst->vehicle?->registration_number ?? $inst->vehicle_id }}</td>
                                    <td>{{ $inst->tyrePosition?->position_name ?? $inst->tyre_position_id }}</td>
                                    <td>{{ $inst->installed_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $inst->odometer_at_install ? number_format($inst->odometer_at_install) : '—' }}</td>
                                    <td>{{ $inst->installer_name ?? ($inst->installer_type ? ucfirst($inst->installer_type) : '—') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('fleet.tyre-installations.show', $inst) }}" class="btn btn-outline-info" title="View"><i class="bx bx-show"></i></a>
                                            <a href="{{ route('fleet.tyre-installations.edit', $inst) }}" class="btn btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                                            <form action="{{ route('fleet.tyre-installations.destroy', $inst) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this installation record? The tyre will be marked as Removed if it has no other installations.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Delete"><i class="bx bx-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No installations yet. <a href="{{ route('fleet.tyre-installations.create') }}">Add your first installation</a>.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($installations->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $installations->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
