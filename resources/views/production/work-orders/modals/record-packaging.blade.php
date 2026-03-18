<!-- Record Packaging Modal -->
<div class="modal fade" id="recordPackagingModal" tabindex="-1" aria-labelledby="recordPackagingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recordPackagingModalLabel">Record Packaging - {{ $workOrder->wo_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('production.work-orders.record-packaging', $workOrder->encoded_id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <!-- Items Section -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Items to Package</h6>
                        </div>
                        <div class="card-body">
                            <div id="packaging-items-container">
                                <div class="packaging-item-row mb-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">Item</label>
                                            <select class="form-select item-select" name="items[0][item_id]" required>
                                                <option value="">Select Item</option>
                                                @foreach(\App\Models\Inventory\Item::where('company_id', Auth::user()->company_id)->where('is_active', true)->get() as $item)
                                                    <option value="{{ $item->id }}" 
                                                            data-cost="{{ $item->cost_price }}" 
                                                            data-price="{{ $item->unit_price }}">
                                                        {{ $item->name }} ({{ $item->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Quantity</label>
                                            <input type="number" class="form-control quantity-input" name="items[0][quantity]" min="1" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Sales Price</label>
                                            <input type="number" class="form-control sales-price-input" name="items[0][sales_price]" step="0.01" min="0" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Cost Price</label>
                                            <input type="number" class="form-control cost-price-input" name="items[0][cost_price]" step="0.01" min="0" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Action</label>
                                            <button type="button" class="btn btn-danger btn-sm remove-item w-100">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="add-packaging-item">Add Item</button>
                        </div>
                    </div>

                    <!-- Packaging Details -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Packaging Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="packed_quantities" class="form-label">Packed Quantities (JSON)</label>
                                    <textarea class="form-control" id="packed_quantities" name="packed_quantities" rows="3" placeholder='{"S": 10, "M": 15, "L": 20}' required></textarea>
                                    <small class="text-muted">Enter as JSON format with sizes and quantities</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="carton_numbers" class="form-label">Carton Numbers (JSON)</label>
                                    <textarea class="form-control" id="carton_numbers" name="carton_numbers" rows="3" placeholder='["CTN001", "CTN002", "CTN003"]' required></textarea>
                                    <small class="text-muted">Enter as JSON array of carton numbers</small>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="barcode_data" class="form-label">Barcode Data (JSON) - Optional</label>
                                    <textarea class="form-control" id="barcode_data" name="barcode_data" rows="2" placeholder='{"CTN001": "123456789", "CTN002": "987654321"}'></textarea>
                                    <small class="text-muted">Enter barcode data for each carton</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Additional packaging notes..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Record Packaging</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let packagingItemIndex = 1;

    // Add packaging item
    $('#add-packaging-item').click(function() {
        const newItemRow = `
            <div class="packaging-item-row mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Item</label>
                        <select class="form-select item-select" name="items[${packagingItemIndex}][item_id]" required>
                            <option value="">Select Item</option>
                            @foreach(\App\Models\Inventory\Item::where('company_id', Auth::user()->company_id)->where('is_active', true)->get() as $item)
                                <option value="{{ $item->id }}" 
                                        data-cost="{{ $item->cost_price }}" 
                                        data-price="{{ $item->unit_price }}">
                                    {{ $item->name }} ({{ $item->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control quantity-input" name="items[${packagingItemIndex}][quantity]" min="1" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sales Price</label>
                        <input type="number" class="form-control sales-price-input" name="items[${packagingItemIndex}][sales_price]" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Cost Price</label>
                        <input type="number" class="form-control cost-price-input" name="items[${packagingItemIndex}][cost_price]" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Action</label>
                        <button type="button" class="btn btn-danger btn-sm remove-item w-100">Remove</button>
                    </div>
                </div>
            </div>
        `;
        $('#packaging-items-container').append(newItemRow);
        packagingItemIndex++;
    });

    // Remove packaging item
    $(document).on('click', '.remove-item', function() {
        if ($('.packaging-item-row').length > 1) {
            $(this).closest('.packaging-item-row').remove();
        } else {
            alert('At least one item is required');
        }
    });

    // Auto-populate prices when item is selected
    $(document).on('change', '.item-select', function() {
        const selectedOption = $(this).find('option:selected');
        const row = $(this).closest('.packaging-item-row');
        
        const costPrice = selectedOption.data('cost') || 0;
        const salesPrice = selectedOption.data('price') || 0;
        
        row.find('.cost-price-input').val(costPrice);
        row.find('.sales-price-input').val(salesPrice);
    });
});
</script>