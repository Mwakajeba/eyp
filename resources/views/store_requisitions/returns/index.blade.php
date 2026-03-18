@extends('layouts.main')

@section('title', 'Store Returns')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Returns', 'url' => '#', 'icon' => 'bx bx-undo']
        ]" />

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Store Returns Management</h5>
                <small class="text-muted">Track and manage store item returns</small>
            </div>
        </div>

        <!-- Returns Table -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bx bx-list-ul me-2"></i>Store Returns
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="returnsTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>Requisition #</th>
                                <th>Return Date</th>
                                <th>Return Reason</th>
                                <th>Processed By</th>
                                <th>Branch</th>
                                <th>Total Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#returnsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('store-returns.index') }}",
        },
        columns: [
            {data: 'voucher_no', name: 'storeRequisition.requisition_number'},
            {data: 'formatted_date', name: 'return_date'},
            {data: 'return_reason', name: 'return_reason'},
            {data: 'processed_by_name', name: 'processedBy.name'},
            {data: 'branch_name', name: 'branch.name'},
            {data: 'formatted_amount', name: 'total_return_amount'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true
    });
});

function viewReturn(id) {
    window.location.href = `{{ url('store-returns') }}/${id}`;
}

function editReturn(id) {
    window.location.href = `{{ url('store-returns') }}/${id}/edit`;
}
</script>
@endpush