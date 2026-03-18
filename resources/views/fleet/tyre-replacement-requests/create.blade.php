@extends('layouts.main')

@section('title', 'New Tyre Replacement Request - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Tyre Replacement Requests', 'url' => route('fleet.tyre-replacement-requests.index'), 'icon' => 'bx bx-error-circle'],
            ['label' => 'New Request', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>New Tyre Replacement Request</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.tyre-replacement-requests.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                            <select name="vehicle_id" class="form-select select2-single" required>
                                <option value="">— Select vehicle —</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}" {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->name ?? $v->registration_number }} ({{ $v->registration_number ?? $v->id }})</option>
                                @endforeach
                            </select>
                            @error('vehicle_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tyre position <span class="text-danger">*</span></label>
                            <select name="tyre_position_id" class="form-select select2-single" required>
                                <option value="">— Select position —</option>
                                @foreach($positions as $p)
                                    <option value="{{ $p->id }}" {{ old('tyre_position_id') == $p->id ? 'selected' : '' }}>{{ $p->position_name }} ({{ $p->position_code ?? $p->id }})</option>
                                @endforeach
                            </select>
                            @error('tyre_position_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12" id="current-installation-box" style="display: none;">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white py-2">
                                    <strong><i class="bx bx-info-circle me-1"></i>Current installation at this position (for comparison)</strong>
                                </div>
                                <div class="card-body py-3" id="current-installation-body">
                                    <p class="mb-0 text-muted">Select vehicle and tyre position to load.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <select name="reason" class="form-select select2-single" required>
                                <option value="worn_out" {{ old('reason', 'worn_out') === 'worn_out' ? 'selected' : '' }}>Worn out</option>
                                <option value="burst" {{ old('reason') === 'burst' ? 'selected' : '' }}>Burst</option>
                                <option value="side_cut" {{ old('reason') === 'side_cut' ? 'selected' : '' }}>Side cut</option>
                                <option value="other" {{ old('reason') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('reason')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mileage at request (km)</label>
                            <input type="number" name="mileage_at_request" class="form-control" value="{{ old('mileage_at_request') }}" min="0" step="0.01" placeholder="Current truck odometer">
                            @error('mileage_at_request')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" maxlength="2000" placeholder="Optional notes or photo references">{{ old('notes') }}</textarea>
                            @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-danger"><i class="bx bx-save me-1"></i>Submit Request</button>
                            <a href="{{ route('fleet.tyre-replacement-requests.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
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
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-single').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Type to search...', allowClear: true, minimumResultsForSearch: 0 });
    }
    function loadInstallationDetails() {
        var v = $('select[name="vehicle_id"]').val();
        var p = $('select[name="tyre_position_id"]').val();
        var box = $('#current-installation-box');
        var body = $('#current-installation-body');
        if (!v || !p) {
            box.hide();
            return;
        }
        $.get('{{ route("fleet.tyre-replacement-requests.installation-details") }}', { vehicle_id: v, tyre_position_id: p }, function(data) {
            if (data.installation) {
                var i = data.installation;
                var html = '<div class="row g-2 small">' +
                    '<div class="col-md-4"><strong>Tyre:</strong> ' + (i.tyre_serial || '—') + ' ' + (i.tyre_brand || '') + '</div>' +
                    '<div class="col-md-4"><strong>Installed:</strong> ' + (i.installed_at || '—') + '</div>' +
                    '<div class="col-md-4"><strong>Odometer at install:</strong> ' + (i.odometer_at_install != null ? Number(i.odometer_at_install).toLocaleString() + ' km' : '—') + '</div>' +
                    '<div class="col-md-4"><strong>Expected lifespan:</strong> ' + (i.tyre_expected_lifespan_km != null ? Number(i.tyre_expected_lifespan_km).toLocaleString() + ' km' : '—') + '</div>' +
                    '<div class="col-md-4"><strong>Installer:</strong> ' + (i.installer_name || i.installer_type || '—') + '</div>' +
                    '</div>';
                body.html(html);
                box.show();
            } else {
                body.html('<p class="mb-0 text-muted">No current installation at this position.</p>');
                box.show();
            }
        }).fail(function() {
            body.html('<p class="mb-0 text-danger">Could not load installation details.</p>');
            box.show();
        });
    }
    $('select[name="vehicle_id"], select[name="tyre_position_id"]').on('change', loadInstallationDetails);
    if ($('select[name="vehicle_id"]').val() && $('select[name="tyre_position_id"]').val()) loadInstallationDetails();
});
</script>
@endpush
