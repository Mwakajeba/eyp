@extends('layouts.main')

@section('title', 'Statutory Compliance Report (Enhanced)')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Payroll Reports', 'url' => route('hr.payroll-reports.index'), 'icon' => 'bx bx-money'],
                ['label' => 'Statutory Compliance (Enhanced)', 'url' => '#', 'icon' => 'bx bx-check-circle']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-check-circle me-2"></i>Statutory Compliance Report (Enhanced)
                            </h4>
                            <div>
                                <form action="{{ route('hr.payroll-reports.statutory-compliance-enhanced.export-excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="department_id" value="{{ $departmentId }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-download me-1"></i>Export Excel
                                    </button>
                                </form>
                                <form action="{{ route('hr.payroll-reports.statutory-compliance-enhanced.export-pdf') }}" method="POST" class="d-inline" target="_blank">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="department_id" value="{{ $departmentId }}">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Year</label>
                                <select class="form-select" name="year">
                                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Month</label>
                                <select class="form-select" name="month">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Department</label>
                                <select class="form-select select2-single" name="department_id" data-placeholder="All Departments">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Compliant Employees</h5>
                                        <h3 class="mb-0">{{ number_format($totalCompliant) }}</h3>
                                        <small class="text-muted">Out of {{ count($complianceData) }} employees</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Violations</h5>
                                        <h3 class="mb-0">{{ number_format($totalViolations) }}</h3>
                                        <small class="text-muted">Critical issues found</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Warnings</h5>
                                        <h3 class="mb-0">{{ number_format($totalWarnings) }}</h3>
                                        <small class="text-muted">Non-critical issues</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Avg. Compliance Score</h5>
                                        <h3 class="mb-0">
                                            @php
                                                $avgScore = 0;
                                                if (count($complianceData) > 0) {
                                                    $totalScore = array_sum(array_map(function($item) {
                                                        return $item['compliance']['compliance_score'] ?? 0;
                                                    }, $complianceData));
                                                    $avgScore = $totalScore / count($complianceData);
                                                }
                                            @endphp
                                            {{ number_format($avgScore, 1) }}%
                                        </h3>
                                        <small class="text-muted">Overall compliance</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Compliance Details Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="complianceTable">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Position</th>
                                        <th class="text-center">Compliance Score</th>
                                        <th class="text-center">Status</th>
                                        <th>Violations</th>
                                        <th>Warnings</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($complianceData as $data)
                                        @php
                                            $employee = $data['employee'];
                                            $compliance = $data['compliance'];
                                            $score = $compliance['compliance_score'];
                                            $isCompliant = $compliance['is_compliant'];
                                            $violationCount = count($compliance['violations']);
                                            $warningCount = count($compliance['warnings']);
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $employee->full_name }}</strong><br>
                                                <small class="text-muted">{{ $employee->employee_number }}</small>
                                            </td>
                                            <td>{{ $employee->department->name ?? 'N/A' }}</td>
                                            <td>{{ $employee->position->position_title ?? $employee->position->title ?? 'N/A' }}</td>
                                            <td class="text-center">
                                                <div class="progress" style="height: 25px;">
                                                    <div class="progress-bar {{ $score >= 80 ? 'bg-success' : ($score >= 60 ? 'bg-warning' : 'bg-danger') }}" 
                                                         role="progressbar" 
                                                         style="width: {{ $score }}%"
                                                         aria-valuenow="{{ $score }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        {{ $score }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($isCompliant)
                                                    <span class="badge bg-success">Compliant</span>
                                                @else
                                                    <span class="badge bg-danger">Non-Compliant</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($violationCount > 0)
                                                    <span class="badge bg-danger">{{ $violationCount }}</span>
                                                    <button class="btn btn-sm btn-link p-0" 
                                                            data-bs-toggle="collapse" 
                                                            data-bs-target="#violations-{{ $employee->id }}">
                                                        <i class="bx bx-chevron-down"></i>
                                                    </button>
                                                    <div class="collapse mt-2" id="violations-{{ $employee->id }}">
                                                        @foreach($compliance['violations'] as $violation)
                                                            <div class="alert alert-danger alert-sm mb-1">
                                                                <strong>{{ $violation['type'] }}:</strong> {{ $violation['message'] }}
                                                                <br><small>{{ $violation['action_required'] }}</small>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-success">None</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($warningCount > 0)
                                                    <span class="badge bg-warning">{{ $warningCount }}</span>
                                                    <button class="btn btn-sm btn-link p-0" 
                                                            data-bs-toggle="collapse" 
                                                            data-bs-target="#warnings-{{ $employee->id }}">
                                                        <i class="bx bx-chevron-down"></i>
                                                    </button>
                                                    <div class="collapse mt-2" id="warnings-{{ $employee->id }}">
                                                        @foreach($compliance['warnings'] as $warning)
                                                            <div class="alert alert-warning alert-sm mb-1">
                                                                <strong>{{ $warning['type'] }}:</strong> {{ $warning['message'] }}
                                                                @if(isset($warning['action_required']))
                                                                    <br><small>{{ $warning['action_required'] }}</small>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-success">None</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('hr.employees.show', $employee->hash_id) }}" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="View Employee">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No payroll data found for the selected period.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        $('#complianceTable').DataTable({
            order: [[3, 'desc']], // Sort by compliance score descending
            pageLength: 25,
            responsive: true,
        });
    });
</script>
@endpush
@endsection

