@extends('layouts.main')

@section('title', 'Cost Categories - Fleet Management')

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
    .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0a58ca) !important; }
    .bg-gradient-success { background: linear-gradient(45deg, #198754, #146c43) !important; }
    .bg-gradient-info { background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important; }
    .bg-gradient-warning { background: linear-gradient(45deg, #ffc107, #ffb300) !important; }
    .card { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
    .card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
    .radius-10 { border-radius: 10px; }
    .border-start { border-left-width: 3px !important; }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Cost Categories', 'url' => '#', 'icon' => 'bx bx-category']
        ]" />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Categories</p>
                                <h4 class="my-1 text-primary">{{ number_format($totalCategories) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-category align-middle"></i> All types</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-category"></i>
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
                                <h4 class="my-1 text-success">{{ number_format($activeCategories) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> In use</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Fuel Categories</p>
                                <h4 class="my-1 text-info">{{ number_format($fuelCategories) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-gas-pump align-middle"></i> Fuel types</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-gas-pump"></i>
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
                                <p class="mb-0 text-secondary">Maintenance</p>
                                <h4 class="my-1 text-warning">{{ number_format($maintenanceCategories) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-wrench align-middle"></i> Service types</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-wrench"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light border-0">
                <h6 class="mb-0"><i class="bx bx-filter me-2"></i>Filters</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="filter-type" class="form-label">Category Type</label>
                        <select id="filter-type" class="form-select form-select-sm">
                            <option value="">All Types</option>
                            @foreach(\App\Models\Fleet\FleetCostCategory::getCategoryTypeOptions() as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter-active" class="form-label">Status</label>
                        <select id="filter-active" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" id="clear-filters" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="bx bx-x me-1"></i>Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-info text-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-category me-2"></i>Cost Categories</h5>
                    <div class="text-white-50">Create and manage cost categories used when recording trip costs (fuel, maintenance, toll, etc.)</div>
                </div>
                <div>
                    <a href="{{ route('fleet.cost-categories.create') }}" class="btn btn-light">
                        <i class="bx bx-plus me-1"></i>Create Category
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="categories-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>SN</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Unit</th>
                                <th>Description</th>
                                <th>Status</th>
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
    var table = $('#categories-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("fleet.cost-categories.data") }}',
            data: function(d) {
                d.category_type = $('#filter-type').val();
                d.is_active = $('#filter-active').val();
            }
        },
        columns: [
            {
                data: null,
                name: 'sn',
                orderable: false,
                searchable: false,
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'name', name: 'name' },
            { data: 'type_display', name: 'category_type', orderable: false },
            { data: 'unit_of_measure', name: 'unit_of_measure', orderable: false },
            { data: 'description', name: 'description', orderable: false },
            { data: 'active_display', name: 'is_active', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        language: {
            processing: '<div class="text-center"><i class="bx bx-loader-alt bx-spin bx-lg"></i> Loading categories...</div>'
        }
    });

    $('#filter-type, #filter-active').on('change', function() {
        table.ajax.reload();
    });

    $('#clear-filters').on('click', function() {
        $('#filter-type, #filter-active').val('');
        table.ajax.reload();
    });

    $(document).on('click', '.delete-category-btn', function(e) {
        e.preventDefault();
        var id = $(this).data('category-id');
        var name = $(this).data('category-name');
        if (!id) return false;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete Category?',
                text: 'Are you sure you want to delete "' + name + '"? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then(function(result) {
                if (result.isConfirmed) {
                    var form = $('<form>', { method: 'POST', action: '{{ url("/fleet/cost-categories") }}/' + id });
                    form.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));
                    form.append($('<input>', { type: 'hidden', name: '_method', value: 'DELETE' }));
                    $('body').append(form);
                    form.submit();
                }
            });
        } else {
            if (confirm('Delete "' + name + '"?')) {
                var form = $('<form>', { method: 'POST', action: '{{ url("/fleet/cost-categories") }}/' + id });
                form.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));
                form.append($('<input>', { type: 'hidden', name: '_method', value: 'DELETE' }));
                $('body').append(form);
                form.submit();
            }
        }
        return false;
    });
});
</script>
@endpush
