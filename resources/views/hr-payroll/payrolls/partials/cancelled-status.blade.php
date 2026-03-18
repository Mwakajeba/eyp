<div class="row mb-4">
    <div class="col-12">
        <div class="card border-secondary">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">
                    <i class="bx bx-x-circle me-2"></i>
                    Payroll Cancelled
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    @if($payroll->rejected_by)
                        <div class="col-md-6">
                            <strong>Rejected By:</strong> {{ $payroll->rejectedBy->name ?? 'N/A' }}<br>
                            <strong>Rejected At:</strong> {{ $payroll->rejected_at?->format('M d, Y h:i A') ?? 'N/A' }}
                        </div>
                    @endif
                    @if($payroll->rejection_remarks)
                        <div class="col-md-6">
                            <strong>Rejection Reason:</strong><br>
                            <em>{{ $payroll->rejection_remarks }}</em>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

