@extends('layouts.main')

@section('title', 'Fleet Settings - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">FLEET SETTINGS</h6>
            <a href="{{ route('fleet.approval-settings.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bx bx-check-shield me-1"></i> Approval Settings
            </a>
        </div>
        <hr />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('fleet.settings.update') }}" id="fleet-settings-form">
            @csrf
            @method('PUT')

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs nav-bordered mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                        <i class="bx bx-cog me-1"></i> General Settings
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="trip-tab" data-bs-toggle="tab" data-bs-target="#trip" type="button" role="tab">
                        <i class="bx bx-map me-1"></i> Trip Management
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="fuel-tab" data-bs-toggle="tab" data-bs-target="#fuel" type="button" role="tab">
                        <i class="bx bx-gas-pump me-1"></i> Fuel & Tracking
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="vehicle-tab" data-bs-toggle="tab" data-bs-target="#vehicle" type="button" role="tab">
                        <i class="bx bx-car me-1"></i> Vehicle & Driver
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="billing-tab" data-bs-toggle="tab" data-bs-target="#billing" type="button" role="tab">
                        <i class="bx bx-money me-1"></i> Billing & Costs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="gps-tab" data-bs-toggle="tab" data-bs-target="#gps" type="button" role="tab">
                        <i class="bx bx-map-alt me-1"></i> GPS & Monitoring
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- General Settings Tab -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bx bx-cog me-2"></i>General Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Default Currency <span class="text-danger">*</span></label>
                                    <select name="default_currency" class="form-select" required>
                                        <option value="TZS" {{ ($settings['default_currency'] ?? 'TZS') == 'TZS' ? 'selected' : '' }}>TZS - Tanzanian Shilling</option>
                                        <option value="USD" {{ ($settings['default_currency'] ?? '') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                        <option value="EUR" {{ ($settings['default_currency'] ?? '') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                        <option value="KES" {{ ($settings['default_currency'] ?? '') == 'KES' ? 'selected' : '' }}>KES - Kenyan Shilling</option>
                                    </select>
                                    <div class="form-text">Default currency for all fleet costs and billing</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Fuel Efficiency Unit <span class="text-danger">*</span></label>
                                    <select name="default_fuel_efficiency_unit" class="form-select" required>
                                        <option value="km/l" {{ ($settings['default_fuel_efficiency_unit'] ?? 'km/l') == 'km/l' ? 'selected' : '' }}>km/l (Kilometers per Liter)</option>
                                        <option value="mpg" {{ ($settings['default_fuel_efficiency_unit'] ?? '') == 'mpg' ? 'selected' : '' }}>mpg (Miles per Gallon)</option>
                                    </select>
                                    <div class="form-text">Unit for measuring fuel efficiency</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Trip Number Prefix</label>
                                    <input type="text" name="trip_number_prefix" class="form-control" value="{{ $settings['trip_number_prefix'] ?? '' }}" maxlength="20" placeholder="e.g., TRIP-">
                                    <div class="form-text">Prefix for trip numbering (optional)</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Work Order Number Prefix</label>
                                    <input type="text" name="work_order_number_prefix" class="form-control" value="{{ $settings['work_order_number_prefix'] ?? '' }}" maxlength="20" placeholder="e.g., WO-">
                                    <div class="form-text">Prefix for work order numbering (optional)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trip Management Tab -->
                <div class="tab-pane fade" id="trip" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bx bx-map me-2"></i>Trip Management Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Default Trip Status <span class="text-danger">*</span></label>
                                    <select name="default_trip_status" class="form-select" required>
                                        <option value="planned" {{ ($settings['default_trip_status'] ?? 'planned') == 'planned' ? 'selected' : '' }}>Planned</option>
                                        <option value="dispatched" {{ ($settings['default_trip_status'] ?? '') == 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                                    </select>
                                    <div class="form-text">Status assigned when a new trip is created. Use Planned so trips can be reviewed and dispatched later.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Trip Approval Workflow</label>
                                    <select name="trip_approval_workflow_id" class="form-select">
                                        <option value="">— No workflow (auto-approved when approval not required) —</option>
                                        @foreach($tripApprovalWorkflows ?? [] as $wf)
                                            <option value="{{ $wf->id }}" {{ ($settings['trip_approval_workflow_id'] ?? '') == $wf->id ? 'selected' : '' }}>{{ $wf->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Workflow used when trip approval is required (approvers and levels defined in workflow)</div>
                                </div>

                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="default_trip_approval_required" value="1" id="trip_approval" {{ ($settings['default_trip_approval_required'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="trip_approval">
                                            Require Approval for Trip Requests
                                        </label>
                                        <div class="form-text">When enabled, new trips are created with approval status &quot;Pending&quot; and must be approved before dispatch. Use the workflow above to define approval levels and approvers.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="enable_trip_tracking" value="1" id="trip_tracking" {{ ($settings['enable_trip_tracking'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="trip_tracking">
                                            Enable GPS Trip Tracking
                                        </label>
                                        <div class="form-text">Track trips using GPS coordinates</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auto_calculate_distances" value="1" id="auto_distance" {{ ($settings['auto_calculate_distances'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="auto_distance">
                                            Auto-calculate Distances
                                        </label>
                                        <div class="form-text">Automatically calculate trip distances using GPS</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="enable_route_optimization" value="1" id="route_optimization" {{ ($settings['enable_route_optimization'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="route_optimization">
                                            Enable Route Optimization
                                        </label>
                                        <div class="form-text">Suggest optimal routes for trips</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="require_vehicle_inspection" value="1" id="vehicle_inspection" {{ ($settings['require_vehicle_inspection'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="vehicle_inspection">
                                            Require Vehicle Inspection Before Trips
                                        </label>
                                        <div class="form-text">Mandatory vehicle inspection before trip dispatch</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fuel & Tracking Tab -->
                <div class="tab-pane fade" id="fuel" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="bx bx-gas-pump me-2"></i>Fuel & Tracking Settings</h6>
                        </div>
                        <div class="card-body">
                            <h6 class="text-warning border-bottom pb-2 mb-3">Fuel consumption & efficiency</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="enable_fuel_tracking" value="1" id="fuel_tracking" {{ ($settings['enable_fuel_tracking'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="fuel_tracking">
                                            Enable Fuel Consumption Tracking
                                        </label>
                                        <div class="form-text">Record fuel refills and consumption per vehicle/trip for efficiency reports</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Low fuel alert threshold (%) <span class="text-danger">*</span></label>
                                    <input type="number" name="fuel_low_threshold_percentage" class="form-control" value="{{ $settings['fuel_low_threshold_percentage'] ?? '20' }}" min="0" max="100" required>
                                    <div class="form-text">Trigger low-fuel alert when tank level falls below this percentage</div>
                                </div>
                            </div>

                            <h6 class="text-warning border-bottom pb-2 mb-3 mt-4">Integrations & odometer</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="enable_fuel_card_integration" value="1" id="fuel_card" {{ ($settings['enable_fuel_card_integration'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="fuel_card">
                                            Enable Fuel Card Integration
                                        </label>
                                        <div class="form-text">Link fuel card data for automatic fuel log entries (when supported)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="enable_odometer_tracking" value="1" id="odometer_tracking" {{ ($settings['enable_odometer_tracking'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="odometer_tracking">
                                            Enable Odometer Tracking
                                        </label>
                                        <div class="form-text">Capture start/end odometer on dispatch and completion for distance and efficiency</div>
                                    </div>
                                </div>
                            </div>

                            <h6 class="text-warning border-bottom pb-2 mb-3 mt-4">Maintenance reminders</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="enable_maintenance_alerts" value="1" id="maintenance_alerts" {{ ($settings['enable_maintenance_alerts'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="maintenance_alerts">
                                            Enable Maintenance Reminder Alerts
                                        </label>
                                        <div class="form-text">Notify when scheduled maintenance is due (e.g. service, oil change)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Reminder lead time (days) <span class="text-danger">*</span></label>
                                    <input type="number" name="maintenance_reminder_days" class="form-control" value="{{ $settings['maintenance_reminder_days'] ?? '7' }}" min="0" max="365" required>
                                    <div class="form-text">Days before due date to send the first reminder</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehicle & Driver Tab -->
                <div class="tab-pane fade" id="vehicle" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="bx bx-car me-2"></i>Vehicle & Driver Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="require_driver_license_check" value="1" id="license_check" {{ ($settings['require_driver_license_check'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="license_check">
                                            Require Driver License Validation
                                        </label>
                                        <div class="form-text">Validate driver licenses before assignment</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="require_vehicle_inspection" value="1" id="vehicle_inspection2" {{ ($settings['require_vehicle_inspection'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="vehicle_inspection2">
                                            Require Vehicle Inspection Before Trips
                                        </label>
                                        <div class="form-text">Mandatory inspection before trip dispatch</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Billing & Costs Tab -->
                <div class="tab-pane fade" id="billing" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0"><i class="bx bx-money me-2"></i>Billing & Cost Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="enable_cost_approval" value="1" id="cost_approval" {{ ($settings['enable_cost_approval'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="cost_approval">
                                            Enable Cost Approval Workflow
                                        </label>
                                        <div class="form-text">Require approval for costs above threshold</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Cost Approval Workflow</label>
                                    <select name="cost_approval_workflow_id" class="form-select">
                                        <option value="">— No workflow (use threshold only) —</option>
                                        @foreach($costApprovalWorkflows ?? [] as $wf)
                                            <option value="{{ $wf->id }}" {{ ($settings['cost_approval_workflow_id'] ?? '') == $wf->id ? 'selected' : '' }}>{{ $wf->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Select a workflow to define who can approve costs (by name or position). Create workflows under Fleet → Approval Workflows with type &quot;Cost Approval&quot; and assign approvers there.</div>
                                </div>

                                @php
                                    $costApprovalWorkflow = isset($settings['cost_approval_workflow_id']) && $settings['cost_approval_workflow_id']
                                        ? collect($costApprovalWorkflows ?? [])->firstWhere('id', $settings['cost_approval_workflow_id'])
                                        : null;
                                @endphp
                                @if($costApprovalWorkflow && $costApprovalWorkflow->approvers && $costApprovalWorkflow->approvers->isNotEmpty())
                                <div class="col-12">
                                    <label class="form-label text-success">Who can approve (cost approval)</label>
                                    <ul class="list-group list-group-flush">
                                        @foreach($costApprovalWorkflow->approvers as $approver)
                                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                <span>{{ $approver->user->name ?? 'User #' . $approver->user_id }}</span>
                                                <span class="text-muted">Order {{ $approver->approval_order ?? $loop->iteration }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif

                                <div class="col-md-6">
                                    <label class="form-label">Cost Approval Threshold (TZS)</label>
                                    <input type="number" name="cost_approval_threshold" class="form-control" value="{{ $settings['cost_approval_threshold'] ?? '' }}" min="0" step="0.01" placeholder="0.00">
                                    <div class="form-text">Costs above this amount require approval</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="enable_automatic_billing" value="1" id="auto_billing" {{ ($settings['enable_automatic_billing'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="auto_billing">
                                            Enable Automatic Billing
                                        </label>
                                        <div class="form-text">Automatically generate invoices for completed trips</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Default Payment Terms (Days)</label>
                                    <input type="number" name="default_payment_terms" class="form-control" value="{{ $settings['default_payment_terms'] ?? '' }}" min="0" placeholder="e.g., 30">
                                    <div class="form-text">Default payment terms for invoices</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart Account Settings -->
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bx bx-book me-2"></i>Chart Account Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Income Chart Account <span class="text-danger">*</span></label>
                                    <select name="fleet_income_chart_account_id" class="form-select select2-single" required>
                                        <option value="">Select Income Account</option>
                                        @foreach($chartAccounts as $account)
                                            <option value="{{ $account->id }}" {{ ($settings['fleet_income_chart_account_id'] ?? '') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Chart account for fleet income/revenue</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Receivable Chart Account <span class="text-danger">*</span></label>
                                    <select name="fleet_receivable_chart_account_id" class="form-select select2-single" required>
                                        <option value="">Select Receivable Account</option>
                                        @foreach($chartAccounts as $account)
                                            <option value="{{ $account->id }}" {{ ($settings['fleet_receivable_chart_account_id'] ?? '') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Chart account for fleet receivables</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Opening Balance Chart Account</label>
                                    <select name="fleet_opening_balance_chart_account_id" class="form-select select2-single">
                                        <option value="">Select Opening Balance Account</option>
                                        @foreach($chartAccounts as $account)
                                            <option value="{{ $account->id }}" {{ ($settings['fleet_opening_balance_chart_account_id'] ?? '') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Chart account for fleet opening balance</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GPS & Monitoring Tab -->
                <div class="tab-pane fade" id="gps" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="bx bx-map-alt me-2"></i>GPS & Monitoring Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="enable_gps_tracking" value="1" id="gps_tracking" {{ ($settings['enable_gps_tracking'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="gps_tracking">
                                            Enable GPS Tracking
                                        </label>
                                        <div class="form-text">Enable real-time GPS tracking for vehicles</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">GPS Update Interval (Minutes)</label>
                                    <input type="number" name="gps_update_interval_minutes" class="form-control" value="{{ $settings['gps_update_interval_minutes'] ?? '5' }}" min="1" max="60" placeholder="5">
                                    <div class="form-text">How often to update GPS coordinates</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="enable_driver_behavior_tracking" value="1" id="behavior_tracking" {{ ($settings['enable_driver_behavior_tracking'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="behavior_tracking">
                                            Enable Driver Behavior Tracking
                                        </label>
                                        <div class="form-text">Monitor driver behavior and driving patterns</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="enable_speed_alerts" value="1" id="speed_alerts" {{ ($settings['enable_speed_alerts'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="speed_alerts">
                                            Enable Speed Alerts
                                        </label>
                                        <div class="form-text">Send alerts when vehicles exceed speed limit</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Speed Alert Threshold (km/h)</label>
                                    <input type="number" name="speed_alert_threshold_kmh" class="form-control" value="{{ $settings['speed_alert_threshold_kmh'] ?? '' }}" min="0" step="1" placeholder="e.g., 120">
                                    <div class="form-text">Speed limit to trigger alerts</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="enable_idle_time_tracking" value="1" id="idle_tracking" {{ ($settings['enable_idle_time_tracking'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="idle_tracking">
                                            Enable Idle Time Tracking
                                        </label>
                                        <div class="form-text">Track vehicle idle time</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Idle Time Threshold (Minutes)</label>
                                    <input type="number" name="idle_time_threshold_minutes" class="form-control" value="{{ $settings['idle_time_threshold_minutes'] ?? '10' }}" min="1" placeholder="10">
                                    <div class="form-text">Alert when vehicle is idle for this duration</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('fleet.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Fleet Management
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Save Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for chart account dropdowns
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: 'Select an option...'
        });
    }

    // Handle checkbox values for boolean fields
    $('#fleet-settings-form').on('submit', function() {
        // Set unchecked checkboxes to 0
        $(this).find('input[type="checkbox"]').each(function() {
            if (!$(this).is(':checked')) {
                $(this).after('<input type="hidden" name="' + $(this).attr('name') + '" value="0">');
            }
        });
    });

    // Copy to clipboard function
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('URL copied to clipboard!');
        }, function(err) {
            console.error('Failed to copy: ', err);
        });
    }
});
</script>
@endpush
