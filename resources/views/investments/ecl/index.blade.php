@extends('layouts.main')

@section('title', 'ECL Calculations')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Investment Management', 'url' => route('investments.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'ECL Calculations', 'url' => '#', 'icon' => 'bx bx-calculator']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">ECL CALCULATIONS</h6>
            <a href="{{ route('investments.ecl.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> Run ECL Calculation
            </a>
        </div>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Total Calculations</h6>
                                <h4 class="mb-0">{{ number_format($totalCalculations ?? 0) }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-calculator fs-1 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Pending Postings</h6>
                                <h4 class="mb-0">{{ number_format($pendingPostings ?? 0) }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-time fs-1 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Total ECL Amount</h6>
                                <h4 class="mb-0">TZS {{ number_format($totalEclAmount ?? 0, 2) }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-money fs-1 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="filter_status" class="form-select select2-single">
                            <option value="">All Status</option>
                            <option value="CALCULATED">Calculated</option>
                            <option value="REVIEWED">Reviewed</option>
                            <option value="APPROVED">Approved</option>
                            <option value="POSTED">Posted</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Stage</label>
                        <select id="filter_stage" class="form-select select2-single">
                            <option value="">All Stages</option>
                            <option value="1">Stage 1</option>
                            <option value="2">Stage 2</option>
                            <option value="3">Stage 3</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Calculation Date</label>
                        <input type="date" id="filter_calculation_date" class="form-control">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" id="btn-filter" class="btn btn-primary me-2">
                            <i class="bx bx-search"></i> Filter
                        </button>
                        <button type="button" id="btn-reset" class="btn btn-secondary">
                            <i class="bx bx-refresh"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ECL Calculations Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="eclCalculationsTable" class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Calculation Date</th>
                                <th>Investment</th>
                                <th>Stage</th>
                                <th>ECL Amount</th>
                                <th>Status</th>
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const table = $('#eclCalculationsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("investments.ecl.data") }}',
            data: function(d) {
                d.status = $('#filter_status').val();
                d.stage = $('#filter_stage').val();
                d.calculation_date = $('#filter_calculation_date').val();
            },
            error: function(xhr, status, error) {
                console.error('DataTables error:', error);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 'calculation_date', name: 'calculation_date' },
            { data: 'investment', name: 'investment', orderable: false },
            { data: 'stage', name: 'stage', orderable: false },
            { data: 'ecl_amount', name: 'ecl_amount' },
            { data: 'status', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            processing: '<i class="bx bx-loader-alt bx-spin"></i> Loading...'
        }
    });

    // Filter button
    $('#btn-filter').on('click', function() {
        table.draw();
    });

    // Reset button
    $('#btn-reset').on('click', function() {
        $('#filter_status').val('').trigger('change');
        $('#filter_stage').val('').trigger('change');
        $('#filter_calculation_date').val('');
        table.draw();
    });
});
</script>
@endpush
@endsection

