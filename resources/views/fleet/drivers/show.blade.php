@extends('layouts.main')

@section('title', 'View Driver: ' . $driver->full_name . ' - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Driver Master', 'url' => route('fleet.drivers.index'), 'icon' => 'bx bx-user'],
            ['label' => 'View: ' . $driver->full_name, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-user me-2"></i>{{ $driver->full_name }}</h6>
                        <div>
                            <a href="{{ route('fleet.drivers.edit', $driver->hash_id) }}" class="btn btn-light btn-sm">
                                <i class="bx bx-edit me-1"></i>Edit
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Driver Code</label>
                                <p class="mb-0">{{ $driver->driver_code ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Employee ID</label>
                                <p class="mb-0">{{ $driver->employee_id ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                <p class="mb-0">{{ $driver->phone_number ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <p class="mb-0">{{ $driver->email ?? 'N/A' }}</p>
                            </div>
                            @if($driver->address)
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Address</label>
                                <p class="mb-0">{{ $driver->address }}</p>
                            </div>
                            @endif
                        </div>

                        <hr class="my-4">
                        <h6 class="text-success mb-3"><i class="bx bx-id-card me-2"></i>License Information</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">License Number</label>
                                <p class="mb-0">{{ $driver->license_number }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">License Class</label>
                                <p class="mb-0">{{ $driver->license_class ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">License Expiry</label>
                                <p class="mb-0">
                                    {{ $driver->license_expiry_date ? $driver->license_expiry_date->format('M d, Y') : 'N/A' }}
                                    @if($driver->license_expiry_date)
                                        @if($driver->license_expiry_date->isPast())
                                            <span class="badge bg-danger ms-2">Expired</span>
                                        @elseif($driver->license_expiry_date->isBefore(now()->addDays(30)))
                                            <span class="badge bg-warning ms-2">Expiring Soon</span>
                                        @else
                                            <span class="badge bg-success ms-2">Valid</span>
                                        @endif
                                    @endif
                                </p>
                            </div>
                            @if($driver->license_issuing_authority)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Issuing Authority</label>
                                <p class="mb-0">{{ $driver->license_issuing_authority }}</p>
                            </div>
                            @endif
                        </div>

                        <hr class="my-4">
                        <h6 class="text-success mb-3"><i class="bx bx-briefcase me-2"></i>Employment Details</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Employment Type</label>
                                <p class="mb-0"><span class="badge bg-info">{{ ucfirst($driver->employment_type) }}</span></p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Status</label>
                                <p class="mb-0">
                                    @php
                                        $statusColors = [
                                            'active' => 'success',
                                            'inactive' => 'secondary',
                                            'suspended' => 'warning',
                                            'terminated' => 'danger',
                                        ];
                                        $color = $statusColors[$driver->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ ucfirst($driver->status) }}</span>
                                </p>
                            </div>
                            @if($driver->salary)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Salary</label>
                                <p class="mb-0">{{ number_format($driver->salary, 2) }} TZS</p>
                            </div>
                            @endif
                            @if($driver->daily_allowance_rate)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Daily Allowance Rate</label>
                                <p class="mb-0">{{ number_format($driver->daily_allowance_rate, 2) }} TZS</p>
                            </div>
                            @endif
                            @if($driver->overtime_rate)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Overtime Rate</label>
                                <p class="mb-0">{{ number_format($driver->overtime_rate, 2) }} TZS</p>
                            </div>
                            @endif
                        </div>

                        @if($driver->assignedVehicle)
                        <hr class="my-4">
                        <h6 class="text-success mb-3"><i class="bx bx-car me-2"></i>Vehicle Assignment</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Assigned Vehicle</label>
                                <p class="mb-0">
                                    <a href="{{ route('fleet.vehicles.show', $driver->assignedVehicle->hash_id) }}">
                                        {{ $driver->assignedVehicle->name }} ({{ $driver->assignedVehicle->registration_number ?? 'N/A' }})
                                    </a>
                                </p>
                            </div>
                            @if($driver->assignment_start_date)
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Assignment Start</label>
                                <p class="mb-0">{{ $driver->assignment_start_date->format('M d, Y') }}</p>
                            </div>
                            @endif
                            @if($driver->assignment_end_date)
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Assignment End</label>
                                <p class="mb-0">{{ $driver->assignment_end_date->format('M d, Y') }}</p>
                            </div>
                            @endif
                        </div>
                        @endif

                        <hr class="my-4">
                        <h6 class="text-success mb-3"><i class="bx bx-credit-card me-2"></i>Assigned Card</h6>
                        <div class="row g-3 align-items-center">
                            @if($driver->fuelCardAccount)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Card Name</label>
                                <p class="mb-0">{{ $driver->fuelCardAccount->name }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Account Number</label>
                                <p class="mb-0">{{ $driver->fuelCardAccount->account_number ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-2">
                                <form action="{{ route('fleet.drivers.assign-card', $driver->hash_id) }}" method="POST" class="d-inline unassign-card-form" data-driver-name="{{ $driver->full_name }}">
                                    @csrf
                                    <input type="hidden" name="fuel_card_bank_account_id" value="">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bx bx-x me-1"></i>Unassign Card
                                    </button>
                                </form>
                            </div>
                            @else
                            <div class="col-12">
                                <p class="mb-0 text-muted">No card assigned. Use the Assign Card action from the drivers list.</p>
                            </div>
                            @endif
                        </div>

                        @if($driver->emergency_contact_name || $driver->emergency_contact_phone)
                        <hr class="my-4">
                        <h6 class="text-success mb-3"><i class="bx bx-phone me-2"></i>Emergency Contact</h6>
                        <div class="row g-3">
                            @if($driver->emergency_contact_name)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Contact Name</label>
                                <p class="mb-0">{{ $driver->emergency_contact_name }}</p>
                            </div>
                            @endif
                            @if($driver->emergency_contact_phone)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Contact Phone</label>
                                <p class="mb-0">{{ $driver->emergency_contact_phone }}</p>
                            </div>
                            @endif
                            @if($driver->emergency_contact_relationship)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Relationship</label>
                                <p class="mb-0">{{ $driver->emergency_contact_relationship }}</p>
                            </div>
                            @endif
                        </div>
                        @endif

                        @if($driver->notes)
                        <hr class="my-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Notes</label>
                            <p class="mb-0">{{ $driver->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Vehicle Assignment History -->
                @if($assignmentHistory && $assignmentHistory->count() > 0)
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-history me-2"></i>Vehicle Assignment History</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Duration</th>
                                        <th>Changed On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assignmentHistory as $history)
                                        @if($history['vehicle_id'])
                                            @php
                                                $vehicle = $vehicles->get($history['vehicle_id']);
                                                $startDate = $history['start_date'];
                                                $endDate = $history['end_date'];
                                                $duration = '';
                                                
                                                if ($startDate && $endDate) {
                                                    $days = $startDate->diffInDays($endDate);
                                                    $duration = $days . ' days';
                                                } elseif ($startDate) {
                                                    $days = $startDate->diffInDays(now());
                                                    $duration = $days . ' days (ongoing)';
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    @if($vehicle)
                                                        <a href="{{ route('fleet.vehicles.show', $vehicle->hash_id) }}">
                                                            {{ $vehicle->name }} ({{ $vehicle->registration_number ?? 'N/A' }})
                                                        </a>
                                                    @else
                                                        Vehicle #{{ $history['vehicle_id'] }}
                                                    @endif
                                                </td>
                                                <td>{{ $startDate ? $startDate->format('M d, Y') : 'N/A' }}</td>
                                                <td>
                                                    @if($endDate)
                                                        {{ $endDate->format('M d, Y') }}
                                                        @if($endDate->isPast())
                                                            <span class="badge bg-secondary badge-sm">Ended</span>
                                                        @else
                                                            <span class="badge bg-success badge-sm">Active</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">Ongoing</span>
                                                    @endif
                                                </td>
                                                <td>{{ $duration }}</td>
                                                <td>{{ $history['changed_at']->format('M d, Y H:i') }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('fleet.drivers.edit', $driver->hash_id) }}" class="btn btn-outline-success">
                                <i class="bx bx-edit me-1"></i>Edit Driver
                            </a>
                            <a href="{{ route('fleet.drivers.index') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i>Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.unassign-card-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Unassign card?',
                text: 'This will remove the fuel card from this driver so it can be assigned to someone else.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, unassign'
            }).then(function(result) {
                if (result.isConfirmed) doUnassign(form);
            });
        } else if (!confirm('Unassign this card from the driver?')) return;
        else doUnassign(form);
    });
});
function doUnassign(form) {
    var btn = form.querySelector('button[type="submit"]');
    var originalHtml = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Unassigning...'; }
    var token = form.querySelector('input[name="_token"]');
    fetch(form.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token ? token.value : '',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ fuel_card_bank_account_id: '' })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Done', text: data.message || 'Card unassigned.' }).then(function() { window.location.reload(); });
            else { alert(data.message || 'Card unassigned.'); window.location.reload(); }
        } else {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to unassign card.' });
            else alert(data.message || 'Failed to unassign card.');
            if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
        }
    })
    .catch(function() {
        if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'Request failed.' });
        else alert('Request failed.');
    });
}
</script>
@endpush
@endsection
