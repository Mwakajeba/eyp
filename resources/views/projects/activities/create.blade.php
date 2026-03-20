@extends('layouts.main')

@section('title', 'Create Project Activity')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Project Management', 'url' => route('projects.index'), 'icon' => 'bx bx-briefcase'],
            ['label' => 'Project Activities', 'url' => route('projects.activities.index'), 'icon' => 'bx bx-task'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus-circle']
        ]" />

        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-uppercase">Create Project Activity</h6>
                            <a href="{{ route('projects.activities.index') }}" class="btn btn-light">Back</a>
                        </div>

                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <form method="POST" action="{{ route('projects.activities.store') }}" id="project-activity-create-form">
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                                    <select name="project_id" id="project_id" class="form-select select2-single" required>
                                        <option value="">Choose project</option>
                                        @foreach($projects as $project)
                                        <option value="{{ $project->id }}" {{ (string) old('project_id') === (string) $project->id ? 'selected' : '' }}>
                                            {{ $project->project_code }} - {{ $project->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="activity_code" class="form-label">Activity Code <span class="text-danger">*</span></label>
                                    <input type="text" name="activity_code" id="activity_code" class="form-control" value="{{ old('activity_code') }}" maxlength="50" required>
                                </div>

                                <div class="col-12">
                                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea name="description" id="description" class="form-control" rows="5" required>{{ old('description') }}</textarea>
                                </div>

                                <div class="col-md-6">
                                    <label for="budget_amount" class="form-label">Budget Amount</label>
                                    <input type="text" name="budget_amount" id="budget_amount" class="form-control budget-amount-input" value="{{ old('budget_amount') }}" inputmode="decimal" placeholder="0.00">
                                </div>

                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bx bx-save me-1"></i>Save Activity
                                    </button>
                                    <a href="{{ route('projects.activities.index') }}" class="btn btn-light">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 mb-4">
                <div class="card border-top border-0 border-4 border-info h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bx bx-info-circle font-22 text-info me-2"></i>
                            <h6 class="mb-0 text-uppercase text-info">Guidelines Information</h6>
                        </div>
                        <hr>

                        <div class="mb-3">
                            <h6 class="fw-bold">What to capture</h6>
                            <p class="text-muted mb-0">Create one activity for each project task, WBS line, or cost bucket you want to track separately.</p>
                        </div>

                        <div class="mb-3">
                            <h6 class="fw-bold">Activity code</h6>
                            <p class="text-muted mb-0">Use a short unique code per project, for example `WBS-001`, `CIVIL-01`, or `SITE-PREP`.</p>
                        </div>

                        <div class="mb-3">
                            <h6 class="fw-bold">Budget amount</h6>
                            <p class="text-muted mb-0">Enter the planned budget for the activity. Commas are added automatically while typing.</p>
                        </div>

                        <div class="alert alert-light border mb-0">
                            <h6 class="fw-bold mb-2">Next use</h6>
                            <p class="text-muted mb-0">These activities can later be linked to invoices, purchase orders, timesheets, and inventory issues for project cost capture.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function () {
        if (typeof $ !== 'undefined' && $.fn.select2) {
            const $project = $('#project_id');

            if ($project.length) {
                $project.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Search and select project...',
                    allowClear: true
                });
            }
        }
    });

    function formatBudgetAmount(value) {
        const digits = value.replace(/[^0-9.]/g, '');
        const parts = digits.split('.');
        const integerPart = parts[0] ? Number(parts[0]).toLocaleString('en-US') : '';

        if (parts.length === 1) {
            return integerPart;
        }

        return `${integerPart}.${parts.slice(1).join('').slice(0, 2)}`;
    }

    document.querySelectorAll('.budget-amount-input').forEach((input) => {
        input.addEventListener('input', function () {
            this.value = formatBudgetAmount(this.value);
        });
    });

    document.getElementById('project-activity-create-form')?.addEventListener('submit', function () {
        this.querySelectorAll('.budget-amount-input').forEach((input) => {
            input.value = input.value.replace(/,/g, '');
        });
    });
</script>
@endpush