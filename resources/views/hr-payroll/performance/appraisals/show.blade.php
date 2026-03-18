@extends('layouts.main')

@section('title', 'View Appraisal')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Performance', 'url' => '#', 'icon' => 'bx bx-trophy'],
            ['label' => 'Appraisals', 'url' => route('hr.appraisals.index'), 'icon' => 'bx bx-clipboard'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">
                <i class="bx bx-clipboard me-1"></i>View Appraisal
            </h6>
            <div class="d-flex gap-2">
                <a href="{{ route('hr.appraisals.edit', $appraisal->id) }}" class="btn btn-primary">
                    <i class="bx bx-edit me-1"></i>Edit
                </a>
                <a href="{{ route('hr.appraisals.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
        </div>
        <hr />

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3 text-primary">Appraisal Information</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <strong>Employee:</strong><br>
                                {{ $appraisal->employee->full_name }} ({{ $appraisal->employee->employee_number }})
                            </div>
                            <div class="col-md-3">
                                <strong>Cycle:</strong><br>
                                {{ $appraisal->cycle->cycle_name }}
                            </div>
                            <div class="col-md-3">
                                <strong>Appraiser:</strong><br>
                                {{ $appraisal->appraiser->name ?? 'N/A' }}
                            </div>
                            <div class="col-md-3">
                                <strong>Status:</strong><br>
                                <span class="badge bg-{{ $appraisal->status == 'approved' ? 'success' : ($appraisal->status == 'submitted' ? 'info' : 'secondary') }}">
                                    {{ ucfirst($appraisal->status) }}
                                </span>
                            </div>
                        </div>

                        <h6 class="mb-3 text-primary mt-4">Scores</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <strong>Self Assessment Score:</strong><br>
                                {{ $appraisal->self_assessment_score ? number_format($appraisal->self_assessment_score, 2) : 'N/A' }}
                            </div>
                            <div class="col-md-3">
                                <strong>Manager Score:</strong><br>
                                {{ $appraisal->manager_score ? number_format($appraisal->manager_score, 2) : 'N/A' }}
                            </div>
                            <div class="col-md-3">
                                <strong>Final Score:</strong><br>
                                <span class="fw-bold">{{ $appraisal->final_score ? number_format($appraisal->final_score, 2) : 'N/A' }}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Rating:</strong><br>
                                @if($appraisal->rating)
                                    <span class="badge bg-{{ $appraisal->rating == 'excellent' ? 'success' : ($appraisal->rating == 'good' ? 'primary' : ($appraisal->rating == 'average' ? 'warning' : 'danger')) }}">
                                        {{ ucfirst($appraisal->rating) }}
                                    </span>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>

                        @if($appraisal->kpiScores->count() > 0)
                        <h6 class="mb-3 text-primary mt-4">KPI Scores</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>KPI</th>
                                        <th>Self Score</th>
                                        <th>Manager Score</th>
                                        <th>Final Score</th>
                                        <th>Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($appraisal->kpiScores as $kpiScore)
                                    <tr>
                                        <td>{{ $kpiScore->kpi->kpi_code }} - {{ $kpiScore->kpi->kpi_name }}</td>
                                        <td>{{ $kpiScore->self_score ? number_format($kpiScore->self_score, 2) : 'N/A' }}</td>
                                        <td>{{ $kpiScore->manager_score ? number_format($kpiScore->manager_score, 2) : 'N/A' }}</td>
                                        <td><strong>{{ $kpiScore->final_score ? number_format($kpiScore->final_score, 2) : 'N/A' }}</strong></td>
                                        <td>{{ $kpiScore->comments ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        @if($appraisal->approved_at)
                        <div class="mt-4">
                            <strong>Approved by:</strong> {{ $appraisal->approver->name ?? 'N/A' }}<br>
                            <strong>Approved at:</strong> {{ $appraisal->approved_at->format('d M Y H:i') }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

