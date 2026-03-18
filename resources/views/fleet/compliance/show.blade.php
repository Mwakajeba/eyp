@extends('layouts.main')

@section('title', 'View Compliance Record - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Compliance & Safety', 'url' => route('fleet.compliance.index'), 'icon' => 'bx bx-shield-check'],
            ['label' => 'View: ' . $record->record_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-shield-check me-2"></i>{{ $record->record_number }}</h6>
                        <div>
                            <a href="{{ route('fleet.compliance.edit', $record->hash_id) }}" class="btn btn-light btn-sm me-1">
                                <i class="bx bx-edit me-1"></i>Edit
                            </a>
                            <a href="{{ route('fleet.compliance.index') }}" class="btn btn-light btn-sm">
                                <i class="bx bx-arrow-back me-1"></i>Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Compliance Type</label>
                                <p class="mb-0">
                                    @php
                                        $types = [
                                            'vehicle_insurance' => 'Vehicle Insurance',
                                            'driver_license' => 'Driver License',
                                            'vehicle_inspection' => 'Vehicle Inspection',
                                            'safety_certification' => 'Safety Certification',
                                            'registration' => 'Registration',
                                            'permit' => 'Permit',
                                            'other' => 'Other',
                                        ];
                                    @endphp
                                    <span class="badge bg-primary">{{ $types[$record->compliance_type] ?? $record->compliance_type }}</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Entity</label>
                                <p class="mb-0">
                                    @if($record->vehicle)
                                        <span class="badge bg-primary">Vehicle</span> {{ $record->vehicle->name }} ({{ $record->vehicle->registration_number ?? 'N/A' }})
                                    @elseif($record->driver)
                                        <span class="badge bg-success">Driver</span> {{ $record->driver->full_name }}
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <p class="mb-0">
                                    <span class="badge bg-{{ $record->getStatusColor() }}">{{ ucfirst(str_replace('_', ' ', $record->status)) }}</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Compliance Status</label>
                                <p class="mb-0">
                                    <span class="badge bg-{{ $record->getComplianceStatusColor() }}">{{ ucfirst(str_replace('_', ' ', $record->compliance_status)) }}</span>
                                    @if($record->isExpired())
                                        <span class="badge bg-danger ms-1">EXPIRED</span>
                                    @elseif($record->isExpiringSoon(30))
                                        <span class="badge bg-warning ms-1">Expires in {{ $record->daysUntilExpiry() }} days</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Document Number</label>
                                <p class="mb-0">{{ $record->document_number ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Issuer Name</label>
                                <p class="mb-0">{{ $record->issuer_name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Issue Date</label>
                                <p class="mb-0">{{ $record->issue_date ? $record->issue_date->format('Y-m-d') : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Expiry Date</label>
                                <p class="mb-0">
                                    <strong class="{{ $record->isExpired() ? 'text-danger' : ($record->isExpiringSoon(30) ? 'text-warning' : '') }}">
                                        {{ $record->expiry_date->format('Y-m-d') }}
                                    </strong>
                                    @if(!$record->isExpired())
                                        <small class="text-muted ms-2">({{ $record->daysUntilExpiry() }} days remaining)</small>
                                    @else
                                        <small class="text-danger ms-2">(Expired {{ abs($record->daysUntilExpiry()) }} days ago)</small>
                                    @endif
                                </p>
                            </div>
                            @if($record->renewal_reminder_date)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Renewal Reminder Date</label>
                                <p class="mb-0">{{ $record->renewal_reminder_date->format('Y-m-d') }}</p>
                            </div>
                            @endif
                            @if($record->premium_amount)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Premium Amount</label>
                                <p class="mb-0">{{ number_format($record->premium_amount, 2) }} {{ $record->currency }}</p>
                            </div>
                            @endif
                            @if($record->payment_frequency)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Payment Frequency</label>
                                <p class="mb-0">{{ ucfirst(str_replace('_', ' ', $record->payment_frequency)) }}</p>
                            </div>
                            @endif
                            @if($record->description)
                            <div class="col-12">
                                <label class="form-label fw-bold">Description</label>
                                <p class="mb-0">{{ $record->description }}</p>
                            </div>
                            @endif
                            @if($record->terms_conditions)
                            <div class="col-12">
                                <label class="form-label fw-bold">Terms & Conditions</label>
                                <p class="mb-0">{{ $record->terms_conditions }}</p>
                            </div>
                            @endif
                            @if($record->notes)
                            <div class="col-12">
                                <label class="form-label fw-bold">Notes</label>
                                <p class="mb-0">{{ $record->notes }}</p>
                            </div>
                            @endif
                        </div>

                        @if($record->attachments && count($record->attachments) > 0)
                        <hr class="my-4">
                        <h6 class="text-warning mb-3"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                        <div class="list-group">
                            @foreach($record->attachments as $index => $attachment)
                                @if(isset($attachment['path']))
                                <a href="{{ Storage::url($attachment['path']) }}" target="_blank" class="list-group-item list-group-item-action">
                                    <i class="bx bx-file me-2"></i>{{ $attachment['original_name'] ?? 'Attachment ' . ($index + 1) }}
                                    @if(isset($attachment['size']))
                                        <small class="text-muted ms-2">({{ number_format($attachment['size'] / 1024, 2) }} KB)</small>
                                    @endif
                                </a>
                                @endif
                            @endforeach
                        </div>
                        @endif

                        @if($record->parentRecord || $record->renewalRecords->count() > 0)
                        <hr class="my-4">
                        <h6 class="text-warning mb-3"><i class="bx bx-refresh me-2"></i>Renewal History</h6>
                        @if($record->parentRecord)
                        <div class="alert alert-info">
                            <strong>Renewed From:</strong> <a href="{{ route('fleet.compliance.show', $record->parentRecord->hash_id) }}">{{ $record->parentRecord->record_number }}</a> (Expired: {{ $record->parentRecord->expiry_date->format('Y-m-d') }})
                        </div>
                        @endif
                        @if($record->renewalRecords->count() > 0)
                        <div class="alert alert-success">
                            <strong>Renewed To:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($record->renewalRecords as $renewal)
                                    <li><a href="{{ route('fleet.compliance.show', $renewal->hash_id) }}">{{ $renewal->record_number }}</a> (Expiry: {{ $renewal->expiry_date->format('Y-m-d') }})</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Timeline Card -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bx bx-time me-2"></i>Timeline</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-secondary"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Created</h6>
                                    <p class="small text-muted mb-0">
                                        {{ $record->created_at->format('Y-m-d H:i') }}
                                        @if($record->createdBy)
                                            by {{ $record->createdBy->name }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @if($record->issue_date)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Issued</h6>
                                    <p class="small text-muted mb-0">{{ $record->issue_date->format('Y-m-d') }}</p>
                                </div>
                            </div>
                            @endif
                            @if($record->updated_at && $record->updated_at != $record->created_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Last Updated</h6>
                                    <p class="small text-muted mb-0">
                                        {{ $record->updated_at->format('Y-m-d H:i') }}
                                        @if($record->updatedBy)
                                            by {{ $record->updatedBy->name }}
                                        @endif
                                    </p>
                                </div>
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

@push('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    .timeline-item {
        position: relative;
        padding-bottom: 20px;
    }
    .timeline-marker {
        position: absolute;
        left: -38px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #dee2e6;
    }
    .timeline-item:not(:last-child)::before {
        content: '';
        position: absolute;
        left: -33px;
        top: 17px;
        width: 2px;
        height: calc(100% - 12px);
        background-color: #dee2e6;
    }
    .timeline-content h6 {
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
</style>
@endpush
