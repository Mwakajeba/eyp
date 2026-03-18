@extends('layouts.main')

@section('title', 'Edit Compliance Record - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Compliance & Safety', 'url' => route('fleet.compliance.index'), 'icon' => 'bx bx-shield-check'],
            ['label' => 'Edit: ' . $record->record_number, 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Compliance Record</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.compliance.update', $record->hash_id) }}" enctype="multipart/form-data" id="compliance-form">
                    @csrf
                    @method('PUT')

                    <!-- Basic Information -->
                    <h6 class="text-warning mb-3"><i class="bx bx-info-circle me-2"></i>Basic Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Compliance Type <span class="text-danger">*</span></label>
                            <select name="compliance_type" id="compliance_type" class="form-select" required>
                                <option value="">Select Type</option>
                                @foreach($complianceTypes as $key => $label)
                                    <option value="{{ $key }}" {{ old('compliance_type', $record->compliance_type) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('compliance_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Entity Type</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="entity_type" id="entity_vehicle" value="vehicle" {{ ($record->vehicle_id && old('entity_type') != 'driver') ? 'checked' : '' }}>
                                <label class="form-check-label" for="entity_vehicle">Vehicle</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="entity_type" id="entity_driver" value="driver" {{ ($record->driver_id || old('entity_type') == 'driver') ? 'checked' : '' }}>
                                <label class="form-check-label" for="entity_driver">Driver</label>
                            </div>
                        </div>

                        <div class="col-md-6" id="vehicle_field" style="{{ $record->vehicle_id ? '' : 'display: none;' }}">
                            <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                            <select name="vehicle_id" id="vehicle_id" class="form-select">
                                <option value="">Select Vehicle</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $record->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->name }} ({{ $vehicle->registration_number ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('vehicle_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6" id="driver_field" style="{{ $record->driver_id ? '' : 'display: none;' }}">
                            <label class="form-label">Driver <span class="text-danger">*</span></label>
                            <select name="driver_id" id="driver_id" class="form-select">
                                <option value="">Select Driver</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ old('driver_id', $record->driver_id) == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->full_name }} ({{ $driver->license_number ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('driver_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Document Number</label>
                            <input type="text" name="document_number" class="form-control" value="{{ old('document_number', $record->document_number) }}" placeholder="Policy number, license number, etc.">
                            @error('document_number')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Issuer Name</label>
                            <input type="text" name="issuer_name" class="form-control" value="{{ old('issuer_name', $record->issuer_name) }}" placeholder="Insurance company, licensing authority, etc.">
                            @error('issuer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Dates -->
                    <h6 class="text-warning mb-3"><i class="bx bx-calendar me-2"></i>Dates</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Issue Date</label>
                            <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', $record->issue_date?->format('Y-m-d')) }}">
                            @error('issue_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date', $record->expiry_date->format('Y-m-d')) }}" required>
                            @error('expiry_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Renewal Reminder Date</label>
                            <input type="date" name="renewal_reminder_date" class="form-control" value="{{ old('renewal_reminder_date', $record->renewal_reminder_date?->format('Y-m-d')) }}">
                            @error('renewal_reminder_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Status & Compliance -->
                    <h6 class="text-warning mb-3"><i class="bx bx-check-shield me-2"></i>Status & Compliance</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status', $record->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="pending_renewal" {{ old('status', $record->status) == 'pending_renewal' ? 'selected' : '' }}>Pending Renewal</option>
                                <option value="expired" {{ old('status', $record->status) == 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="renewed" {{ old('status', $record->status) == 'renewed' ? 'selected' : '' }}>Renewed</option>
                                <option value="cancelled" {{ old('status', $record->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Compliance Status <span class="text-danger">*</span></label>
                            <select name="compliance_status" class="form-select" required>
                                <option value="compliant" {{ old('compliance_status', $record->compliance_status) == 'compliant' ? 'selected' : '' }}>Compliant</option>
                                <option value="warning" {{ old('compliance_status', $record->compliance_status) == 'warning' ? 'selected' : '' }}>Warning</option>
                                <option value="non_compliant" {{ old('compliance_status', $record->compliance_status) == 'non_compliant' ? 'selected' : '' }}>Non-Compliant</option>
                                <option value="critical" {{ old('compliance_status', $record->compliance_status) == 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                            @error('compliance_status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Financial Information -->
                    <h6 class="text-warning mb-3"><i class="bx bx-money me-2"></i>Financial Information</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Premium/Amount</label>
                            <input type="number" step="0.01" min="0" name="premium_amount" class="form-control" value="{{ old('premium_amount', $record->premium_amount) }}" placeholder="0.00">
                            @error('premium_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Currency</label>
                            <select name="currency" class="form-select">
                                <option value="TZS" {{ old('currency', $record->currency) == 'TZS' ? 'selected' : '' }}>TZS</option>
                                <option value="USD" {{ old('currency', $record->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="EUR" {{ old('currency', $record->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                            </select>
                            @error('currency')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Payment Frequency</label>
                            <select name="payment_frequency" class="form-select">
                                <option value="">Select</option>
                                <option value="monthly" {{ old('payment_frequency', $record->payment_frequency) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="quarterly" {{ old('payment_frequency', $record->payment_frequency) == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="semi_annual" {{ old('payment_frequency', $record->payment_frequency) == 'semi_annual' ? 'selected' : '' }}>Semi-Annual</option>
                                <option value="annual" {{ old('payment_frequency', $record->payment_frequency) == 'annual' ? 'selected' : '' }}>Annual</option>
                            </select>
                            @error('payment_frequency')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Description & Notes -->
                    <h6 class="text-warning mb-3"><i class="bx bx-note me-2"></i>Description & Notes</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Describe the compliance record...">{{ old('description', $record->description) }}</textarea>
                            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Terms & Conditions</label>
                            <textarea name="terms_conditions" class="form-control" rows="3" placeholder="Terms and conditions...">{{ old('terms_conditions', $record->terms_conditions) }}</textarea>
                            @error('terms_conditions')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes...">{{ old('notes', $record->notes) }}</textarea>
                            @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Existing Attachments -->
                    @if($record->attachments && count($record->attachments) > 0)
                    <div class="mb-3">
                        <h6 class="text-warning mb-3"><i class="bx bx-paperclip me-2"></i>Existing Attachments</h6>
                        @foreach($record->attachments as $index => $attachment)
                            @if(isset($attachment['path']))
                            <div class="row g-3 mb-2">
                                <div class="col-md-11">
                                    <div class="d-flex align-items-center">
                                        <a href="{{ Storage::url($attachment['path']) }}" target="_blank" class="text-decoration-none me-2">
                                            <i class="bx bx-file"></i> {{ $attachment['original_name'] ?? 'Attachment ' . ($index + 1) }}
                                        </a>
                                        @if(isset($attachment['size']))
                                            <small class="text-muted">({{ number_format($attachment['size'] / 1024, 2) }} KB)</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_attachments[]" value="{{ $index }}" id="remove_attachment_{{ $index }}">
                                        <label class="form-check-label" for="remove_attachment_{{ $index }}" title="Remove">
                                            <i class="bx bx-trash text-danger"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach
                        <small class="text-muted">Check to remove attachments</small>
                    </div>
                    @endif

                    <!-- New Attachments -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-warning mb-0"><i class="bx bx-paperclip me-2"></i>Add New Attachments</h6>
                        <button type="button" class="btn btn-sm btn-outline-warning" id="add-attachment-line">
                            <i class="bx bx-plus me-1"></i>Add Line
                        </button>
                    </div>
                    <div id="attachment-lines">
                        <div class="row g-3 mb-2 attachment-line">
                            <div class="col-md-11">
                                <div class="mb-3">
                                    <label class="form-label">Attach File</label>
                                    <input type="file" name="attachments[]" class="form-control" accept="image/*,.pdf,.doc,.docx">
                                    <div class="form-text">Images, PDF, Word documents (Max 10MB)</div>
                                    @error('attachments.*')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-attachment-line" style="display: none;">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Options -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="auto_renewal_enabled" value="1" id="auto_renewal" {{ old('auto_renewal_enabled', $record->auto_renewal_enabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="auto_renewal">
                                    Enable Auto-Renewal Reminder
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.compliance.show', $record->hash_id) }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bx bx-save me-1"></i>Update Compliance Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Set initial required state from record: if vehicle was selected, only vehicle is required; if driver, only driver
    const hasVehicle = {{ $record->vehicle_id ? 'true' : 'false' }};
    const hasDriver = {{ $record->driver_id ? 'true' : 'false' }};
    if (hasVehicle) {
        $('#vehicle_id').prop('required', true);
        $('#driver_id').prop('required', false);
    } else if (hasDriver) {
        $('#vehicle_id').prop('required', false);
        $('#driver_id').prop('required', true);
    }

    // Show/hide vehicle or driver field based on entity type
    $('input[name="entity_type"]').on('change', function() {
        const entityType = $(this).val();
        if (entityType === 'vehicle') {
            $('#vehicle_field').show();
            $('#driver_field').hide();
            $('#vehicle_id').prop('required', true);
            $('#driver_id').prop('required', false).val('');
        } else {
            $('#vehicle_field').hide();
            $('#driver_field').show();
            $('#vehicle_id').prop('required', false).val('');
            $('#driver_id').prop('required', true);
        }
    });

    // Attachment lines management
    let attachmentLineIndex = 0;

    $('#add-attachment-line').on('click', function() {
        attachmentLineIndex++;
        const lineHtml = `
            <div class="row g-3 mb-2 attachment-line" data-index="${attachmentLineIndex}">
                <div class="col-md-11">
                    <div class="mb-3">
                        <label class="form-label">Attach File</label>
                        <input type="file" name="attachments[]" class="form-control" accept="image/*,.pdf,.doc,.docx">
                        <div class="form-text">Images, PDF, Word documents (Max 10MB)</div>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-attachment-line">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#attachment-lines').append(lineHtml);
        updateAttachmentRemoveButtons();
    });

    $(document).on('click', '.remove-attachment-line', function() {
        if ($('.attachment-line').length > 1) {
            $(this).closest('.attachment-line').remove();
            updateAttachmentRemoveButtons();
        } else {
            alert('At least one attachment line is required.');
        }
    });

    function updateAttachmentRemoveButtons() {
        if ($('.attachment-line').length > 1) {
            $('.remove-attachment-line').show();
        } else {
            $('.remove-attachment-line').hide();
        }
    }

    updateAttachmentRemoveButtons();
});
</script>
@endpush
