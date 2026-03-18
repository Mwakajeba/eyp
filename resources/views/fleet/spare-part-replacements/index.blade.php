@extends('layouts.main')

@section('title', 'Spare Parts (Vipuri) Replacement - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Spare Parts Replacement', 'url' => route('fleet.spare-part-replacements.index'), 'icon' => 'bx bx-refresh']
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
            <div class="card-header d-flex justify-content-between align-items-center bg-orange text-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-refresh me-2"></i>Spare Parts (Vipuri) Replacement</h5>
                    <div class="text-white-50">Record and approve spare part replacements. System checks last replacement and expected lifespan.</div>
                </div>
                <div>
                    <a href="{{ route('fleet.spare-part-replacements.create') }}" class="btn btn-warning text-dark"><i class="bx bx-plus me-1"></i>Add New Replacement</a>
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
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-orange"><i class="bx bx-search me-1"></i>Filter</button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Part category</th>
                                <th>Replaced at</th>
                                <th>Odometer</th>
                                <th>Cost</th>
                                <th>Status</th>
                                <th width="80">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($replacements as $rep)
                                <tr>
                                    <td>{{ $rep->vehicle?->name ?? $rep->vehicle?->registration_number ?? $rep->vehicle_id }}</td>
                                    <td>{{ $rep->sparePartCategory?->name ?? $rep->spare_part_category_id }}</td>
                                    <td>{{ $rep->replaced_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $rep->odometer_at_replacement ? number_format($rep->odometer_at_replacement) : '—' }}</td>
                                    <td>{{ $rep->cost ? number_format($rep->cost) : '—' }}</td>
                                    <td><span class="badge bg-{{ $rep->status === 'approved' ? 'success' : ($rep->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($rep->status) }}</span></td>
                                    <td><a href="{{ route('fleet.spare-part-replacements.show', $rep) }}" class="btn btn-outline-info btn-sm" title="View"><i class="bx bx-show"></i></a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No spare part replacements yet. <a href="{{ route('fleet.spare-part-replacements.create') }}">Add your first replacement</a>.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($replacements->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $replacements->withQueryString()->links() }}
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
