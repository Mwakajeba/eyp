@extends('layouts.main')

@section('title', 'View External Loan Institution')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'External Loan Institutions', 'url' => route('hr.external-loan-institutions.index'), 'icon' => 'bx bx-building'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">View External Loan Institution</h6>
        <hr />

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-show me-2"></i>Institution Details</h6>
                        <div>
                            <a href="{{ route('hr.external-loan-institutions.edit', $institution->hash_id) }}" class="btn btn-sm btn-primary">
                                <i class="bx bx-edit me-1"></i>Edit
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Institution Name:</strong>
                                <p>{{ $institution->name }}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Code:</strong>
                                <p>{{ $institution->code ?: '—' }}</p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Contact Person:</strong>
                                <p>{{ $institution->contact_person ?: '—' }}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong>
                                <p>
                                    @if($institution->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Email:</strong>
                                <p>{{ $institution->email ?: '—' }}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Phone:</strong>
                                <p>{{ $institution->phone ?: '—' }}</p>
                            </div>
                        </div>

                        @if($institution->address)
                        <div class="mb-3">
                            <strong>Address:</strong>
                            <p>{{ $institution->address }}</p>
                        </div>
                        @endif

                        @if($institution->notes)
                        <div class="mb-3">
                            <strong>Notes:</strong>
                            <p>{{ $institution->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                @if($institution->externalLoans->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-credit-card me-2"></i>Associated Loans ({{ $institution->externalLoans->count() }})</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Total Loan</th>
                                        <th>Monthly Deduction</th>
                                        <th>Start Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($institution->externalLoans as $loan)
                                    <tr>
                                        <td>{{ $loan->employee->full_name ?? '—' }}</td>
                                        <td>{{ number_format($loan->total_loan, 2) }}</td>
                                        <td>{{ number_format($loan->monthly_deduction, 2) }}</td>
                                        <td>{{ $loan->date ? $loan->date->format('Y-m-d') : '—' }}</td>
                                        <td>
                                            @if($loan->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

