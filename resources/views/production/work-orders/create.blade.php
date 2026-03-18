@extends('layouts.main')

@section('title', 'Create Work Order')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Production Management', 'url' => '#', 'icon' => 'bx bx-cog'],
            ['label' => 'Work Orders', 'url' => route('production.work-orders.index'), 'icon' => 'bx bx-list-ul'],
            ['label' => 'Create', 'url' => route('production.work-orders.create'), 'icon' => 'bx bx-plus']
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

        <form method="POST" action="{{ route('production.work-orders.store') }}">
            @csrf
            <div class="row">
                <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title mb-3">Work Order Details</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_id" class="form-label">Customer (Optional)</label>
                                    <select class="form-select" name="customer_id" id="customer_id">
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="due_date" id="due_date" 
                                           value="{{ old('due_date') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="product_name" id="product_name" 
                                           value="{{ old('product_name', 'SWEATER') }}" required placeholder="e.g., SWEATER">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="style" class="form-label">Style <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="style" id="style" 
                                           value="{{ old('style') }}" required placeholder="e.g., Crew Neck, V-Neck">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="requires_logo" id="requires_logo" 
                                               value="1" {{ old('requires_logo') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="requires_logo">
                                            Requires Logo/Embroidery
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="notes" rows="3" 
                                      placeholder="Special instructions, color requirements, etc.">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Sizes and Quantities -->
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title mb-3">Sizes & Quantities</h4>
                        
                        <div id="sizes-container">
                            <div class="row size-row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Size</label>
                                        <select class="form-select" name="sizes_quantities[0][size]" required>
                                            <option value="">Select Size</option>
                                            <option value="XS">XS</option>
                                            <option value="S">S</option>
                                            <option value="M">M</option>
                                            <option value="L">L</option>
                                            <option value="XL">XL</option>
                                            <option value="XXL">XXL</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" class="form-control" name="sizes_quantities[0][quantity]" 
                                               min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-danger remove-size">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" id="add-size" class="btn btn-sm btn-success">
                            <i class="mdi mdi-plus"></i> Add Size
                        </button>
                    </div>
                </div>

                <!-- Bill of Materials -->
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title mb-3">Bill of Materials (BOM)</h4>
                        
                        <div id="bom-container">
                            <div class="row bom-row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Material</label>
                                        <select class="form-select" name="bom[0][material_id]" required>
                                            <option value="">Select Material</option>
                                            @foreach($materials as $material)
                                                <option value="{{ $material->id }}">{{ $material->name }} ({{ $material->code }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label">Type</label>
                                        <select class="form-select" name="bom[0][material_type]" required>
                                            <option value="yarn">Yarn</option>
                                            <option value="thread">Thread</option>
                                            <option value="labels">Labels</option>
                                            <option value="trims">Trims</option>
                                            <option value="packaging">Packaging</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" class="form-control" name="bom[0][quantity]" 
                                               step="0.001" min="0.001" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label">Unit</label>
                                        <select class="form-select" name="bom[0][unit]" required>
                                            <option value="kg">kg</option>
                                            <option value="pcs">pcs</option>
                                            <option value="m">m</option>
                                            <option value="cones">cones</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label">Variance %</label>
                                        <input type="number" class="form-control" name="bom[0][variance]" 
                                               step="0.1" min="0" max="100" value="5">
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-danger remove-bom">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" id="add-bom" class="btn btn-sm btn-success">
                            <i class="mdi mdi-plus"></i> Add Material
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title mb-3">Production Process</h4>
                        
                        <div class="process-flow">
                            <div class="process-step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h6>Planning</h6>
                                    <p class="text-muted mb-0">Create WO with style, sizes, quantities, due date. Confirm BOM and capacity.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h6>Material Issue</h6>
                                    <p class="text-muted mb-0">Issue yarn cones with lot numbers from Stores to WIP.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h6>Knitting</h6>
                                    <p class="text-muted mb-0">Knit panels according to gauge and size specifications.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h6>Cutting</h6>
                                    <p class="text-muted mb-0">Trim/shape panels to pattern.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">5</div>
                                <div class="step-content">
                                    <h6>Joining/Stitching</h6>
                                    <p class="text-muted mb-0">Assemble sweater pieces using thread.</p>
                                </div>
                            </div>
                            
                            <div class="process-step embroidery-step" style="display: none;">
                                <div class="step-number">5A</div>
                                <div class="step-content">
                                    <h6>Embroidery</h6>
                                    <p class="text-muted mb-0">Add logo/embroidery if required.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">6</div>
                                <div class="step-content">
                                    <h6>Ironing/Finishing</h6>
                                    <p class="text-muted mb-0">Pressing, loose-thread removal, measurement checks.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">7</div>
                                <div class="step-content">
                                    <h6>Quality Check</h6>
                                    <p class="text-muted mb-0">Inspect according to checklist.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">8</div>
                                <div class="step-content">
                                    <h6>Packaging</h6>
                                    <p class="text-muted mb-0">Bagging, cartonizing, labeling.</p>
                                </div>
                            </div>
                            
                            <div class="process-step">
                                <div class="step-number">9</div>
                                <div class="step-content">
                                    <h6>Dispatch</h6>
                                    <p class="text-muted mb-0">Ready for delivery to customer.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="mdi mdi-check"></i> Create Work Order
                        </button>
                        <a href="{{ route('production.work-orders.index') }}" class="btn btn-light btn-lg ms-2">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
.process-flow {
    position: relative;
}

.process-step {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
    position: relative;
}

.step-number {
    width: 30px;
    height: 30px;
    background: #727cf5;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 12px;
    margin-right: 15px;
    flex-shrink: 0;
}

.step-content h6 {
    margin-bottom: 5px;
    color: #6c757d;
}

.step-content p {
    font-size: 0.875rem;
}

.process-step:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 14px;
    top: 30px;
    width: 2px;
    height: 20px;
    background: #dee2e6;
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let sizeIndex = 1;
    let bomIndex = 1;

    // Add Size functionality
    $('#add-size').click(function() {
        const newRow = `
            <div class="row size-row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Size</label>
                        <select class="form-select" name="sizes_quantities[${sizeIndex}][size]" required>
                            <option value="">Select Size</option>
                            <option value="XS">XS</option>
                            <option value="S">S</option>
                            <option value="M">M</option>
                            <option value="L">L</option>
                            <option value="XL">XL</option>
                            <option value="XXL">XXL</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="sizes_quantities[${sizeIndex}][quantity]" 
                               min="1" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="button" class="btn btn-sm btn-danger remove-size">Remove</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#sizes-container').append(newRow);
        sizeIndex++;
    });

    // Remove Size functionality
    $(document).on('click', '.remove-size', function() {
        if ($('.size-row').length > 1) {
            $(this).closest('.size-row').remove();
        } else {
            alert('At least one size is required.');
        }
    });

    // Add BOM functionality
    $('#add-bom').click(function() {
        const newRow = `
            <div class="row bom-row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Material</label>
                        <select class="form-select" name="bom[${bomIndex}][material_id]" required>
                            <option value="">Select Material</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->name }} ({{ $material->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="bom[${bomIndex}][material_type]" required>
                            <option value="yarn">Yarn</option>
                            <option value="thread">Thread</option>
                            <option value="labels">Labels</option>
                            <option value="trims">Trims</option>
                            <option value="packaging">Packaging</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="bom[${bomIndex}][quantity]" 
                               step="0.001" min="0.001" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Unit</label>
                        <select class="form-select" name="bom[${bomIndex}][unit]" required>
                            <option value="kg">kg</option>
                            <option value="pcs">pcs</option>
                            <option value="m">m</option>
                            <option value="cones">cones</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Variance %</label>
                        <input type="number" class="form-control" name="bom[${bomIndex}][variance]" 
                               step="0.1" min="0" max="100" value="5">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="button" class="btn btn-sm btn-danger remove-bom">Remove</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#bom-container').append(newRow);
        bomIndex++;
    });

    // Remove BOM functionality
    $(document).on('click', '.remove-bom', function() {
        if ($('.bom-row').length > 1) {
            $(this).closest('.bom-row').remove();
        } else {
            alert('At least one material is required.');
        }
    });

    // Show/hide embroidery step based on checkbox
    $('#requires_logo').change(function() {
        if ($(this).is(':checked')) {
            $('.embroidery-step').show();
        } else {
            $('.embroidery-step').hide();
        }
    });

    // Convert sizes_quantities to the correct format before submitting
    $('form').submit(function() {
        const sizesData = {};
        $('.size-row').each(function() {
            const size = $(this).find('select[name*="[size]"]').val();
            const quantity = $(this).find('input[name*="[quantity]"]').val();
            if (size && quantity) {
                sizesData[size] = parseInt(quantity);
            }
        });
        
        // Add hidden input with correct format
        $('<input>').attr({
            type: 'hidden',
            name: 'sizes_quantities',
            value: JSON.stringify(sizesData)
        }).appendTo(this);
        
        // Remove original inputs to avoid conflicts
        $('select[name*="sizes_quantities"], input[name*="sizes_quantities"]').not('[name="sizes_quantities"]').remove();
    });
});
</script>
@endpush