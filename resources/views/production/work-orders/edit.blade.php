@extends('layouts.main')

@section('title', 'Edit Work Order')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Production Management', 'url' => '#', 'icon' => 'bx bx-cog'],
            ['label' => 'Work Orders', 'url' => route('production.work-orders.index'), 'icon' => 'bx bx-list-ul'],
            ['label' => $workOrder->wo_number, 'url' => route('production.work-orders.show', $workOrder->encoded_id), 'icon' => 'bx bx-file'],
            ['label' => 'Edit', 'url' => route('production.work-orders.edit', $workOrder->encoded_id), 'icon' => 'bx bx-edit']
        ]" />

        @if ($errors->any())
            <div class="alert alert-danger">
                <h6><i class="bx bx-error-circle me-2"></i>Validation Errors:</h6>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('production.work-orders.update', $workOrder->encoded_id) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title mb-3">Edit Work Order: {{ $workOrder->wo_number }}</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_id" class="form-label">Customer (Optional)</label>
                                    <select class="form-select" name="customer_id" id="customer_id">
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id', $workOrder->customer_id) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="due_date" name="due_date" value="{{ old('due_date', $workOrder->due_date?->format('Y-m-d')) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="product_name" name="product_name" value="{{ old('product_name', $workOrder->product_name) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="style" class="form-label">Style <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="style" name="style" value="{{ old('style', $workOrder->style) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="requires_logo" name="requires_logo" value="1" {{ old('requires_logo', $workOrder->requires_logo) ? 'checked' : '' }}>
                                <label class="form-check-label" for="requires_logo">
                                    Requires Logo/Embroidery
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $workOrder->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Sizes and Quantities -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Sizes and Quantities</h5>
                        <div id="sizes-container">
                            @php
                                $sizesQuantities = old('sizes_quantities', $workOrder->sizes_quantities ?? []);
                                // Handle case where sizes_quantities is a JSON string
                                if (is_string($sizesQuantities)) {
                                    $sizesQuantities = json_decode($sizesQuantities, true) ?? [];
                                }
                            @endphp
                            @foreach($sizesQuantities as $size => $quantity)
                            <div class="row mb-2 size-row">
                                <div class="col-md-4">
                                    <input type="text" class="form-control size-input" placeholder="Size (e.g., S, M, L)" value="{{ $size }}">
                                </div>
                                <div class="col-md-6">
                                    <input type="number" class="form-control quantity-input" placeholder="Quantity" min="1" value="{{ $quantity }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-sm remove-size">Remove</button>
                                </div>
                            </div>
                            @endforeach
                            @if(empty($sizesQuantities))
                            <div class="row mb-2 size-row">
                                <div class="col-md-4">
                                    <input type="text" class="form-control size-input" placeholder="Size (e.g., S, M, L)">
                                </div>
                                <div class="col-md-6">
                                    <input type="number" class="form-control quantity-input" placeholder="Quantity" min="1">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-sm remove-size">Remove</button>
                                </div>
                            </div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="add-size">Add Size</button>
                        <input type="hidden" name="sizes_quantities" id="sizes_quantities">
                    </div>
                </div>
                </div>

                <div class="col-xl-4">
                <!-- Bill of Materials -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Bill of Materials (BOM)</h5>
                        <div id="bom-container">
                            @foreach($workOrder->bom as $index => $bomItem)
                            <div class="bom-item mb-3">
                                <div class="row mb-2">
                                    <div class="col-md-12">
                                        <label class="form-label">Material</label>
                                        <select class="form-select material-select" name="bom[{{ $index }}][material_id]" required>
                                            <option value="">Select Material</option>
                                            @foreach($materials as $material)
                                                <option value="{{ $material->id }}" {{ $bomItem->material_item_id == $material->id ? 'selected' : '' }}>
                                                    {{ $material->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Type</label>
                                        <select class="form-select" name="bom[{{ $index }}][material_type]" required>
                                            <option value="yarn" {{ $bomItem->material_type == 'yarn' ? 'selected' : '' }}>Yarn</option>
                                            <option value="thread" {{ $bomItem->material_type == 'thread' ? 'selected' : '' }}>Thread</option>
                                            <option value="accessory" {{ $bomItem->material_type == 'accessory' ? 'selected' : '' }}>Accessory</option>
                                            <option value="packaging" {{ $bomItem->material_type == 'packaging' ? 'selected' : '' }}>Packaging</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Unit</label>
                                        <select class="form-select" name="bom[{{ $index }}][unit]" required>
                                            <option value="kg" {{ $bomItem->unit_of_measure == 'kg' ? 'selected' : '' }}>Kg</option>
                                            <option value="meters" {{ $bomItem->unit_of_measure == 'meters' ? 'selected' : '' }}>Meters</option>
                                            <option value="pieces" {{ $bomItem->unit_of_measure == 'pieces' ? 'selected' : '' }}>Pieces</option>
                                            <option value="rolls" {{ $bomItem->unit_of_measure == 'rolls' ? 'selected' : '' }}>Rolls</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" class="form-control" name="bom[{{ $index }}][quantity]" step="0.001" min="0.001" value="{{ $bomItem->required_quantity }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Variance %</label>
                                        <input type="number" class="form-control" name="bom[{{ $index }}][variance]" min="0" max="100" value="{{ $bomItem->variance_allowed }}">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm remove-bom">Remove</button>
                                <hr>
                            </div>
                            @endforeach
                            @if($workOrder->bom->isEmpty())
                            <div class="bom-item mb-3">
                                <div class="row mb-2">
                                    <div class="col-md-12">
                                        <label class="form-label">Material</label>
                                        <select class="form-select material-select" name="bom[0][material_id]" required>
                                            <option value="">Select Material</option>
                                            @foreach($materials as $material)
                                                <option value="{{ $material->id }}">{{ $material->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Type</label>
                                        <select class="form-select" name="bom[0][material_type]" required>
                                            <option value="">Select Type</option>
                                            <option value="yarn">Yarn</option>
                                            <option value="thread">Thread</option>
                                            <option value="accessory">Accessory</option>
                                            <option value="packaging">Packaging</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Unit</label>
                                        <select class="form-select" name="bom[0][unit]" required>
                                            <option value="">Select Unit</option>
                                            <option value="kg">Kg</option>
                                            <option value="meters">Meters</option>
                                            <option value="pieces">Pieces</option>
                                            <option value="rolls">Rolls</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" class="form-control" name="bom[0][quantity]" step="0.001" min="0.001" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Variance %</label>
                                        <input type="number" class="form-control" name="bom[0][variance]" min="0" max="100" value="5">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm remove-bom">Remove</button>
                                <hr>
                            </div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="add-bom">Add Material</button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="card">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-success btn-lg">Update Work Order</button>
                        <a href="{{ route('production.work-orders.show', $workOrder->encoded_id) }}" class="btn btn-secondary btn-lg">Cancel</a>
                    </div>
                </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let bomIndex = {{ $workOrder->bom->count() }};

    // Add size functionality
    $('#add-size').click(function() {
        const newSizeRow = `
            <div class="row mb-2 size-row">
                <div class="col-md-4">
                    <input type="text" class="form-control size-input" placeholder="Size (e.g., S, M, L)">
                </div>
                <div class="col-md-6">
                    <input type="number" class="form-control quantity-input" placeholder="Quantity" min="1">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-size">Remove</button>
                </div>
            </div>
        `;
        $('#sizes-container').append(newSizeRow);
    });

    // Remove size functionality
    $(document).on('click', '.remove-size', function() {
        if ($('.size-row').length > 1) {
            $(this).closest('.size-row').remove();
        }
    });

    // Add BOM functionality
    $('#add-bom').click(function() {
        const newBomItem = `
            <div class="bom-item mb-3">
                <div class="row mb-2">
                    <div class="col-md-12">
                        <label class="form-label">Material</label>
                        <select class="form-select material-select" name="bom[${bomIndex}][material_id]" required>
                            <option value="">Select Material</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="bom[${bomIndex}][material_type]" required>
                            <option value="">Select Type</option>
                            <option value="yarn">Yarn</option>
                            <option value="thread">Thread</option>
                            <option value="accessory">Accessory</option>
                            <option value="packaging">Packaging</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Unit</label>
                        <select class="form-select" name="bom[${bomIndex}][unit]" required>
                            <option value="">Select Unit</option>
                            <option value="kg">Kg</option>
                            <option value="meters">Meters</option>
                            <option value="pieces">Pieces</option>
                            <option value="rolls">Rolls</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="bom[${bomIndex}][quantity]" step="0.001" min="0.001" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Variance %</label>
                        <input type="number" class="form-control" name="bom[${bomIndex}][variance]" min="0" max="100" value="5">
                    </div>
                </div>
                <button type="button" class="btn btn-danger btn-sm remove-bom">Remove</button>
                <hr>
            </div>
        `;
        $('#bom-container').append(newBomItem);
        bomIndex++;
    });

    // Remove BOM functionality
    $(document).on('click', '.remove-bom', function() {
        if ($('.bom-item').length > 1) {
            $(this).closest('.bom-item').remove();
        }
    });

    // Form submission - collect sizes and quantities
    $('form').submit(function() {
        const sizesQuantities = {};
        $('.size-row').each(function() {
            const size = $(this).find('.size-input').val().trim();
            const quantity = $(this).find('.quantity-input').val();
            if (size && quantity) {
                sizesQuantities[size] = parseInt(quantity);
            }
        });
        $('#sizes_quantities').val(JSON.stringify(sizesQuantities));
    });
});
</script>
@endpush