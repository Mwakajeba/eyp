<div class="row mb-4">
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bx bx-money me-2"></i>
                    Payment Completed
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    @if($payroll->paid_by)
                        <div class="col-md-6">
                            <strong>Paid By:</strong> {{ $payroll->paidBy->name ?? 'N/A' }}<br>
                            <strong>Paid At:</strong> {{ $payroll->paid_at?->format('M d, Y h:i A') ?? 'N/A' }}
                        </div>
                    @endif
                    @if($payroll->payment_remarks)
                        <div class="col-md-6">
                            <strong>Payment Remarks:</strong><br>
                            <em>{{ $payroll->payment_remarks }}</em>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

