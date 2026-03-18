@extends('layouts.main')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@section('title', 'Create Write-off')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Write-offs', 'url' => route('inventory.write-offs.index'), 'icon' => 'bx bx-x-circle'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-x-circle me-2"></i>Create Write-off
                                </h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            Please fix the following errors:
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        <div class="alert alert-warning">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-info-circle me-2 fs-4"></i>
                                <div>
                                    <strong>Note:</strong> Select items with stock > 0 to write off. GL transactions will be created (Debit: Expense Account, Credit: Inventory Account).
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('inventory.write-offs.store') }}" method="POST" id="writeOffForm">
                            @csrf

                            <!-- Write-off Information -->
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-uppercase">Write-off Information</h6>
                                    <hr>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Reference -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reference</label>
                                    <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror"
                                        value="{{ old('reference', 'WO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)) }}" 
                                        placeholder="Enter reference (optional)">
                                    @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <!-- Write-off Date -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Write-off Date <span class="text-danger">*</span></label>
                                    <input type="date" name="movement_date" class="form-control @error('movement_date') is-invalid @enderror"
                                        value="{{ old('movement_date', date('Y-m-d')) }}" required>
                                    @error('movement_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <!-- Items Section -->
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-uppercase">Items</h6>
                                    <hr>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-dark" id="add-item">
                                        <i class="bx bx-plus me-1"></i>Add Item
                                    </button>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="itemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Current Stock</th>
                                            <th>Quantity to Write Off</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Items will be added here via JavaScript -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary -->
                            <div class="row">
                                <div class="col-md-6 offset-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Total Items:</strong></td>
                                            <td class="text-end"><span id="total-items">0</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Quantity:</strong></td>
                                            <td class="text-end"><span id="total-quantity">0.00</span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-uppercase">Additional Information</h6>
                                    <hr>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Reason -->
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror"
                                              rows="3" placeholder="Enter reason for this write-off" required>{{ old('reason', '') }}</textarea>
                                    @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <!-- Notes -->
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                              rows="2" placeholder="Additional notes (optional)">{{ old('notes', '') }}</textarea>
                                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-dark px-5">
                                        <i class="bx bx-save me-1"></i>Create Write-off
                                    </button>
                                    <a href="{{ route('inventory.write-offs.index') }}" class="btn btn-secondary px-5 ms-2">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="modal_item_id" class="form-label">Select Item</label>
                    <select class="form-select select2-modal" id="modal_item_id">
                        <option value="">Choose an item...</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" 
                                    data-name="{{ $item->name }}" 
                                    data-code="{{ $item->code }}"
                                    data-stock="{{ $locationStocks[$item->id] ?? 0 }}"
                                    data-unit="{{ $item->unit_of_measure }}">
                                {{ $item->name }} ({{ $item->code }}) - Stock: {{ number_format($locationStocks[$item->id] ?? 0, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_current_stock" class="form-label">Current Stock</label>
                            <div class="form-control-plaintext border rounded p-2 bg-light">
                                <strong id="modal_current_stock_display">0.00</strong>
                                <span id="modal_unit_display">Unit</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_quantity" class="form-label">Quantity to Write Off</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="modal_quantity" value="1" step="0.01" min="0.01">
                                <span class="input-group-text" id="modal_unit_display_text">Unit</span>
                            </div>
                            <small class="text-muted">Maximum: <span id="modal_max_quantity">0.00</span></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark" id="add-item-to-table">Add Item</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let itemCounter = 0;

    // Initialize Select2 for modal
    $('.select2-modal').select2({
        placeholder: 'Search for an item...',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5',
        dropdownParent: $('#itemModal'),
        minimumInputLength: 0,
        templateResult: formatItemOption,
        templateSelection: formatItemSelection
    });

    // Format item options in dropdown
    function formatItemOption(item) {
        if (!item.id) {
            return item.text;
        }
        
        const $item = $(item.element);
        const stock = $item.data('stock');
        const code = $item.data('code');
        
        return $(`
            <div>
                <strong>${item.text.split(' (')[0]}</strong>
                <br>
                <small class="text-muted">Code: ${code} | Stock: ${stock}</small>
            </div>
        `);
    }

    // Format selected item
    function formatItemSelection(item) {
        return item.text;
    }

    // Add Item Button
    $('#add-item').click(function() {
        $('#itemModal').modal('show');
        resetItemModal();
    });

    // Item selection change in modal
    $('#modal_item_id').change(function() {
        const selectedOption = $(this).find(':selected');
        const stock = parseFloat(selectedOption.data('stock')) || 0;
        const unit = selectedOption.data('unit') || 'Unit';
        
        $('#modal_current_stock_display').text(stock.toFixed(2));
        $('#modal_unit_display').text(unit);
        $('#modal_unit_display_text').text(unit);
        $('#modal_max_quantity').text(stock.toFixed(2));
        $('#modal_quantity').attr('max', stock);
        // Set default quantity to 1, but don't exceed available stock
        if (stock > 0) {
            // If stock is available, default to 1 (or stock if less than 1)
            $('#modal_quantity').val(stock >= 1 ? 1 : stock);
        } else {
            // If no stock, still default to 1 (user can adjust)
            $('#modal_quantity').val(1);
        }
    });

    // Validate quantity doesn't exceed stock
    $('#modal_quantity').on('input', function() {
        const maxStock = parseFloat($('#modal_item_id option:selected').data('stock')) || 0;
        const enteredQty = parseFloat($(this).val()) || 0;
        
        if (enteredQty > maxStock) {
            $(this).val(maxStock);
            Swal.fire('Warning', 'Quantity cannot exceed available stock of ' + maxStock.toFixed(2), 'warning');
        }
    });

    function resetItemModal() {
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#modal_current_stock_display').text('0.00');
        $('#modal_unit_display').text('Unit');
        $('#modal_unit_display_text').text('Unit');
        $('#modal_max_quantity').text('0.00');
    }

    // Add item to table
    $('#add-item-to-table').click(function() {
        const itemId = $('#modal_item_id').val();
        const itemName = $('#modal_item_id option:selected').data('name');
        const itemCode = $('#modal_item_id option:selected').data('code');
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const currentStock = parseFloat($('#modal_item_id option:selected').data('stock')) || 0;
        const unit = $('#modal_item_id option:selected').data('unit') || 'Unit';

        if (!itemId || quantity <= 0) {
            Swal.fire('Error', 'Please select an item and enter a valid quantity', 'error');
            return;
        }

        if (quantity > currentStock) {
            Swal.fire('Error', 'Quantity cannot exceed available stock of ' + currentStock.toFixed(2), 'error');
            return;
        }

        // Check if item already exists
        if ($(`tr[data-item-id="${itemId}"]`).length > 0) {
            Swal.fire('Error', 'This item is already added. Please edit the existing entry.', 'error');
            return;
        }

        const newRow = `
            <tr data-item-id="${itemId}">
                <td>
                    <input type="hidden" name="items[${itemCounter}][item_id]" value="${itemId}">
                    <input type="hidden" name="items[${itemCounter}][item_name]" value="${itemName}">
                    <input type="hidden" name="items[${itemCounter}][item_code]" value="${itemCode}">
                    <strong>${itemName}</strong><br>
                    <small class="text-muted">${itemCode}</small>
                </td>
                <td class="text-end">
                    <span class="fw-bold">${currentStock.toFixed(2)}</span><br>
                    <small class="text-muted">${unit}</small>
                </td>
                <td>
                    <input type="number" step="0.01" name="items[${itemCounter}][quantity]" class="form-control form-control-sm quantity-input" 
                           value="${quantity}" min="0.01" max="${currentStock}" required>
                    <small class="text-muted">Max: ${currentStock.toFixed(2)}</small>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-item">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#itemsTable tbody').append(newRow);
        itemCounter++;
        
        updateSummary();
        $('#itemModal').modal('hide');
    });

    // Remove item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        updateSummary();
    });

    // Update totals when quantity changes
    $(document).on('input', '.quantity-input', function() {
        const row = $(this).closest('tr');
        const maxStock = parseFloat(row.find('td:eq(1)').find('.fw-bold').text()) || 0;
        const enteredQty = parseFloat($(this).val()) || 0;
        
        if (enteredQty > maxStock) {
            $(this).val(maxStock);
            Swal.fire('Warning', 'Quantity cannot exceed available stock', 'warning');
        }
        
        updateSummary();
    });

    function updateSummary() {
        const totalItems = $('#itemsTable tbody tr').length;
        let totalQuantity = 0;
        
        $('#itemsTable tbody tr').each(function() {
            const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
            totalQuantity += quantity;
        });
        
        $('#total-items').text(totalItems);
        $('#total-quantity').text(totalQuantity.toFixed(2));
    }

    // Form validation
    $('#writeOffForm').on('submit', function(e) {
        const totalItems = $('#itemsTable tbody tr').length;
        if (totalItems === 0) {
            e.preventDefault();
            Swal.fire('Error', 'Please add at least one item to write off.', 'error');
            return false;
        }
    });

    // Initialize summary
    updateSummary();
});
</script>
@endpush
