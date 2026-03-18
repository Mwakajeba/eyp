@extends('layouts.main')

@section('title', 'Multi-Level Approval Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Approval Settings', 'url' => route('imprest.multi-approval-settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Multi-Level Settings', 'url' => '#', 'icon' => 'bx bx-git-branch']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Multi-Level Approval Settings</h5>
                <small class="text-muted">Configure flexible approval workflows for imprest requests</small>
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
                    <h6 class="mb-1">Enhanced Approval System</h6>
                    <p class="mb-0">This multi-level system replaces the previous approval settings and provides more flexibility with 1-5 configurable approval levels, amount thresholds, and multiple approvers per level.</p>
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
                if (is_string($approvers)) {
                    $approvers = json_decode($approvers, true) ?? [];
                }
                if (!is_array($approvers)) {
                    $approvers = [];
                }
                $approverIds = array_map('intval', $approvers);
                $approverNames = $users->whereIn('id', $approverIds)->pluck('name')->toArray();
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
                <form action="{{ route('imprest.multi-approval-settings.store') }}" method="POST">
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
                                        <small class="text-muted">Turn on to require multiple approvals before disbursement</small>
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
                                        <small class="text-muted">How many approval levels before disbursement</small>
                                    </div>

                                    <div class="mb-3" id="preset_section">
                                        <label class="form-label fw-bold">
                                            <i class="bx bx-magic-wand me-1"></i>Quick Setup (Optional)
                                        </label>
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-outline-info btn-sm px-3 py-2" onclick="applyPreset('simple')">
                                                <i class="bx bx-check me-2"></i><span>Simple (1 Level)</span>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning btn-sm px-3 py-2" onclick="applyPreset('standard')">
                                                <i class="bx bx-check-double me-2"></i><span>Standard (2 Levels)</span>
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-sm px-3 py-2" onclick="applyPreset('corporate')">
                                                <i class="bx bx-shield-check me-2"></i><span>Corporate (3 Levels)</span>
                                            </button>
                                        </div>
                                        <small class="text-muted d-block mt-2">Use preset configurations for common approval workflows</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">
                                            <i class="bx bx-note me-1"></i>Notes
                                        </label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                                  placeholder="Additional notes about the approval workflow">{{ old('notes', $settings?->notes) }}</textarea>
                                    </div>

                                    @if($settings)
                                        <div class="mt-4 p-4 bg-white rounded-3 border shadow-sm">
                                            <h6 class="text-primary mb-4 fw-bold d-flex align-items-center">
                                                <i class="bx bx-info-circle me-2 fs-5"></i>Current Status
                                            </h6>

                                            <!-- Status and Levels Row -->
                                            <div class="row g-3 mb-4">
                                                <div class="col-6">
                                                    <div class="d-flex flex-column">
                                                        <span class="text-muted small mb-1">Status</span>
                                                        <span class="badge {{ $settings->approval_required ? 'bg-success' : 'bg-secondary' }} px-3 py-2 fs-6 fw-normal w-100 text-center">
                                                            {{ $settings->approval_required ? 'Enabled' : 'Disabled' }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex flex-column">
                                                        <span class="text-muted small mb-1">Levels</span>
                                                        <span class="badge bg-primary px-3 py-2 fs-6 fw-bold w-100 text-center">
                                                            {{ $settings->approval_levels }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            @if($settings->approval_required)
                                                <!-- Configured Levels Section -->
                                                <div class="mt-4 pt-4 border-top">
                                                    <h6 class="text-muted small mb-3 fw-bold text-uppercase">Configured Levels</h6>
                                                    @for($i = 1; $i <= $settings->approval_levels; $i++)
                                                        @php
                                                            $approverProp = 'level' . $i . '_approvers';
                                                            $thresholdProp = 'level' . $i . '_amount_threshold';
                                                            $levelApprovers = $settings->$approverProp ?? [];
                                                            if (is_string($levelApprovers)) {
                                                                $levelApprovers = json_decode($levelApprovers, true) ?? [];
                                                            }
                                                            if (!is_array($levelApprovers)) {
                                                                $levelApprovers = [];
                                                            }
                                                            $levelThreshold = $settings->$thresholdProp ?? null;
                                                            $isLast = $i == $settings->approval_levels;
                                                        @endphp
                                                        <div class="mb-3 pb-3 {{ !$isLast ? 'border-bottom' : '' }}">
                                                            <div class="d-flex align-items-center mb-2">
                                                                <strong class="text-dark me-2">Level {{ $i }}:</strong>
                                                            </div>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                @if(!empty($levelApprovers))
                                                                    <span class="badge bg-info px-3 py-2 fs-6">
                                                                        {{ count($levelApprovers) }} approver(s)
                                                                    </span>
                                                                    @if($levelThreshold)
                                                                        <span class="badge bg-success px-3 py-2 fs-6">
                                                                            Threshold: {{ number_format($levelThreshold, 2) }}
                                                                        </span>
                                                                    @endif
                                                                @else
                                                                    <span class="badge bg-warning px-3 py-2 fs-6">
                                                                        No approvers configured
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endfor
                                                </div>
                                            @endif

                                            <!-- Last Updated -->
                                            <div class="mt-4 pt-3 border-top">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted small">Last Updated</span>
                                                    <span class="text-muted small fw-medium">{{ $settings->updated_at?->diffForHumans() ?? 'Never' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Approval Levels Configuration Column -->
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
                                            <i class="bx bx-info-circle fs-4 me-3 mt-1"></i>
                                            <div>
                                                <h6 class="mb-2"><strong>How to Add Approvers:</strong></h6>
                                                <ol class="mb-0 ps-3">
                                                    <li>Set the amount threshold for each level (optional)</li>
                                                    <li><strong>Type a user's name</strong> in the search box below</li>
                                                    <li><strong>Click on the user</strong> from the dropdown that appears</li>
                                                    <li><strong>Click the "Add" button</strong> to add them</li>
                                                    <li>The user will appear in a gray box below - repeat for more approvers</li>
                                                </ol>
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
                                                <div>
                                                    <span class="badge bg-info px-3 py-2" id="level{{ $level }}_approver_count">
                                                        <i class="bx bx-user me-1"></i>
                                                        <span id="level{{ $level }}_count_text">
                                                            @php
                                                                $approverProperty = 'level' . $level . '_approvers';
                                                                $savedApprovers = $settings ? ($settings->$approverProperty ?? []) : [];
                                                                if (is_string($savedApprovers)) {
                                                                    $savedApprovers = json_decode($savedApprovers, true) ?? [];
                                                                }
                                                                if (!is_array($savedApprovers)) {
                                                                    $savedApprovers = [];
                                                                }
                                                                $count = count($savedApprovers);
                                                            @endphp
                                                            {{ $count }} Approver{{ $count != 1 ? 's' : '' }}
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-5 mb-3">
                                                    <label for="level{{ $level }}_amount_threshold" class="form-label">
                                                        <i class="bx bx-money me-1"></i>Minimum Amount Threshold
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">{{ auth()->user()->company->currency ?? 'TZS' }}</span>
                                                        @php
                                                            $thresholdProperty = 'level' . $level . '_amount_threshold';
                                                            $thresholdValue = old('level' . $level . '_amount_threshold', $settings ? ($settings->$thresholdProperty ?? null) : null);
                                                        @endphp
                                                        <input type="number" class="form-control"
                                                               id="level{{ $level }}_amount_threshold"
                                                               name="level{{ $level }}_amount_threshold"
                                                               min="0" step="0.01"
                                                               value="{{ $thresholdValue }}"
                                                               placeholder="0.00">
                                                    </div>
                                                    <small class="text-muted">Requests above this amount need this level approval</small>
                                                </div>

                                                <div class="col-md-7 mb-3">
                                                    <label for="level{{ $level }}_approvers" class="form-label">
                                                        <i class="bx bx-user-circle me-1"></i>Approvers for Level {{ $level }}
                                                    </label>

                                                    <!-- Add Approver Section with Live Search - Only shown when approval is required -->
                                                    <div class="mb-2 add-approver-form" id="level{{ $level }}_add_form" style="{{ old('approval_required', $settings?->approval_required) ? '' : 'display: none;' }}">
                                                        <div class="search-select-container position-relative">
                                                            <div class="input-group">
                                                                <input type="text"
                                                                       class="form-control search-input"
                                                                       id="level{{ $level }}_search_input"
                                                                       placeholder="Type to search users..."
                                                                       autocomplete="off">
                                                                <button type="button" class="btn btn-primary" id="level{{ $level }}_add_btn" disabled>
                                                                    <i class="bx bx-plus"></i> Add
                                                                </button>
                                                            </div>

                                                            <!-- Live Search Dropdown -->
                                                            <div class="search-dropdown position-absolute w-100 bg-white border rounded shadow-sm"
                                                                 id="level{{ $level }}_dropdown"
                                                                 style="display: none; max-height: 200px; overflow-y: auto; z-index: 1000; top: 100%;">
                                                                <!-- Results will be populated here -->
                                                            </div>
                                                        </div>
                                                        <small class="text-muted">Start typing to search for users, then click on a user to select</small>
                                                    </div>

                                                    <!-- Selected Approvers Display - Always visible -->
                                                    <div id="level{{ $level }}_selected_approvers" class="selected-approvers">
                                                        @php
                                                            // Get approvers from old input or settings, ensuring it's always an array
                                                            $approverProperty = 'level' . $level . '_approvers';
                                                            $oldApprovers = old('level' . $level . '_approvers');
                                                            $savedApprovers = $settings ? ($settings->$approverProperty ?? []) : [];

                                                            // Handle case where approvers might be stored as JSON string
                                                            if (is_string($savedApprovers)) {
                                                                $savedApprovers = json_decode($savedApprovers, true) ?? [];
                                                            }

                                                            // Ensure it's an array
                                                            if (!is_array($savedApprovers)) {
                                                                $savedApprovers = [];
                                                            }

                                                            $selectedApprovers = $oldApprovers ?? $savedApprovers;
                                                        @endphp
                                                        @if(!empty($selectedApprovers) && is_array($selectedApprovers))
                                                            @foreach($selectedApprovers as $approverId)
                                                                @php
                                                                    // Handle both integer IDs and string IDs
                                                                    $approverId = (int) $approverId;
                                                                    $approver = $users->firstWhere('id', $approverId);
                                                                @endphp
                                                                @if($approver)
                                                                    <div class="approver-item mb-2 p-3 bg-light rounded-3 d-flex justify-content-between align-items-center border shadow-sm" data-user-id="{{ $approver->id }}">
                                                                        <div class="d-flex align-items-center">
                                                                            <i class="bx bx-user me-2 text-primary fs-5"></i>
                                                                            <div>
                                                                                <strong class="d-block">{{ $approver->name }}</strong>
                                                                                <small class="text-muted">{{ $approver->email }}</small>
                                                                            </div>
                                                                        </div>
                                                                        <button type="button" class="btn btn-sm btn-outline-danger approver-remove-btn px-3" onclick="removeApprover({{ $level }}, {{ $approver->id }})" style="{{ old('approval_required', $settings?->approval_required) ? '' : 'display: none;' }}">
                                                                            <i class="bx bx-x me-1"></i>Remove
                                                                        </button>
                                                                        <input type="hidden" name="level{{ $level }}_approvers[]" value="{{ $approver->id }}">
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    </div>

                                                    <!-- Empty State -->
                                                    <div id="level{{ $level }}_empty_state" class="text-center p-3 bg-light rounded" style="{{ !empty($selectedApprovers) ? 'display: none;' : '' }}">
                                                        <i class="bx bx-user-plus text-muted" style="font-size: 2rem;"></i>
                                                        <p class="text-muted mb-0 mt-2">No approvers selected</p>
                                                        <small class="text-muted" id="level{{ $level }}_empty_hint" style="{{ old('approval_required', $settings?->approval_required) ? '' : 'display: none;' }}">Use the search above to add approvers</small>
                                                    </div>
                                                </div>
                                            </div>

                                            @if($settings && $settings->approval_required)
                                                @php
                                                    $thresholdProperty = 'level' . $level . '_amount_threshold';
                                                    $approverProperty = 'level' . $level . '_approvers';
                                                    $threshold = $settings->$thresholdProperty ?? null;
                                                    $approvers = $settings->$approverProperty ?? [];

                                                    // Handle case where approvers might be stored as JSON string
                                                    if (is_string($approvers)) {
                                                        $approvers = json_decode($approvers, true) ?? [];
                                                    }

                                                    // Ensure it's an array
                                                    if (!is_array($approvers)) {
                                                        $approvers = [];
                                                    }

                                                    // Convert approver IDs to integers for comparison
                                                    $approverIds = array_map('intval', $approvers);
                                                @endphp
                                                @if(!empty($approverIds))
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
                                                                        @foreach($users->whereIn('id', $approverIds) as $user)
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

                                    <div class="alert alert-warning">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>How it works:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>Requests will require approval from <strong>all specified levels</strong> based on the amount</li>
                                            <li>Each level can have multiple approvers - <strong>any one</strong> of them can approve for that level</li>
                                            <li>Approvals must be completed in <strong>sequential order</strong> (Level 1, then 2, then 3, etc.)</li>
                                            <li>Leave amount threshold empty to require approval for <strong>all amounts</strong> at that level</li>
                                        </ul>
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
                            <i class="bx bx-save me-2"></i><span>Save Configuration</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
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

.approver-item {
    transition: all 0.3s ease;
}

.approver-item:hover {
    transform: translateX(5px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
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

.search-dropdown {
    max-height: 250px;
    overflow-y: auto;
}

.user-option:hover {
    background-color: #f8f9fa !important;
}

.user-option.bg-light {
    background-color: #e9ecef !important;
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
// Users data for JavaScript
const usersData = @json($users->map(function($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email
    ];
}));

// Global variables for search state
let searchStates = {};
let selectedUsers = {};

// Initialize search states for each level
for (let level = 1; level <= 5; level++) {
    searchStates[level] = {
        selectedUser: null,
        filteredUsers: [...usersData],
        highlightedIndex: -1
    };
    selectedUsers[level] = new Set();
}

function initializeLiveSearch(level) {
    const searchInput = document.getElementById(`level${level}_search_input`);
    const dropdown = document.getElementById(`level${level}_dropdown`);
    const addBtn = document.getElementById(`level${level}_add_btn`);

    if (!searchInput || !dropdown || !addBtn) return;

    // Initialize selected users from existing approvers (both from HTML and hidden inputs)
    const existingApprovers = document.querySelectorAll(`#level${level}_selected_approvers .approver-item`);
    existingApprovers.forEach(item => {
        const userId = parseInt(item.dataset.userId);
        if (userId && !isNaN(userId)) {
            selectedUsers[level].add(userId);
        }
    });

    // Also check hidden inputs in case approvers are loaded but not displayed
    const hiddenInputs = document.querySelectorAll(`input[name="level${level}_approvers[]"]`);
    hiddenInputs.forEach(input => {
        const userId = parseInt(input.value);
        if (userId && !isNaN(userId)) {
            selectedUsers[level].add(userId);
        }
    });

    // Search input event listeners
    searchInput.addEventListener('input', function() {
        handleSearch(level, this.value);
    });

    searchInput.addEventListener('focus', function() {
        showDropdown(level);
    });

    searchInput.addEventListener('keydown', function(e) {
        handleKeyNavigation(level, e);
    });

    // Add button click
    addBtn.addEventListener('click', function() {
        addSelectedUser(level);
    });

    // Click outside to close dropdown
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            hideDropdown(level);
        }
    });
}

function handleSearch(level, query) {
    const state = searchStates[level];

    if (query.trim() === '') {
        state.filteredUsers = [...usersData];
        state.selectedUser = null;
        updateAddButton(level, false);
        hideDropdown(level);
        return;
    }

    // Filter users based on query
    const searchTerm = query.toLowerCase();
    state.filteredUsers = usersData.filter(user =>
        (user.name.toLowerCase().includes(searchTerm) ||
         user.email.toLowerCase().includes(searchTerm)) &&
        !selectedUsers[level].has(user.id)
    );

    state.highlightedIndex = -1;
    state.selectedUser = null;
    updateAddButton(level, false);
    renderDropdown(level);
    showDropdown(level);
}

function renderDropdown(level) {
    const dropdown = document.getElementById(`level${level}_dropdown`);
    const state = searchStates[level];

    if (state.filteredUsers.length === 0) {
        dropdown.innerHTML = `
            <div class="dropdown-item text-muted p-3 text-center">
                <i class="bx bx-search-alt me-2"></i>No users found
            </div>
        `;
        return;
    }

    dropdown.innerHTML = state.filteredUsers.map((user, index) => `
        <div class="dropdown-item user-option p-3 ${index === state.highlightedIndex ? 'bg-light' : ''}"
             data-user-id="${user.id}"
             data-index="${index}"
             style="cursor: pointer; border-bottom: 1px solid #f0f0f0;">
            <div class="d-flex align-items-center">
                <i class="bx bx-user me-2 text-primary"></i>
                <div>
                    <div class="fw-bold">${user.name}</div>
                    <small class="text-muted">${user.email}</small>
                </div>
            </div>
        </div>
    `).join('');

    // Add click listeners to options
    dropdown.querySelectorAll('.user-option').forEach(option => {
        option.addEventListener('click', function() {
            selectUser(level, parseInt(this.dataset.userId), parseInt(this.dataset.index));
        });

        option.addEventListener('mouseenter', function() {
            highlightOption(level, parseInt(this.dataset.index));
        });
    });
}

function handleKeyNavigation(level, e) {
    const state = searchStates[level];

    switch(e.key) {
        case 'ArrowDown':
            e.preventDefault();
            if (state.highlightedIndex < state.filteredUsers.length - 1) {
                highlightOption(level, state.highlightedIndex + 1);
            }
            break;

        case 'ArrowUp':
            e.preventDefault();
            if (state.highlightedIndex > 0) {
                highlightOption(level, state.highlightedIndex - 1);
            }
            break;

        case 'Enter':
            e.preventDefault();
            if (state.highlightedIndex >= 0) {
                const user = state.filteredUsers[state.highlightedIndex];
                selectUser(level, user.id, state.highlightedIndex);
            } else if (state.selectedUser) {
                addSelectedUser(level);
            }
            break;

        case 'Escape':
            hideDropdown(level);
            break;
    }
}

function highlightOption(level, index) {
    const state = searchStates[level];
    state.highlightedIndex = index;

    // Update visual highlighting
    const dropdown = document.getElementById(`level${level}_dropdown`);
    dropdown.querySelectorAll('.user-option').forEach((option, i) => {
        option.classList.toggle('bg-light', i === index);
    });
}

function selectUser(level, userId, index) {
    const user = usersData.find(u => u.id === userId);
    if (!user) return;

    const state = searchStates[level];
    state.selectedUser = user;
    state.highlightedIndex = index;

    // Update input with selected user
    const searchInput = document.getElementById(`level${level}_search_input`);
    searchInput.value = `${user.name} (${user.email})`;

    updateAddButton(level, true);
    hideDropdown(level);
}

function addSelectedUser(level) {
    const state = searchStates[level];
    if (!state.selectedUser) return;

    const user = state.selectedUser;

    // Check if user is already added
    if (selectedUsers[level].has(user.id)) {
        showToast('User is already added as approver', 'warning');
        return;
    }

    // Add to selected users
    selectedUsers[level].add(user.id);

    // Create approver item
    const approverItem = document.createElement('div');
    approverItem.className = 'approver-item mb-2 p-3 bg-light rounded-3 d-flex justify-content-between align-items-center border shadow-sm';
    approverItem.setAttribute('data-user-id', user.id);
    approverItem.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bx bx-user me-2 text-primary fs-5"></i>
            <div>
                <strong class="d-block">${user.name}</strong>
                <small class="text-muted">${user.email}</small>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger approver-remove-btn px-3" onclick="removeApprover(${level}, ${user.id})">
            <i class="bx bx-x me-1"></i>Remove
        </button>
        <input type="hidden" name="level${level}_approvers[]" value="${user.id}">
    `;

    // Add to selected approvers container
    const selectedApproversContainer = document.getElementById(`level${level}_selected_approvers`);
    selectedApproversContainer.appendChild(approverItem);

    // Hide empty state
    const emptyState = document.getElementById(`level${level}_empty_state`);
    if (emptyState) {
        emptyState.style.display = 'none';
    }

    // Reset search
    resetSearch(level);

    // Update counter
    updateApproverCount(level);

    showToast('Approver added successfully', 'success');
}

function removeApprover(level, userId) {
    const approverItem = document.querySelector(`#level${level}_selected_approvers .approver-item[data-user-id="${userId}"]`);
    if (approverItem) {
        approverItem.remove();
        selectedUsers[level].delete(userId);

        // Show empty state if no approvers left
        const remainingApprovers = document.querySelectorAll(`#level${level}_selected_approvers .approver-item`);
        if (remainingApprovers.length === 0) {
            const emptyState = document.getElementById(`level${level}_empty_state`);
            if (emptyState) {
                emptyState.style.display = 'block';
            }
        }

        // Update counter
        updateApproverCount(level);

        showToast('Approver removed', 'info');
    }
}

function updateApproverCount(level) {
    const approverItems = document.querySelectorAll(`#level${level}_selected_approvers .approver-item`);
    const count = approverItems.length;
    const countText = document.getElementById(`level${level}_count_text`);
    if (countText) {
        countText.textContent = `${count} Approver${count !== 1 ? 's' : ''}`;
    }
}

function resetSearch(level) {
    const searchInput = document.getElementById(`level${level}_search_input`);
    searchInput.value = '';

    const state = searchStates[level];
    state.selectedUser = null;
    state.highlightedIndex = -1;
    state.filteredUsers = [...usersData];

    updateAddButton(level, false);
    hideDropdown(level);
}

function updateAddButton(level, enabled) {
    const addBtn = document.getElementById(`level${level}_add_btn`);
    addBtn.disabled = !enabled;
}

function showDropdown(level) {
    const dropdown = document.getElementById(`level${level}_dropdown`);
    dropdown.style.display = 'block';
}

function hideDropdown(level) {
    const dropdown = document.getElementById(`level${level}_dropdown`);
    dropdown.style.display = 'none';
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    const approvalRequiredCheckbox = document.getElementById('approval_required');
    const approvalLevelsConfig = document.getElementById('approval_levels_config');
    const approvalLevelsSelect = document.getElementById('approval_levels');

    // Initialize live search for all levels
    for (let level = 1; level <= 5; level++) {
        initializeLiveSearch(level);
    }

    // Toggle approval sections based on checkbox
    function toggleApprovalSections() {
        const isRequired = approvalRequiredCheckbox.checked;
        approvalLevelsConfig.style.display = isRequired ? '' : 'none';

        // Toggle add approver forms and remove buttons
        for (let level = 1; level <= 5; level++) {
            const addForm = document.getElementById(`level${level}_add_form`);
            const emptyHint = document.getElementById(`level${level}_empty_hint`);
            const removeButtons = document.querySelectorAll(`#level${level}_selected_approvers .approver-remove-btn`);

            if (addForm) {
                addForm.style.display = isRequired ? '' : 'none';
            }
            if (emptyHint) {
                emptyHint.style.display = isRequired ? '' : 'none';
            }
            removeButtons.forEach(btn => {
                btn.style.display = isRequired ? '' : 'none';
            });
        }

        if (isRequired) {
            updateLevelVisibility();
        }
    }

    // Update visibility of level configurations
    function updateLevelVisibility() {
        const selectedLevels = parseInt(approvalLevelsSelect.value);

        for (let i = 1; i <= 5; i++) {
            const levelConfig = document.getElementById(`level_${i}_config`);
            if (levelConfig) {
                levelConfig.style.display = i <= selectedLevels ? '' : 'none';
            }
        }
    }

    // Apply preset configurations
    function applyPreset(type) {
        approvalRequiredCheckbox.checked = true;
        toggleApprovalSections();

        if (type === 'simple') {
            // 1 Level: Manager approval only
            approvalLevelsSelect.value = 1;
            updateLevelVisibility();

        } else if (type === 'standard') {
            // 2 Levels: Manager > Finance
            approvalLevelsSelect.value = 2;
            updateLevelVisibility();

        } else if (type === 'corporate') {
            // 3 Levels: Manager > Finance > CEO
            approvalLevelsSelect.value = 3;
            updateLevelVisibility();
        }

        // Show notification
        showToast(`Preset "${type}" configuration applied. Please select approvers for each level and save.`, 'success');
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
            let approverCounts = [];

            for (let i = 1; i <= selectedLevels; i++) {
                const selectedApprovers = document.querySelectorAll(`#level${i}_selected_approvers .approver-item`);
                approverCounts.push(`Level ${i}: ${selectedApprovers.length} approver${selectedApprovers.length !== 1 ? 's' : ''}`);
                if (selectedApprovers.length > 0) {
                    hasValidConfig = true;
                }
            }

            if (!hasValidConfig) {
                e.preventDefault();
                const message = `Please add at least one approver to at least one level.\n\nCurrent status:\n${approverCounts.join('\n')}\n\nSteps to add approvers:\n1. Type a user's name in the search box\n2. Click on the user from the dropdown\n3. Click the "Add" button\n4. The user should appear below with a gray background`;
                alert(message);
                showToast('No approvers configured! Please add approvers before saving.', 'danger');
                return false;
            }
        }
    });

    // Make applyPreset global
    window.applyPreset = applyPreset;
});
</script>
@endpush
