@extends('layouts.main')

@section('title', 'View Onboarding Record')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Onboarding', 'url' => '#', 'icon' => 'bx bx-list-check'],
                ['label' => 'Onboarding Records', 'url' => route('hr.onboarding-records.index'), 'icon' => 'bx bx-file'],
                ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-file me-1"></i>Onboarding Record Details</h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.onboarding-records.edit', $onboardingRecord->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('hr.onboarding-records.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Record Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Employee:</strong></div>
                                <div class="col-md-8">{{ $onboardingRecord->employee->full_name }} ({{ $onboardingRecord->employee->employee_number }})</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Checklist:</strong></div>
                                <div class="col-md-8">{{ $onboardingRecord->checklist->checklist_name }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Status:</strong></div>
                                <div class="col-md-8">
                                    @php
                                        $badges = [
                                            'in_progress' => 'primary',
                                            'completed' => 'success',
                                            'on_hold' => 'warning',
                                            'cancelled' => 'danger',
                                        ];
                                        $badge = $badges[$onboardingRecord->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ ucfirst(str_replace('_', ' ', $onboardingRecord->status)) }}</span>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Start Date:</strong></div>
                                <div class="col-md-8">{{ $onboardingRecord->start_date->format('d M Y') }}</div>
                            </div>

                            @if($onboardingRecord->completion_date)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Completion Date:</strong></div>
                                <div class="col-md-8">{{ $onboardingRecord->completion_date->format('d M Y') }}</div>
                            </div>
                            @endif

                            @if($onboardingRecord->notes)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Notes:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($onboardingRecord->notes)) !!}</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Checklist Items Progress</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Item</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Completed Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($onboardingRecord->recordItems as $item)
                                        <tr>
                                            <td>{{ $item->checklistItem->sequence_order }}</td>
                                            <td>
                                                <strong>{{ $item->checklistItem->item_title }}</strong>
                                                @if($item->checklistItem->is_mandatory)
                                                    <span class="badge bg-danger ms-1">Mandatory</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $item->checklistItem->item_type)) }}</span>
                                            </td>
                                            <td>
                                                @if($item->is_completed)
                                                    <span class="badge bg-success">Completed</span>
                                                @else
                                                    <span class="badge bg-warning">Pending</span>
                                                @endif
                                            </td>
                                            <td>{{ $item->completed_at?->format('d M Y') ?? 'N/A' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Progress</h5>
                            
                            @php
                                $totalItems = $onboardingRecord->recordItems->count();
                                $completedItems = $onboardingRecord->recordItems->where('is_completed', true)->count();
                                $progress = $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0;
                            @endphp

                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Overall Progress</span>
                                    <span>{{ number_format($progress, 1) }}%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%">{{ $completedItems }}/{{ $totalItems }}</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Total Items:</strong></div>
                                <div class="col-md-7">{{ $totalItems }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Completed:</strong></div>
                                <div class="col-md-7">{{ $completedItems }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Pending:</strong></div>
                                <div class="col-md-7">{{ $totalItems - $completedItems }}</div>
                            </div>

                            @if($onboardingRecord->assignedTo)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Assigned To:</strong></div>
                                <div class="col-md-7">{{ $onboardingRecord->assignedTo->name }}</div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Created:</strong></div>
                                <div class="col-md-7">{{ $onboardingRecord->created_at->format('d M Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

