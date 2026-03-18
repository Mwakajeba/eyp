@extends('layouts.main')

@section('title', 'Cost Components - ' . $asset->name)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Intangible Assets', 'url' => route('assets.intangible.index'), 'icon' => 'bx bx-brain'],
            ['label' => 'Cost Components', 'url' => '#', 'icon' => 'bx bx-list-ul']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">Cost Components Management</h5>
                <p class="text-muted mb-0">{{ $asset->name }} ({{ $asset->code }})</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('assets.intangible.cost-components.export', $encodedId) }}" class="btn btn-outline-info">
                    <i class="bx bx-download me-1"></i>Export
                </a>
                <a href="{{ route('assets.intangible.cost-components.create', $encodedId) }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Add Cost Component
                </a>
                <a href="{{ route('assets.intangible.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Cost Summary Card -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-title bg-primary text-white rounded-2">
                                    <i class="bx bx-dollar"></i>
                                </span>
                            </div>
                            <div>
                                <p class="text-muted mb-1 small">Asset Cost</p>
                                <h4 class="mb-0">TZS {{ number_format($asset->cost, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-title bg-info text-white rounded-2">
                                    <i class="bx bx-list-check"></i>
                                </span>
                            </div>
                            <div>
                                <p class="text-muted mb-1 small">Total Components</p>
                                <h4 class="mb-0">TZS {{ number_format($totalCostComponents, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-{{ abs($costDifference) < 0.01 ? 'success' : 'warning' }}">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm flex-shrink-0 me-3">
                                <span class="avatar-title bg-{{ abs($costDifference) < 0.01 ? 'success' : 'warning' }} text-white rounded-2">
                                    <i class="bx bx-{{ abs($costDifference) < 0.01 ? 'check' : 'error' }}"></i>
                                </span>
                            </div>
                            <div>
                                <p class="text-muted mb-1 small">Difference</p>
                                <h4 class="mb-0">TZS {{ number_format($costDifference, 2) }}</h4>
                                @if(abs($costDifference) >= 0.01)
                                    <small class="text-warning"><i class="bx bx-info-circle"></i> Discrepancy detected</small>
                                @else
                                    <small class="text-success"><i class="bx bx-check-circle"></i> Balanced</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(abs($costDifference) >= 0.01)
        <div class="alert alert-warning">
            <i class="bx bx-info-circle me-2"></i>
            <strong>Cost Discrepancy Detected:</strong> The total of cost components (TZS {{ number_format($totalCostComponents, 2) }}) 
            does not match the asset cost (TZS {{ number_format($asset->cost, 2) }}). 
            Difference: TZS {{ number_format($costDifference, 2) }}. 
            Please review and adjust cost components or update the asset cost.
        </div>
        @endif

        <!-- Cost Components Table -->
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Cost Components Breakdown</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="costComponentsTable" class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const table = $('#costComponentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("assets.intangible.cost-components.data", $encodedId) }}',
            error: function(xhr, status, error) {
                console.error('DataTables error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error loading data!',
                    text: 'Could not load cost components. Please try again later.',
                });
            }
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'date', name: 'date' },
            { data: 'type', name: 'type' },
            { data: 'description', name: 'description' },
            { data: 'amount', name: 'amount' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<i class="bx bx-loader bx-spin"></i> Loading...',
            emptyTable: '<div class="text-center text-muted py-4"><i class="bx bx-info-circle me-2"></i>No cost components found</div>',
            zeroRecords: '<div class="text-center text-muted py-4"><i class="bx bx-info-circle me-2"></i>No matching cost components found</div>'
        }
    });

    // Handle delete button click
    $(document).on('click', '.delete-component', function() {
        const componentId = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("assets.intangible.cost-components.destroy", [$encodedId, ":component"]) }}'.replace(':component', componentId),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire(
                            'Deleted!',
                            response.message || 'Cost component has been deleted.',
                            'success'
                        );
                        table.draw(); // Reload DataTable
                    },
                    error: function(xhr) {
                        console.error('Error deleting cost component:', xhr.responseText);
                        Swal.fire(
                            'Error!',
                            xhr.responseJSON?.message || 'Failed to delete cost component.',
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>
@endpush

