@extends('layouts.main')

@section('title', 'Project Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Project Management', 'url' => '#', 'icon' => 'bx bx-briefcase']
        ]" />
        <h6 class="mb-0 text-uppercase">PROJECT MANAGEMENT</h6>
        <hr />
        <!-- Project Receipts & Payments Reports -->
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

                            <!-- 2. Project Activities -->
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

                            <!-- 3. AUC / WIP Ledger -->
                            <!-- <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            @php
                                                $aucCount = 0;
                                                // TODO: Replace with actual count when implemented
                                            @endphp
                                            {{ $aucCount }}
                                            <span class="visually-hidden">AUC accounts count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-layer fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">AUC / WIP Ledger</h5>
                                        <p class="card-text">View balances, drill into cost lines, aging analysis, and select for capitalization.</p>
                                        <a href="#" class="btn btn-info">
                                            <i class="bx bx-book-open me-1"></i> View Ledger
                                        </a>
                                    </div>
                                </div>
                            </div> -->

                            <!-- 4. Capitalization Requests -->
                            <!-- <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                            @php
                                                $capRequests = 0;
                                                // TODO: Replace with actual count when implemented
                                            @endphp
                                            {{ $capRequests }}
                                            <span class="visually-hidden">capitalization requests count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-transfer fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Capitalization Requests</h5>
                                        <p class="card-text">Select AUC/cost lines → intended use → attachments → preview journals → submit for approval.</p>
                                        <a href="#" class="btn btn-warning">
                                            <i class="bx bx-file me-1"></i> Create Request
                                        </a>
                                    </div>
                                </div>
                            </div> -->

                            <!-- 5. Approval Inbox -->
                            <!-- <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            @php
                                                $pendingApprovals = 0;
                                                // TODO: Replace with actual count when implemented
                                            @endphp
                                            {{ $pendingApprovals }}
                                            <span class="visually-hidden">pending approvals count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-check-circle fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Approval Inbox</h5>
                                        <p class="card-text">Review and approve/reject capitalization requests, budget changes, and project changes.</p>
                                        <a href="#" class="btn btn-danger">
                                            <i class="bx bx-inbox me-1"></i> View Approvals
                                        </a>
                                    </div>
                                </div>
                            </div> -->

                            <!-- 6. Billing & Revenue -->
                            <!-- <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-purple">
                                            @php
                                                $projectInvoices = 0;
                                                // TODO: Replace with actual count when implemented
                                            @endphp
                                            {{ $projectInvoices }}
                                            <span class="visually-hidden">project invoices count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-money fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Billing & Revenue</h5>
                                        <p class="card-text">External project invoicing, milestone billing, revenue recognition, WIP to COGS.</p>
                                        <a href="#" class="btn btn-purple">
                                            <i class="bx bx-receipt me-1"></i> Manage Billing
                                        </a>
                                    </div>
                                </div>
                            </div> -->

                            <!-- 7. Donor Fund Management -->
                            <!-- <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                                            @php
                                                $donorFunds = 0;
                                                // TODO: Replace with actual count when implemented
                                                // $donorFunds = \App\Models\Project\DonorFund::forCompany($companyId)
                                                //     ->when($branchId, fn($q) => $q->forBranch($branchId))
                                                //     ->count();
                                            @endphp
                                            {{ $donorFunds }}
                                            <span class="visually-hidden">donor funds count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-donate-heart fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Donor Fund Management</h5>
                                        <p class="card-text">Track disbursements, restrictions, matching recognition, and generate donor reports.</p>
                                        <a href="{{ route('projects.donor-assignments.create') }}" class="btn btn-secondary">
                                            <i class="bx bx-money-withdraw me-1"></i> Manage Funds
                                        </a>
                                    </div>
                                </div>
                            </div> -->

                            <!-- 8. Timesheets & Resources -->
                            <!-- <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            @php
                                                $timesheets = 0;
                                                // TODO: Replace with actual count when implemented
                                            @endphp
                                            {{ $timesheets }}
                                            <span class="visually-hidden">timesheets count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-time-five fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Timesheets & Resources</h5>
                                        <p class="card-text">Submit, approve, and post labor costs to projects. Resource allocation and utilization tracking.</p>
                                        <a href="#" class="btn btn-info">
                                            <i class="bx bx-calendar-check me-1"></i> Manage Timesheets
                                        </a>
                                    </div>
                                </div>
                            </div> -->

                            <!-- 9. Procurement Integration -->
                            <!-- <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            @php
                                                $projectPOs = 0;
                                                // TODO: Replace with actual count when implemented
                                            @endphp
                                            {{ $projectPOs }}
                                            <span class="visually-hidden">project POs count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-purchase-tag fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Procurement Integration</h5>
                                        <p class="card-text">Link purchase orders to projects/WBS. Track commitments and auto-create cost lines.</p>
                                        <a href="#" class="btn btn-success">
                                            <i class="bx bx-link me-1"></i> Link POs
                                        </a>
                                    </div>
                                </div> -->
                            <!-- </div>--> 

                            <!-- 10. Fixed Asset Creation -->
                            <!-- <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            @php
                                                $capitalizedAssets = 0;
                                                // TODO: Replace with actual count when implemented
                                            @endphp
                                            {{ $capitalizedAssets }}
                                            <span class="visually-hidden">capitalized assets count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-cabinet fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Fixed Asset Creation</h5>
                                        <p class="card-text">Post-capitalization review, asset tagging, depreciation setup, and tax book updates.</p>
                                        <a href="#" class="btn btn-primary">
                                            <i class="bx bx-check-square me-1"></i> Review Assets
                                        </a>
                                    </div>
                                </div>
                            </div> -->

                            <!-- 11. Reports & Analytics -->
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

                            <!-- 12. Settings & Configuration -->
                            <!-- <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-cog fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Settings & Configuration</h5>
                                        <p class="card-text">GL mappings, approval matrices, thresholds, tax settings, document templates, and workflows.</p>
                                        <a href="#" class="btn btn-dark">
                                            <i class="bx bx-cog me-1"></i> Configure
                                        </a>
                                    </div>
                                </div>
                            </div> -->

                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
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
