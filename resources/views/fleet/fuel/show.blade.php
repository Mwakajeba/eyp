@extends('layouts.main')

@section('title', 'View Fuel Log - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fuel Management', 'url' => route('fleet.fuel.index'), 'icon' => 'bx bx-gas-pump'],
            ['label' => 'View Fuel Log', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="card">
            <div class="card-header bg-orange text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="mb-0"><i class="bx bx-gas-pump me-2"></i>Fuel Log Details</h6>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @if($fuelLog->approval_status === 'pending')
                    <form method="POST" action="{{ route('fleet.fuel.approve', $fuelLog->hash_id) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bx bx-check me-1"></i>Approve
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('fleet.fuel.export-pdf', $fuelLog->hash_id) }}" class="btn btn-danger btn-sm" target="_blank">
                        <i class="bx bx-file-blank me-1"></i>Export to PDF
                    </a>
                    <a href="{{ route('fleet.fuel.print', $fuelLog->hash_id) }}" class="btn btn-dark btn-sm" target="_blank">
                        <i class="bx bx-printer me-1"></i>Print
                    </a>
                    <a href="{{ route('fleet.fuel.edit', $fuelLog->hash_id) }}" class="btn btn-dark btn-sm">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('fleet.fuel.index') }}" class="btn btn-dark btn-sm">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <div class="card-body">

                <!-- Vehicle & Trip Information -->
                <h6 class="text-orange mb-3"><i class="bx bx-car me-2"></i>Vehicle & Trip Information</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Vehicle</label>
                        <p class="mb-0">{{ $fuelLog->vehicle->name ?? 'N/A' }} ({{ $fuelLog->vehicle->registration_number ?? 'N/A' }})</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Trip</label>
                        <p class="mb-0">{{ $fuelLog->trip->trip_number ?? 'N/A' }}</p>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Fuel Details -->
                <h6 class="text-orange mb-3"><i class="bx bx-gas-pump me-2"></i>Fuel Details</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Fuel Type</label>
                        <p class="mb-0">{{ $fuelLog->fuel_type ? ucfirst($fuelLog->fuel_type) : 'N/A' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Date Filled</label>
                        <p class="mb-0">{{ $fuelLog->date_filled ? $fuelLog->date_filled->format('Y-m-d') : 'N/A' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Time Filled</label>
                        <p class="mb-0">{{ $fuelLog->time_filled ? \Carbon\Carbon::parse($fuelLog->time_filled)->format('H:i') : 'N/A' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Fuel Station</label>
                        <p class="mb-0">{{ $fuelLog->fuel_station ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Liters Filled</label>
                        <p class="mb-0">{{ number_format($fuelLog->liters_filled ?? 0, 2) }} L</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Cost Per Liter (TZS)</label>
                        <p class="mb-0">{{ number_format($fuelLog->cost_per_liter ?? 0, 2) }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Total Cost (TZS)</label>
                        <p class="mb-0">{{ number_format($fuelLog->total_cost ?? 0, 2) }}</p>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Odometer Information -->
                <h6 class="text-orange mb-3"><i class="bx bx-tachometer me-2"></i>Odometer Information</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Current Odometer Reading</label>
                        <p class="mb-0">{{ number_format($fuelLog->odometer_reading ?? 0, 2) }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Previous Odometer Reading</label>
                        <p class="mb-0">{{ $fuelLog->previous_odometer !== null ? number_format($fuelLog->previous_odometer, 2) : 'N/A' }}</p>
                    </div>
                    @if($fuelLog->km_since_last_fill || $fuelLog->fuel_efficiency_km_per_liter || $fuelLog->cost_per_km)
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Km Since Last Fill</label>
                        <p class="mb-0">{{ $fuelLog->km_since_last_fill ? number_format($fuelLog->km_since_last_fill, 2) . ' km' : 'N/A' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Fuel Efficiency</label>
                        <p class="mb-0">{{ $fuelLog->fuel_efficiency_km_per_liter ? number_format($fuelLog->fuel_efficiency_km_per_liter, 2) . ' km/L' : 'N/A' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Cost Per Km (TZS)</label>
                        <p class="mb-0">{{ $fuelLog->cost_per_km ? number_format($fuelLog->cost_per_km, 2) : 'N/A' }}</p>
                    </div>
                    @endif
                </div>

                <hr class="my-4">

                <!-- Fuel Card Information -->
                <h6 class="text-orange mb-3"><i class="bx bx-credit-card me-2"></i>Fuel Card Information</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Fuel Card Used</label>
                        <p class="mb-0">{{ $fuelLog->fuel_card_used ? 'Yes' : 'No' }}</p>
                    </div>
                    @if($fuelLog->fuel_card_used)
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Fuel Card Number</label>
                        <p class="mb-0">{{ $fuelLog->fuel_card_number ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Fuel Card Type</label>
                        <p class="mb-0">{{ $fuelLog->fuel_card_type ?? 'N/A' }}</p>
                    </div>
                    @endif
                </div>

                <hr class="my-4">

                <!-- Cost Lines -->
                <h6 class="text-orange mb-3"><i class="bx bx-money me-2"></i>Cost Lines</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>GL Account</th>
                                <th>Liters Filled</th>
                                <th>Cost Per Liter</th>
                                <th>Fuel Station</th>
                                <th>Amount (TZS)</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($costLines as $line)
                            <tr>
                                <td>{{ $line->chartAccount->account_code ?? '' }} - {{ $line->chartAccount->account_name ?? 'N/A' }}</td>
                                <td>{{ $costLines->count() === 1 ? number_format($fuelLog->liters_filled ?? 0, 2) : '-' }}</td>
                                <td>{{ $costLines->count() === 1 ? number_format($fuelLog->cost_per_liter ?? 0, 2) : '-' }}</td>
                                <td>{{ $costLines->count() === 1 ? ($fuelLog->fuel_station ?? '-') : '-' }}</td>
                                <td>{{ number_format($line->amount ?? 0, 2) }}</td>
                                <td>{{ $line->description ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No cost lines</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Total Amount:</td>
                                <td class="fw-bold">{{ number_format($fuelLog->total_cost ?? 0, 2) }} TZS</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <hr class="my-4">

                <!-- Attachments -->
                @if($fuelLog->attachments && count($fuelLog->attachments) > 0)
                <h6 class="text-orange mb-3"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                <ul class="list-group list-group-flush">
                    @foreach($fuelLog->attachments as $att)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ asset('storage/'.($att['path'] ?? '')) }}" target="_blank">{{ $att['original_name'] ?? 'Attachment' }}</a>
                    </li>
                    @endforeach
                </ul>
                <hr class="my-4">
                @endif

                <!-- Receipt & Payment Information -->
                <h6 class="text-orange mb-3"><i class="bx bx-wallet me-2"></i>Receipt & Payment Information</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Receipt Number</label>
                        <p class="mb-0">{{ $fuelLog->receipt_number ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Paid From (Bank Account)</label>
                        <p class="mb-0">{{ $paidFromAccount ? $paidFromAccount->name . ($paidFromAccount->account_number ? ' - ' . $paidFromAccount->account_number : '') : 'N/A' }}</p>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Notes & Status -->
                <h6 class="text-orange mb-3"><i class="bx bx-note me-2"></i>Additional Notes</h6>
                <div class="row g-3">
                    <div class="col-md-12">
                        <p class="mb-0">{{ $fuelLog->notes ?? 'None' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Approval Status</label>
                        <p class="mb-0">
                            @php
                                $statusColors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                                $color = $statusColors[$fuelLog->approval_status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $color }}">{{ ucfirst($fuelLog->approval_status) }}</span>
                        </p>
                    </div>
                    @if($fuelLog->approval_status === 'approved' && $fuelLog->approvedBy)
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Approved By</label>
                        <p class="mb-0">{{ $fuelLog->approvedBy->name ?? 'N/A' }} on {{ $fuelLog->approved_at ? $fuelLog->approved_at->format('Y-m-d H:i') : '' }}</p>
                    </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
