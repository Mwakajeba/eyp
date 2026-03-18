@extends('layouts.main')

@section('title', 'Spare Parts Master (Vipuri) - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Spare Parts Master (Vipuri)', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />

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

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-purple text-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-package me-2"></i>Spare Parts Master (Vipuri)</h5>
                    <div class="text-white-50">Define categories with expected lifespan, replacement intervals, and approval thresholds.</div>
                </div>
                <div>
                    <a href="{{ route('fleet.spare-part-categories.create') }}" class="btn btn-light"><i class="bx bx-plus me-1"></i>Add New Category</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Expected life (km / months)</th>
                                <th>Min interval</th>
                                <th>Cost range</th>
                                <th>Approval threshold</th>
                                <th>Active</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $cat)
                                <tr>
                                    <td><strong>{{ $cat->name }}</strong></td>
                                    <td>{{ $cat->expected_lifespan_km ? number_format($cat->expected_lifespan_km) . ' km' : '—' }} / {{ $cat->expected_lifespan_months ? $cat->expected_lifespan_months . ' mo' : '—' }}</td>
                                    <td>{{ $cat->min_replacement_interval_km ? number_format($cat->min_replacement_interval_km) . ' km' : '—' }} / {{ $cat->min_replacement_interval_months ? $cat->min_replacement_interval_months . ' mo' : '—' }}</td>
                                    <td>{{ $cat->standard_cost_min || $cat->standard_cost_max ? (number_format($cat->standard_cost_min ?? 0) . ' – ' . number_format($cat->standard_cost_max ?? 0)) : '—' }}</td>
                                    <td>{{ $cat->approval_threshold ? number_format($cat->approval_threshold) : '—' }}</td>
                                    <td>@if($cat->is_active)<span class="badge bg-success">Yes</span>@else<span class="badge bg-secondary">No</span>@endif</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('fleet.spare-part-categories.show', $cat->id) }}" class="btn btn-outline-info" title="View"><i class="bx bx-show"></i></a>
                                            <a href="{{ route('fleet.spare-part-categories.edit', $cat->id) }}" class="btn btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                                            <form action="{{ route('fleet.spare-part-categories.destroy', $cat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Delete"><i class="bx bx-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No spare part categories yet. <a href="{{ route('fleet.spare-part-categories.create') }}">Add your first category</a>.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($categories->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $categories->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-purple { background-color: #6f42c1 !important; }
</style>
@endpush
