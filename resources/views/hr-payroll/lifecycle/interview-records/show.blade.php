@extends('layouts.main')

@section('title', 'View Interview Record')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Applicants', 'url' => route('hr.applicants.index'), 'icon' => 'bx bx-user-plus'],
                ['label' => 'Interview Records', 'url' => route('hr.interview-records.index'), 'icon' => 'bx bx-conversation'],
                ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-conversation me-1"></i>Interview Record Details</h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.interview-records.edit', $interviewRecord->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('hr.interview-records.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Interview Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Applicant:</strong></div>
                                <div class="col-md-8">{{ $interviewRecord->applicant->full_name }} ({{ $interviewRecord->applicant->application_number }})</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Interview Date & Time:</strong></div>
                                <div class="col-md-8">
                                    {{ $interviewRecord->interview_date->format('d M Y') }} at {{ date('H:i', strtotime($interviewRecord->interview_time)) }}
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Interview Type:</strong></div>
                                <div class="col-md-8">
                                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $interviewRecord->interview_type)) }}</span>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Round:</strong></div>
                                <div class="col-md-8">{{ $interviewRecord->round_number }}</div>
                            </div>

                            @if($interviewRecord->overall_score)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Overall Score:</strong></div>
                                <div class="col-md-8">{{ number_format($interviewRecord->overall_score, 2) }} / 100</div>
                            </div>
                            @endif

                            @if($interviewRecord->recommendation)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Recommendation:</strong></div>
                                <div class="col-md-8">
                                    @php
                                        $badges = [
                                            'hire' => 'success',
                                            'maybe' => 'warning',
                                            'reject' => 'danger',
                                            'next_round' => 'info',
                                        ];
                                        $badge = $badges[$interviewRecord->recommendation] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ ucfirst(str_replace('_', ' ', $interviewRecord->recommendation)) }}</span>
                                </div>
                            </div>
                            @endif

                            @if($interviewRecord->feedback)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Feedback:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($interviewRecord->feedback)) !!}</div>
                            </div>
                            @endif

                            @if($interviewRecord->strengths)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Strengths:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($interviewRecord->strengths)) !!}</div>
                            </div>
                            @endif

                            @if($interviewRecord->weaknesses)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Weaknesses:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($interviewRecord->weaknesses)) !!}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Additional Information</h5>
                            
                            @if($interviewRecord->location)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Location:</strong></div>
                                <div class="col-md-7">{{ $interviewRecord->location }}</div>
                            </div>
                            @endif

                            @if($interviewRecord->meeting_link)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Meeting Link:</strong></div>
                                <div class="col-md-7">
                                    <a href="{{ $interviewRecord->meeting_link }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-link me-1"></i>Open Link
                                    </a>
                                </div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Interviewed By:</strong></div>
                                <div class="col-md-7">{{ $interviewRecord->interviewer->name ?? 'N/A' }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Created:</strong></div>
                                <div class="col-md-7">{{ $interviewRecord->created_at->format('d M Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

