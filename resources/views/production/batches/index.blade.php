@extends('layouts.main')
@section('content')
@section('title', 'Production Batches')
@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Production Batches', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />
        <div class="page-title-box">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="page-title mb-0">Production Batches</h4>
                </div>
                <div class="col-md-6">
                    <div class="float-end">
                        <a href="{{ route('production.batches.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i>Create New Batch
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-body">
                <div class="d-lg-flex align-items-center mb-4 gap-3">
                    <div class="position-relative">
                        <input type="text" class="form-control ps-5 radius-30" placeholder="Search Batches..." id="search-input">
                        <span class="position-absolute top-50 product-show translate-middle-y"><i class="bx bx-search"></i></span>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('production.batches.create') }}" class="btn btn-primary radius-30 mt-2 mt-lg-0">
                            <i class="bx bxs-plus-square"></i>Add Batch
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0" id="batches-table">
                        <thead class="table-light">
                            <tr>
                                <th>Batch #</th>
                                <th>Item</th>
                                <th>Quantity Planned</th>
                                <th>Quantity Produced</th>
                                <th>Status</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batches as $batch)
                                @php $hashid = Vinkla\Hashids\Facades\Hashids::encode($batch->id); @endphp
                                <tr>
                                    <td>{{ $batch->batch_number }}</td>
                                    <td>{{ optional($batch->item)->name }}</td>
                                    <td>{{ $batch->quantity_planned }}</td>
                                    <td>{{ $batch->quantity_produced }}</td>
                                    <td>{{ $batch->status }}</td>
                                    <td>{{ $batch->start_date }}</td>
                                    <td>{{ $batch->end_date }}</td>
                                    <td>
                                        <a href="{{ route('production.batches.show', $hashid) }}" class="btn btn-info btn-sm">View</a>
                                        <a href="{{ route('production.batches.edit', $hashid) }}" class="btn btn-warning btn-sm">Edit</a>
                                        <form action="{{ route('production.batches.destroy', $hashid) }}" method="POST" style="display:inline-block;" class="delete-batch-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-sm delete-batch-btn" data-batch-number="{{ $batch->batch_number }}">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bx bx-package fs-1 d-block mb-2"></i>
                                        <h6>No Production Batches Found</h6>
                                        <p class="mb-0">Get started by creating your first production batch</p>
                                        <a href="{{ route('production.batches.create') }}" class="btn btn-primary btn-sm mt-2">
                                            <i class="bx bx-plus me-1"></i> Add Batch
                                        </a>
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
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('#batches-table').DataTable({
        pageLength: 10,
        order: [[5, 'desc']],
        responsive: true,
        language: {
            emptyTable: `
                <div class=\"text-center text-muted py-4\">
                    <i class=\"bx bx-package fs-1 d-block mb-2\"></i>
                    <h6>No Production Batches Found</h6>
                    <p class=\"mb-0\">Get started by creating your first production batch</p>
                    <a href=\"{{ route('production.batches.create') }}\" class=\"btn btn-primary btn-sm mt-2\">
                        <i class=\"bx bx-plus me-1\"></i> Add Batch
                    </a>
                </div>
            `
        }
    });
    // SweetAlert for delete
    document.querySelectorAll('.delete-batch-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            var form = btn.closest('form');
            var batchNumber = btn.getAttribute('data-batch-number');
            Swal.fire({
                title: 'Delete Batch?',
                text: 'Are you sure you want to delete batch "' + batchNumber + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
    // Search functionality
    $('#search-input').on('keyup', function() {
        $('#batches-table').DataTable().search(this.value).draw();
    });
});
</script>
@endpush
@endsection
