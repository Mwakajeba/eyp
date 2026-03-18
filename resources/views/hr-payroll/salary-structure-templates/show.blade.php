@extends('layouts.main')

@section('title', 'Salary Structure Template Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Salary Structure Templates', 'url' => route('hr.salary-structure-templates.index'), 'icon' => 'bx bx-template'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0"><i class="bx bx-template me-2"></i>Template Details</h5>
                <p class="mb-0 text-muted">{{ $template->template_name }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('hr.salary-structure-templates.edit', $template->id) }}" class="btn btn-primary">
                    <i class="bx bx-edit me-1"></i>Edit
                </a>
                <a href="{{ route('hr.salary-structure-templates.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to List
                </a>
            </div>
        </div>
        <hr />

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="bx bx-info-circle me-1"></i>Template Information</h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Template Name:</strong><br>
                                <span class="h6">{{ $template->template_name }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Template Code:</strong><br>
                                <code class="h6">{{ $template->template_code }}</code>
                            </div>
                        </div>

                        @if($template->description)
                        <div class="mb-3">
                            <strong>Description:</strong><br>
                            <p class="text-muted">{{ $template->description }}</p>
                        </div>
                        @endif

                        <div class="mb-3">
                            <strong>Status:</strong><br>
                            @if($template->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </div>

                        <hr>

                        <h6 class="mb-3"><i class="bx bx-calculator me-1"></i>Template Components</h6>

                        @if($earnings->count() > 0)
                        <div class="mb-4">
                            <h6 class="text-success mb-3">Earnings</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Component</th>
                                            <th>Code</th>
                                            <th>Amount</th>
                                            <th>Percentage</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($earnings as $tc)
                                        <tr>
                                            <td>{{ $tc->component->component_name }}</td>
                                            <td><code>{{ $tc->component->component_code }}</code></td>
                                            <td>{{ $tc->amount ? number_format($tc->amount, 2) : '-' }}</td>
                                            <td>{{ $tc->percentage ? $tc->percentage . '%' : '-' }}</td>
                                            <td>{{ $tc->notes ?? '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                        @if($deductions->count() > 0)
                        <div class="mb-4">
                            <h6 class="text-danger mb-3">Deductions</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Component</th>
                                            <th>Code</th>
                                            <th>Amount</th>
                                            <th>Percentage</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($deductions as $tc)
                                        <tr>
                                            <td>{{ $tc->component->component_name }}</td>
                                            <td><code>{{ $tc->component->component_code }}</code></td>
                                            <td>{{ $tc->amount ? number_format($tc->amount, 2) : '-' }}</td>
                                            <td>{{ $tc->percentage ? $tc->percentage . '%' : '-' }}</td>
                                            <td>{{ $tc->notes ?? '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                        @if($earnings->count() == 0 && $deductions->count() == 0)
                        <div class="alert alert-warning">
                            <i class="bx bx-info-circle me-1"></i>No components assigned to this template.
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="mb-3">
                            <i class="bx bx-info-circle text-primary me-1"></i>Template Actions
                        </h6>
                        <hr>
                        
                        <div class="d-grid gap-2">
                            <a href="{{ route('hr.employee-salary-structure.apply-template-form') }}?template_id={{ $template->id }}" 
                                class="btn btn-success">
                                <i class="bx bx-check me-1"></i>Apply to Employees
                            </a>
                            <a href="{{ route('hr.salary-structure-templates.edit', $template->id) }}" 
                                class="btn btn-primary">
                                <i class="bx bx-edit me-1"></i>Edit Template
                            </a>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <strong>Created:</strong><br>
                            <small class="text-muted">{{ $template->created_at->format('M d, Y h:i A') }}</small>
                        </div>

                        <div class="mb-3">
                            <strong>Last Updated:</strong><br>
                            <small class="text-muted">{{ $template->updated_at->format('M d, Y h:i A') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

