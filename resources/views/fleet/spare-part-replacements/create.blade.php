@extends('layouts.main')

@section('title', 'Record Spare Part Replacement - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Spare Parts Replacement', 'url' => route('fleet.spare-part-replacements.index'), 'icon' => 'bx bx-refresh'],
            ['label' => 'Add Replacement', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-orange text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Record Spare Part Replacement (Vipuri)</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.spare-part-replacements.store') }}">
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
                            <label class="form-label">Spare part category <span class="text-danger">*</span></label>
                            <select name="spare_part_category_id" class="form-select select2-single" required>
                                <option value="">— Select category —</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" {{ old('spare_part_category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code ?? $c->id }})</option>
                                @endforeach
                            </select>
                            @if($categories->isEmpty())
                                <small class="text-muted">No categories. Add spare part categories first.</small>
                            @endif
                            @error('spare_part_category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12" id="last-replacement-box" style="display: none;">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white py-2">
                                    <strong><i class="bx bx-history me-1"></i>Last replacement (for comparison)</strong>
                                </div>
                                <div class="card-body py-2" id="last-replacement-body"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Replaced at (date) <span class="text-danger">*</span></label>
                            <input type="date" name="replaced_at" class="form-control" value="{{ old('replaced_at', date('Y-m-d')) }}" required>
                            @error('replaced_at')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Odometer at replacement (km)</label>
                            <input type="number" name="odometer_at_replacement" class="form-control" value="{{ old('odometer_at_replacement') }}" min="0" step="0.01" placeholder="km">
                            @error('odometer_at_replacement')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cost</label>
                            <input type="number" name="cost" class="form-control" value="{{ old('cost') }}" min="0" step="0.01" placeholder="0">
                            @error('cost')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason / notes</label>
                            <textarea name="reason" class="form-control" rows="2" maxlength="1000" placeholder="Reason for replacement or garage report">{{ old('reason') }}</textarea>
                            @error('reason')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-orange"><i class="bx bx-save me-1"></i>Record Replacement</button>
                            <a href="{{ route('fleet.spare-part-replacements.index') }}" class="btn btn-secondary">Cancel</a>
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
    function loadLastReplacement() {
        var vid = $('select[name="vehicle_id"]').val();
        var cid = $('select[name="spare_part_category_id"]').val();
        var $box = $('#last-replacement-box');
        var $body = $('#last-replacement-body');
        if (!vid || !cid) {
            $box.hide();
            return;
        }
        $.get('{{ route("fleet.spare-part-replacements.last-replacement-details") }}', { vehicle_id: vid, spare_part_category_id: cid })
            .done(function(r) {
                if (r.found) {
                    var html = '<table class="table table-sm table-borderless mb-0"><tr><td class="text-muted">Replaced at</td><td>' + (r.replaced_at || '—') + '</td></tr>';
                    html += '<tr><td class="text-muted">Odometer</td><td>' + (r.odometer_at_replacement ? r.odometer_at_replacement + ' km' : '—') + '</td></tr>';
                    html += '<tr><td class="text-muted">Cost</td><td>' + (r.cost ? r.cost : '—') + '</td></tr>';
                    if (r.reason) html += '<tr><td class="text-muted">Reason</td><td>' + r.reason + '</td></tr>';
                    html += '</table>';
                    $body.html(html);
                } else {
                    $body.html('<p class="mb-0 text-muted">' + (r.message || 'No previous replacement for this vehicle and category.') + '</p>');
                }
                $box.show();
            })
            .fail(function() {
                $body.html('<p class="mb-0 text-muted">Could not load last replacement.</p>');
                $box.show();
            });
    }
    $('select[name="vehicle_id"], select[name="spare_part_category_id"]').on('change', loadLastReplacement);
    if ($('select[name="vehicle_id"]').val() && $('select[name="spare_part_category_id"]').val()) loadLastReplacement();
});
</script>
@endpush
