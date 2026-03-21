@extends('layouts.main')

@section('title', 'Project Management')

@section('content')
@can('view projects')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Project Management', 'url' => '#', 'icon' => 'bx bx-briefcase']
        ]" />
        <h6 class="mb-0 text-uppercase">PROJECT MANAGEMENT</h6>
        <hr />
        <!-- Project Receipts & Payments Reports -->
        @can('view project reports')
        <div class="row">
            <div class="col-12 col-lg-6 mb-4">
                <div class="card border-top border-0 border-4 border-success h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-wallet-alt me-1 font-22 text-success"></i></div>
                            <h5 class="mb-0 text-success">Project Receipts Report</h5>
                        </div>
                        <hr>
                        <p class="text-muted mb-3">Select project first, then filter receipt transactions by date range and export.</p>

                        <form id="project-receipts-report-form" method="POST" action="{{ route('projects.reports.receipts.export-pdf') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="receipts_project_id" class="form-label">Project <span class="text-danger">*</span></label>
                                    <select name="project_id" id="receipts_project_id" class="form-select" required>
                                        <option value="">Choose project</option>
                                        @foreach(($projects ?? collect()) as $project)
                                            <option value="{{ $project->id }}" {{ (string) old('project_id') === (string) $project->id ? 'selected' : '' }}>
                                                {{ $project->project_code }} - {{ $project->name }} ({{ $project->type }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="receipts_date_from" class="form-label">Date From <span class="text-danger">*</span></label>
                                    <input type="date" name="date_from" id="receipts_date_from" class="form-control project-dependent" value="{{ old('date_from', now()->startOfMonth()->format('Y-m-d')) }}" required disabled>
                                </div>

                                <div class="col-md-6">
                                    <label for="receipts_date_to" class="form-label">Date To <span class="text-danger">*</span></label>
                                    <input type="date" name="date_to" id="receipts_date_to" class="form-control project-dependent" value="{{ old('date_to', now()->format('Y-m-d')) }}" required disabled>
                                </div>

                                <div class="col-12">
                                    <small class="text-muted">Choose project first to enable date range and export buttons.</small>
                                </div>

                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-danger project-dependent" disabled>
                                        <i class="bx bx-file me-1"></i> Export PDF
                                    </button>
                                    <button type="submit" class="btn btn-success project-dependent" formaction="{{ route('projects.reports.receipts.export-excel') }}" formmethod="POST" disabled>
                                        <i class="bx bx-spreadsheet me-1"></i> Export Excel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6 mb-4">
                <div class="card border-top border-0 border-4 border-danger h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-credit-card-front me-1 font-22 text-danger"></i></div>
                            <h5 class="mb-0 text-danger">Project Payments Report</h5>
                        </div>
                        <hr>
                        <p class="text-muted mb-3">Select project first, then filter payment transactions by date range and export.</p>

                        <form id="project-payments-report-form" method="POST" action="{{ route('projects.reports.payments.export-pdf') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="payments_project_id" class="form-label">Project <span class="text-danger">*</span></label>
                                    <select name="project_id" id="payments_project_id" class="form-select" required>
                                        <option value="">Choose project</option>
                                        @foreach(($projects ?? collect()) as $project)
                                            <option value="{{ $project->id }}" {{ (string) old('project_id') === (string) $project->id ? 'selected' : '' }}>
                                                {{ $project->project_code }} - {{ $project->name }} ({{ $project->type }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="payments_date_from" class="form-label">Date From <span class="text-danger">*</span></label>
                                    <input type="date" name="date_from" id="payments_date_from" class="form-control project-dependent" value="{{ old('date_from', now()->startOfMonth()->format('Y-m-d')) }}" required disabled>
                                </div>

                                <div class="col-md-6">
                                    <label for="payments_date_to" class="form-label">Date To <span class="text-danger">*</span></label>
                                    <input type="date" name="date_to" id="payments_date_to" class="form-control project-dependent" value="{{ old('date_to', now()->format('Y-m-d')) }}" required disabled>
                                </div>

                                <div class="col-12">
                                    <small class="text-muted">Choose project first to enable date range and export buttons.</small>
                                </div>

                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-danger project-dependent" disabled>
                                        <i class="bx bx-file me-1"></i> Export PDF
                                    </button>
                                    <button type="submit" class="btn btn-success project-dependent" formaction="{{ route('projects.reports.payments.export-excel') }}" formmethod="POST" disabled>
                                        <i class="bx bx-spreadsheet me-1"></i> Export Excel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        <!-- Project Management Modules -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-grid me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Project Management Modules</h5>
                        </div>
                        <hr>
                        <div class="row">
                            <!-- 1. Project Setup & WBS -->
                            @can('create project')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            @php
                                                $totalProjects = ($projects ?? collect())->count();
                                            @endphp
                                            {{ $totalProjects }}
                                            <span class="visually-hidden">projects count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-folder-plus fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Project Setup & WBS</h5>
                                        <p class="card-text">Create projects (Internal/External/Donor), define WBS structure, budgets, and components.</p>
                                        <a href="{{ route('projects.project.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i> Manage Projects
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- 2. Project Activities -->
                            @can('view project activities')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            @php
                                                $activityCount = (int) ($activityCount ?? 0);
                                            @endphp
                                            {{ $activityCount }}
                                            <span class="visually-hidden">project activities count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-task fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Project Activities</h5>
                                        <p class="card-text">Define project activities and WBS budget lines to prepare project-linked cost capture and AUC/WIP processing.</p>
                                        <a href="{{ route('projects.activities.index') }}" class="btn btn-success">
                                            <i class="bx bx-plus-circle me-1"></i> Manage Activities
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- 3. Donor Details -->
                            @can('view donors')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                                            {{ $donorCount ?? 0 }}
                                            <span class="visually-hidden">donors count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-donate-heart fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Donor Details</h5>
                                        <p class="card-text">Create, manage, and view donor profiles. Donors are linked to projects for fund tracking and utilization reporting.</p>
                                        <a href="{{ route('projects.donors.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-group me-1"></i> Manage Donors
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- 4. Donor Fund Management -->
                            @can('manage donor assignments')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-donate-heart fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Donor Fund Management</h5>
                                        <p class="card-text">Assign donors to projects, track disbursements, restrictions, matching recognition, and generate donor reports.</p>
                                        <a href="{{ route('projects.donor-assignments.create') }}" class="btn btn-info">
                                            <i class="bx bx-money-withdraw me-1"></i> Manage Funds
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- 5. Reports & Analytics -->
                            @can('view project reports')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-bar-chart-alt-2 fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Reports & Analytics</h5>
                                        <p class="card-text">AUC/WIP registers, capitalization register, profitability, donor utilization, deferred tax schedules.</p>
                                        <a href="#" class="btn btn-danger">
                                            <i class="bx bx-file-blank me-1"></i> View Reports
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endcan
@endsection

@push('styles')
<style>
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.5em 0.75em;
    }

    .fs-1 {
        font-size: 3rem !important;
    }

    /* Notification badge positioning */
    .position-relative .badge {
        z-index: 10;
        font-size: 0.7rem;
        min-width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .border-primary {
        border-color: #0d6efd !important;
    }

    .border-success {
        border-color: #198754 !important;
    }

    .border-warning {
        border-color: #ffc107 !important;
    }

    .border-info {
        border-color: #0dcaf0 !important;
    }

    .border-danger {
        border-color: #dc3545 !important;
    }

    .border-secondary {
        border-color: #6c757d !important;
    }

    .border-dark {
        border-color: #212529 !important;
    }

    .border-purple {
        border-color: #6f42c1 !important;
    }

    .text-purple {
        color: #6f42c1 !important;
    }

    .bg-purple {
        background-color: #6f42c1 !important;
    }

    .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
        color: #fff;
    }

    .btn-purple:hover {
        background-color: #5a32a3;
        border-color: #5a32a3;
        color: #fff;
    }

</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Initialize DataTable for recent projects if needed
        if ($('#recent-projects-table').length) {
            $('#recent-projects-table').DataTable({
                responsive: true,
                order: [[6, 'desc']], // Sort by date descending
                pageLength: 5,
                searching: false,
                lengthChange: false,
                info: false,
                language: {
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }

        function toggleProjectReportForm(formSelector) {
            const $form = $(formSelector);
            const hasProject = !!$form.find('select[name="project_id"]').val();
            $form.find('.project-dependent').prop('disabled', !hasProject);
        }

        ['#project-receipts-report-form', '#project-payments-report-form'].forEach(function(selector) {
            toggleProjectReportForm(selector);
            $(selector).on('change', 'select[name="project_id"]', function() {
                toggleProjectReportForm(selector);
            });
        });

    });
</script>
@endpush
