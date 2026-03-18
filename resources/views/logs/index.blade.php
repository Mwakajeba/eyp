@extends('layouts.main')

@section('title', 'Activity Logs Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumbs -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Activity Logs', 'url' => '#', 'icon' => 'bx bx-history']
        ]" />
        <h6 class="mb-0 text-uppercase">ACTIVITY LOGS MANAGEMENT</h6>
        <hr />

        <!-- Page Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-history me-1 font-22 text-primary"></i></div>
                                    <h5 class="mb-0 text-primary">Activity Logs & Audit Trail</h5>
                                </div>
                                <p class="mb-0 text-muted">Comprehensive audit trail of all system activities including FX revaluations, rate changes, and transactions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row row-cols-1 row-cols-lg-4 mb-4">
            <div class="col mb-4">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Total Activities</p>
                                <h4 class="mb-0" id="totalLogs">-</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-burning text-white rounded-circle p-3">
                                <i class='bx bx-history fs-4'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col mb-4">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">FX Revaluations</p>
                                <h4 class="mb-0" id="fxRevaluations">-</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-info text-white rounded-circle p-3">
                                <i class='bx bx-refresh fs-4'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col mb-4">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                                <p class="text-muted mb-1">FX Rate Changes</p>
                                <h4 class="mb-0" id="fxRates">-</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-primary text-white rounded-circle p-3">
                                <i class='bx bx-dollar fs-4'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col mb-4">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Today's Activities</p>
                                <h4 class="mb-0" id="todayLogs">-</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-success text-white rounded-circle p-3">
                                <i class='bx bx-calendar-check fs-4'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card radius-10 border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0">
                <h6 class="mb-0"><i class="bx bx-filter me-2"></i>Filters</h6>
            </div>
            <div class="card-body">
                <form id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">User</label>
                            <select name="user_id" id="filter_user_id" class="form-select select2-single">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Model/Type</label>
                            <select name="model" id="filter_model" class="form-select select2-single">
                                <option value="">All Types</option>
                                @foreach($models as $model)
                                    <option value="{{ $model }}">{{ $model }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Action</label>
                            <select name="action" id="filter_action" class="form-select select2-single">
                                <option value="">All Actions</option>
                                <option value="create">Create</option>
                                <option value="update">Update</option>
                                <option value="delete">Delete</option>
                                <option value="post">Post</option>
                                <option value="reverse">Reverse</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" id="filter_date_from" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" id="filter_date_to" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <button type="button" id="applyFilters" class="btn btn-primary">
                                <i class="bx bx-search me-1"></i> Apply Filters
                            </button>
                            <button type="button" id="resetFilters" class="btn btn-outline-secondary">
                                <i class="bx bx-refresh me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Activity Logs Table -->
        <div class="card radius-10 border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Activity Logs</h6>
                </div>
            </div>
                    <div class="card-body">
                        <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="logsTable">
                                <thead class="table-light">
                                    <tr>
                                <th>Date & Time</th>
                                        <th>User</th>
                                <th>Model/Type</th>
                                        <th>Action</th>
                                <th>Description</th>
                                        <th>IP Address</th>
                                        <th>Device</th>
                                <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                            <!-- DataTables will populate this via AJAX -->
                                </tbody>
                            </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .widgets-icons {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bg-gradient-burning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .bg-gradient-info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .bg-gradient-success {
        background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Initialize Select2
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select an option',
            allowClear: true
        });

        // Initialize DataTable
        var table = $('#logsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("settings.logs.data") }}',
                data: function(d) {
                    d.user_id = $('#filter_user_id').val();
                    d.model = $('#filter_model').val();
                    d.action = $('#filter_action').val();
                    d.date_from = $('#filter_date_from').val();
                    d.date_to = $('#filter_date_to').val();
                }
            },
            columns: [
                { data: 'formatted_date', name: 'activity_time' },
                { data: 'user_name', name: 'user.name', orderable: false },
                { data: 'model_badge', name: 'model', orderable: true, searchable: true },
                { data: 'action_badge', name: 'action', orderable: true, searchable: true },
                { data: 'detailed_description', name: 'description', orderable: false },
                { data: 'ip_address', name: 'ip_address', orderable: false },
                { data: 'device', name: 'device', orderable: false },
                { data: 'related_link', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[0, 'desc']], // Sort by date descending
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                processing: '<i class="bx bx-loader bx-spin"></i> Loading...',
                emptyTable: '<div class="text-center py-4"><i class="bx bx-history font-48 text-muted mb-3"></i><h6 class="text-muted">No Activity Logs Found</h6></div>',
                zeroRecords: '<div class="text-center py-4"><i class="bx bx-history font-48 text-muted mb-3"></i><h6 class="text-muted">No matching records found</h6></div>'
            },
            drawCallback: function(settings) {
                // Update summary cards
                updateSummaryCards();
                
                // Re-initialize Select2 after table redraw
                $('.select2-single').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Select an option',
                    allowClear: true
                });
            }
        });

        // Apply filters
        $('#applyFilters').on('click', function() {
            table.ajax.reload();
        });

        // Reset filters
        $('#resetFilters').on('click', function() {
            $('#filterForm')[0].reset();
            $('.select2-single').val(null).trigger('change');
            table.ajax.reload();
        });

        // Update summary cards
        function updateSummaryCards() {
            // Make AJAX call to get summary stats
            $.ajax({
                url: '{{ route("settings.logs.data") }}',
                data: {
                    user_id: $('#filter_user_id').val(),
                    model: $('#filter_model').val(),
                    action: $('#filter_action').val(),
                    date_from: $('#filter_date_from').val(),
                    date_to: $('#filter_date_to').val(),
                    summary_only: true
                },
                success: function(response) {
                    if (response.summary) {
                        $('#totalLogs').text(response.summary.total || 0);
                        $('#fxRevaluations').text(response.summary.fx_revaluations || 0);
                        $('#fxRates').text(response.summary.fx_rates || 0);
                        $('#todayLogs').text(response.summary.today || 0);
                    }
                },
                error: function() {
                    // Set defaults if summary fails
                    $('#totalLogs').text('-');
                    $('#fxRevaluations').text('-');
                    $('#fxRates').text('-');
                    $('#todayLogs').text('-');
                }
            });
        }

        // Initial summary load
        updateSummaryCards();
        
        // Update summary when filters change
        $('#applyFilters').on('click', function() {
            updateSummaryCards();
        });
    });
</script>
@endpush
