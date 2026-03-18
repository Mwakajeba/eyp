@extends('layouts.main')
@section('content')
@section('title', isset($batch) ? 'Edit Production Batch' : 'Create Production Batch')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Production Batches', 'url' => route('production.batches.index'), 'icon' => 'bx bx-package'],
            ['label' => isset($batch) ? 'Edit Batch' : 'Create Batch', 'url' => '#', 'icon' => isset($batch) ? 'bx bx-edit' : 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">{{ isset($batch) ? 'EDIT' : 'CREATE' }} PRODUCTION BATCH</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form action="{{ isset($batch) ? route('production.batches.update', Vinkla\Hashids\Facades\Hashids::encode($batch->id)) : route('production.batches.store') }}" method="POST">
                    @csrf
                    @if(isset($batch))
                        @method('PUT')
                    @endif
                    <div class="form-group mb-3">
                        <label for="batch_number">Batch Number</label>
                        <input type="text" name="batch_number" id="batch_number" class="form-control" value="{{ old('batch_number', $batch->batch_number ?? '') }}" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="item_id">Item</label>
                        <select name="item_id" id="item_id" class="form-control select2" required>
                            <option value="">Select Item</option>
                            @foreach(App\Models\Inventory\Item::all() as $item)
                                <option value="{{ $item->id }}" {{ (old('item_id', $batch->item_id ?? '') == $item->id) ? 'selected' : '' }}>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="quantity_planned">Quantity Planned</label>
                        <input type="number" name="quantity_planned" id="quantity_planned" class="form-control" value="{{ old('quantity_planned', $batch->quantity_planned ?? '') }}">
                    </div>
                    <div class="form-group mb-3">
                        <label for="quantity_produced">Quantity Produced</label>
                        <input type="number" name="quantity_produced" id="quantity_produced" class="form-control" value="{{ old('quantity_produced', $batch->quantity_produced ?? 0) }}">
                    </div>
                    <div class="form-group mb-3">
                        <label for="quantity_defective">Quantity Defective</label>
                        <input type="number" name="quantity_defective" id="quantity_defective" class="form-control" value="{{ old('quantity_defective', $batch->quantity_defective ?? 0) }}">
                    </div>
                    <div class="form-group mb-3">
                        <label for="start_date">Start Date</label>
                        <input type="datetime-local" name="start_date" id="start_date" class="form-control" value="{{ old('start_date', isset($batch) ? $batch->start_date : '') }}">
                    </div>
                    <div class="form-group mb-3">
                        <label for="end_date">End Date</label>
                        <input type="datetime-local" name="end_date" id="end_date" class="form-control" value="{{ old('end_date', isset($batch) ? $batch->end_date : '') }}">
                    </div>
                    <div class="form-group mb-3">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="planned" {{ old('status', $batch->status ?? '') == 'planned' ? 'selected' : '' }}>Planned</option>
                            <option value="in_progress" {{ old('status', $batch->status ?? '') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ old('status', $batch->status ?? '') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ old('status', $batch->status ?? '') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ isset($batch) ? 'Update' : 'Save' }}</button>
                    <a href="{{ route('production.batches.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(function() {
    $('#item_id.select2').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select Item',
        allowClear: true,
        width: '100%'
    });
});
</script>
@endpush
@endsection
