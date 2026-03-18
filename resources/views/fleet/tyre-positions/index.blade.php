@extends('layouts.main')

@section('title', 'Truck Tyre Configuration - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Truck Tyre Configuration', 'url' => '#', 'icon' => 'bx bx-grid-alt']
        ]" />

        <h6 class="mb-0 text-uppercase">Truck Tyre Configuration</h6>
        <hr />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs nav-bordered mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="positions-tab" data-bs-toggle="tab" data-bs-target="#positions" type="button" role="tab">
                    <i class="bx bx-grid-alt me-1"></i> Tyre Positions
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="approval-tab" data-bs-toggle="tab" data-bs-target="#approval" type="button" role="tab">
                    <i class="bx bx-cog me-1"></i> Approval Settings
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Tyre Positions Tab -->
            <div class="tab-pane fade show active" id="positions" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-grid-alt me-2"></i>Tyre Positions</h6>
                        <a href="{{ route('fleet.tyre-positions.create') }}" class="btn btn-light btn-sm text-dark"><i class="bx bx-plus me-1"></i>Add New Position</a>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Define tyre positions (Front Left, Rear Axle, etc.) so you can track which tyre is on which position.</p>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Code</th>
                                        <th>Position name</th>
                                        <th>Active</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($positions as $pos)
                                        <tr>
                                            <td>{{ $pos->sort_order }}</td>
                                            <td><code>{{ $pos->position_code ?? '—' }}</code></td>
                                            <td>{{ $pos->position_name }}</td>
                                            <td>@if($pos->is_active)<span class="badge bg-success">Yes</span>@else<span class="badge bg-secondary">No</span>@endif</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
<a href="{{ route('fleet.tyre-positions.show', $pos) }}" class="btn btn-outline-info" title="View"><i class="bx bx-show"></i></a>
                                            <a href="{{ route('fleet.tyre-positions.edit', $pos) }}" class="btn btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                                            <form action="{{ route('fleet.tyre-positions.destroy', $pos) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this position?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="Delete"><i class="bx bx-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No positions defined. <a href="{{ route('fleet.tyre-positions.create') }}">Add your first position</a>.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($positions->hasPages())
                            <div class="d-flex justify-content-center mt-3">
                                {{ $positions->withQueryString()->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Approval Settings Tab -->
            <div class="tab-pane fade" id="approval" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Tyre & Spare Replacement Approval Settings</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">Configure approval rules and thresholds for tyre replacement requests and spare part (Vipuri) replacements.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-info"><i class="bx bx-circle me-2"></i>Tyre Replacement Approval</h6>
                                        <p class="card-text small text-muted mb-2">Minimum km before same position can request replacement, approval thresholds, risk score rules, and multi-level approvers.</p>
                                        <span class="badge bg-secondary">Configure in approval settings</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-primary"><i class="bx bx-package me-2"></i>Spare Part (Vipuri) Replacement Approval</h6>
                                        <p class="card-text small text-muted mb-2">Cost thresholds for auto-approve vs finance approval, minimum interval checks, and approver workflow.</p>
                                        <span class="badge bg-secondary">Configure in approval settings</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('fleet.tyre-approval-settings.index') }}" class="btn btn-primary"><i class="bx bx-cog me-1"></i>Open Approval Settings</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
