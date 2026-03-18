<div class="modal fade" id="recalculateEirModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recalculate EIR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('investments.master.recalculate-eir', $master->hash_id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>This will recalculate the Effective Interest Rate (EIR) based on current cash flows and investment terms.</p>
                    <div class="alert alert-info">
                        <strong>Current EIR:</strong> {{ $master->eir_rate ? number_format($master->eir_rate, 4) . '%' : 'Not calculated' }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Recalculate EIR? This may affect amortization schedules.')">
                        <i class="bx bx-calculator"></i> Recalculate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

