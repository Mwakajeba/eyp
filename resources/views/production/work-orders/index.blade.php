@extends('layouts.main')

@section('title', 'Work Orders')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Production Management', 'url' => '#', 'icon' => 'bx bx-cog'],
            ['label' => 'Work Orders', 'url' => route('production.work-orders.index'), 'icon' => 'bx bx-list-ul']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-2">
                        <div class="col-sm-8">
                            <div class="text-sm-end">
                                <a href="{{ route('production.work-orders.create') }}" class="btn btn-danger mb-2">
                                <i class="mdi mdi-plus-circle me-2"></i> Add Work Order
                            </a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="work-orders-table" class="table table-centered w-100 dt-responsive nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>WO Number</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th>Style</th>
                                    <th>Quantity</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stage Advancement Modal -->
<div class="modal fade" id="advanceStageModal" tabindex="-1" aria-labelledby="advanceStageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="advanceStageModalLabel">Advance Work Order Stage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to advance this work order to the next stage?</p>
                <div id="stage-details"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmAdvanceStage">Advance Stage</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#work-orders-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('production.work-orders.index') }}",
            type: 'GET'
        },
        columns: [
            { data: 'wo_number', name: 'wo_number' },
            { data: 'customer_name', name: 'customer.name' },
            { data: 'product_name', name: 'product_name' },
            { data: 'style', name: 'style' },
            { data: 'total_quantity', name: 'total_quantity', searchable: false },
            { data: 'due_date', name: 'due_date' },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'progress_bar', name: 'progress_bar', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Handle stage advancement
    var currentWorkOrderId = null;

    $(document).on('click', '.advance-stage', function() {
        currentWorkOrderId = $(this).data('id');
        $('#advanceStageModal').modal('show');
    });

    $('#confirmAdvanceStage').click(function() {
        if (currentWorkOrderId) {
            $.ajax({
                url: "{{ route('production.work-orders.advance-stage', ':id') }}".replace(':id', currentWorkOrderId),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $('#advanceStageModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    var response = xhr.responseJSON;
                    toastr.error(response.message || 'An error occurred');
                }
            });
        }
    });

    // Reset modal when closed
    $('#advanceStageModal').on('hidden.bs.modal', function () {
        currentWorkOrderId = null;
    });
});
</script>
@endpush