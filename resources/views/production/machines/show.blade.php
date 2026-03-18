@extends('layouts.main')

@section('title', 'Production Machine Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Production Management', 'url' => '#', 'icon' => 'bx bx-cog'],
            ['label' => 'Production Machines', 'url' => route('production.machines.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Machine Details', 'url' => '#', 'icon' => 'bx bx-book-open']
        ]" />
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 text-dark fw-bold">
                            <i class="bx bx-book-open me-2 text-primary"></i>
                            Production Machine Details
                        </h4>
                    </div>
                    <div class="d-flex gap-2">
                        @php $hashid = Vinkla\Hashids\Facades\Hashids::encode($machine->id); @endphp
                        <a href="{{ route('production.machines.edit', $hashid) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i> Edit Machine
                        </a>
                        <a href="{{ route('production.machines.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered" style="max-width: 500px;">
                    <tr>
                        <th>Machine Name</th>
                        <td>{{ $machine->machine_name }}</td>
                    </tr>
                    <tr>
                        <th>Purchased Date</th>
                        <td>{{ $machine->purchased_date->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($machine->status === 'new')
                                <span class="badge bg-success">New</span>
                            @else
                                <span class="badge bg-warning">Used</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Location</th>
                        <td>{{ $machine->location }}</td>
                    </tr>
                    <tr>
                        <th>Production Stage</th>
                        <td>
                            @if($machine->production_stage)
                                {!! $machine->stage_badge !!}
                            @else
                                <span class="badge bg-secondary">No Stage Assigned</span>
                            @endif
                        </td>
                    </tr>
                    @if($machine->production_stage === 'KNITTING')
                    <tr>
                        <th>Gauge</th>
                        <td>
                            <strong>{{ $machine->gauge ?? 'Not specified' }}</strong>
                            @if($machine->gauge)
                                <small class="text-muted d-block">Knitting machine specification</small>
                            @endif
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
