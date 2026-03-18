@extends('layouts.main')

@section('title', 'View Training Bond')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Training', 'url' => '#', 'icon' => 'bx bx-book'],
            ['label' => 'Training Bonds', 'url' => route('hr.training-bonds.index'), 'icon' => 'bx bx-lock'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">
                <i class="bx bx-lock me-1"></i>View Training Bond
            </h6>
            <div class="d-flex gap-2">
                <a href="{{ route('hr.training-bonds.edit', $trainingBond->id) }}" class="btn btn-primary">
                    <i class="bx bx-edit me-1"></i>Edit
                </a>
                <a href="{{ route('hr.training-bonds.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
        </div>
        <hr />

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3 text-primary">Bond Information</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <strong>Employee:</strong><br>
                                {{ $trainingBond->employee->full_name }} ({{ $trainingBond->employee->employee_number }})
                            </div>
                            <div class="col-md-6">
                                <strong>Training Program:</strong><br>
                                {{ $trainingBond->trainingProgram->program_code }} - {{ $trainingBond->trainingProgram->program_name }}
                            </div>
                            <div class="col-md-6">
                                <strong>Bond Amount:</strong><br>
                                <span class="fw-bold">{{ number_format($trainingBond->bond_amount, 2) }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Bond Period:</strong><br>
                                {{ $trainingBond->bond_period_months }} months
                            </div>
                            <div class="col-md-6">
                                <strong>Start Date:</strong><br>
                                {{ $trainingBond->start_date->format('d M Y') }}
                            </div>
                            <div class="col-md-6">
                                <strong>End Date:</strong><br>
                                {{ $trainingBond->end_date->format('d M Y') }}
                            </div>
                            <div class="col-md-6">
                                <strong>Remaining Days:</strong><br>
                                @if($trainingBond->remaining_days > 0)
                                    <span class="badge bg-{{ $trainingBond->remaining_days <= 90 ? 'warning' : 'success' }}">
                                        {{ $trainingBond->remaining_days }} days
                                    </span>
                                @else
                                    <span class="badge bg-danger">Expired</span>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                <span class="badge bg-{{ $trainingBond->status == 'fulfilled' ? 'success' : ($trainingBond->status == 'recovered' ? 'danger' : 'primary') }}">
                                    {{ ucfirst($trainingBond->status) }}
                                </span>
                            </div>
                            @if($trainingBond->recovery_rules)
                            <div class="col-md-12">
                                <strong>Recovery Rules:</strong><br>
                                <pre class="bg-light p-3 rounded">{{ json_encode($trainingBond->recovery_rules, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

