@extends('layouts.main')

@section('title', 'Payment Approval Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Payment Approval Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Payment Approval Settings</h5>
                <small class="text-muted">Configure multi-level approval workflow for payroll payment processing</small>
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
                    <h6 class="mb-1">Payment Approval Workflow</h6>
                    <p class="mb-0">Set up multi-level approval system for payroll payments. After payroll is <strong>Completed</strong>, payment must be approved before it can be processed. This ensures proper authorization for cash outflows.</p>
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
                <form action="{{ route('hr-payroll.payment-approval-settings.store') }}" method="POST">
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
                                            <input class="form-check-input" type="checkbox" id="payment_approval_required" 
                                                   name="payment_approval_required" value="1"
                                                   {{ old('payment_approval_required', $settings?->payment_approval_required) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="payment_approval_required">
                                                <strong>Enable Payment Approval</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted">Require approval before processing payroll payments</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="payment_approval_levels" class="form-label">
                                            <i class="bx bx-layer-plus me-1"></i>Number of Approval Levels
                                        </label>
                                        <select class="form-select" id="payment_approval_levels" name="payment_approval_levels">
                                            @for($i = 1; $i <= 5; $i++)
                                                <option value="{{ $i }}" {{ old('payment_approval_levels', $settings?->payment_approval_levels ?? 1) == $i ? 'selected' : '' }}>
                                                    {{ $i }} Level{{ $i > 1 ? 's' : '' }}
                                                </option>
                                            @endfor
                                        </select>
                                        <small class="text-muted">How many approval levels before payment can be processed</small>
                                    </div>

                                    <div class="mb-3" id="preset_section">
                                        <label class="form-label">
                                            <i class="bx bx-magic-wand me-1"></i>Quick Setup (Optional)
                                        </label>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button type="button" class="btn btn-sm btn-outline-info" onclick="applyPreset('simple')">
                                                <i class="bx bx-check me-1"></i>Simple (1 Level)
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="applyPreset('standard')">
                                                <i class="bx bx-check-double me-1"></i>Standard (2 Levels)
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="applyPreset('corporate')">
                                                <i class="bx bx-shield-check me-1"></i>Corporate (3 Levels)
                                            </button>
                                        </div>
                                        <small class="text-muted">Use preset configurations for common approval workflows</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">
                                            <i class="bx bx-note me-1"></i>Notes
                                        </label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                  placeholder="Additional notes about the payment approval workflow">{{ old('notes', $settings?->notes) }}</textarea>
                                    </div>

                                    @if($settings)
                                        <div class="mt-4 p-3 bg-light rounded">
                                            <h6 class="text-info mb-2">Current Status</h6>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Status:</span>
                                                <span class="badge {{ $settings->payment_approval_required ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $settings->payment_approval_required ? 'Enabled' : 'Disabled' }}
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Levels:</span>
                                                <span class="fw-bold">{{ $settings->payment_approval_levels }}</span>
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

                        <!-- Approval Levels Configuration Column -->
                        <div class="col-lg-8">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 text-primary">
                                        <i class="bx bx-user-check me-2"></i>Level Configuration
                                    </h6>
                                </div>
                                <div class="card-body" id="payment_approval_levels_config" style="{{ old('payment_approval_required', $settings?->payment_approval_required) ? '' : 'display: none;' }}">
                                    @for($level = 1; $level <= 5; $level++)
                                        <div class="approval-level-config mb-4 p-3 border rounded" id="payment_level_{{ $level }}_config" 
                                             style="{{ $level <= old('payment_approval_levels', $settings?->payment_approval_levels ?? 1) ? '' : 'display: none;' }}">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="badge bg-primary me-2">{{ $level }}</div>
                                                <h6 class="mb-0 text-primary">Level {{ $level }} Approval</h6>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-5 mb-3">
                                                    <label for="payment_level{{ $level }}_amount_threshold" class="form-label">
                                                        <i class="bx bx-money me-1"></i>Minimum Payment Amount Threshold
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">{{ auth()->user()->company->currency ?? 'TZS' }}</span>
                                                        <input type="number" class="form-control" 
                                                               id="payment_level{{ $level }}_amount_threshold" 
                                                               name="payment_level{{ $level }}_amount_threshold" 
                                                               min="0" step="0.01"
                                                               value="{{ old('payment_level' . $level . '_amount_threshold', $settings?->{'payment_level' . $level . '_amount_threshold'}) }}"
                                                               placeholder="0.00">
                                                    </div>
                                                    <small class="text-muted">Payments above this amount need this level approval</small>
                                                </div>
                                                
                                                <div class="col-md-7 mb-3">
                                                    <label for="payment_level{{ $level }}_approvers" class="form-label">
                                                        <i class="bx bx-user-circle me-1"></i>Approvers for Level {{ $level }}
                                                    </label>
                                                    
                                                    <!-- Add Approver Section with Live Search -->
                                                    <div class="mb-2">
                                                        <div class="search-select-container position-relative">
                                                            <div class="input-group">
                                                                <input type="text" 
                                                                       class="form-control search-input" 
                                                                       id="payment_level{{ $level }}_search_input"
                                                                       placeholder="Type to search users..." 
                                                                       autocomplete="off">
                                                                <button type="button" class="btn btn-primary" id="payment_level{{ $level }}_add_btn" disabled>
                                                                    <i class="bx bx-plus"></i> Add
                                                                </button>
                                                            </div>
                                                            
                                                            <!-- Live Search Dropdown -->
                                                            <div class="search-dropdown position-absolute w-100 bg-white border rounded shadow-sm" 
                                                                 id="payment_level{{ $level }}_dropdown" 
                                                                 style="display: none; max-height: 200px; overflow-y: auto; z-index: 1000; top: 100%;">
                                                                <!-- Results will be populated here -->
                                                            </div>
                                                        </div>
                                                        <small class="text-muted">Start typing to search for users, then click on a user to select</small>
                                                    </div>
                                                    
                                                    <!-- Selected Approvers Display -->
                                                    <div id="payment_level{{ $level }}_selected_approvers" class="selected-approvers">
                                                        @php
                                                            $selectedApprovers = old('payment_level' . $level . '_approvers', $settings?->{'payment_level' . $level . '_approvers'} ?? []);
                                                        @endphp
                                                        @if(!empty($selectedApprovers))
                                                            @foreach($selectedApprovers as $approverId)
                                                                @php
                                                                    $approver = $users->firstWhere('id', $approverId);
                                                                @endphp
                                                                @if($approver)
                                                                    <div class="approver-item mb-2 p-2 bg-light rounded d-flex justify-content-between align-items-center" data-user-id="{{ $approver->id }}">
                                                                        <div>
                                                                            <i class="bx bx-user me-1 text-primary"></i>
                                                                            <strong>{{ $approver->name }}</strong>
                                                                            <small class="text-muted">({{ $approver->email }})</small>
                                                                        </div>
                                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeApprover({{ $level }}, {{ $approver->id }})">
                                                                            <i class="bx bx-x"></i>
                                                                        </button>
                                                                        <input type="hidden" name="payment_level{{ $level }}_approvers[]" value="{{ $approver->id }}">
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                    
                                                    <!-- Empty State -->
                                                    <div id="payment_level{{ $level }}_empty_state" class="text-center p-3 bg-light rounded" style="{{ !empty($selectedApprovers) ? 'display: none;' : '' }}">
                                                        <i class="bx bx-user-plus text-muted" style="font-size: 2rem;"></i>
                                                        <p class="text-muted mb-0 mt-2">No approvers selected</p>
                                                        <small class="text-muted">Use the search above to add approvers</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-2"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
// Users data for JavaScript
const usersData = @json($users->map(function($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email
    ];
})->values());

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

// Preset configurations
const presets = {
    simple: {
        levels: 1,
        level1: { threshold: 0, approvers: [] }
    },
    standard: {
        levels: 2,
        level1: { threshold: 0, approvers: [] },
        level2: { threshold: 5000000, approvers: [] }
    },
    corporate: {
        levels: 3,
        level1: { threshold: 0, approvers: [] },
        level2: { threshold: 5000000, approvers: [] },
        level3: { threshold: 50000000, approvers: [] }
    }
};

function initializeLiveSearch(level) {
    const searchInput = document.getElementById(`payment_level${level}_search_input`);
    const dropdown = document.getElementById(`payment_level${level}_dropdown`);
    const addBtn = document.getElementById(`payment_level${level}_add_btn`);
    
    if (!searchInput || !dropdown || !addBtn) return;
    
    // Initialize selected users from existing approvers
    const existingApprovers = document.querySelectorAll(`#payment_level${level}_selected_approvers .approver-item`);
    existingApprovers.forEach(item => {
        const userId = parseInt(item.dataset.userId);
        selectedUsers[level].add(userId);
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
    const dropdown = document.getElementById(`payment_level${level}_dropdown`);
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
    const dropdown = document.getElementById(`payment_level${level}_dropdown`);
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
    const searchInput = document.getElementById(`payment_level${level}_search_input`);
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
    approverItem.className = 'approver-item mb-2 p-2 bg-light rounded d-flex justify-content-between align-items-center';
    approverItem.setAttribute('data-user-id', user.id);
    approverItem.innerHTML = `
        <div>
            <i class="bx bx-user me-1 text-primary"></i>
            <strong>${user.name}</strong>
            <small class="text-muted">(${user.email})</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeApprover(${level}, ${user.id})">
            <i class="bx bx-x"></i>
        </button>
        <input type="hidden" name="payment_level${level}_approvers[]" value="${user.id}">
    `;
    
    // Add to selected approvers container
    const selectedApproversContainer = document.getElementById(`payment_level${level}_selected_approvers`);
    selectedApproversContainer.appendChild(approverItem);
    
    // Hide empty state
    const emptyState = document.getElementById(`payment_level${level}_empty_state`);
    if (emptyState) {
        emptyState.style.display = 'none';
    }
    
    // Reset search
    resetSearch(level);
    showToast('Approver added successfully', 'success');
}

function removeApprover(level, userId) {
    const approverItem = document.querySelector(`#payment_level${level}_selected_approvers .approver-item[data-user-id="${userId}"]`);
    if (approverItem) {
        approverItem.remove();
        selectedUsers[level].delete(userId);
        
        // Show empty state if no approvers left
        const remainingApprovers = document.querySelectorAll(`#payment_level${level}_selected_approvers .approver-item`);
        if (remainingApprovers.length === 0) {
            const emptyState = document.getElementById(`payment_level${level}_empty_state`);
            if (emptyState) {
                emptyState.style.display = 'block';
            }
        }
        
        showToast('Approver removed', 'info');
    }
}

function resetSearch(level) {
    const searchInput = document.getElementById(`payment_level${level}_search_input`);
    searchInput.value = '';
    
    const state = searchStates[level];
    state.selectedUser = null;
    state.highlightedIndex = -1;
    state.filteredUsers = [...usersData];
    
    updateAddButton(level, false);
    hideDropdown(level);
}

function updateAddButton(level, enabled) {
    const addBtn = document.getElementById(`payment_level${level}_add_btn`);
    addBtn.disabled = !enabled;
}

function showDropdown(level) {
    const dropdown = document.getElementById(`payment_level${level}_dropdown`);
    dropdown.style.display = 'block';
}

function hideDropdown(level) {
    const dropdown = document.getElementById(`payment_level${level}_dropdown`);
    dropdown.style.display = 'none';
}

function applyPreset(presetName) {
    const preset = presets[presetName];
    if (!preset) return;

    // Set approval levels
    document.getElementById('payment_approval_levels').value = preset.levels;
    updateApprovalLevels();

    // Set thresholds for each level
    for (let i = 1; i <= preset.levels; i++) {
        const levelKey = `level${i}`;
        if (preset[levelKey]) {
            const thresholdInput = document.getElementById(`payment_level${i}_amount_threshold`);
            if (thresholdInput && preset[levelKey].threshold !== undefined) {
                thresholdInput.value = preset[levelKey].threshold;
            }
        }
    }

    // Show success message
    showToast('Preset applied successfully! Please select approvers for each level.', 'success');
}

function updateApprovalLevels() {
    const selectedLevels = parseInt(document.getElementById('payment_approval_levels').value);
    
    for (let i = 1; i <= 5; i++) {
        const levelConfig = document.getElementById(`payment_level_${i}_config`);
        if (levelConfig) {
            levelConfig.style.display = i <= selectedLevels ? 'block' : 'none';
        }
    }
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

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Initialize live search for all levels
    for (let level = 1; level <= 5; level++) {
        initializeLiveSearch(level);
    }
    
    // Toggle approval levels config when checkbox is changed
    document.getElementById('payment_approval_required').addEventListener('change', function() {
        const configDiv = document.getElementById('payment_approval_levels_config');
        configDiv.style.display = this.checked ? 'block' : 'none';
    });

    // Update visible levels when dropdown changes
    document.getElementById('payment_approval_levels').addEventListener('change', updateApprovalLevels);
});
</script>
@endsection

