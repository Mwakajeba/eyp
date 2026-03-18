@extends('layouts.main')

@section('title', 'Timesheet Approval Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Timesheet Approval Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Timesheet Approval Settings</h5>
                <small class="text-muted">Configure who can approve employee timesheets</small>
            </div>
            <div>
                <a href="{{ route('hr-payroll.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Payroll
                </a>
            </div>
        </div>

        <div class="alert alert-info border-0">
            <div class="d-flex align-items-center">
                <i class="bx bx-info-circle fs-4 me-3"></i>
                <div>
                    <h6 class="mb-1">Timesheet Approval Workflow</h6>
                    <p class="mb-0">
                        When enabled, only configured approvers will be able to approve or reject employee timesheets.
                        This helps ensure that department heads or HR officers validate time allocation against projects
                        and departmental priorities before they are finalized.
                    </p>
                </div>
            </div>
        </div>

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

        <div class="card">
            <div class="card-body">
                <form action="{{ route('hr-payroll.timesheet-approval-settings.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <!-- Basic Settings Column -->
                        <div class="col-lg-4">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 text-primary">
                                        <i class="bx bx-cog me-2"></i>Basic Configuration
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="approval_required" 
                                                   name="approval_required" value="1"
                                                   {{ old('approval_required', $settings?->approval_required) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="approval_required">
                                                <strong>Enable Timesheet Approval</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted">Require approval before timesheets are marked as approved</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">
                                            <i class="bx bx-note me-1"></i>Notes
                                        </label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                  placeholder="Additional notes about the timesheet approval workflow">{{ old('notes', $settings?->notes) }}</textarea>
                                    </div>

                                    @if($settings)
                                        <div class="mt-4 p-3 bg-light rounded">
                                            <h6 class="text-info mb-2">Current Status</h6>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Status:</span>
                                                <span class="badge {{ $settings->approval_required ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $settings->approval_required ? 'Enabled' : 'Disabled' }}
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Last Updated:</span>
                                                <span class="text-muted small">{{ $settings->updated_at->format('M d, Y') }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Approvers Configuration Column -->
                        <div class="col-lg-8">
                            <div class="card border">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-primary">
                                        <i class="bx bx-user-check me-2"></i>Approver Configuration
                                    </h6>
                                    <small class="text-muted d-none d-md-inline">
                                        Select users who are allowed to approve or reject timesheets
                                    </small>
                                </div>
                                <div class="card-body" id="approval_config" style="{{ old('approval_required', $settings?->approval_required) ? '' : 'display: none;' }}">
                                    <div class="mb-3">
                                        <label for="approvers" class="form-label">
                                            <i class="bx bx-user-circle me-1"></i>Timesheet Approvers
                                        </label>
                                        @php
                                            $selectedApprovers = old('approvers', $settings?->approvers ?? []);
                                        @endphp
                                        <select name="approvers[]" id="approvers" 
                                                class="form-select select2-single @error('approvers') is-invalid @enderror" 
                                                multiple>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" 
                                                    {{ in_array($user->id, $selectedApprovers ?? []) ? 'selected' : '' }}>
                                                    {{ $user->name }} @if($user->branch?->name) ({{ $user->branch->name }}) @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('approvers')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">
                                            Hold <kbd>Ctrl</kbd> (Windows) or <kbd>Cmd</kbd> (Mac) to select multiple approvers.
                                            Approvers can view and act on all timesheets in this company/branch.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('hr-payroll.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Toggle approver configuration visibility
        $('#approval_required').on('change', function() {
            if ($(this).is(':checked')) {
                $('#approval_config').slideDown();
            } else {
                $('#approval_config').slideUp();
            }
        });

        // Initialize Select2 for approvers
        $('#approvers').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Select Approvers --',
            allowClear: true
        });
    });
</script>
@endpush


