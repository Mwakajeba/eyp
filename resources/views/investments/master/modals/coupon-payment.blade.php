<div class="modal fade" id="couponPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Coupon Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('investments.master.coupon-payment', $master->hash_id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Coupon Amount (TZS) <span class="text-danger">*</span></label>
                        <input type="number" name="coupon_amount" class="form-control" step="0.01" min="0.01" required>
                        <small class="text-muted">Amount of coupon payment received</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bank Reference</label>
                        <input type="text" name="bank_ref" class="form-control" placeholder="Bank transaction reference">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Process coupon payment?')">
                        <i class="bx bx-money"></i> Process Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

