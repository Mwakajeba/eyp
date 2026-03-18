@extends('layouts.main')
@section('title', 'Add Raw Material Item to Batch')
@section('content')
<div class="modal-dialog" role="document">
    <div class="modal-content">
    <form id="addItemBatchForm" method="POST" action="{{ route('production.batches.add-item.store', Vinkla\Hashids\Facades\Hashids::encode($batch->id)) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Raw Material Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
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
                    <input type="number" name="quantity" id="quantity" class="form-control" min="0.01" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label for="cost" class="form-label">Cost</label>
                    <input type="number" name="cost" id="cost" class="form-control" min="0" step="0.01">
                </div>
                <div id="addItemBatchError" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="submit" id="addItemBatchBtn" class="btn btn-primary">
                    <span id="addItemBatchBtnText">Add Item</span>
                    <span id="addItemBatchBtnLoading" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
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
        var form = document.getElementById('addItemBatchForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = document.getElementById('addItemBatchBtn');
                var btnText = document.getElementById('addItemBatchBtnText');
                var btnLoading = document.getElementById('addItemBatchBtnLoading');
                var errorDiv = document.getElementById('addItemBatchError');
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
</script>
@endsection
