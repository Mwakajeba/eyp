@extends('layouts.main')

@section('title', 'Employee Salary Structure')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Employee Salary Structures', 'url' => route('hr.employee-salary-structure.index'), 'icon' => 'bx bx-money'],
            ['label' => 'View Structure', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0"><i class="bx bx-money me-2"></i>Salary Structure</h5>
                <p class="mb-0 text-muted">{{ $employee->full_name }}@if($employee->employee_number) ({{ $employee->employee_number }})@endif</p>
            </div>
            <div>
                <a href="{{ route('hr.employee-salary-structure.create', ['employee_id' => $employee->id]) }}" class="btn btn-primary me-2">
                    <i class="bx bx-edit me-1"></i>Edit Structure
                </a>
                <a href="{{ route('hr.employee-salary-structure.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to List
                </a>
            </div>
        </div>
        <hr />

        <!-- Employee Info Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Employee Name:</strong><br>
                        {{ $employee->full_name }}
                    </div>
                    <div class="col-md-3">
                        <strong>Employee Number:</strong><br>
                        {{ $employee->employee_number ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Department:</strong><br>
                        {{ $employee->department->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Basic Salary (Fallback):</strong><br>
                        {{ number_format($employee->basic_salary ?? 0, 2) }} TZS
                    </div>
                </div>
            </div>
        </div>

        @if($currentStructures->count() > 0)
            <!-- Current Structure -->
            <div class="card border-top border-0 border-4 border-success mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0 text-success">
                        <i class="bx bx-check-circle me-1"></i>Current Active Structure
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Effective Date:</strong> 
                            {{ $currentStructures->first()->effective_date->format('d M Y') }}
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong> 
                            <span class="badge bg-success">Active</span>
                        </div>
                    </div>

                    <!-- Earnings -->
                    @php
                        $earnings = $currentStructures->filter(function($s) {
                            return $s->component->component_type === 'earning';
                        });
                    @endphp
                    @if($earnings->count() > 0)
                    <div class="mb-4">
                        <h6 class="text-success mb-3">
                            <i class="bx bx-trending-up me-1"></i>Earnings
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Component</th>
                                        <th>Code</th>
                                        <th>Calculation Type</th>
                                        <th>Amount/Percentage</th>
                                        <th>Calculated Amount</th>
                                        <th>Taxable</th>
                                        <th>Pensionable</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalEarnings = 0;
                                        $baseAmount = $employee->basic_salary ?? 0;
                                    @endphp
                                    @foreach($earnings as $structure)
                                        @php
                                            $calculatedAmount = $structure->component->calculateAmount($baseAmount, $structure);
                                            $totalEarnings += $calculatedAmount;
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $structure->component->component_name }}</strong></td>
                                            <td><code>{{ $structure->component->component_code }}</code></td>
                                            <td>
                                                <span class="badge bg-info">{{ ucfirst($structure->component->calculation_type) }}</span>
                                            </td>
                                            <td>
                                                @if($structure->amount)
                                                    {{ number_format($structure->amount, 2) }} TZS
                                                @elseif($structure->percentage)
                                                    {{ number_format($structure->percentage, 2) }}%
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td><strong>{{ number_format($calculatedAmount, 2) }} TZS</strong></td>
                                            <td>
                                                @if($structure->component->is_taxable)
                                                    <span class="badge bg-success">Yes</span>
                                                @else
                                                    <span class="badge bg-secondary">No</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($structure->component->is_pensionable)
                                                    <span class="badge bg-success">Yes</span>
                                                @else
                                                    <span class="badge bg-secondary">No</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-success">
                                        <td colspan="4" class="text-end"><strong>Total Earnings:</strong></td>
                                        <td colspan="3"><strong>{{ number_format($totalEarnings, 2) }} TZS</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Deductions -->
                    @php
                        $deductions = $currentStructures->filter(function($s) {
                            return $s->component->component_type === 'deduction';
                        });
                    @endphp
                    @if($deductions->count() > 0)
                    <div class="mb-4">
                        <h6 class="text-danger mb-3">
                            <i class="bx bx-trending-down me-1"></i>Deductions
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Component</th>
                                        <th>Code</th>
                                        <th>Calculation Type</th>
                                        <th>Amount/Percentage</th>
                                        <th>Calculated Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalDeductions = 0;
                                    @endphp
                                    @foreach($deductions as $structure)
                                        @php
                                            $calculatedAmount = $structure->component->calculateAmount($totalEarnings, $structure);
                                            $totalDeductions += $calculatedAmount;
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $structure->component->component_name }}</strong></td>
                                            <td><code>{{ $structure->component->component_code }}</code></td>
                                            <td>
                                                <span class="badge bg-info">{{ ucfirst($structure->component->calculation_type) }}</span>
                                            </td>
                                            <td>
                                                @if($structure->amount)
                                                    {{ number_format($structure->amount, 2) }} TZS
                                                @elseif($structure->percentage)
                                                    {{ number_format($structure->percentage, 2) }}%
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td><strong>{{ number_format($calculatedAmount, 2) }} TZS</strong></td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-danger">
                                        <td colspan="4" class="text-end"><strong>Total Deductions:</strong></td>
                                        <td><strong>{{ number_format($totalDeductions, 2) }} TZS</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Summary -->
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Total Earnings:</strong><br>
                                    <h4 class="text-success mb-0">{{ number_format($totalEarnings, 2) }} TZS</h4>
                                </div>
                                <div class="col-md-4">
                                    <strong>Total Deductions:</strong><br>
                                    <h4 class="text-danger mb-0">{{ number_format($totalDeductions, 2) }} TZS</h4>
                                </div>
                                <div class="col-md-4">
                                    <strong>Net Salary (Estimated):</strong><br>
                                    <h4 class="text-primary mb-0">{{ number_format($totalEarnings - $totalDeductions, 2) }} TZS</h4>
                                    <small class="text-muted">* Excludes statutory deductions (PAYE, Pension, NHIF, etc.)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($currentStructures->whereNotNull('notes')->count() > 0)
                    <div class="mt-3">
                        <h6>Notes:</h6>
                        <ul>
                            @foreach($currentStructures->whereNotNull('notes') as $structure)
                                <li><strong>{{ $structure->component->component_name }}:</strong> {{ $structure->notes }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
        @else
            <!-- No Structure -->
            <div class="card border-top border-0 border-4 border-warning">
                <div class="card-body text-center py-5">
                    <i class="bx bx-info-circle fs-1 text-warning mb-3"></i>
                    <h5>No Salary Structure Assigned</h5>
                    <p class="text-muted">This employee does not have an active salary structure. The system will use the employee's basic salary as fallback.</p>
                    <a href="{{ route('hr.employee-salary-structure.create', ['employee_id' => $employee->id]) }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>Assign Structure
                    </a>
                </div>
            </div>
        @endif

        <!-- Historical Structures -->
        @if($historicalStructures->count() > 0)
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bx bx-history me-1"></i>Historical Structures
                </h6>
            </div>
            <div class="card-body">
                @foreach($historicalStructures as $period => $structures)
                    <div class="mb-4">
                        <h6 class="text-muted">{{ \Carbon\Carbon::parse($period . '-01')->format('F Y') }}</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Component</th>
                                        <th>Effective Date</th>
                                        <th>End Date</th>
                                        <th>Amount/Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($structures as $structure)
                                        <tr>
                                            <td>{{ $structure->component->component_name }}</td>
                                            <td>{{ $structure->effective_date->format('d M Y') }}</td>
                                            <td>{{ $structure->end_date ? $structure->end_date->format('d M Y') : 'Ongoing' }}</td>
                                            <td>
                                                @if($structure->amount)
                                                    {{ number_format($structure->amount, 2) }} TZS
                                                @elseif($structure->percentage)
                                                    {{ number_format($structure->percentage, 2) }}%
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

