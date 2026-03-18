@extends('layouts.main')

@section('title', 'View Onboarding Checklist')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Onboarding', 'url' => '#', 'icon' => 'bx bx-list-check'],
                ['label' => 'Checklists', 'url' => route('hr.onboarding-checklists.index'), 'icon' => 'bx bx-check-square'],
                ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-check-square me-1"></i>Onboarding Checklist Details</h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.onboarding-checklists.edit', $onboardingChecklist->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('hr.onboarding-checklists.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Checklist Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Checklist Name:</strong></div>
                                <div class="col-md-8">{{ $onboardingChecklist->checklist_name }}</div>
                            </div>

                            @if($onboardingChecklist->description)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Description:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($onboardingChecklist->description)) !!}</div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Applicable To:</strong></div>
                                <div class="col-md-8">
                                    @php
                                        $badges = [
                                            'all' => 'primary',
                                            'department' => 'info',
                                            'position' => 'warning',
                                            'role' => 'success',
                                        ];
                                        $badge = $badges[$onboardingChecklist->applicable_to] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ ucfirst($onboardingChecklist->applicable_to) }}</span>
                                </div>
                            </div>

                            @if($onboardingChecklist->department)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Department:</strong></div>
                                <div class="col-md-8">{{ $onboardingChecklist->department->name }}</div>
                            </div>
                            @endif

                            @if($onboardingChecklist->position)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Position:</strong></div>
                                <div class="col-md-8">{{ $onboardingChecklist->position->title }}</div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Status:</strong></div>
                                <div class="col-md-8">
                                    <span class="badge bg-{{ $onboardingChecklist->is_active ? 'success' : 'secondary' }}">
                                        {{ $onboardingChecklist->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Checklist Items ({{ $onboardingChecklist->checklistItems->count() }})</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Item Title</th>
                                            <th>Type</th>
                                            <th>Mandatory</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($onboardingChecklist->checklistItems->sortBy('sequence_order') as $item)
                                        <tr>
                                            <td>{{ $item->sequence_order }}</td>
                                            <td>
                                                <strong>{{ $item->item_title }}</strong>
                                                @if($item->item_description)
                                                    <br><small class="text-muted">{{ $item->item_description }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $item->item_type)) }}</span>
                                            </td>
                                            <td>
                                                @if($item->is_mandatory)
                                                    <span class="badge bg-danger">Yes</span>
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

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Additional Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Total Items:</strong></div>
                                <div class="col-md-7">{{ $onboardingChecklist->checklistItems->count() }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Mandatory Items:</strong></div>
                                <div class="col-md-7">{{ $onboardingChecklist->checklistItems->where('is_mandatory', true)->count() }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Created:</strong></div>
                                <div class="col-md-7">{{ $onboardingChecklist->created_at->format('d M Y H:i') }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Last Updated:</strong></div>
                                <div class="col-md-7">{{ $onboardingChecklist->updated_at->format('d M Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

