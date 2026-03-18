@extends('layouts.main')

@section('title', 'Disposal Reason Codes')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Disposals', 'url' => route('assets.disposals.index'), 'icon' => 'bx bx-trash'],
            ['label' => 'Reason Codes', 'url' => route('assets.disposals.reason-codes.index'), 'icon' => 'bx bx-list-ul']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">Disposal Reason Codes</h5>
                <div class="text-muted">Manage standardized disposal reason codes</div>
            </div>
            <a href="{{ route('assets.disposals.reason-codes.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> New Reason Code
            </a>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="reasonCodesTable" class="table table-striped table-hover align-middle" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Disposal Type</th>
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
$(function(){
    $('#reasonCodesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('assets.disposals.reason-codes.data') }}'
        },
        columns: [
            { data: 'code', name: 'code', render: function(d){ return `<span class="badge bg-light text-dark">${d}</span>`; } },
            { data: 'name', name: 'name' },
            { data: 'description', name: 'description', render: function(d){ return d || '-'; } },
            { data: 'disposal_type_display', name: 'disposal_type' },
            { data: 'status_badge', name: 'is_active', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1,'asc']],
        lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'All']],
        pageLength: 25,
        dom: 'lfrtip'
    });
});
</script>
@endpush

