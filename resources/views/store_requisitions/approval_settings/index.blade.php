@extends('layouts.main')

@section('title', 'Store Requisition Approval Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Requisition Management', 'url' => route('store-requisitions.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Approval Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Multi-Level Approval Settings</h5>
                <small class="text-muted">Configure flexible approval workflows for store requisitions</small>
            </div>
            <div>
                <a href="{{ route('store-requisitions.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Store Requisitions
                </a>
            </div>
        </div>

        <div class="alert alert-info border-0">
            <div class="d-flex align-items-center">
                <i class="bx bx-info-circle fs-4 me-3"></i>
                <div>
                    <h6 class="mb-1">Store Requisition Approval System</h6>
                    <p class="mb-0">Configure up to 5 approval levels for store requisitions. Each level can be assigned to specific users or roles for flexible workflow management.</p>
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
                <form action="{{ route('store-requisitions.approval-settings.store') }}" method="POST" id="approvalSettingsForm">
                    @csrf
                    
                    <div class="row">
                        @for($level = 1; $level <= 5; $level++)
                        <div class="col-lg-6 mb-4">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h6 class="mb-0 text-primary">
                                            <i class="bx bx-shield-check me-2"></i>Level {{ $level }} Approval
                                        </h6>
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="level_{{ $level }}_enabled" value="0">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="level_{{ $level }}_enabled" 
                                                   name="level_{{ $level }}_enabled" 
                                                   value="1"
                                                   {{ $settings->{"level_{$level}_enabled"} ? 'checked' : '' }}
                                                   onchange="toggleLevel({{ $level }})">
                                            <label class="form-check-label" for="level_{{ $level }}_enabled">
                                                Enable Level {{ $level }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body level-{{ $level }}-content" style="{{ $settings->{"level_{$level}_enabled"} ? '' : 'display: none;' }}">
                                    <div class="mb-3">
                                        <label for="level_{{ $level }}_user_id" class="form-label">Specific User</label>
                                        <select name="level_{{ $level }}_user_id" id="level_{{ $level }}_user_id" class="form-select user-select">
                                            <option value="">Select User (Optional)</option>
                                            @foreach($users as $user)
                                            <option value="{{ $user->id }}" 
                                                {{ $settings->{"level_{$level}_user_id"} == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Assign a specific user to this approval level</div>
                                        @error("level_{$level}_user_id")
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="level_{{ $level }}_role_id" class="form-label">Role-Based Approval</label>
                                        <select name="level_{{ $level }}_role_id" id="level_{{ $level }}_role_id" class="form-select role-select">
                                            <option value="">Select Role (Optional)</option>
                                            @foreach($roles as $role)
                                            <option value="{{ $role->id }}" 
                                                {{ $settings->{"level_{$level}_role_id"} == $role->id ? 'selected' : '' }}>
                                                {{ $role->display_name ?? $role->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Any user with this role can approve at this level</div>
                                        @error("level_{$level}_role_id")
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="alert alert-warning">
                                        <small><i class="bx bx-info-circle me-1"></i>
                                        You can assign either a specific user OR a role, or both. If both are assigned, either the specific user or any user with the role can approve.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($level % 2 == 0 && $level < 5)
                            </div><div class="row">
                        @endif
                        @endfor
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="submit" class="btn btn-primary" onclick="console.log('Submit button clicked')">
                                            <i class="bx bx-save me-2"></i>Save Approval Settings
                                        </button>
                                        <button type="button" class="btn btn-warning" onclick="resetSettings()">
                                            <i class="bx bx-refresh me-2"></i>Reset to Default
                                        </button>
                                        <!--
                                        <button type="button" class="btn btn-info" onclick="testConfiguration()">
                                            <i class="bx bx-test-tube me-2"></i>Test Configuration
                                        </button>
                                        <a href="{{ route('store-requisitions.approval-settings.summary') }}" class="btn btn-secondary">
                                            <i class="bx bx-list-check me-2"></i>View Summary
                                        </a> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Settings Summary -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bx bx-list-check me-2"></i>Current Approval Flow</h6>
            </div>
            <div class="card-body">
                @php
                    $enabledLevels = [];
                    for($i = 1; $i <= 5; $i++) {
                        if($settings->{"level_{$i}_enabled"}) {
                            $enabledLevels[] = $i;
                        }
                    }
                @endphp

                @if(count($enabledLevels) > 0)
                    <div class="approval-flow">
                        @foreach($enabledLevels as $index => $level)
                            <div class="d-flex align-items-center mb-2">
                                <div class="badge bg-primary me-3">{{ $index + 1 }}</div>
                                <div class="flex-grow-1">
                                    <strong>Level {{ $level }}</strong>
                                    <div class="text-muted small">
                                        @if($settings->{"level_{$level}_user_id"})
                                            User: {{ $users->find($settings->{"level_{$level}_user_id"})->name ?? 'Unknown User' }}
                                        @endif
                                        @if($settings->{"level_{$level}_user_id"} && $settings->{"level_{$level}_role_id"})
                                            <span class="text-muted">or</span>
                                        @endif
                                        @if($settings->{"level_{$level}_role_id"})
                                            Role: {{ $roles->find($settings->{"level_{$level}_role_id"})->display_name ?? $roles->find($settings->{"level_{$level}_role_id"})->name ?? 'Unknown Role' }}
                                        @endif
                                    </div>
                                </div>
                                @if($index < count($enabledLevels) - 1)
                                    <i class="bx bx-right-arrow-alt text-muted"></i>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bx bx-info-circle fs-1"></i>
                        <p class="mt-2">No approval levels configured. All store requisitions will be auto-approved.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function toggleLevel(level) {
    const checkbox = document.getElementById(`level_${level}_enabled`);
    const content = document.querySelector(`.level-${level}-content`);
    
    if (checkbox.checked) {
        content.style.display = 'block';
    } else {
        content.style.display = 'none';
        // Clear selections when disabled
        document.getElementById(`level_${level}_user_id`).value = '';
        document.getElementById(`level_${level}_role_id`).value = '';
    }
}

function resetSettings() {
    Swal.fire({
        title: 'Reset Approval Settings?',
        text: "This will disable all approval levels and reset to default settings.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, reset it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit reset form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('store-requisitions.approval-settings.reset') }}";
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function testConfiguration() {
    // Test the current configuration
    Swal.fire({
        title: 'Testing Configuration...',
        text: 'Please wait while we validate your approval settings.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch("{{ route('store-requisitions.approval-settings.test-configuration') }}", {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            Swal.fire('Configuration Valid!', data.message, 'success');
        } else {
            Swal.fire('Configuration Issues Found', data.message, 'warning');
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire('Error!', 'Failed to test configuration. Please try again.', 'error');
    });
}

// Form validation
document.getElementById('approvalSettingsForm').addEventListener('submit', function(e) {
    console.log('Form submission started');
    let hasEnabledLevel = false;
    let hasError = false;
    
    for (let level = 1; level <= 5; level++) {
        const enabled = document.getElementById(`level_${level}_enabled`).checked;
        console.log(`Level ${level} enabled:`, enabled);
        
        if (enabled) {
            hasEnabledLevel = true;
            const userId = document.getElementById(`level_${level}_user_id`).value;
            const roleId = document.getElementById(`level_${level}_role_id`).value;
            console.log(`Level ${level} - User ID: ${userId}, Role ID: ${roleId}`);
            
            if (!userId && !roleId) {
                Swal.fire('Validation Error!', `Level ${level} is enabled but has no user or role assigned.`, 'error');
                hasError = true;
                break;
            }
        }
    }
    
    console.log('Has enabled level:', hasEnabledLevel);
    console.log('Has error:', hasError);
    
    if (hasError) {
        e.preventDefault();
        return false;
    }
    
    console.log('Form submission proceeding');
});

// Initialize Select2 for searchable dropdowns
$(document).ready(function() {
    // Initialize user select dropdowns
    $('.user-select').select2({
        theme: 'bootstrap-5',
        placeholder: 'Search and select user...',
        allowClear: true,
        width: '100%'
    });

    // Initialize role select dropdowns
    $('.role-select').select2({
        theme: 'bootstrap-5',
        placeholder: 'Search and select role...',
        allowClear: true,
        width: '100%'
    });
});
</script>
@endpush