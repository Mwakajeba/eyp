@extends('layouts.main')

@section('title', 'Leave Type Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Leave Management', 'url' => route('hr.leave.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Leave Types', 'url' => route('hr.leave.types.index'), 'icon' => 'bx bx-cog'],
            ['label' => $type->name, 'url' => '#', 'icon' => 'bx bx-detail']
        ]" />
            <h6 class="mb-0 text-uppercase">LEAVE TYPE DETAILS</h6>
            <hr />

            <!-- Action Buttons -->
            <div class="row mb-3">
                <div class="col-12 text-end">
                    <a href="{{ route('hr.leave.types.index') }}" class="btn btn-secondary me-2">
                        <i class="bx bx-arrow-back"></i> Back
                    </a>
                    @can('update', $type)
                        <a href="{{ route('hr.leave.types.edit', $type->id) }}" class="btn btn-warning">
                            <i class="bx bx-edit"></i> Edit
                        </a>
                    @elsecan('edit leave type')
                        <a href="{{ route('hr.leave.types.edit', $type->id) }}" class="btn btn-warning">
                            <i class="bx bx-edit"></i> Edit
                        </a>
                    @endcan
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>{{ $type->name }}</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">Leave Type Name:</th>
                                    <td>{{ $type->name }}</td>
                                </tr>
                                <tr>
                                    <th>Code:</th>
                                    <td>{{ $type->code ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Description:</th>
                                    <td>{{ $type->description ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Annual Entitlement:</th>
                                    <td><strong>{{ $type->annual_entitlement }} days</strong></td>
                                </tr>
                                <tr>
                                    <th>Accrual Type:</th>
                                    <td><span class="badge bg-info">{{ ucfirst($type->accrual_type) }}</span></td>
                                </tr>
                                <tr>
                                    <th>Notice Days Required:</th>
                                    <td>{{ $type->notice_days }} days</td>
                                </tr>
                                <tr>
                                    <th>Carryover Cap:</th>
                                    <td>{{ $type->carryover_cap_days ? $type->carryover_cap_days . ' days' : 'No carryover' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Document Required After:</th>
                                    <td>{{ $type->doc_required_after_days ? $type->doc_required_after_days . ' days' : 'Not required' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Paid/Unpaid:</th>
                                    <td>
                                        <span class="badge bg-{{ $type->is_paid ? 'success' : 'warning' }}">
                                            {{ $type->is_paid ? 'Paid' : 'Unpaid' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Allow Half Day:</th>
                                    <td>
                                        <span class="badge bg-{{ $type->allow_half_day ? 'success' : 'secondary' }}">
                                            {{ $type->allow_half_day ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Encashable:</th>
                                    <td>
                                        <span class="badge bg-{{ $type->encashable ? 'success' : 'secondary' }}">
                                            {{ $type->encashable ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-{{ $type->is_active ? 'success' : 'secondary' }}">
                                            {{ $type->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created At:</th>
                                    <td>{{ $type->created_at->format('d M Y, h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th>Last Updated:</th>
                                    <td>{{ $type->updated_at->format('d M Y, h:i A') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>Configuration</h6>
                                <ul class="list-unstyled">
                                    <li>✓ {{ $type->is_paid ? 'Paid' : 'Unpaid' }} leave</li>
                                    <li>✓ {{ $type->allow_half_day ? 'Half day allowed' : 'Full day only' }}</li>
                                    <li>✓ {{ $type->encashable ? 'Can be encashed' : 'Not encashable' }}</li>
                                    <li>✓ {{ $type->allow_negative ? 'Can go negative' : 'Cannot go negative' }}</li>
                                </ul>
                            </div>

                            @if($type->eligibility)
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">Eligibility Criteria</h6>
                                    <pre class="mb-0 small">{{ json_encode($type->eligibility, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection