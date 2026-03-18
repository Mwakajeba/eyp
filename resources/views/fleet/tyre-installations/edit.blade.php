@extends('layouts.main')

@section('title', 'Edit Tyre Installation - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Tyre Installation', 'url' => route('fleet.tyre-installations.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Edit Installation #' . $installation->id, 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Tyre Installation</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.tyre-installations.update', $installation) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tyre <span class="text-danger">*</span></label>
                            <select name="tyre_id" class="form-select select2-single" required>
                                <option value="">— Select tyre —</option>
                                @foreach($tyres as $t)
                                    <option value="{{ $t->id }}" {{ old('tyre_id', $installation->tyre_id) == $t->id ? 'selected' : '' }}>{{ $t->tyre_serial }} — {{ $t->brand ?? 'N/A' }} ({{ $t->status }})</option>
                                @endforeach
                            </select>
                            @error('tyre_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                            <select name="vehicle_id" class="form-select select2-single" required>
                                <option value="">— Select vehicle —</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}" {{ old('vehicle_id', $installation->vehicle_id) == $v->id ? 'selected' : '' }}>{{ $v->name ?? $v->registration_number }} ({{ $v->registration_number ?? $v->id }})</option>
                                @endforeach
                            </select>
                            @error('vehicle_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tyre position <span class="text-danger">*</span></label>
                            <select name="tyre_position_id" class="form-select select2-single" required>
                                <option value="">— Select position —</option>
                                @foreach($positions as $p)
                                    <option value="{{ $p->id }}" {{ old('tyre_position_id', $installation->tyre_position_id) == $p->id ? 'selected' : '' }}>{{ $p->position_name }} ({{ $p->position_code ?? $p->id }})</option>
                                @endforeach
                            </select>
                            @error('tyre_position_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Installation date <span class="text-danger">*</span></label>
                            <input type="date" name="installed_at" class="form-control" value="{{ old('installed_at', $installation->installed_at?->format('Y-m-d')) }}" required>
                            @error('installed_at')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Odometer at install</label>
                            <input type="number" name="odometer_at_install" class="form-control" value="{{ old('odometer_at_install', $installation->odometer_at_install) }}" min="0" step="0.01" placeholder="km">
                            @error('odometer_at_install')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Installer type</label>
                            <select name="installer_type" class="form-select select2-single">
                                <option value="">—</option>
                                <option value="garage" {{ old('installer_type', $installation->installer_type) === 'garage' ? 'selected' : '' }}>Garage</option>
                                <option value="internal" {{ old('installer_type', $installation->installer_type) === 'internal' ? 'selected' : '' }}>Internal</option>
                            </select>
                            @error('installer_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Installer name</label>
                            <input type="text" name="installer_name" class="form-control" value="{{ old('installer_name', $installation->installer_name) }}" maxlength="255">
                            @error('installer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" maxlength="1000">{{ old('notes', $installation->notes) }}</textarea>
                            @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success"><i class="bx bx-save me-1"></i>Update</button>
                            <a href="{{ route('fleet.tyre-installations.show', $installation) }}" class="btn btn-secondary">Cancel</a>
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
});
</script>
@endpush
