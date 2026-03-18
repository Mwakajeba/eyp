<div class="modal fade" id="postAccrualModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Post Interest Accrual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('investments.master.post-accrual', $master->hash_id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Accrual Date <span class="text-danger">*</span></label>
                        <input type="date" name="accrual_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        <small class="text-muted">Date for which to accrue interest</small>
                    </div>
                    @if($amortizationLines->where('posted', false)->count() > 0)
                    <div class="alert alert-info">
                        <strong>Next Pending Accrual:</strong><br>
                        Period: {{ $amortizationLines->where('posted', false)->first()->period_start->format('M d, Y') }} to {{ $amortizationLines->where('posted', false)->first()->period_end->format('M d, Y') }}<br>
                        Amount: TZS {{ number_format($amortizationLines->where('posted', false)->first()->interest_income, 2) }}
                    </div>
                    @else
                    <div class="alert alert-warning">
                        No pending accrual lines found. Generate amortization schedule first.
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" {{ $amortizationLines->where('posted', false)->count() == 0 ? 'disabled' : '' }}>
                        <i class="bx bx-plus-circle"></i> Post Accrual
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

