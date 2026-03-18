@extends('layouts.main')

@section('title', 'Payroll Audit Trail Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Payroll Reports', 'url' => route('hr.payroll-reports.index'), 'icon' => 'bx bx-money'],
                ['label' => 'Payroll Audit Trail', 'url' => '#', 'icon' => 'bx bx-history']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-history me-2"></i>Payroll Audit Trail Report
                            </h4>
                            <div>
                                <form action="{{ route('hr.payroll-reports.payroll-audit-trail.export-excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="payroll_id" value="{{ $payrollId }}">
                                    <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                                    <input type="hidden" name="date_to" value="{{ $dateTo }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-download me-1"></i>Export Excel
                                    </button>
                                </form>
                                <form action="{{ route('hr.payroll-reports.payroll-audit-trail.export-pdf') }}" method="POST" class="d-inline" target="_blank">
                                    @csrf
                                    <input type="hidden" name="payroll_id" value="{{ $payrollId }}">
                                    <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                                    <input type="hidden" name="date_to" value="{{ $dateTo }}">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Payroll</label>
                                <select class="form-select select2-single" name="payroll_id" data-placeholder="All Payrolls">
                                    <option value="">All Payrolls</option>
                                    @foreach($payrolls as $payroll)
                                        <option value="{{ $payroll->id }}" {{ $payrollId == $payroll->id ? 'selected' : '' }}>
                                            {{ $payroll->reference }} - {{ \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Audit Logs Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Payroll</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Field</th>
                                        <th>Old Value</th>
                                        <th>New Value</th>
                                        <th>Description</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($auditLogs as $log)
                                        <tr>
                                            <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                            <td>
                                                @if($log->payroll)
                                                    <a href="{{ route('hr.payrolls.show', $log->payroll->hash_id) }}">
                                                        {{ $log->payroll->reference }}
                                                    </a>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $log->user->name ?? 'System' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $log->action == 'created' ? 'success' : ($log->action == 'updated' ? 'info' : ($log->action == 'deleted' ? 'danger' : 'warning')) }}">
                                                    {{ ucfirst($log->action) }}
                                                </span>
                                            </td>
                                            <td>{{ $log->field ?? '-' }}</td>
                                            <td>
                                                @if($log->old_value)
                                                    <small class="text-muted">{{ Str::limit($log->old_value, 30) }}</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @if($log->new_value)
                                                    <small class="text-success">{{ Str::limit($log->new_value, 30) }}</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $log->description ?? '-' }}</td>
                                            <td>{{ $log->reason ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">No audit logs found for the selected criteria.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-3">
                            {{ $auditLogs->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

