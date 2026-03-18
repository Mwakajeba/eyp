@extends('layouts.main')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Work Orders Debug</h5>
                        <div id="debug-info" class="alert alert-info">
                            Checking DataTables initialization...
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="work-orders-table" class="table table-striped">
                            <thead>
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
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    console.log('Starting Work Orders DataTables debug...');
    $('#debug-info').html('jQuery ready, initializing DataTables...');
    
    try {
        var table = $('#work-orders-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('production.work-orders.index') }}",
                type: 'GET',
                beforeSend: function() {
                    console.log('DataTables AJAX: Sending request...');
                    $('#debug-info').html('Sending AJAX request...');
                },
                success: function(data) {
                    console.log('DataTables AJAX Success:', data);
                    $('#debug-info').html('✅ Success! Found ' + data.recordsTotal + ' work orders');
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTables AJAX Error:', error, thrown);
                    console.error('Response:', xhr.responseText);
                    $('#debug-info').html('❌ Error: ' + error + '<br>Response: ' + xhr.responseText);
                }
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
            responsive: true,
            initComplete: function() {
                console.log('DataTables initialized successfully');
                $('#debug-info').append('<br>DataTables initialization complete!');
            }
        });
    } catch (e) {
        console.error('DataTables initialization error:', e);
        $('#debug-info').html('❌ DataTables Error: ' + e.message);
    }
});
</script>
@endpush