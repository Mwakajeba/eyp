@extends('layouts.main')
@section('title', 'Production Batch Details')
@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Production Batches', 'url' => route('production.batches.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Batch Details', 'url' => '#', 'icon' => 'bx bx-book-open']
        ]" />
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 text-dark fw-bold">
                            <i class="bx bx-book-open me-2 text-primary"></i>
                            Production Batch Details
                        </h4>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('production.batches.edit', Vinkla\Hashids\Facades\Hashids::encode($batch->id)) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i> Edit Batch
                        </a>
                        <a href="{{ route('production.batches.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <table class="table table-bordered" style="max-width: 600px;">
                    <tr>
                        <th>Batch Number</th>
                        <td>{{ $batch->batch_number }}</td>
                    </tr>
                    <tr>
                        <th>Item</th>
                        <td>{{ optional($batch->item)->name }}</td>
                    </tr>
                    <tr>
                        <th>Quantity Planned</th>
                        <td>{{ $batch->quantity_planned }}</td>
                    </tr>
                    <tr>
                        <th>Quantity Produced</th>
                        <td>{{ $batch->quantity_produced }}</td>
                    </tr>
                    <tr>
                        <th>Quantity Defective</th>
                        <td>{{ $batch->quantity_defective }}</td>
                    </tr>
                    <tr>
                        <th>Start Date</th>
                        <td>{{ $batch->start_date }}</td>
                    </tr>
                    <tr>
                        <th>End Date</th>
                        <td>{{ $batch->end_date }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>{{ $batch->status }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">Assigned Orders</span>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignOrderModal" onclick="loadAssignOrderModal()">
                    <i class="bx bx-plus"></i> Assign Order
                </button>
            </div>
            <div class="card-body">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Order Date</th>
                            <th>Assigned Quantity</th>
                            <th>Actions</th>
                    </thead>
                    <tbody>
                        @forelse($batch->orders as $order)
                            <tr>
                                <td>{{ $order->order_number ?? $order->id }}</td>
                                <td>{{ $order->customer ? $order->customer->name : '' }}</td>
                                <td>{{ $order->order_date ?? '' }}</td>
                                <td>{{ $order->pivot->assigned_quantity }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="editAssignedOrder('{{ Vinkla\Hashids\Facades\Hashids::encode($batch->id) }}', '{{ Vinkla\Hashids\Facades\Hashids::encode($order->id) }}', {{ $order->pivot->assigned_quantity }})">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteAssignedOrder('{{ Vinkla\Hashids\Facades\Hashids::encode($batch->id) }}', '{{ Vinkla\Hashids\Facades\Hashids::encode($order->id) }}', '{{ $order->order_number }}')">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </td>
                            </tr>
            <!-- Edit Assigned Order Modal -->
            <div class="modal fade" id="editAssignedOrderModal" tabindex="-1" aria-labelledby="editAssignedOrderModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content" id="editAssignedOrderModalContent">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editAssignedOrderModalLabel">Edit Assigned Quantity</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editAssignedOrderForm">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="edit_assigned_quantity" class="form-label">Assigned Quantity</label>
                                    <input type="number" name="assigned_quantity" id="edit_assigned_quantity" class="form-control" min="1" required>
                                </div>
                                <div id="editAssignedOrderError" class="alert alert-danger d-none"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" id="editAssignedOrderBtn" class="btn btn-primary">
                                    <span id="editAssignedOrderBtnText">Save</span>
                                    <span id="editAssignedOrderBtnLoading" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script nonce="{{ $cspNonce ?? '' }}">
                let editBatchId = '';
                let editOrderId = '';
                function editAssignedOrder(batchId, orderId, assignedQty) {
                    editBatchId = batchId;
                    editOrderId = orderId;
                    document.getElementById('edit_assigned_quantity').value = assignedQty;
                    var modal = new bootstrap.Modal(document.getElementById('editAssignedOrderModal'));
                    modal.show();
                }
                document.getElementById('editAssignedOrderForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    var btn = document.getElementById('editAssignedOrderBtn');
                    var btnText = document.getElementById('editAssignedOrderBtnText');
                    var btnLoading = document.getElementById('editAssignedOrderBtnLoading');
                    var errorDiv = document.getElementById('editAssignedOrderError');
                    btnText.classList.add('d-none');
                    btnLoading.classList.remove('d-none');
                    errorDiv.classList.add('d-none');
                    var assignedQty = document.getElementById('edit_assigned_quantity').value;
                    fetch(`/production/batches/${editBatchId}/assigned-orders/${editOrderId}/update`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ assigned_quantity: assignedQty })
                    })
                    .then(response => response.json())
                    .then(data => {
                        btnText.classList.remove('d-none');
                        btnLoading.classList.add('d-none');
                        if (data.success) {
                            var modal = bootstrap.Modal.getInstance(document.getElementById('editAssignedOrderModal'));
                            modal.hide();
                            location.reload();
                        } else {
                            errorDiv.textContent = data.message || 'Failed to update.';
                            errorDiv.classList.remove('d-none');
                        }
                    })
                    .catch(() => {
                        btnText.classList.remove('d-none');
                        btnLoading.classList.add('d-none');
                        errorDiv.textContent = 'Failed to update.';
                        errorDiv.classList.remove('d-none');
                    });
                });
                function deleteAssignedOrder(batchId, orderId, orderNumber) {
                    Swal.fire({
                        title: 'Delete Assigned Order',
                        text: `Are you sure you want to remove order ${orderNumber} from this batch?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/production/batches/${batchId}/assigned-orders/${orderId}/delete`, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Deleted!', 'Order removed from batch.', 'success');
                                    setTimeout(() => location.reload(), 1000);
                                } else {
                                    Swal.fire('Error', data.message || 'Failed to delete.', 'error');
                                }
                            })
                            .catch(() => {
                                Swal.fire('Error', 'Failed to delete.', 'error');
                            });
                        }
                    });
                }
            </script>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No orders assigned to this batch.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Assign Order Modal -->
            <div class="modal fade" id="assignOrderModal" tabindex="-1" aria-labelledby="assignOrderModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content" id="assignOrderModalContent">
                        <div class="modal-header">
                            <h5 class="modal-title" id="assignOrderModalLabel">Assign Order to Batch</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">Raw Material Items Used</span>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addItemBatchModal" onclick="loadAddItemBatchModal()">
                    <i class="bx bx-plus"></i> Add Item
                </button>
            </div>
            <div class="card-body">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batch->itemBatches as $itemBatch)
                            <tr>
                                <td>{{ $itemBatch->item->name ?? '' }}</td>
                                <td>{{ $itemBatch->quantity }}</td>
                                <td>{{ $itemBatch->cost }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteItemBatch({{ $itemBatch->id }})">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No raw material items added to this batch.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Add Item Batch Modal (AJAX loaded) -->
            <div class="modal fade" id="addItemBatchModal" tabindex="-1" aria-labelledby="addItemBatchModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content" id="addItemBatchModalContent">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addItemBatchModalLabel">Add Raw Material Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="addItemBatchForm" method="POST" action="{{ route('production.batches.add-item.store', Vinkla\Hashids\Facades\Hashids::encode($batch->id)) }}">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select name="category_id" id="category_id" class="form-control" required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="item_id" class="form-label">Item</label>
                                    <select name="item_id" id="item_id" class="form-control" required>
                                        <option value="">Select Item</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}" data-category="{{ $item->category_id }}" data-cost="{{ $item->cost }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
                                </div>
                                <div class="mb-3">
                                    <label for="cost" class="form-label">Cost</label>
                                    <input type="number" name="cost" id="cost" class="form-control" min="0" step="0.01" required>
                                </div>
                                <div id="addItemBatchError" class="alert alert-danger d-none"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" id="addItemBatchBtn" class="btn btn-success">
                                    <span id="addItemBatchBtnText">Add Item</span>
                                    <span id="addItemBatchBtnLoading" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <!-- All modals and JS at the end -->
    <div class="modal fade" id="addItemBatchModal" tabindex="-1" aria-labelledby="addItemBatchModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" id="addItemBatchModalContent">
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemBatchModalLabel">Add Raw Material Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function() {
        var categorySelect = document.getElementById('category_id');
        var itemSelect = document.getElementById('item_id');
        var costInput = document.getElementById('cost');
        if (categorySelect && itemSelect) {
            categorySelect.addEventListener('change', function() {
                var selectedCategory = this.value;
                Array.from(itemSelect.options).forEach(function(opt) {
                    if (!opt.value) {
                        opt.style.display = '';
                        return;
                    }
                    opt.style.display = (opt.getAttribute('data-category') === selectedCategory) ? '' : 'none';
                });
                itemSelect.value = '';
                if (costInput) costInput.value = '';
            });
            itemSelect.addEventListener('change', function() {
                var selectedOption = itemSelect.options[itemSelect.selectedIndex];
                if (selectedOption && costInput) {
                    var cost = selectedOption.getAttribute('data-cost');
                    costInput.value = cost !== null ? cost : '';
                }
            });
        }
        var addItemBatchForm = document.getElementById('addItemBatchForm');
        if (addItemBatchForm) {
            addItemBatchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = document.getElementById('addItemBatchBtn');
                var btnText = document.getElementById('addItemBatchBtnText');
                var btnLoading = document.getElementById('addItemBatchBtnLoading');
                var errorDiv = document.getElementById('addItemBatchError');
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
                errorDiv.classList.add('d-none');
                var formData = new FormData(addItemBatchForm);
                fetch(addItemBatchForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': formData.get('_token') || '{{ csrf_token() }}',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    btnText.classList.remove('d-none');
                    btnLoading.classList.add('d-none');
                    if (data.success) {
                        var modalElem = document.getElementById('addItemBatchModal');
                        if (modalElem) {
                            var modal = bootstrap.Modal.getInstance(modalElem);
                            if (modal) modal.hide();
                        }
                        location.reload();
                    } else {
                        errorDiv.textContent = data.message || 'Failed to add item.';
                        errorDiv.classList.remove('d-none');
                    }
                })
                .catch(() => {
                    btnText.classList.remove('d-none');
                    btnLoading.classList.add('d-none');
                    errorDiv.textContent = 'Failed to add item.';
                    errorDiv.classList.remove('d-none');
                });
            });
        }
    });
// ...existing code...
</script>
<script nonce="{{ $cspNonce ?? '' }}">
    }

    function deleteItemBatch(id) {
        if (!confirm('Are you sure you want to remove this item from the batch?')) return;
        fetch(`/production/batches/item-batch/${id}/delete`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to delete item.');
            }
        })
        .catch(() => {
            alert('Failed to delete item.');
        });
    }

    // Existing assign order modal and JS
    function loadAssignOrderModal() {
        var modalContent = document.getElementById('assignOrderModalContent');
        modalContent.innerHTML = '<div class="modal-header"><h5 class="modal-title" id="assignOrderModalLabel">Assign Order to Batch</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        fetch("{{ route('production.batches.assign-order', Vinkla\Hashids\Facades\Hashids::encode($batch->id)) }}")
            .then(response => response.text())
            .then(html => {
                var tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                var modalDialog = tempDiv.querySelector('.modal-dialog');
                if (modalDialog) {
                    modalContent.innerHTML = modalDialog.innerHTML;
                } else {
                    modalContent.innerHTML = html;
                }
                // Attach spinner logic after modal content is loaded
                var assignForm = document.getElementById('assignOrderForm');
                if (assignForm) {
                    assignForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        var btn = document.getElementById('assignOrderBtn');
                        var btnText = document.getElementById('assignOrderBtnText');
                        var btnLoading = document.getElementById('assignOrderBtnLoading');
                        var errorDiv = document.getElementById('assignOrderError');
                        btnText.classList.add('d-none');
                        btnLoading.classList.remove('d-none');
                        errorDiv.classList.add('d-none');
                        var formData = new FormData(this);
                        fetch(this.action, {
                       // Ensure category_id is sent
                       formData.append('category_id', categorySelect.value);
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': formData.get('_token'),
                            },
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            btnText.classList.remove('d-none');
                            btnLoading.classList.add('d-none');
                            if (data.success) {
                                var modalElem = document.getElementById('assignOrderModal');
                                if (modalElem) {
                                    var modal = bootstrap.Modal.getInstance(modalElem);
                                    if (modal) modal.hide();
                                }
                                location.reload();
                            } else {
                                errorDiv.textContent = data.message || 'Failed to assign order.';
                                errorDiv.classList.remove('d-none');
                            }
                        })
                        .catch(() => {
                            btnText.classList.remove('d-none');
                            btnLoading.classList.add('d-none');
                            errorDiv.textContent = 'Failed to assign order.';
                            errorDiv.classList.remove('d-none');
                        });
                    });
                }
            });
    }

    // Existing edit assigned order modal and JS
    let editBatchId = '';
    let editOrderId = '';
    function editAssignedOrder(batchId, orderId, assignedQty) {
        editBatchId = batchId;
        editOrderId = orderId;
        document.getElementById('edit_assigned_quantity').value = assignedQty;
        var modal = new bootstrap.Modal(document.getElementById('editAssignedOrderModal'));
        modal.show();
    }
    document.getElementById('editAssignedOrderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('editAssignedOrderBtn');
        var btnText = document.getElementById('editAssignedOrderBtnText');
        var btnLoading = document.getElementById('editAssignedOrderBtnLoading');
        var errorDiv = document.getElementById('editAssignedOrderError');
        btnText.classList.add('d-none');
        btnLoading.classList.remove('d-none');
        errorDiv.classList.add('d-none');
        var assignedQty = document.getElementById('edit_assigned_quantity').value;
        fetch(`/production/batches/${editBatchId}/assigned-orders/${editOrderId}/update`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ assigned_quantity: assignedQty })
        })
        .then(response => response.json())
        .then(data => {
            btnText.classList.remove('d-none');
            btnLoading.classList.add('d-none');
            if (data.success) {
                var modal = bootstrap.Modal.getInstance(document.getElementById('editAssignedOrderModal'));
                modal.hide();
                location.reload();
            } else {
                errorDiv.textContent = data.message || 'Failed to update.';
                errorDiv.classList.remove('d-none');
            }
        })
        .catch(() => {
            btnText.classList.remove('d-none');
            btnLoading.classList.add('d-none');
            errorDiv.textContent = 'Failed to update.';
            errorDiv.classList.remove('d-none');
        });
    });
    function deleteAssignedOrder(batchId, orderId, orderNumber) {
        Swal.fire({
            title: 'Delete Assigned Order',
            text: `Are you sure you want to remove order ${orderNumber} from this batch?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/production/batches/${batchId}/assigned-orders/${orderId}/delete`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted!', 'Order removed from batch.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        Swal.fire('Error', data.message || 'Failed to delete.', 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Error', 'Failed to delete.', 'error');
                });
            }
        });
    }
</script>
        </div>

       
            <script nonce="{{ $cspNonce ?? '' }}">
                function loadAssignOrderModal() {
                    var modalContent = document.getElementById('assignOrderModalContent');
                    modalContent.innerHTML = '<div class="modal-header"><h5 class="modal-title" id="assignOrderModalLabel">Assign Order to Batch</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                    fetch("{{ route('production.batches.assign-order', Vinkla\Hashids\Facades\Hashids::encode($batch->id)) }}")
                        .then(response => response.text())
                        .then(html => {
                            var tempDiv = document.createElement('div');
                            tempDiv.innerHTML = html;
                            var modalDialog = tempDiv.querySelector('.modal-dialog');
                            if (modalDialog) {
                                modalContent.innerHTML = modalDialog.innerHTML;
                            } else {
                                modalContent.innerHTML = html;
                            }
                            // Attach spinner logic after modal content is loaded
                            var assignForm = document.getElementById('assignOrderForm');
                            if (assignForm) {
                                assignForm.addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    var btn = document.getElementById('assignOrderBtn');
                                    var btnText = document.getElementById('assignOrderBtnText');
                                    var btnLoading = document.getElementById('assignOrderBtnLoading');
                                    var errorDiv = document.getElementById('assignOrderError');
                                    btnText.classList.add('d-none');
                                    btnLoading.classList.remove('d-none');
                                    errorDiv.classList.add('d-none');
                                    var formData = new FormData(this);
                                    fetch(this.action, {
                                        method: 'POST',
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'X-CSRF-TOKEN': formData.get('_token'),
                                        },
                                        body: formData
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        btnText.classList.remove('d-none');
                                        btnLoading.classList.add('d-none');
                                        if (data.success) {
                                            var modalElem = document.getElementById('assignOrderModal');
                                            if (modalElem) {
                                                var modal = bootstrap.Modal.getInstance(modalElem);
                                                if (modal) modal.hide();
                                            }
                                            location.reload();
                                        } else {
                                            errorDiv.textContent = data.message || 'Failed to assign order.';
                                            errorDiv.classList.remove('d-none');
                                        }
                                    })
                                    .catch(() => {
                                        btnText.classList.remove('d-none');
                                        btnLoading.classList.add('d-none');
                                        errorDiv.textContent = 'Failed to assign order.';
                                        errorDiv.classList.remove('d-none');
                                    });
                                });
                            }
                        });
                }
            </script>
        </div>
    </div>
</div>
@endsection
