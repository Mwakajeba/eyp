@extends('layouts.main')

@section('title', 'View Maintenance Schedule - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Maintenance Schedules', 'url' => route('fleet.maintenance.schedules.index'), 'icon' => 'bx bx-calendar'],
            ['label' => 'View Schedule', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-purple text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-calendar me-2"></i>Schedule Details</h6>
                        <div>
                            <a href="{{ route('fleet.maintenance.schedules.edit', $schedule->hash_id) }}" class="btn btn-light btn-sm">
                                <i class="bx bx-edit me-1"></i>Edit
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Schedule Name</label>
                                <p class="mb-0">{{ $schedule->schedule_name }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vehicle</label>
                                <p class="mb-0">
                                    @if($schedule->vehicle)
                                        @php
                                            $vehicleHashId = method_exists($schedule->vehicle, 'getHashIdAttribute') 
                                                ? $schedule->vehicle->hash_id 
                                                : \Vinkla\Hashids\Facades\Hashids::encode($schedule->vehicle->id);
                                        @endphp
                                        <a href="{{ route('fleet.vehicles.show', $vehicleHashId) }}">
                                            {{ $schedule->vehicle->name }} ({{ $schedule->vehicle->registration_number ?? 'N/A' }})
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Maintenance Category</label>
                                <p class="mb-0">{{ $schedule->maintenance_category }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Schedule Type</label>
                                <p class="mb-0">
                                    <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $schedule->schedule_type)) }}</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <p class="mb-0">
                                    @php
                                        $statusColors = [
                                            'up_to_date' => 'success',
                                            'due_soon' => 'warning',
                                            'overdue' => 'danger',
                                            'completed' => 'info',
                                        ];
                                        $color = $statusColors[$schedule->current_status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ ucfirst(str_replace('_', ' ', $schedule->current_status)) }}</span>
                                    @if(!$schedule->is_active)
                                        <span class="badge bg-secondary ms-1">Inactive</span>
                                    @endif
                                </p>
                            </div>
                            @if($schedule->description)
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Description</label>
                                <p class="mb-0">{{ $schedule->description }}</p>
                            </div>
                            @endif
                        </div>

                        <hr class="my-4">

                        <!-- Time-Based Schedule -->
                        @if(in_array($schedule->schedule_type, ['time_based', 'both']))
                        <h6 class="text-purple mb-3"><i class="bx bx-time me-2"></i>Time-Based Schedule</h6>
                        <div class="row g-3">
                            @if($schedule->interval_days)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Interval (Days)</label>
                                <p class="mb-0">{{ $schedule->interval_days }} days</p>
                            </div>
                            @endif
                            @if($schedule->interval_months)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Interval (Months)</label>
                                <p class="mb-0">{{ $schedule->interval_months }} months</p>
                            </div>
                            @endif
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Alert Days Before</label>
                                <p class="mb-0">{{ $schedule->alert_days_before ?? 7 }} days</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Last Performed Date</label>
                                <p class="mb-0">{{ $schedule->last_performed_date ? $schedule->last_performed_date->format('Y-m-d') : 'Never' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Next Due Date</label>
                                <p class="mb-0">
                                    @if($schedule->next_due_date)
                                        <span class="{{ $schedule->next_due_date->isPast() ? 'text-danger' : '' }}">
                                            {{ $schedule->next_due_date->format('Y-m-d') }}
                                        </span>
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                        <hr class="my-4">
                        @endif

                        <!-- Mileage-Based Schedule -->
                        @if(in_array($schedule->schedule_type, ['mileage_based', 'both']))
                        <h6 class="text-purple mb-3"><i class="bx bx-tachometer me-2"></i>Mileage-Based Schedule</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Interval (Kilometers)</label>
                                <p class="mb-0">{{ number_format($schedule->interval_km ?? 0, 0) }} km</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Alert Kilometers Before</label>
                                <p class="mb-0">{{ number_format($schedule->alert_km_before ?? 500, 0) }} km</p>
                            </div>
                            @if($schedule->vehicle)
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Current Odometer</label>
                                <p class="mb-0">{{ number_format($schedule->vehicle->current_odometer ?? 0, 0) }} km</p>
                            </div>
                            @endif
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Last Performed Odometer</label>
                                <p class="mb-0">{{ $schedule->last_performed_odometer ? number_format($schedule->last_performed_odometer, 0) . ' km' : 'Never' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Next Due Odometer</label>
                                <p class="mb-0">
                                    @if($schedule->next_due_odometer)
                                        @php
                                            $currentOdometer = $schedule->vehicle->current_odometer ?? 0;
                                            $kmRemaining = $schedule->next_due_odometer - $currentOdometer;
                                        @endphp
                                        <span class="{{ $kmRemaining <= 0 ? 'text-danger' : '' }}">
                                            {{ number_format($schedule->next_due_odometer, 0) }} km
                                            @if($kmRemaining > 0)
                                                <small class="text-muted">({{ number_format($kmRemaining, 0) }} km remaining)</small>
                                            @endif
                                        </span>
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                        <hr class="my-4">
                        @endif

                        <!-- Cost & Settings -->
                        <h6 class="text-purple mb-3"><i class="bx bx-money me-2"></i>Cost & Settings</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Estimated Cost</label>
                                <p class="mb-0">{{ $schedule->estimated_cost ? number_format($schedule->estimated_cost, 2) . ' TZS' : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Block Dispatch When Overdue</label>
                                <p class="mb-0">
                                    @if($schedule->block_dispatch_when_overdue)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if($schedule->notes)
                        <hr class="my-4">
                        <h6 class="text-purple mb-3"><i class="bx bx-note me-2"></i>Notes</h6>
                        <p>{{ $schedule->notes }}</p>
                        @endif
                    </div>
                </div>

                <!-- Work Orders from this Schedule -->
                @if($schedule->workOrders->count() > 0)
                <div class="card mt-4">
                    <div class="card-header bg-purple text-white">
                        <h6 class="mb-0"><i class="bx bx-wrench me-2"></i>Work Orders from this Schedule</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>WO Number</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($schedule->workOrders as $wo)
                                    <tr>
                                        <td>{{ $wo->wo_number }}</td>
                                        <td><span class="badge bg-primary">{{ ucfirst($wo->maintenance_type) }}</span></td>
                                        <td>
                                            @php
                                                $woStatusColors = [
                                                    'draft' => 'secondary',
                                                    'scheduled' => 'info',
                                                    'in_progress' => 'warning',
                                                    'on_hold' => 'danger',
                                                    'completed' => 'success',
                                                    'cancelled' => 'dark',
                                                ];
                                                $woColor = $woStatusColors[$wo->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $woColor }}">{{ ucfirst(str_replace('_', ' ', $wo->status)) }}</span>
                                        </td>
                                        <td>{{ $wo->scheduled_date ? $wo->scheduled_date->format('Y-m-d') : 'N/A' }}</td>
                                        <td>
                                            <a href="{{ route('fleet.maintenance.work-orders.show', $wo->hash_id) }}" class="btn btn-sm btn-outline-info">
                                                <i class="bx bx-show"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-purple text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Timeline</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Created</h6>
                                    <p class="text-muted mb-0">{{ $schedule->created_at->format('Y-m-d H:i') }}</p>
                                    @if($schedule->createdBy)
                                        <small class="text-muted">By: {{ $schedule->createdBy->name }}</small>
                                    @endif
                                </div>
                            </div>
                            @if($schedule->updated_at != $schedule->created_at)
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Last Updated</h6>
                                    <p class="text-muted mb-0">{{ $schedule->updated_at->format('Y-m-d H:i') }}</p>
                                    @if($schedule->updatedBy)
                                        <small class="text-muted">By: {{ $schedule->updatedBy->name }}</small>
                                    @endif
                                </div>
                            </div>
                            @endif
                            @if($schedule->last_performed_date)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6>Last Performed</h6>
                                    <p class="text-muted mb-0">{{ $schedule->last_performed_date->format('Y-m-d') }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
