@extends('layouts.main')

@section('title', 'Payroll Calendars')

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .bg-gradient-danger {
        background: linear-gradient(45deg, #dc3545, #b02a37) !important;
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
</style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Payroll Calendars', 'url' => '#', 'icon' => 'bx bx-calendar']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-calendar me-2"></i>Payroll Calendars</h5>
                    <p class="mb-0 text-muted">Manage payroll cycles, cut-off dates, and pay dates</p>
                </div>
                <a href="{{ route('hr.payroll-calendars.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Create Calendar
                </a>
            </div>
            <hr />

            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card radius-10 border-start border-0 border-3 border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Total Calendars</p>
                                    <h4 class="my-1 text-primary">{{ number_format($stats['total']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-primary"><i class="bx bx-calendar align-middle"></i> All periods</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                    <i class="bx bx-calendar"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card radius-10 border-start border-0 border-3 border-success">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">This Year</p>
                                    <h4 class="my-1 text-success">{{ number_format($stats['this_year']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-success"><i class="bx bx-calendar-check align-middle"></i> {{ date('Y') }} periods</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                    <i class="bx bx-calendar-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card radius-10 border-start border-0 border-3 border-danger">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Locked</p>
                                    <h4 class="my-1 text-danger">{{ number_format($stats['locked']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-danger"><i class="bx bx-lock align-middle"></i> Cannot be edited</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-danger text-white ms-auto">
                                    <i class="bx bx-lock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card radius-10 border-start border-0 border-3 border-warning">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Upcoming</p>
                                    <h4 class="my-1 text-warning">{{ number_format($stats['upcoming']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-warning"><i class="bx bx-time align-middle"></i> Future periods</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                    <i class="bx bx-time"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="payroll-calendars-table" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Period</th>
                                    <th>Cut-off Date</th>
                                    <th>Pay Date</th>
                                    <th>Status</th>
                                    <th>Locked By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
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
    let table = $('#payroll-calendars-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.payroll-calendars.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'period_label', name: 'period_label'},
            {data: 'cut_off_date', name: 'cut_off_date'},
            {data: 'pay_date', name: 'pay_date'},
            {data: 'status_badge', name: 'status', orderable: false, searchable: false},
            {data: 'locked_by_name', name: 'locked_by_name'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[1, 'desc'], [2, 'desc']],
        pageLength: 25,
        responsive: true
    });

    window.lockCalendar = function(id) {
        Swal.fire({
            title: 'Lock Payroll Calendar?',
            text: "Once locked, the calendar cannot be edited. Continue?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, lock it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('hr.payroll-calendars.index') }}/" + id + "/lock",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Locked!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        Swal.fire('Error!', response.message || 'An error occurred.', 'error');
                    }
                });
            }
        });
    };

    window.unlockCalendar = function(id) {
        Swal.fire({
            title: 'Unlock Payroll Calendar?',
            text: "This will allow editing of the calendar. Continue?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, unlock it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('hr.payroll-calendars.index') }}/" + id + "/unlock",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Unlocked!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        Swal.fire('Error!', response.message || 'An error occurred.', 'error');
                    }
                });
            }
        });
    };
});
</script>
@endpush

