@extends('layouts.main')

@section('title', 'View Trip Cost - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Cost Management', 'url' => route('fleet.trip-costs.index'), 'icon' => 'bx bx-money'],
            ['label' => 'View Cost', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-money me-2"></i>Trip Cost Details</h6>
                        <div>
                            @if($cost->approval_status == 'pending')
                                <a href="{{ route('fleet.trip-costs.edit', $cost->hash_id) }}" class="btn btn-light btn-sm me-1">
                                    <i class="bx bx-edit me-1"></i>Edit
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Trip</label>
                                <p class="mb-0">
                                    @if($cost->trip)
                                        <a href="{{ route('fleet.trips.show', $cost->trip->hash_id) }}">{{ $cost->trip->trip_number }}</a>
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vehicle</label>
                                <p class="mb-0">{{ $cost->vehicle ? $cost->vehicle->name . ' (' . ($cost->vehicle->registration_number ?? 'N/A') . ')' : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Cost Type</label>
                                <p class="mb-0">
                                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $cost->cost_type)) }}</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Amount</label>
                                <p class="mb-0"><strong>{{ number_format($cost->amount, 2) }} {{ $cost->currency ?? 'TZS' }}</strong></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date Incurred</label>
                                <p class="mb-0">{{ $cost->date_incurred->format('Y-m-d') }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Approval Status</label>
                                <p class="mb-0">
                                    @php
                                        $statusColors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                                        $color = $statusColors[$cost->approval_status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ ucfirst($cost->approval_status) }}</span>
                                </p>
                            </div>
                            @if($cost->description)
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Description</label>
                                <p class="mb-0">{{ $cost->description }}</p>
                            </div>
                            @endif
                        </div>

                        @if($cost->cost_type == 'fuel')
                        <hr class="my-4">
                        <h6 class="text-danger mb-3"><i class="bx bx-gas-pump me-2"></i>Fuel Details</h6>
                        <div class="row g-3">
                            @if($cost->fuel_liters)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Liters</label>
                                <p class="mb-0">{{ number_format($cost->fuel_liters, 2) }} L</p>
                            </div>
                            @endif
                            @if($cost->fuel_price_per_liter)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Price per Liter</label>
                                <p class="mb-0">{{ number_format($cost->fuel_price_per_liter, 2) }} TZS</p>
                            </div>
                            @endif
                            @if($cost->fuel_site)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Fuel Site</label>
                                <p class="mb-0">{{ $cost->fuel_site }}</p>
                            </div>
                            @endif
                            @if($cost->odometer_reading)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Odometer Reading</label>
                                <p class="mb-0">{{ number_format($cost->odometer_reading, 2) }} km</p>
                            </div>
                            @endif
                            @if($cost->fuel_card_number)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Fuel Card Number</label>
                                <p class="mb-0">{{ $cost->fuel_card_number }}</p>
                            </div>
                            @endif
                        </div>
                        @endif

                        @if(in_array($cost->cost_type, ['driver_allowance', 'overtime']))
                        <hr class="my-4">
                        <h6 class="text-danger mb-3"><i class="bx bx-user me-2"></i>Driver Cost Details</h6>
                        <div class="row g-3">
                            @if($cost->driver_allowance_amount)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Allowance Amount</label>
                                <p class="mb-0">{{ number_format($cost->driver_allowance_amount, 2) }} TZS</p>
                            </div>
                            @endif
                            @if($cost->overtime_hours)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Overtime Hours</label>
                                <p class="mb-0">{{ number_format($cost->overtime_hours, 2) }} hours</p>
                            </div>
                            @endif
                            @if($cost->overtime_rate)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Overtime Rate</label>
                                <p class="mb-0">{{ number_format($cost->overtime_rate, 2) }} TZS/hour</p>
                            </div>
                            @endif
                        </div>
                        @endif

                        @if($cost->cost_type == 'toll')
                        <hr class="my-4">
                        <h6 class="text-danger mb-3"><i class="bx bx-road me-2"></i>Toll Details</h6>
                        <div class="row g-3">
                            @if($cost->toll_point_name)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Toll Point Name</label>
                                <p class="mb-0">{{ $cost->toll_point_name }}</p>
                            </div>
                            @endif
                            @if($cost->toll_receipt_number)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Toll Receipt Number</label>
                                <p class="mb-0">{{ $cost->toll_receipt_number }}</p>
                            </div>
                            @endif
                        </div>
                        @endif

                        <hr class="my-4">
                        <h6 class="text-danger mb-3"><i class="bx bx-wallet me-2"></i>Payment Information</h6>
                        <div class="row g-3">
                            @php
                                $paidFromGlTransaction = \App\Models\GlTransaction::where('transaction_type', 'fleet_trip_cost')
                                    ->where('transaction_id', $cost->id)
                                    ->where('nature', 'credit')
                                    ->with('chartAccount')
                                    ->first();
                                
                                $bankAccount = null;
                                if ($paidFromGlTransaction && $paidFromGlTransaction->chartAccount) {
                                    $bankAccount = \App\Models\BankAccount::where('chart_account_id', $paidFromGlTransaction->chart_account_id)
                                        ->where('company_id', $cost->company_id)
                                        ->first();
                                }
                            @endphp
                            @if($bankAccount)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Paid From Account</label>
                                <p class="mb-0">
                                    {{ $bankAccount->name }}@if($bankAccount->account_number) - {{ $bankAccount->account_number }}@endif@if($bankAccount->currency) ({{ $bankAccount->currency }})@endif
                                </p>
                            </div>
                            @elseif($paidFromGlTransaction && $paidFromGlTransaction->chartAccount)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Paid From Account</label>
                                <p class="mb-0">{{ $paidFromGlTransaction->chartAccount->account_code }} - {{ $paidFromGlTransaction->chartAccount->account_name }}</p>
                            </div>
                            @endif
                            @if($cost->receipt_number)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Receipt Number</label>
                                <p class="mb-0">{{ $cost->receipt_number }}</p>
                            </div>
                            @endif
                        </div>

                        <hr class="my-4">
                        <h6 class="text-danger mb-3"><i class="bx bx-receipt me-2"></i>Additional Information</h6>
                        <div class="row g-3">
                            @if($cost->costCategory)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Cost Category</label>
                                <p class="mb-0">{{ $cost->costCategory->name }}</p>
                            </div>
                            @endif
                            @if($cost->glAccount)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">GL Account</label>
                                <p class="mb-0">{{ $cost->glAccount->account_code }} - {{ $cost->glAccount->account_name }}</p>
                            </div>
                            @endif
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Billable to Customer</label>
                                <p class="mb-0">
                                    <span class="badge bg-{{ $cost->is_billable_to_customer ? 'success' : 'secondary' }}">
                                        {{ $cost->is_billable_to_customer ? 'Yes' : 'No' }}
                                    </span>
                                </p>
                            </div>
                            @if($cost->notes)
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Notes</label>
                                <p class="mb-0">{{ $cost->notes }}</p>
                            </div>
                            @endif
                        </div>

                        @if($cost->attachments && count($cost->attachments) > 0)
                        <hr class="my-4">
                        <h6 class="text-danger mb-3"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                        <div class="row g-3">
                            @foreach($cost->attachments as $index => $attachment)
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">
                                                    <i class="bx bx-file me-2"></i>{{ $attachment['original_name'] ?? 'Attachment ' . ($index + 1) }}
                                                </h6>
                                                <small class="text-muted">
                                                    @if(isset($attachment['size']))
                                                        {{ number_format($attachment['size'] / 1024, 2) }} KB
                                                    @endif
                                                    @if(isset($attachment['uploaded_at']))
                                                        | {{ \Carbon\Carbon::parse($attachment['uploaded_at'])->format('M d, Y H:i') }}
                                                    @endif
                                                </small>
                                            </div>
                                            <div class="ms-2">
                                                @if(isset($attachment['path']))
                                                    @php
                                                        $filePath = str_replace('storage/', '', $attachment['path']);
                                                        $fileUrl = asset('storage/' . $filePath);
                                                    @endphp
                                                    <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary me-1" title="View">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                    <a href="{{ $fileUrl }}" download="{{ $attachment['original_name'] ?? 'attachment' }}" class="btn btn-sm btn-outline-success" title="Download">
                                                        <i class="bx bx-download"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        @if($cost->approval_status == 'pending')
                        <hr class="my-4">
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('fleet.trip-costs.approve', $cost->hash_id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="bx bx-check me-1"></i>Approve
                                </button>
                            </form>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bx bx-x me-1"></i>Reject
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('fleet.trip-costs.reject', $cost->hash_id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Cost</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="approval_notes" class="form-control" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Cost</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
