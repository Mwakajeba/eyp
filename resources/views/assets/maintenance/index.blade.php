@extends('layouts.main')

@section('title', 'Maintenance Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Maintenance', 'url' => '#', 'icon' => 'bx bx-wrench']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">Maintenance Management</h5>
                <p class="text-muted mb-0">Track and manage asset maintenance requests, work orders, and costs</p>
            </div>
            <a href="{{ route('assets.maintenance.settings') }}" class="btn btn-outline-secondary">
                <i class="bx bx-cog me-1"></i>Settings
            </a>
        </div>

        <!-- KPIs -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 mb-4">
            <div class="col">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1 small">Total Requests</p>
                                <h4 class="mb-0 fw-bold">{{ number_format($totalRequests ?? 0) }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-burning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class='bx bx-file'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-0 shadow-sm border-start border-4 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1 small">Pending Requests</p>
                                <h4 class="mb-0 fw-bold text-warning">{{ number_format($pendingRequests ?? 0) }}</h4>
                            </div>
                            <div class="widgets-icons bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class='bx bx-time-five'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-0 shadow-sm border-start border-4 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1 small">Open Work Orders</p>
                                <h4 class="mb-0 fw-bold text-primary">{{ number_format($openWorkOrders ?? 0) }}</h4>
                            </div>
                            <div class="widgets-icons bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class='bx bx-wrench'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-0 shadow-sm border-start border-4 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1 small">Completed This Month</p>
                                <h4 class="mb-0 fw-bold text-success">{{ number_format($completedThisMonth ?? 0) }}</h4>
                            </div>
                            <div class="widgets-icons bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class='bx bx-check-circle'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cost Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-top border-0 border-4 border-info">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Cost (YTD)</h6>
                        <h3 class="mb-0">TZS {{ number_format($totalCostYtd ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-top border-0 border-4 border-warning">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Expensed (YTD)</h6>
                        <h3 class="mb-0">TZS {{ number_format($expensedCostYtd ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-top border-0 border-4 border-success">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Capitalized (YTD)</h6>
                        <h3 class="mb-0">TZS {{ number_format($capitalizedCostYtd ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-category me-2"></i>Maintenance Types</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Define maintenance categories (Preventive, Corrective, Major Overhaul)</p>
                        <div class="d-flex gap-2">
                            <a href="{{ route('assets.maintenance.types.index') }}" class="btn btn-outline-success">
                                <i class="bx bx-list-ul me-1"></i>View All
                            </a>
                            <a href="{{ route('assets.maintenance.types.create') }}" class="btn btn-success">
                                <i class="bx bx-plus me-1"></i>New Type
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-file me-2"></i>Maintenance Requests</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Create and manage maintenance requests for assets</p>
                        <div class="d-flex gap-2">
                            <a href="{{ route('assets.maintenance.requests.index') }}" class="btn btn-outline-primary">
                                <i class="bx bx-list-ul me-1"></i>View All
                            </a>
                            <a href="{{ route('assets.maintenance.requests.create') }}" class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i>New Request
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-wrench me-2"></i>Work Orders</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Manage work orders and track maintenance execution</p>
                        <div class="d-flex gap-2">
                            <a href="{{ route('assets.maintenance.work-orders.index') }}" class="btn btn-outline-info">
                                <i class="bx bx-list-ul me-1"></i>View All
                            </a>
                            <a href="{{ route('assets.maintenance.work-orders.create') }}" class="btn btn-info">
                                <i class="bx bx-plus me-1"></i>New Work Order
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Maintenance -->
        @if(isset($upcomingMaintenance) && $upcomingMaintenance->count() > 0)
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bx bx-calendar me-2"></i>Upcoming Maintenance (Next 30 Days)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Request #</th>
                                <th>Asset</th>
                                <th>Type</th>
                                <th>Scheduled Date</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingMaintenance as $request)
                            <tr>
                                <td>{{ $request->request_number }}</td>
                                <td>{{ $request->asset->name ?? 'N/A' }}</td>
                                <td><span class="badge bg-info">{{ $request->maintenanceType->name ?? 'N/A' }}</span></td>
                                <td>{{ $request->preferred_start_date ? $request->preferred_start_date->format('M d, Y') : 'N/A' }}</td>
                                <td>
                                    @php
                                        $priorityColors = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
                                    @endphp
                                    <span class="badge bg-{{ $priorityColors[$request->priority] ?? 'secondary' }}">{{ ucfirst($request->priority) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('assets.maintenance.requests.show', \Vinkla\Hashids\Facades\Hashids::encode($request->id)) }}" class="btn btn-sm btn-outline-primary">
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

        <!-- Recent Work Orders -->
        @if(isset($recentWorkOrders) && $recentWorkOrders->count() > 0)
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bx bx-history me-2"></i>Recent Work Orders</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>WO Number</th>
                                <th>Asset</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Cost</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentWorkOrders as $wo)
                            <tr>
                                <td>{{ $wo->wo_number }}</td>
                                <td>{{ $wo->asset->name ?? 'N/A' }}</td>
                                <td><span class="badge bg-secondary">{{ $wo->maintenanceType->name ?? 'N/A' }}</span></td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'approved' => 'success',
                                            'in_progress' => 'primary',
                                            'on_hold' => 'warning',
                                            'completed' => 'info',
                                            'cancelled' => 'danger'
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$wo->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $wo->status)) }}</span>
                                </td>
                                <td>TZS {{ number_format($wo->total_actual_cost ?: $wo->total_estimated_cost, 2) }}</td>
                                <td>{{ $wo->created_at->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('assets.maintenance.work-orders.show', \Vinkla\Hashids\Facades\Hashids::encode($wo->id)) }}" class="btn btn-sm btn-outline-primary">
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
</div>
@endsection

