@extends('layouts.main')

@section('title', 'Statutory Rules')

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
    
    .bg-gradient-danger {
        background: linear-gradient(45deg, #dc3545, #b02a37) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ff9800) !important;
    }
    
    .bg-gradient-secondary {
        background: linear-gradient(45deg, #6c757d, #5a6268) !important;
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
                ['label' => 'Statutory Rules', 'url' => '#', 'icon' => 'bx bx-shield']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-shield me-2"></i>Statutory Rules</h5>
                    <p class="mb-0 text-muted">Manage statutory compliance rules for Tanzania (PAYE, NHIF, Pension, WCF, SDL, HESLB)</p>
                </div>
                <a href="{{ route('hr.statutory-rules.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Create Rule
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
                                    <p class="mb-0 text-secondary">Total Rules</p>
                                    <h4 class="my-1 text-primary">{{ number_format($stats['total']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-primary"><i class="bx bx-shield align-middle"></i> All rules</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                    <i class="bx bx-shield"></i>
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
                                    <p class="mb-0 text-secondary">Active</p>
                                    <h4 class="my-1 text-success">{{ number_format($stats['active']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Currently active</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                    <i class="bx bx-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @foreach($ruleTypes as $type => $config)
                <div class="col-xl-3 col-md-6">
                    <div class="card radius-10 border-start border-0 border-3 border-{{ $config['color'] }}">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">{{ $config['label'] }}</p>
                                    <h4 class="my-1 text-{{ $config['color'] }}">{{ number_format($stats[$type] ?? 0) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-{{ $config['color'] }}"><i class="bx {{ $config['icon'] }} align-middle"></i> {{ $config['description'] }}</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-{{ $config['color'] }} text-white ms-auto">
                                    <i class="bx {{ $config['icon'] }}"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="statutory-rules-table" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Rule Name</th>
                                    <th>Effective Period</th>
                                    <th>Status</th>
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
    let table = $('#statutory-rules-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.statutory-rules.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'type_badge', name: 'rule_type', orderable: false},
            {data: 'rule_name', name: 'rule_name'},
            {data: 'effective_period', name: 'effective_period', orderable: false},
            {data: 'status_badge', name: 'status', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[2, 'asc']],
        pageLength: 25,
        responsive: true
    });
});
</script>
@endpush

