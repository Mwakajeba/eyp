@extends('layouts.main')

@section('title', 'View Training Program')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Training', 'url' => '#', 'icon' => 'bx bx-book'],
            ['label' => 'Programs', 'url' => route('hr.training-programs.index'), 'icon' => 'bx bx-book-open'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">
                <i class="bx bx-book-open me-1"></i>View Training Program
            </h6>
            <div class="d-flex gap-2">
                <a href="{{ route('hr.training-programs.edit', $trainingProgram->id) }}" class="btn btn-primary">
                    <i class="bx bx-edit me-1"></i>Edit
                </a>
                <a href="{{ route('hr.training-programs.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
        </div>
        <hr />

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3 text-primary">Program Information</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <strong>Program Code:</strong><br>
                                {{ $trainingProgram->program_code }}
                            </div>
                            <div class="col-md-6">
                                <strong>Program Name:</strong><br>
                                {{ $trainingProgram->program_name }}
                            </div>
                            <div class="col-md-6">
                                <strong>Provider:</strong><br>
                                @if($trainingProgram->provider)
                                    <span class="badge bg-{{ $trainingProgram->provider == 'internal' ? 'success' : 'info' }}">
                                        {{ ucfirst($trainingProgram->provider) }}
                                    </span>
                                @else
                                    N/A
                                @endif
                            </div>
                            <div class="col-md-6">
                                <strong>Funding Source:</strong><br>
                                @if($trainingProgram->funding_source)
                                    <span class="badge bg-primary">{{ strtoupper($trainingProgram->funding_source) }}</span>
                                @else
                                    N/A
                                @endif
                            </div>
                            <div class="col-md-6">
                                <strong>Cost:</strong><br>
                                {{ $trainingProgram->cost ? number_format($trainingProgram->cost, 2) : 'N/A' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Duration:</strong><br>
                                {{ $trainingProgram->duration_days ? $trainingProgram->duration_days . ' days' : 'N/A' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                <span class="badge bg-{{ $trainingProgram->is_active ? 'success' : 'secondary' }}">
                                    {{ $trainingProgram->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="col-md-6">
                                <strong>Total Attendance:</strong><br>
                                {{ $trainingProgram->attendance->count() }}
                            </div>
                            @if($trainingProgram->description)
                            <div class="col-md-12">
                                <strong>Description:</strong><br>
                                {{ $trainingProgram->description }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($trainingProgram->attendance->count() > 0)
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3 text-primary">Program Attendance</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Employee #</th>
                                        <th>Status</th>
                                        <th>Completion Date</th>
                                        <th>Evaluation Score</th>
                                        <th>Certification</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trainingProgram->attendance as $attendance)
                                    <tr>
                                        <td>{{ $attendance->employee->full_name }}</td>
                                        <td>{{ $attendance->employee->employee_number }}</td>
                                        <td>
                                            <span class="badge bg-{{ $attendance->attendance_status == 'completed' ? 'success' : ($attendance->attendance_status == 'attended' ? 'info' : ($attendance->attendance_status == 'absent' ? 'danger' : 'secondary')) }}">
                                                {{ ucfirst($attendance->attendance_status) }}
                                            </span>
                                        </td>
                                        <td>{{ $attendance->completion_date ? $attendance->completion_date->format('d M Y') : 'N/A' }}</td>
                                        <td>{{ $attendance->evaluation_score ? number_format($attendance->evaluation_score, 2) : 'N/A' }}</td>
                                        <td>
                                            @if($attendance->certification_received)
                                                <span class="badge bg-success"><i class="bx bx-check"></i> Yes</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
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

