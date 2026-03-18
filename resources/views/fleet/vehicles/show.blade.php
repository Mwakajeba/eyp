@extends('layouts.main')

@section('title', 'View Vehicle: ' . $vehicle->name . ' - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Vehicle Master', 'url' => route('fleet.vehicles.index'), 'icon' => 'bx bx-car'],
            ['label' => 'View: ' . $vehicle->name, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <!-- Vehicle Details -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-car me-2"></i>{{ $vehicle->name }}</h6>
                        <div>
                            <a href="{{ route('fleet.vehicles.edit', $vehicle->hash_id) }}" class="btn btn-light btn-sm">
                                <i class="bx bx-edit me-1"></i>Edit
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Asset Code</label>
                                    <p class="mb-0">{{ $vehicle->code ?? 'Not assigned' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Category</label>
                                    <p class="mb-0">{{ $vehicle->category->name ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Model</label>
                                    <p class="mb-0">{{ $vehicle->model ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Manufacturer</label>
                                    <p class="mb-0">{{ $vehicle->manufacturer ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Serial Number/VIN</label>
                                    <p class="mb-0">{{ $vehicle->serial_number ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Purchase Cost</label>
                                    <p class="mb-0">{{ number_format($vehicle->purchase_cost, 2) }} TZS</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Purchase Date</label>
                                    <p class="mb-0">{{ $vehicle->purchase_date ? $vehicle->purchase_date->format('M d, Y') : 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Department</label>
                                    <p class="mb-0">{{ $vehicle->department->name ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Vehicle Specifications -->
                        <hr class="my-4">
                        <h6 class="text-primary mb-3"><i class="bx bx-car me-2"></i>Vehicle Specifications</h6>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Registration Number</label>
                                    <p class="mb-0">{{ $vehicle->registration_number ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ownership Type</label>
                                    <p class="mb-0">{{ $vehicle->ownership_type ? ucfirst($vehicle->ownership_type) : 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Fuel Type</label>
                                    <p class="mb-0">{{ $vehicle->fuel_type ? ucfirst($vehicle->fuel_type) : 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Capacity (Tons)</label>
                                    <p class="mb-0">{{ $vehicle->capacity_tons ? $vehicle->capacity_tons . ' tons' : 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Capacity (Volume)/km</label>
                                    <p class="mb-0">{{ $vehicle->capacity_volume !== null && $vehicle->capacity_volume !== '' ? $vehicle->capacity_volume . ' L/km' : 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Capacity (Passengers)</label>
                                    <p class="mb-0">{{ $vehicle->capacity_passengers ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">License Expiry</label>
                                    <p class="mb-0">{{ $vehicle->license_expiry_date ? $vehicle->license_expiry_date->format('M d, Y') : 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Inspection Expiry</label>
                                    <p class="mb-0">{{ $vehicle->inspection_expiry_date ? $vehicle->inspection_expiry_date->format('M d, Y') : 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Operational Status</label>
                                    <p class="mb-0">
                                        @php
                                            $statusColors = [
                                                'available' => 'success',
                                                'assigned' => 'primary',
                                                'in_repair' => 'warning',
                                                'retired' => 'secondary',
                                            ];
                                            $color = $statusColors[$vehicle->operational_status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ ucfirst(str_replace('_', ' ', $vehicle->operational_status ?? 'n/a')) }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">GPS Device ID</label>
                                    <p class="mb-0">{{ $vehicle->gps_device_id ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Current Location</label>
                                    <p class="mb-0">{{ $vehicle->current_location ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Physical Location</label>
                                    <p class="mb-0">{{ $vehicle->location ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        @if($vehicle->description)
                        <hr class="my-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <p class="mb-0">{{ $vehicle->description }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Status & Actions Sidebar -->
            <div class="col-lg-4">
                <!-- Status Cards -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Status Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Asset Status</label>
                            <p class="mb-0">
                                <span class="badge bg-success">{{ ucfirst($vehicle->status ?? 'active') }}</span>
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">HFS Status</label>
                            <p class="mb-0">
                                @if(!$vehicle->hfs_status || $vehicle->hfs_status == 'none')
                                    <span class="badge bg-secondary">Not HFS</span>
                                @else
                                    @php
                                        $hfsColors = [
                                            'pending' => 'warning',
                                            'classified' => 'info',
                                            'sold' => 'success',
                                            'cancelled' => 'secondary',
                                        ];
                                        $hfsColor = $hfsColors[$vehicle->hfs_status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $hfsColor }}">{{ ucfirst($vehicle->hfs_status) }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Depreciation Status</label>
                            <p class="mb-0">
                                @if($vehicle->depreciation_stopped)
                                    <span class="badge bg-danger">Stopped</span>
                                @else
                                    <span class="badge bg-success">Active</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Compliance Alerts -->
                @php
                    $today = now();
                    $alerts = [];

                    if ($vehicle->license_expiry_date && $vehicle->license_expiry_date->isBefore($today->copy()->addDays(30))) {
                        $alerts[] = ['type' => 'warning', 'message' => 'License expires soon: ' . $vehicle->license_expiry_date->format('M d, Y')];
                    }

                    if ($vehicle->inspection_expiry_date && $vehicle->inspection_expiry_date->isBefore($today->copy()->addDays(30))) {
                        $alerts[] = ['type' => 'warning', 'message' => 'Inspection expires soon: ' . $vehicle->inspection_expiry_date->format('M d, Y')];
                    }

                    if ($vehicle->insurance_expiry_date && $vehicle->insurance_expiry_date->isBefore($today->copy()->addDays(30))) {
                        $alerts[] = ['type' => 'danger', 'message' => 'Insurance expires soon: ' . $vehicle->insurance_expiry_date->format('M d, Y')];
                    }
                @endphp

                @if(count($alerts) > 0)
                <div class="card mb-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bx bx-bell me-2"></i>Compliance Alerts</h6>
                    </div>
                    <div class="card-body">
                        @foreach($alerts as $alert)
                        <div class="alert alert-{{ $alert['type'] }} py-2 px-3 mb-2">
                            <small>{{ $alert['message'] }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Attachments -->
                @if($vehicle->attachments && count(json_decode($vehicle->attachments, true) ?? []) > 0)
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            @php
                                $attachments = json_decode($vehicle->attachments, true) ?? [];
                            @endphp
                            @foreach($attachments as $index => $attachment)
                            <div class="col-md-6 col-lg-4">
                                <div class="d-flex align-items-center p-2 border rounded">
                                    <i class="bx bx-file me-2 text-info fs-4"></i>
                                    <div class="flex-grow-1">
                                        <small class="d-block fw-bold">Attachment {{ $index + 1 }}</small>
                                        <small class="text-muted">{{ basename($attachment) }}</small>
                                    </div>
                                    <a href="{{ asset('storage/' . $attachment) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="bx bx-show"></i> View
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('fleet.vehicles.edit', $vehicle->hash_id) }}" class="btn btn-outline-primary">
                                <i class="bx bx-edit me-1"></i>Edit Vehicle
                            </a>
                            <a href="{{ route('fleet.vehicles.index') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i>Back to List
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Driver Assignment History -->
                @if(isset($driverHistory) && $driverHistory->count() > 0)
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-history me-2"></i>Driver Assignment History</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Driver</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Duration</th>
                                        <th>Changed On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($driverHistory as $history)
                                        @if($history['driver_id'])
                                            @php
                                                $driver = $drivers->get($history['driver_id']);
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
                                                    @if($driver)
                                                        <a href="{{ route('fleet.drivers.show', $driver->hash_id) }}">
                                                            {{ $driver->full_name }}
                                                        </a>
                                                        <br>
                                                        <small class="text-muted">{{ $driver->driver_code }}</small>
                                                    @else
                                                        Driver #{{ $history['driver_id'] }}
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
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .badge {
        font-size: 0.75rem;
    }

    .alert {
        font-size: 0.875rem;
    }
</style>
@endpush