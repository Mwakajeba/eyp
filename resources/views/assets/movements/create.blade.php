@extends('layouts.main')

@section('title', 'Initiate Asset Movement')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-archive'],
            ['label' => 'Movements', 'url' => route('assets.movements.index'), 'icon' => 'bx bx-transfer-alt'],
            ['label' => 'Initiate', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card border-top border-0 border-4 border-primary">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4">
                    <div><i class="bx bx-transfer me-2 font-22 text-primary"></i></div>
                    <h5 class="mb-0 text-primary">Initiate Asset Movement</h5>
                </div>
                <hr>
                
                <form method="POST" action="{{ route('assets.movements.store') }}" class="needs-validation" novalidate>
                    @csrf
                    <div class="row g-4">
                        <!-- Left Column: Form Fields -->
                        <div class="col-lg-8">
                            <!-- Asset Selection -->
                            <div class="card shadow-sm mb-3">
                                <div class="card-header bg-light">
                                    <strong><i class="bx bx-cube me-1 text-primary"></i> Asset Selection</strong>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Asset <span class="text-danger">*</span></label>
                                        <select name="asset_id" id="asset_id" class="form-select select2-single" required>
                                            <option value="">Select asset...</option>
                                            @foreach($assets as $a)
                                            <option value="{{ $a->id }}" data-code="{{ $a->code }}" data-branch="{{ $a->branch_id }}" data-dept="{{ $a->department_id }}" data-user="{{ $a->custodian_user_id }}">{{ $a->name }} ({{ $a->code }})</option>
                                            @endforeach
                                        </select>
                                        @error('asset_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        <small class="text-muted"><i class="bx bx-info-circle me-1"></i> Disposed or inactive assets cannot be moved.</small>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label fw-semibold">Reason for Movement</label>
                                        <input type="text" name="reason" class="form-control" placeholder="e.g., Change of custodian, Relocation, Department transfer" value="{{ old('reason') }}">
                                        <small class="text-muted">Optional: Provide a brief reason for this movement.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Transfer Details -->
                            <div class="card shadow-sm mb-3">
                                <div class="card-header bg-light">
                                    <strong><i class="bx bx-map me-1 text-primary"></i> Transfer Destination</strong>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">To Branch</label>
                                            <select name="to_branch_id" id="to_branch_id" class="form-select select2-single">
                                                <option value="">No change</option>
                                                @foreach(\App\Models\Branch::orderBy('name')->get(['id','name']) as $b)
                                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Select a branch to enable department and user options.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">To Department</label>
                                            <select name="to_department_id" id="to_department_id" class="form-select select2-single" disabled>
                                                <option value="">No change</option>
                                            </select>
                                            <small class="text-muted">Departments will load based on selected branch.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">To User (Custodian)</label>
                                            <select name="to_user_id" id="to_user_id" class="form-select select2-single" disabled>
                                                <option value="">No change</option>
                                            </select>
                                            <small class="text-muted">Users will load based on selected branch.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Post Reclass to GL</label>
                                            <select name="gl_post" id="gl_post" class="form-select">
                                                <option value="0" selected>No</option>
                                                <option value="1">Yes</option>
                                            </select>
                                            <small class="text-muted">Enable for cost center reclassification.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- GL Posting Information -->
                            <div class="alert alert-info mb-3" id="gl-info" style="display: none;">
                                <div class="d-flex align-items-start">
                                    <i class="bx bx-info-circle me-2 fs-5"></i>
                                    <div>
                                        <strong>GL Reclassification:</strong>
                                        <p class="mb-0 small">When enabled, the system will post a journal entry to reclassify the asset's Net Book Value (NBV) from the old cost center to the new one. This is typically used when moving assets between departments or branches for accounting purposes.</p>
                                        <p class="mb-0 small mt-1"><strong>Amount:</strong> Current NBV (Cost - Accumulated Depreciation) as of movement date.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Preview Panel -->
                        <div class="col-lg-4">
                            <div class="card shadow-sm sticky-top" style="top: 20px;">
                                <div class="card-header bg-primary text-white">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <strong><i class="bx bx-show me-1"></i> Movement Preview</strong>
                                        <span class="badge bg-light text-dark" id="preview-badge">Awaiting</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Asset Info -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <div class="d-flex align-items-start">
                                            <div class="me-2"><i class="bx bx-cube fs-4 text-primary"></i></div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold text-dark" id="preview-asset">—</div>
                                                <small class="text-muted" id="preview-code">—</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Current Location -->
                                    <div class="mb-3">
                                        <small class="text-muted text-uppercase fw-semibold d-block mb-2">Current Location</small>
                                        <div class="bg-light rounded p-2 mb-2">
                                            <small class="text-muted d-block">Branch</small>
                                            <div id="preview-from-branch">—</div>
                                        </div>
                                        <div class="bg-light rounded p-2 mb-2">
                                            <small class="text-muted d-block">Department</small>
                                            <div id="preview-from-dept">—</div>
                                        </div>
                                        <div class="bg-light rounded p-2">
                                            <small class="text-muted d-block">Custodian</small>
                                            <div id="preview-from-user">—</div>
                                        </div>
                                    </div>

                                    <!-- Arrow -->
                                    <div class="text-center my-2">
                                        <i class="bx bx-down-arrow-alt fs-3 text-primary"></i>
                                    </div>

                                    <!-- New Location -->
                                    <div class="mb-3">
                                        <small class="text-muted text-uppercase fw-semibold d-block mb-2">New Location</small>
                                        <div class="border border-primary rounded p-2 mb-2">
                                            <small class="text-muted d-block">Branch</small>
                                            <div class="fw-semibold" id="preview-branch">No change</div>
                                        </div>
                                        <div class="border border-primary rounded p-2 mb-2">
                                            <small class="text-muted d-block">Department</small>
                                            <div class="fw-semibold" id="preview-dept">No change</div>
                                        </div>
                                        <div class="border border-primary rounded p-2">
                                            <small class="text-muted d-block">Custodian</small>
                                            <div class="fw-semibold" id="preview-user">No change</div>
                                        </div>
                                    </div>

                                    <!-- GL Status -->
                                    <div class="mt-3 pt-3 border-top">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <small class="text-muted">GL Posting</small>
                                            <span class="badge bg-secondary" id="preview-gl">Disabled</span>
                                        </div>
                                    </div>

                                    <!-- Reason -->
                                    <div class="mt-2" id="preview-reason-container" style="display: none;">
                                        <small class="text-muted d-block mb-1">Reason</small>
                                        <div class="bg-light rounded p-2" id="preview-reason">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 d-flex gap-2 justify-content-end">
                        <a href="{{ route('assets.movements.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-check me-1"></i>Initiate Movement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(function(){
    $('.select2-single').select2({ theme:'bootstrap-5', width:'100%' });

    const $asset = $('#asset_id');
    const $branch = $('#to_branch_id');
    const $dept = $('#to_department_id');
    const $user = $('#to_user_id');
    const $glPost = $('#gl_post');
    const $reason = $('input[name="reason"]');

    // Store asset data for preview
    let assetData = {};

    function resetDependent($select, placeholder){
        $select.prop('disabled', true);
        $select.html(`<option value="">${placeholder}</option>`).trigger('change');
    }

    function updatePreview(){
        const assetText = $asset.find('option:selected').text() || '—';
        const assetCode = $asset.find('option:selected').data('code') || '—';
        const branchText = $branch.find('option:selected').text() || 'No change';
        const deptText = $dept.find('option:selected').text() || 'No change';
        const userText = $user.find('option:selected').text() || 'No change';
        const glPostValue = $glPost.val() === '1';
        const reasonText = $reason.val() || '';

        // Asset preview
        $('#preview-asset').text(assetText === 'Select asset...' ? '—' : assetText);
        $('#preview-code').text(assetCode === '—' ? '—' : `Code: ${assetCode}`);

        // Current location (from asset data)
        $('#preview-from-branch').text(assetData.branch || '—');
        $('#preview-from-dept').text(assetData.dept || '—');
        $('#preview-from-user').text(assetData.user || '—');

        // New location
        $('#preview-branch').text(branchText === 'No change' ? 'No change' : branchText);
        $('#preview-dept').text(deptText === 'No change' ? 'No change' : deptText);
        $('#preview-user').text(userText === 'No change' ? 'No change' : userText);

        // GL status
        $('#preview-gl').text(glPostValue ? 'Enabled' : 'Disabled')
            .toggleClass('bg-success', glPostValue)
            .toggleClass('bg-secondary', !glPostValue);

        // Badge
        if ($asset.val()) {
            $('#preview-badge').text('Ready').removeClass('bg-secondary').addClass('bg-success');
        } else {
            $('#preview-badge').text('Awaiting').removeClass('bg-success').addClass('bg-secondary');
        }

        // Reason
        if (reasonText) {
            $('#preview-reason').text(reasonText);
            $('#preview-reason-container').show();
        } else {
            $('#preview-reason-container').hide();
        }

        // GL info alert
        if (glPostValue) {
            $('#gl-info').slideDown();
        } else {
            $('#gl-info').slideUp();
        }
    }

    // Asset selection - load current location
    $asset.on('change', function(){
        const assetId = $(this).val();
        if (assetId) {
            // Initialize assetData if needed
            if (!assetData) {
                assetData = {};
            }
            
            // Fetch asset details including current location
            $.get("{{ route('assets.movements.lookup.asset-details') }}", { asset_id: assetId })
                .done(function(data){
                    console.log('Asset details received:', data);
                    assetData.branch = (data && data.branch) ? data.branch : '—';
                    assetData.dept = (data && data.department) ? data.department : '—';
                    assetData.user = (data && data.custodian) ? data.custodian : '—';
                    updatePreview();
                })
                .fail(function(xhr, status, error){
                    console.error('Failed to fetch asset details:', status, error);
                    assetData = { branch: '—', dept: '—', user: '—' };
                    updatePreview();
                });
        } else {
            assetData = {};
            updatePreview();
        }
    });

    // Branch change - load departments and users
    $branch.on('change', function(){
        const branchId = $(this).val();
        resetDependent($dept, 'No change');
        resetDependent($user, 'No change');
        updatePreview();
        
        if (!branchId) return;

        // Load departments
        $.get("{{ route('assets.movements.lookup.departments') }}", { branch_id: branchId })
            .done(function(rows){
                $dept.prop('disabled', false);
                let opts = '<option value="">No change</option>';
                (rows || []).forEach(r => { opts += `<option value="${r.id}">${r.name}</option>`; });
                $dept.html(opts).trigger('change');
            });

        // Load users for the branch
        $.get("{{ route('assets.movements.lookup.users') }}", { branch_id: branchId })
            .done(function(rows){
                $user.prop('disabled', false);
                let opts = '<option value="">No change</option>';
                (rows || []).forEach(r => { opts += `<option value="${r.id}">${r.name}</option>`; });
                $user.html(opts).trigger('change');
            });
    });

    // Update preview on any change
    $dept.on('change', updatePreview);
    $user.on('change', updatePreview);
    $glPost.on('change', updatePreview);
    $reason.on('input', updatePreview);

    // Initial preview
    updatePreview();
});
</script>
@endpush
@endsection
