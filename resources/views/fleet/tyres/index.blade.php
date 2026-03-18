@extends('layouts.main')

@section('title', 'Tyre Master Register - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Tyre Master Register', 'url' => '#', 'icon' => 'bx bx-circle']
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
            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-circle me-2"></i>Tyre Master Register</h5>
                    <div class="text-white-50">Every tyre must be registered before use. Identity, warranty, and expected lifespan.</div>
                </div>
                <div>
                    <a href="{{ route('fleet.tyres.create') }}" class="btn btn-light"><i class="bx bx-plus me-1"></i>Add New Tyre</a>
                </div>
            </div>
            <div class="card-body">
                <form method="get" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Serial, DOT, brand..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm select2-single">
                            <option value="">All statuses</option>
                            <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
                            <option value="in_use" {{ request('status') === 'in_use' ? 'selected' : '' }}>In use</option>
                            <option value="removed" {{ request('status') === 'removed' ? 'selected' : '' }}>Removed</option>
                            <option value="under_warranty_claim" {{ request('status') === 'under_warranty_claim' ? 'selected' : '' }}>Under warranty claim</option>
                            <option value="scrapped" {{ request('status') === 'scrapped' ? 'selected' : '' }}>Scrapped</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-search me-1"></i>Filter</button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Serial</th>
                                <th>DOT / Brand</th>
                                <th>Size</th>
                                <th>Purchase</th>
                                <th>Warranty</th>
                                <th>Expected life (km)</th>
                                <th>Status</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tyres as $tyre)
                                <tr>
                                    <td><strong>{{ $tyre->tyre_serial ?? '—' }}</strong></td>
                                    <td>{{ $tyre->dot_number ?? '—' }} / {{ $tyre->brand ?? '—' }}</td>
                                    <td>{{ $tyre->tyre_size ?? '—' }}</td>
                                    <td>{{ $tyre->purchase_date?->format('d/m/Y') ?? '—' }} ({{ $tyre->purchase_cost ? number_format($tyre->purchase_cost) : '—' }})</td>
                                    <td>{{ $tyre->warranty_type ? ucfirst($tyre->warranty_type) . ' ' . ($tyre->warranty_limit_value ?? '') : '—' }}</td>
                                    <td>{{ $tyre->expected_lifespan_km ? number_format($tyre->expected_lifespan_km) : '—' }}</td>
                                    <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $tyre->status)) }}</span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('fleet.tyres.show', $tyre) }}" class="btn btn-outline-info" title="View"><i class="bx bx-show"></i></a>
                                            <a href="{{ route('fleet.tyres.edit', $tyre) }}" class="btn btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                                            <form action="{{ route('fleet.tyres.destroy', $tyre) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this tyre?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Delete"><i class="bx bx-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No tyres registered yet. <a href="{{ route('fleet.tyres.create') }}">Add your first tyre</a>.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($tyres->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $tyres->withQueryString()->links() }}
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
