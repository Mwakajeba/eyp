@extends('layouts.main')

@section('title', 'Tyre Replacement Requests - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Tyre Replacement Requests', 'url' => '#', 'icon' => 'bx bx-error-circle']
        ]" />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-danger text-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-error-circle me-2"></i>Tyre Replacement Requests</h5>
                    <div class="text-white-50">Driver requests with photos; system validates mileage, warranty, and risk. Multi-level approval.</div>
                </div>
                <div>
                    <a href="{{ route('fleet.tyre-replacement-requests.create') }}" class="btn btn-light"><i class="bx bx-plus me-1"></i>Add New Request</a>
                </div>
            </div>
            <div class="card-body">
                <form method="get" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <select name="status" class="form-select form-select-sm select2-single">
                            <option value="">All statuses</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="inspected" {{ request('status') === 'inspected' ? 'selected' : '' }}>Inspected</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="bx bx-search me-1"></i>Filter</button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Position</th>
                                <th>Reason</th>
                                <th>Mileage used</th>
                                <th>Risk</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th width="80">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $req)
                                <tr>
                                    <td>{{ $req->vehicle?->name ?? $req->vehicle?->registration_number ?? $req->vehicle_id }}</td>
                                    <td>{{ $req->tyrePosition?->position_name ?? $req->tyre_position_id }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $req->reason)) }}</td>
                                    <td>{{ $req->tyre_mileage_used ? number_format($req->tyre_mileage_used) . ' km' : '—' }}</td>
                                    <td>@if($req->risk_score)<span class="badge bg-{{ $req->risk_score === 'high' ? 'danger' : ($req->risk_score === 'medium' ? 'warning' : 'secondary') }}">{{ ucfirst($req->risk_score) }}</span>@else—@endif</td>
                                    <td><span class="badge bg-{{ $req->status === 'approved' ? 'success' : ($req->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($req->status) }}</span></td>
                                    <td>{{ $req->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td><a href="{{ route('fleet.tyre-replacement-requests.show', $req) }}" class="btn btn-outline-info btn-sm" title="View"><i class="bx bx-show"></i></a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No replacement requests yet. <a href="{{ route('fleet.tyre-replacement-requests.create') }}">Add your first request</a>.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($requests->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $requests->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-single').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Type to search...', allowClear: true, minimumResultsForSearch: 0 });
    }
});
</script>
@endpush
