@extends('layouts.main')

@section('title', 'Production Machines')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Production Management', 'url' => '#', 'icon' => 'bx bx-cog'],
            ['label' => 'Production Machines', 'url' => route('production.machines.index'), 'icon' => 'bx bx-cog']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-sm-4">
                                <a href="{{ route('production.machines.create') }}" class="btn btn-danger mb-2">
                                    <i class="bx bx-plus-circle me-2"></i> Add Machine
                                </a>
                            </div>
                            <div class="col-sm-8">
                                <div class="text-sm-end">
                                    <button type="button" class="btn btn-success mb-2 me-1">
                                        <i class="bx bx-export"></i> Export
                                    </button>
                                    <button type="button" class="btn btn-light mb-2">
                                        <i class="bx bx-filter-alt"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-centered w-100 dt-responsive nowrap">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Machine Name</th>
                                        <th>Production Stage</th>
                                        <th>Gauge</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Purchased Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($machines as $machine)
                                    <tr>
                                        <td>{{ $machine->id }}</td>
                                        <td>
                                            <strong>{{ $machine->machine_name }}</strong>
                                        </td>
                                        <td>
                                            @if($machine->production_stage)
                                                {!! $machine->stage_badge !!}
                                            @else
                                                <span class="badge bg-secondary">No Stage</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $machine->gauge ?? 'N/A' }}
                                        </td>
                                        <td>
                                            <i class="bx bx-map text-muted me-1"></i>
                                            {{ $machine->location }}
                                        </td>
                                        <td>
                                            @if($machine->status === 'new')
                                                <span class="badge bg-success">New</span>
                                            @else
                                                <span class="badge bg-warning">Used</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $machine->purchased_date ? $machine->purchased_date->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td>
                                            @php
                                                $hashid = Vinkla\Hashids\Facades\Hashids::encode($machine->id);
                                            @endphp
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('production.machines.show', $hashid) }}" class="btn btn-sm btn-info">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('production.machines.edit', $hashid) }}" class="btn btn-sm btn-warning">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger delete-machine-btn" 
                                                        data-machine-id="{{ $hashid }}" 
                                                        data-machine-name="{{ $machine->machine_name }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bx bx-info-circle me-2"></i>
                                                No production machines found. <a href="{{ route('production.machines.create') }}">Add your first machine</a>.
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteMachineModal" tabindex="-1" aria-labelledby="deleteMachineModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteMachineModalLabel">Delete Machine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="machineNameToDelete"></strong>?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteMachineForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Machine</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Handle delete button clicks
    $('.delete-machine-btn').click(function() {
        var machineId = $(this).data('machine-id');
        var machineName = $(this).data('machine-name');
        
        $('#machineNameToDelete').text(machineName);
        $('#deleteMachineForm').attr('action', '{{ route("production.machines.destroy", ":id") }}'.replace(':id', machineId));
        $('#deleteMachineModal').modal('show');
    });
});
</script>
@endpush
