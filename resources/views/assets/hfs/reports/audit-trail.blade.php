@extends('layouts.main')

@section('title', 'HFS Audit Trail')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Held for Sale', 'url' => route('assets.hfs.requests.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Audit Trail', 'url' => '#', 'icon' => 'bx bx-history']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-history me-2"></i>HFS Audit Trail</h5>
                    <div class="text-muted">Complete audit log of all HFS activities</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Print
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="exportToExcel()">
                        <i class="bx bx-file me-1"></i>Export Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="audit-trail-table">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>HFS Request #</th>
                                <th>Action</th>
                                <th>Action Type</th>
                                <th>User</th>
                                <th>Description</th>
                                <th>Related Entity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditLogs as $log)
                            <tr>
                                <td>{{ $log->action_date->format('d M Y H:i:s') }}</td>
                                <td>
                                    <a href="{{ route('assets.hfs.requests.show', \Vinkla\Hashids\Facades\Hashids::encode($log->hfs_id)) }}">
                                        {{ $log->hfsRequest->request_no ?? 'N/A' }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ ucfirst($log->action) }}</span>
                                </td>
                                <td>{{ ucfirst(str_replace('_', ' ', $log->action_type ?? 'general')) }}</td>
                                <td>{{ $log->user->name ?? 'System' }}</td>
                                <td>{{ $log->description }}</td>
                                <td>
                                    @if($log->related_type && $log->related_id)
                                        {{ ucfirst(str_replace('_', ' ', $log->related_type)) }} #{{ $log->related_id }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No audit log entries found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function exportToExcel() {
    window.location.href = '{{ route("assets.hfs.reports.audit-trail") }}?export=excel';
}
</script>
@endpush

