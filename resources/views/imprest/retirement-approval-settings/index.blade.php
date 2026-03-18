@extends('layouts.main')

@section('title', 'Retirement Approval Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Retirement Approval Settings', 'url' => route('imprest.retirement-approval-settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Multi-Level Settings', 'url' => '#', 'icon' => 'bx bx-git-branch']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Retirement Multi-Level Approval Settings</h5>
                <small class="text-muted">Configure flexible approval workflows for retirement requests</small>
            </div>
            <div>
                <a href="{{ route('imprest.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Imprest
                </a>
            </div>
        </div>

        <div class="alert alert-info border-0">
            <div class="d-flex align-items-center">
                <i class="bx bx-info-circle fs-4 me-3"></i>
                <div>
                    <h6 class="mb-1">Retirement Approval System</h6>
                    <p class="mb-0">Configure multi-level approval workflows for retirement processing with 1-5 configurable approval levels, amount thresholds, and multiple approvers per level.</p>
                </div>
            </div>
        </div>

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bx bx-info-circle me-2"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

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

        @if($settings && $settings->approval_required)
            @php
                $firstLevel = 1;
                $threshold = $settings->{'level' . $firstLevel . '_amount_threshold'};
                $approvers = $settings->{'level' . $firstLevel . '_approvers'} ?? [];
                $approverNames = $users->whereIn('id', $approvers)->pluck('name')->toArray();
            @endphp
            <div class="alert alert-primary border-primary mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <i class="bx bx-info-circle fs-5 me-3"></i>
                        <div>
                            <strong>Current Approval Rule:</strong>
                            <span class="ms-2">
                                @if($threshold)
                                    Amounts ≥ {{ number_format($threshold, 2) }}
                                @else
                                    All amounts
                                @endif
                                | Approvers:
                                @if(count($approverNames) > 0)
                                    <span class="fw-bold">{{ implode(', ', $approverNames) }}</span>
                                @else
                                    <span class="text-muted">No approvers set</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('imprest.retirement-approval-settings.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        <!-- Basic Settings Column -->
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
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
                                                <strong>Enable Multi-Level Approval</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted">Turn on to require multiple approvals before retirement processing</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="approval_levels" class="form-label">
                                            <i class="bx bx-layer-plus me-1"></i>Number of Approval Levels
                                        </label>
                                        <select class="form-select" id="approval_levels" name="approval_levels">
                                            @for($i = 1; $i <= 5; $i++)
                                                <option value="{{ $i }}" {{ old('approval_levels', $settings?->approval_levels ?? 1) == $i ? 'selected' : '' }}>
                                                    {{ $i }} Level{{ $i > 1 ? 's' : '' }}
                                                </option>
                                            @endfor
                                        </select>
                                        <small class="text-muted">How many approval levels before retirement processing</small>
                                    </div>

                                    <div class="mb-3" id="preset_section">
                                        <label class="form-label fw-bold">
                                            <i class="bx bx-magic-wand me-1"></i>Quick Setup (Optional)
                                        </label>
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-outline-info btn-sm px-3 py-2" onclick="applyPreset('supervisor')">
                                                <i class="bx bx-check me-2"></i><span>Supervisor (1 Level)</span>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning btn-sm px-3 py-2" onclick="applyPreset('department_manager')">
                                                <i class="bx bx-check-double me-2"></i><span>Department + Manager (2 Levels)</span>
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-sm px-3 py-2" onclick="applyPreset('three_tier')">
                                                <i class="bx bx-shield-check me-2"></i><span>Three-Tier (3 Levels)</span>
                                            </button>
                                        </div>
                                        <small class="text-muted d-block mt-2">Use preset configurations for common retirement approval workflows</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">
                                            <i class="bx bx-note me-1"></i>Notes
                                        </label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                                  placeholder="Additional notes about the retirement approval workflow">{{ old('notes', $settings?->notes) }}</textarea>
                                    </div>

                                    @if($settings)
                                        <div class="mt-4 p-3 bg-gradient bg-light rounded-3 border">
                                            <h6 class="text-primary mb-3 fw-bold">
                                                <i class="bx bx-info-circle me-1"></i>Current Status
                                            </h6>
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                <span class="fw-medium">Status:</span>
                                                <span class="badge {{ $settings->approval_required ? 'bg-success' : 'bg-secondary' }} px-3 py-2">
                                                    {{ $settings->approval_required ? 'Enabled' : 'Disabled' }}
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                <span class="fw-medium">Levels:</span>
                                                <span class="badge bg-primary px-3 py-2 fw-bold">{{ $settings->approval_levels ?? 1 }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-medium">Last Updated:</span>
                                                <span class="text-muted small">{{ $settings->updated_at?->diffForHumans() ?? 'Never' }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Approval Levels Configuration -->
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="bx bx-user-check me-2"></i>Level Configuration
                                    </h6>
                                </div>
                                <div class="card-body" id="approval_levels_config" style="{{ old('approval_required', $settings?->approval_required) ? '' : 'display: none;' }}">
                                    <div class="alert alert-primary border-primary mb-4">
                                        <div class="d-flex align-items-start">
                                            <i class="bx bx-info-circle fs-5 me-3 mt-1"></i>
                                            <div>
                                                <h6 class="mb-2"><strong>Configuration Instructions:</strong></h6>
                                                <ul class="mb-0 ps-3 small">
                                                    <li>Set amount thresholds to determine when each level is required</li>
                                                    <li>Hold Ctrl/Cmd to select multiple approvers for each level</li>
                                                    <li>Any one approver from each level can provide approval</li>
                                                    <li>Approvals must be completed sequentially (Level 1 → 2 → 3, etc.)</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    @for($level = 1; $level <= 5; $level++)
                                        <div class="approval-level-config mb-4 p-4 border rounded-3 shadow-sm" id="level_{{ $level }}_config"
                                             style="{{ $level <= old('approval_levels', $settings?->approval_levels ?? 1) ? '' : 'display: none;' }}">
                                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                                <div class="d-flex align-items-center">
                                                    <div class="badge bg-primary me-3 px-3 py-2 fs-6">{{ $level }}</div>
                                                    <h6 class="mb-0 text-primary fw-bold">Level {{ $level }} Approval</h6>
                                                </div>
                                                @if($settings && $settings->approval_required)
                                                    @php
                                                        $currentApprovers = $settings->{'level' . $level . '_approvers'} ?? [];
                                                        $approverCount = count($currentApprovers);
                                                    @endphp
                                                    <span class="badge bg-info px-3 py-2">
                                                        <i class="bx bx-user me-1"></i>{{ $approverCount }} Approver{{ $approverCount != 1 ? 's' : '' }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="row">
                                                <div class="col-md-5 mb-3">
                                                    <label for="level{{ $level }}_amount_threshold" class="form-label">
                                                        <i class="bx bx-money me-1"></i>Minimum Amount Threshold
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">{{ auth()->user()->company->currency ?? 'TZS' }}</span>
                                                        <input type="number" class="form-control"
                                                               id="level{{ $level }}_amount_threshold"
                                                               name="level{{ $level }}_amount_threshold"
                                                               min="0" step="0.01"
                                                               value="{{ old('level' . $level . '_amount_threshold', $settings?->{'level' . $level . '_amount_threshold'}) }}"
                                                               placeholder="0.00">
                                                    </div>
                                                    <small class="text-muted">Retirements above this amount need this level approval</small>
                                                </div>

                                                <div class="col-md-7 mb-3">
                                                    <label for="level{{ $level }}_approvers" class="form-label">
                                                        <i class="bx bx-user-circle me-1"></i>Approvers for Level {{ $level }}
                                                    </label>
                                                    <select class="form-select" multiple
                                                            id="level{{ $level }}_approvers"
                                                            name="level{{ $level }}_approvers[]"
                                                            size="4">
                                                        @foreach($users as $user)
                                                            <option value="{{ $user->id }}"
                                                                {{ in_array($user->id, old('level' . $level . '_approvers', $settings?->{'level' . $level . '_approvers'} ?? [])) ? 'selected' : '' }}>
                                                                {{ $user->name }} ({{ $user->email }})
                                                                @if($user->branch)
                                                                    - {{ $user->branch->name }}
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple users</small>
                                                </div>
                                            </div>

                                            @if($settings && $settings->approval_required)
                                                @php
                                                    $threshold = $settings->{'level' . $level . '_amount_threshold'};
                                                    $approvers = $settings->{'level' . $level . '_approvers'} ?? [];
                                                @endphp
                                                @if(!empty($approvers))
                                                    <div class="alert alert-info mb-0 mt-3">
                                                        <div class="d-flex align-items-start">
                                                            <i class="bx bx-info-circle me-2 mt-1"></i>
                                                            <div class="flex-grow-1">
                                                                <strong>Current Configuration:</strong>
                                                                <div class="mt-1">
                                                                    @if($threshold)
                                                                        <span class="badge bg-primary me-2">Amounts ≥ {{ number_format($threshold, 2) }}</span>
                                                                    @else
                                                                        <span class="badge bg-secondary me-2">All amounts</span>
                                                                    @endif
                                                                    <span class="text-muted">| Approvers:</span>
                                                                    <div class="mt-2">
                                                                        @foreach($users->whereIn('id', $approvers) as $user)
                                                                            <span class="badge bg-success me-1 mb-1 px-2 py-1">{{ $user->name }}</span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @endfor

                                    <div class="alert alert-warning mt-3" id="no_levels_warning" style="display: none;">
                                        <small><i class="bx bx-warning me-1"></i>Please enable approval requirement and configure at least one approval level.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <a href="{{ route('imprest.index') }}" class="btn btn-secondary btn-lg px-4">
                            <i class="bx bx-arrow-back me-2"></i><span>Cancel</span>
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="bx bx-save me-2"></i><span>{{ $settings ? 'Update' : 'Save' }} Settings</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($settings)
        <!-- Current Settings Summary -->
        <div class="card mt-4 shadow-sm border-0">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bx bx-list-check me-2"></i>Current Configuration Summary
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-center p-4 border rounded-3 shadow-sm h-100">
                            <h2 class="{{ $settings->approval_required ? 'text-success' : 'text-secondary' }} mb-3">
                                <i class="bx {{ $settings->approval_required ? 'bx-check-shield' : 'bx-shield-x' }}"></i>
                            </h2>
                            <p class="mb-1 fw-bold">Multi-Level Approval</p>
                            <small class="text-muted">{{ $settings->approval_required ? 'Enabled' : 'Disabled' }}</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-4 border rounded-3 shadow-sm h-100">
                            <h2 class="text-primary mb-3">{{ $settings->approval_levels }}</h2>
                            <p class="mb-1 fw-bold">Approval Levels</p>
                            <small class="text-muted">Configured</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-4 border rounded-3 shadow-sm h-100">
                            @php
                                $totalApprovers = 0;
                                for($i = 1; $i <= $settings->approval_levels; $i++) {
                                    $approvers = $settings->{'level' . $i . '_approvers'} ?? [];
                                    $totalApprovers += count($approvers);
                                }
                            @endphp
                            <h2 class="text-info mb-3">{{ $totalApprovers }}</h2>
                            <p class="mb-1 fw-bold">Total Approvers</p>
                            <small class="text-muted">Across all levels</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-4 border rounded-3 shadow-sm h-100">
                            <h2 class="text-warning mb-3">
                                <i class="bx bx-time"></i>
                            </h2>
                            <p class="mb-1 fw-bold">Last Updated</p>
                            <small class="text-muted">{{ $settings->updated_at?->diffForHumans() ?? 'Never' }}</small>
                        </div>
                    </div>
                </div>

                @if($settings->approval_required && $settings->approval_levels > 0)
                <div class="mt-4 pt-3 border-top">
                    <h6 class="text-primary fw-bold mb-3">
                        <i class="bx bx-list-ul me-2"></i>Level Details:
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th class="fw-bold">Level</th>
                                    <th class="fw-bold">Amount Threshold</th>
                                    <th class="fw-bold">Approvers</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i = 1; $i <= $settings->approval_levels; $i++)
                                    @php
                                        $threshold = $settings->{'level' . $i . '_amount_threshold'};
                                        $approvers = $settings->{'level' . $i . '_approvers'} ?? [];
                                    @endphp
                                    <tr>
                                        <td class="fw-bold">
                                            <span class="badge bg-primary px-3 py-2 me-2">{{ $i }}</span>
                                            Level {{ $i }}
                                        </td>
                                        <td>
                                            @if($threshold)
                                                <span class="badge bg-success px-3 py-2">TZS {{ number_format($threshold, 2) }}</span>
                                            @else
                                                <em class="text-muted">No threshold</em>
                                            @endif
                                        </td>
                                        <td>
                                            @if(count($approvers) > 0)
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($users->whereIn('id', $approvers) as $user)
                                                        <span class="badge bg-primary px-3 py-2 mb-1">{{ $user->name }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <em class="text-muted">No approvers set</em>
                                            @endif
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
.approval-level-config {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    transition: all 0.3s ease;
    border: 1px solid #dee2e6 !important;
}

.approval-level-config:hover {
    background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}

.badge {
    font-size: 0.875rem;
    font-weight: 600;
    white-space: nowrap;
}

.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
}

.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.875rem;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
}

.badge.bg-primary,
.badge.bg-success,
.badge.bg-info {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
    white-space: normal;
    word-break: break-word;
    display: inline-block;
    max-width: 100%;
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const approvalRequiredCheckbox = document.getElementById('approval_required');
    const approvalLevelsSelect = document.getElementById('approval_levels');
    const approvalLevelsConfig = document.getElementById('approval_levels_config');
    const noLevelsWarning = document.getElementById('no_levels_warning');

    // Toggle approval configuration visibility
    function toggleApprovalSections() {
        if (approvalRequiredCheckbox.checked) {
            approvalLevelsConfig.style.display = 'block';
            noLevelsWarning.style.display = 'none';
            updateLevelVisibility();
        } else {
            approvalLevelsConfig.style.display = 'none';
            noLevelsWarning.style.display = 'block';
        }
    }

    // Show/hide approval levels based on selection
    function updateLevelVisibility() {
        const selectedLevels = parseInt(approvalLevelsSelect.value);

        for (let i = 1; i <= 5; i++) {
            const levelDiv = document.getElementById(`level_${i}_config`);
            if (i <= selectedLevels) {
                levelDiv.style.display = 'block';
            } else {
                levelDiv.style.display = 'none';
                // Clear hidden level data
                document.getElementById(`level${i}_amount_threshold`).value = '';
                const selectElement = document.getElementById(`level${i}_approvers`);
                Array.from(selectElement.options).forEach(option => option.selected = false);
            }
        }
    }

    // Preset configurations
    window.applyPreset = function(type) {
        // Enable approval first
        approvalRequiredCheckbox.checked = true;
        toggleApprovalSections();

        if (type === 'supervisor') {
            // 1 Level: Supervisor approval only
            approvalLevelsSelect.value = 1;
            updateLevelVisibility();
            document.getElementById('level1_amount_threshold').value = '50000';

        } else if (type === 'department_manager') {
            // 2 Levels: Department > Manager
            approvalLevelsSelect.value = 2;
            updateLevelVisibility();
            document.getElementById('level1_amount_threshold').value = '25000';
            document.getElementById('level2_amount_threshold').value = '100000';

        } else if (type === 'three_tier') {
            // 3 Levels: Department > Manager > Finance
            approvalLevelsSelect.value = 3;
            updateLevelVisibility();
            document.getElementById('level1_amount_threshold').value = '10000';
            document.getElementById('level2_amount_threshold').value = '50000';
            document.getElementById('level3_amount_threshold').value = '200000';
        }

        // Show notification
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-info alert-dismissible fade show mt-3';
        alertDiv.innerHTML = `
            <i class="bx bx-info-circle me-2"></i>
            Preset "${type}" configuration applied. Please select approvers for each level and save.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.card-body').insertBefore(alertDiv, document.querySelector('form'));

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Event listeners
    approvalRequiredCheckbox.addEventListener('change', toggleApprovalSections);
    approvalLevelsSelect.addEventListener('change', updateLevelVisibility);

    // Initialize visibility
    toggleApprovalSections();

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        if (approvalRequiredCheckbox.checked) {
            const selectedLevels = parseInt(approvalLevelsSelect.value);
            let hasValidConfig = false;

            for (let i = 1; i <= selectedLevels; i++) {
                const approvers = document.getElementById(`level${i}_approvers`);
                if (approvers.selectedOptions.length > 0) {
                    hasValidConfig = true;
                    break;
                }
            }

            if (!hasValidConfig) {
                e.preventDefault();
                alert('Please select at least one approver for the configured levels.');
                return false;
            }
        }
    });
});
</script>
@endpush
