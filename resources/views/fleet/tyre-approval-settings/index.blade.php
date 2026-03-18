@extends('layouts.main')

@section('title', 'Tyre & Spare Approval Settings - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Truck Tyre Configuration', 'url' => route('fleet.tyre-positions.index'), 'icon' => 'bx bx-grid-alt'],
            ['label' => 'Approval Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />

        <h6 class="mb-0 text-uppercase">Tyre & Spare Replacement Approval Settings</h6>
        <hr />

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

        <form method="POST" action="{{ route('fleet.tyre-approval-settings.update') }}" id="tyre-approval-settings-form">
            @csrf
            @method('PUT')

            <ul class="nav nav-tabs nav-bordered mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tyre-tab" data-bs-toggle="tab" data-bs-target="#tyre" type="button" role="tab">
                        <i class="bx bx-circle me-1"></i> Tyre Replacement Approval
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="spare-tab" data-bs-toggle="tab" data-bs-target="#spare" type="button" role="tab">
                        <i class="bx bx-package me-1"></i> Spare Part (Vipuri) Replacement Approval
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Tyre Replacement Approval Tab -->
                <div class="tab-pane fade show active" id="tyre" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bx bx-circle me-2"></i>Tyre Replacement Approval</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Minimum km before same position can request replacement, approval thresholds, and multi-level approvers.</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Minimum km before replacement request allowed</label>
                                    <input type="number" name="tyre_replacement_min_km_before_request" class="form-control" value="{{ $settings['tyre_replacement_min_km_before_request'] ?? '' }}" min="0" step="1" placeholder="e.g. 50000">
                                    <div class="form-text">Leave empty for no minimum. Same position cannot request replacement until the current tyre has run at least this many km.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Approval workflow (multi-level approvers)</label>
                                    <select name="tyre_replacement_approval_workflow_id" class="form-select select2-single">
                                        <option value="">— No workflow (use default approval) —</option>
                                        @foreach($workflows as $wf)
                                            <option value="{{ $wf->id }}" {{ ($settings['tyre_replacement_approval_workflow_id'] ?? '') == $wf->id ? 'selected' : '' }}>{{ $wf->name }} ({{ $wf->workflow_type }})</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Optional workflow for multi-level approvers. To set who can approve, use <a href="{{ route('fleet.settings.index') }}">Fleet Settings</a> → Trip Management (or create workflows of type Cost Approval / Maintenance) and assign approvers there; then select that workflow here.</div>
                                </div>
                                @if(isset($tyreWorkflow) && $tyreWorkflow && $tyreWorkflow->approvers->isNotEmpty())
                                <div class="col-12">
                                    <label class="form-label text-success">Who can approve (tyre replacement)</label>
                                    <ul class="list-group list-group-flush small">
                                        @foreach($tyreWorkflow->approvers as $approver)
                                            <li class="list-group-item d-flex justify-content-between py-1">
                                                <span>{{ $approver->user->name ?? 'User #' . $approver->user_id }}</span>
                                                <span class="text-muted">Order {{ $approver->approval_order }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="tyre_replacement_require_approval" value="1" id="tyre_require_approval" {{ ($settings['tyre_replacement_require_approval'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="tyre_require_approval">
                                            Require approval for tyre replacement requests
                                        </label>
                                        <div class="form-text">When enabled, tyre replacement requests stay pending until approved.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Spare Part Replacement Approval Tab -->
                <div class="tab-pane fade" id="spare" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bx bx-package me-2"></i>Spare Part (Vipuri) Replacement Approval</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Cost thresholds for auto-approve vs finance approval, minimum interval checks, and approver workflow.</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Auto-approve when cost below (amount)</label>
                                    <input type="number" name="spare_replacement_auto_approve_max_cost" class="form-control" value="{{ $settings['spare_replacement_auto_approve_max_cost'] ?? '' }}" min="0" step="0.01" placeholder="e.g. 500000">
                                    <div class="form-text">Replacements with cost at or below this amount can be auto-approved. Above this threshold, approval is required (or use workflow).</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Minimum interval between replacements (days)</label>
                                    <input type="number" name="spare_replacement_min_interval_days" class="form-control" value="{{ $settings['spare_replacement_min_interval_days'] ?? '' }}" min="0" step="1" placeholder="e.g. 30">
                                    <div class="form-text">Minimum days between replacements for the same vehicle + category. Leave empty for no check.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Approval workflow (finance / multi-level)</label>
                                    <select name="spare_replacement_approval_workflow_id" class="form-select select2-single">
                                        <option value="">— No workflow (use default approval) —</option>
                                        @foreach($workflows as $wf)
                                            <option value="{{ $wf->id }}" {{ ($settings['spare_replacement_approval_workflow_id'] ?? '') == $wf->id ? 'selected' : '' }}>{{ $wf->name }} ({{ $wf->workflow_type }})</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Optional workflow for cost approval or multi-level approvers. To set who can approve, use <a href="{{ route('fleet.settings.index') }}">Fleet Settings</a> and manage approval workflows (assign approvers to workflows), then select that workflow here.</div>
                                </div>
                                @if(isset($spareWorkflow) && $spareWorkflow && $spareWorkflow->approvers->isNotEmpty())
                                <div class="col-12">
                                    <label class="form-label text-success">Who can approve (spare replacement)</label>
                                    <ul class="list-group list-group-flush small">
                                        @foreach($spareWorkflow->approvers as $approver)
                                            <li class="list-group-item d-flex justify-content-between py-1">
                                                <span>{{ $approver->user->name ?? 'User #' . $approver->user_id }}</span>
                                                <span class="text-muted">Order {{ $approver->approval_order }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('fleet.tyre-positions.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Tyre Configuration
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Save Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-single').select2({ theme: 'bootstrap-5', width: '100%', allowClear: true, placeholder: 'Select...' });
    }
    $('#tyre-approval-settings-form').on('submit', function() {
        $(this).find('input[type="checkbox"]').each(function() {
            if (!$(this).is(':checked')) {
                $(this).after('<input type="hidden" name="' + $(this).attr('name') + '" value="0">');
            }
        });
    });
});
</script>
@endpush
