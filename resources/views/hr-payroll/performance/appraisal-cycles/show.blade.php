@extends('layouts.main')

@section('title', 'View Appraisal Cycle')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Performance', 'url' => '#', 'icon' => 'bx bx-trophy'],
            ['label' => 'Appraisal Cycles', 'url' => route('hr.appraisal-cycles.index'), 'icon' => 'bx bx-calendar'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">
                <i class="bx bx-calendar me-1"></i>View Appraisal Cycle
            </h6>
            <div class="d-flex gap-2">
                <a href="{{ route('hr.appraisal-cycles.edit', $appraisalCycle->id) }}" class="btn btn-primary">
                    <i class="bx bx-edit me-1"></i>Edit
                </a>
                <a href="{{ route('hr.appraisal-cycles.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
        </div>
        <hr />

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3 text-primary">Cycle Information</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <strong>Cycle Name:</strong><br>
                                {{ $appraisalCycle->cycle_name }}
                            </div>
                            <div class="col-md-6">
                                <strong>Type:</strong><br>
                                <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $appraisalCycle->cycle_type)) }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Start Date:</strong><br>
                                {{ $appraisalCycle->start_date->format('d M Y') }}
                            </div>
                            <div class="col-md-6">
                                <strong>End Date:</strong><br>
                                {{ $appraisalCycle->end_date->format('d M Y') }}
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                <span class="badge bg-{{ $appraisalCycle->status == 'active' ? 'success' : ($appraisalCycle->status == 'completed' ? 'primary' : 'secondary') }}">
                                    {{ ucfirst($appraisalCycle->status) }}
                                </span>
                            </div>
                            <div class="col-md-6">
                                <strong>Total Appraisals:</strong><br>
                                {{ $appraisalCycle->appraisals->count() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($appraisalCycle->appraisals->count() > 0)
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3 text-primary">Appraisals in this Cycle</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Employee #</th>
                                        <th>Appraiser</th>
                                        <th>Final Score</th>
                                        <th>Rating</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($appraisalCycle->appraisals as $appraisal)
                                    <tr>
                                        <td>{{ $appraisal->employee->full_name }}</td>
                                        <td>{{ $appraisal->employee->employee_number }}</td>
                                        <td>{{ $appraisal->appraiser->name ?? 'N/A' }}</td>
                                        <td>{{ $appraisal->final_score ? number_format($appraisal->final_score, 2) : 'N/A' }}</td>
                                        <td>
                                            @if($appraisal->rating)
                                                <span class="badge bg-{{ $appraisal->rating == 'excellent' ? 'success' : ($appraisal->rating == 'good' ? 'primary' : ($appraisal->rating == 'average' ? 'warning' : 'danger')) }}">
                                                    {{ ucfirst($appraisal->rating) }}
                                                </span>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $appraisal->status == 'approved' ? 'success' : ($appraisal->status == 'submitted' ? 'info' : 'secondary') }}">
                                                {{ ucfirst($appraisal->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

