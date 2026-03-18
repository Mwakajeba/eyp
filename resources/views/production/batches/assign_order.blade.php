@extends('layouts.main')
@section('title', 'Assign Order to Batch')
@section('content')
<div class="modal-dialog" role="document">
    <div class="modal-content">
        <form id="assignOrderForm" method="POST" action="{{ route('production.batches.assign-order.store', Vinkla\Hashids\Facades\Hashids::encode($batch->id)) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Assign Order to Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="order_id" class="form-label">Sales Order</label>
                    <select name="order_id" id="order_id" class="form-select" required onchange="updateMaxQuantity()">
                        <option value="">Select Sales Order</option>
                        @foreach($orders as $order)
                            @php $totalQty = $order->items->sum('quantity'); @endphp
                            <option value="{{ $order->id }}" data-qty="{{ $totalQty }}">
                                {{ $order->order_number }} - {{ $order->customer_name ?? '' }} (Qty: {{ $totalQty }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="assigned_quantity" class="form-label">Assigned Quantity</label>
                    <input type="number" name="assigned_quantity" id="assigned_quantity" class="form-control" min="1" required>
                    <small id="maxQtyHelp" class="form-text text-muted"></small>
                </div>
                <div id="assignOrderError" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="submit" id="assignOrderBtn" class="btn btn-primary">
                    <span id="assignOrderBtnText">Assign Order</span>
                    <span id="assignOrderBtnLoading" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true">
                        <span class="visually-hidden">Loading...</span>
                    </span>
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
        <!-- Move script outside form for reliability -->
    </div>
</div>
<div id="pageLoadTestMsg" style="color:blue;">PAGE LOAD TEST</div>
<script nonce="{{ $cspNonce ?? '' }}">
    function updateMaxQuantity() {
        var select = document.getElementById('order_id');
        var input = document.getElementById('assigned_quantity');
        var help = document.getElementById('maxQtyHelp');
        var selected = select.options[select.selectedIndex];
        var maxQty = selected.getAttribute('data-qty');
        if (maxQty) {
            input.max = maxQty;
            input.value = maxQty;
            input.readOnly = true;
            help.textContent = 'Max assignable quantity: ' + maxQty;
        } else {
            input.removeAttribute('max');
            input.value = '';
            input.readOnly = false;
            help.textContent = '';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        // Show page load test message
        var pageLoadTestMsg = document.getElementById('pageLoadTestMsg');
        if (pageLoadTestMsg) {
            pageLoadTestMsg.textContent = 'PAGE LOAD TEST: JS RUNNING';
        }
        var select = document.getElementById('order_id');
        if (select) {
            select.addEventListener('change', updateMaxQuantity);
            updateMaxQuantity(); // initialize on load
        }
        var form = document.getElementById('assignOrderForm');
        if (form) {
            form.addEventListener('submit', function(e) {
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
                    if (data.success) {
                        // Hide modal if possible
                        var modalElem = document.getElementById('assignOrderModal');
                        if (modalElem) {
                            var modal = bootstrap.Modal.getInstance(modalElem);
                            if (modal) modal.hide();
                        }
                        location.reload();
                    } else {
                        btnText.classList.remove('d-none');
                        btnLoading.classList.add('d-none');
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
            // DEBUG: Show page load test message and spinner on button click
            var assignBtn = document.getElementById('assignOrderBtn');
            if (assignBtn) {
                assignBtn.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent form submission for test
                    var btnText = document.getElementById('assignOrderBtnText');
                    var btnLoading = document.getElementById('assignOrderBtnLoading');
                    if (btnText && btnLoading) {
                        btnText.classList.add('d-none');
                        btnLoading.classList.remove('d-none');
                    }
                    if (pageLoadTestMsg) {
                        pageLoadTestMsg.textContent = 'BUTTON CLICKED: JS RUNNING';
                        pageLoadTestMsg.style.color = 'red';
                    }
                });
            }
    });
</script>
@endsection
