@extends('layouts.main')

@section('title', 'Create New Equipment')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Equipment Master', 'url' => route('rental-event-equipment.equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE NEW EQUIPMENT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-plus me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Add New Equipment Item</h5>
                        </div>
                        <hr />

                        <form action="{{ route('rental-event-equipment.equipment.store') }}" method="POST" id="equipment-create-form">
                            @csrf

                            <!-- 1. Select from inventory (name, cost & quantity auto-fill) -->
                            <div class="card border-success mb-4">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="bx bx-package me-2"></i> Select from inventory</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @if(!empty($inventoryCategories) && $inventoryCategories->isNotEmpty())
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="inventory_category_id" class="form-label fw-bold">Inventory category</label>
                                                <select class="form-select select2-single" id="inventory_category_id">
                                                    <option value="">— All categories —</option>
                                                    @foreach($inventoryCategories as $ic)
                                                        <option value="{{ $ic->id }}" {{ ($selectedInventoryCategoryId ?? '') == $ic->id ? 'selected' : '' }}>{{ $ic->name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="form-text">Filter items by category</div>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="{{ !empty($inventoryCategories) && $inventoryCategories->isNotEmpty() ? 'col-md-8' : 'col-12' }}">
                                            <div class="mb-3">
                                                <label for="item_id" class="form-label fw-bold">Inventory item</label>
                                                <select class="form-select select2-single @error('item_id') is-invalid @enderror" id="item_id" name="item_id" data-placeholder="Search inventory item...">
                                                    <option value="">— Chagua bidhaa kutoka inventory (optional) —</option>
                                                    @foreach($inventoryItems ?? [] as $inv)
                                                        <option value="{{ $inv->id }}" data-name="{{ $inv->name }}" data-code="{{ $inv->code }}" {{ old('item_id') == $inv->id ? 'selected' : '' }}>{{ $inv->code }} - {{ $inv->name }}@if($inv->relationLoaded('category') && $inv->category) ({{ $inv->category->name }})@endif</option>
                                                    @endforeach
                                                </select>
                                                @error('item_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Jina, gharama na quantity available zitajaza moja kwa moja.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Information -->
                            <div class="card border-primary mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i> Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="name" class="form-label fw-bold">Equipment Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                       id="name" name="name" value="{{ old('name') }}"
                                                       placeholder="Inajaza kutoka inventory" required>
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="equipment_code" class="form-label fw-bold">Equipment Code</label>
                                                <input type="text" class="form-control @error('equipment_code') is-invalid @enderror"
                                                       id="equipment_code" name="equipment_code" value="{{ old('equipment_code') }}"
                                                       placeholder="Auto-generated if left empty">
                                                @error('equipment_code')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Leave empty to auto-generate</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="status" class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                                    <option value="available" {{ old('status') == 'available' ? 'selected' : '' }}>Available</option>
                                                    <option value="reserved" {{ old('status') == 'reserved' ? 'selected' : '' }}>Reserved</option>
                                                    <option value="on_rent" {{ old('status') == 'on_rent' ? 'selected' : '' }}>On Rent</option>
                                                    <option value="in_event_use" {{ old('status') == 'in_event_use' ? 'selected' : '' }}>In Event Use</option>
                                                    <option value="under_repair" {{ old('status') == 'under_repair' ? 'selected' : '' }}>Under Repair</option>
                                                    <option value="lost" {{ old('status') == 'lost' ? 'selected' : '' }}>Lost</option>
                                                </select>
                                                @error('status')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label for="description" class="form-label fw-bold">Description</label>
                                                <textarea class="form-control @error('description') is-invalid @enderror"
                                                          id="description" name="description" rows="3"
                                                          placeholder="Enter equipment description...">{{ old('description') }}</textarea>
                                                @error('description')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quantity & Cost Information -->
                            <div class="card border-info mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bx bx-calculator me-2"></i> Quantity & Cost Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="quantity_owned" class="form-label fw-bold">Quantity Owned <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control @error('quantity_owned') is-invalid @enderror"
                                                       id="quantity_owned" name="quantity_owned" value="{{ old('quantity_owned', 0) }}"
                                                       min="0" required readonly>
                                                @error('quantity_owned')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Inajaza kutoka stock ya inventory.</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="replacement_cost" class="form-label fw-bold">Replacement Cost <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control @error('replacement_cost') is-invalid @enderror"
                                                       id="replacement_cost" name="replacement_cost" value="{{ old('replacement_cost', 0) }}"
                                                       min="0" required readonly>
                                                @error('replacement_cost')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Inajaza kutoka cost price ya inventory.</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="rental_rate" class="form-label fw-bold">Rental Rate</label>
                                                <input type="number" step="0.01" class="form-control @error('rental_rate') is-invalid @enderror"
                                                       id="rental_rate" name="rental_rate" value="{{ old('rental_rate') }}"
                                                       min="0">
                                                @error('rental_rate')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="{{ route('rental-event-equipment.equipment.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Equipment
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Create Equipment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var detailsUrl = '{{ url("rental-event-equipment/equipment/inventory-item-details") }}';
    var createUrl = '{{ route("rental-event-equipment.equipment.create") }}';

    $('#inventory_category_id').on('change', function() {
        var val = $(this).val();
        window.location = val ? createUrl + '?inventory_category_id=' + encodeURIComponent(val) : createUrl;
    });

    $('#item_id').on('change', function() {
        var itemId = $(this).val();
        if (!itemId) {
            $('#name').val('');
            $('#replacement_cost').val('0');
            $('#quantity_owned').val('0');
            return;
        }
        $.ajax({
            url: detailsUrl + '/' + itemId,
            method: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .done(function(data) {
            $('#name').val(data.name || '');
            $('#replacement_cost').val(data.cost_price != null ? data.cost_price : '0');
            $('#quantity_owned').val(data.total_stock != null ? Math.max(0, Math.floor(data.total_stock)) : '0');
        })
        .fail(function() {
            var opt = $('#item_id option:selected');
            if (opt.length && opt.data('name')) {
                $('#name').val(opt.data('name'));
            }
        });
    });

    // If already selected (e.g. validation old input), fill fields once
    if ($('#item_id').val()) {
        $('#item_id').trigger('change');
    }
});
</script>
@endpush
