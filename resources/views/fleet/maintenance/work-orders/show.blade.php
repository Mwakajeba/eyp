@extends('layouts.main')

@section('title', 'View Work Order - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Maintenance Work Orders', 'url' => route('fleet.maintenance.work-orders.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'View Work Order', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-purple text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-wrench me-2"></i>Work Order Details</h6>
                        <div>
                            @if(in_array($workOrder->status, ['draft', 'scheduled']))
                                <a href="{{ route('fleet.maintenance.work-orders.edit', $workOrder->hash_id) }}" class="btn btn-light btn-sm me-1">
                                    <i class="bx bx-edit me-1"></i>Edit
                                </a>
                            @endif
                            @if($workOrder->status == 'draft')
                                <form action="{{ route('fleet.maintenance.work-orders.start', $workOrder->hash_id) }}" method="POST" class="d-inline" id="start-work-order-form">
                                    @csrf
                                    <button type="submit" class="btn btn-light btn-sm" id="start-work-order-btn">
                                        <i class="bx bx-play me-1"></i>Start
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">WO Number</label>
                                <p class="mb-0">{{ $workOrder->wo_number }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vehicle</label>
                                <p class="mb-0">
                                    @if($workOrder->vehicle)
                                        @php
                                            $vehicleHashId = method_exists($workOrder->vehicle, 'getHashIdAttribute') 
                                                ? $workOrder->vehicle->hash_id 
                                                : \Vinkla\Hashids\Facades\Hashids::encode($workOrder->vehicle->id);
                                        @endphp
                                        <a href="{{ route('fleet.vehicles.show', $vehicleHashId) }}">
                                            {{ $workOrder->vehicle->name }} ({{ $workOrder->vehicle->registration_number ?? 'N/A' }})
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Maintenance Type</label>
                                <p class="mb-0">
                                    <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $workOrder->maintenance_type)) }}</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Maintenance Category</label>
                                <p class="mb-0">{{ $workOrder->maintenance_category ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <p class="mb-0">
                                    @php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'scheduled' => 'info',
                                            'in_progress' => 'warning',
                                            'on_hold' => 'danger',
                                            'completed' => 'success',
                                            'cancelled' => 'dark',
                                        ];
                                        $color = $statusColors[$workOrder->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ ucfirst(str_replace('_', ' ', $workOrder->status)) }}</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Priority</label>
                                <p class="mb-0">
                                    @php
                                        $priorityColors = [
                                            'low' => 'secondary',
                                            'medium' => 'info',
                                            'high' => 'warning',
                                            'urgent' => 'danger',
                                        ];
                                        $pColor = $priorityColors[$workOrder->priority] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $pColor }}">{{ ucfirst($workOrder->priority) }}</span>
                                </p>
                            </div>
                            @if($workOrder->maintenanceSchedule)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Maintenance Schedule</label>
                                <p class="mb-0">{{ $workOrder->maintenanceSchedule->schedule_name }}</p>
                            </div>
                            @endif
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Execution Type</label>
                                <p class="mb-0">{{ ucfirst(str_replace('_', ' ', $workOrder->execution_type)) }}</p>
                            </div>
                            @if($workOrder->vendor)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vendor</label>
                                <p class="mb-0">{{ $workOrder->vendor->name }}</p>
                            </div>
                            @endif
                            @if($workOrder->assignedTechnician)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Assigned Technician</label>
                                <p class="mb-0">{{ $workOrder->assignedTechnician->name }}</p>
                            </div>
                            @endif
                            @if($workOrder->scheduled_date)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Scheduled Date</label>
                                <p class="mb-0">{{ $workOrder->scheduled_date->format('Y-m-d H:i') }}</p>
                            </div>
                            @endif
                            @if($workOrder->estimated_start_date)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Estimated Start Date</label>
                                <p class="mb-0">{{ $workOrder->estimated_start_date->format('Y-m-d H:i') }}</p>
                            </div>
                            @endif
                            @if($workOrder->estimated_completion_date)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Estimated Completion Date</label>
                                <p class="mb-0">{{ $workOrder->estimated_completion_date->format('Y-m-d H:i') }}</p>
                            </div>
                            @endif
                            @if($workOrder->actual_start_date)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Actual Start Date</label>
                                <p class="mb-0">{{ $workOrder->actual_start_date->format('Y-m-d H:i') }}</p>
                            </div>
                            @endif
                            @if($workOrder->actual_completion_date)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Actual Completion Date</label>
                                <p class="mb-0">{{ $workOrder->actual_completion_date->format('Y-m-d H:i') }}</p>
                            </div>
                            @endif
                            @if($workOrder->work_description)
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Work Description</label>
                                <p class="mb-0">{{ $workOrder->work_description }}</p>
                            </div>
                            @endif
                            @if($workOrder->work_performed)
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Work Performed</label>
                                <p class="mb-0">{{ $workOrder->work_performed }}</p>
                            </div>
                            @endif
                            @if($workOrder->technician_notes)
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Technician Notes</label>
                                <p class="mb-0">{{ $workOrder->technician_notes }}</p>
                            </div>
                            @endif
                        </div>

                        <hr class="my-4">

                        <h6 class="text-purple mb-3"><i class="bx bx-money me-2"></i>Cost Information</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Estimated Cost (TZS)</label>
                                <p class="mb-0">{{ number_format($workOrder->estimated_cost, 2) }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Actual Cost (TZS)</label>
                                <p class="mb-0">
                                    <strong>{{ number_format($workOrder->actual_cost, 2) }}</strong>
                                    @if($workOrder->estimated_cost > 0 && $workOrder->actual_cost > 0)
                                        @php
                                            $variance = $workOrder->actual_cost - $workOrder->estimated_cost;
                                            $variancePercent = ($variance / $workOrder->estimated_cost) * 100;
                                        @endphp
                                        <span class="badge bg-{{ $variance > 0 ? 'danger' : 'success' }} ms-2">
                                            {{ $variance > 0 ? '+' : '' }}{{ number_format($variance, 2) }} ({{ number_format($variancePercent, 1) }}%)
                                        </span>
                                    @endif
                                </p>
                            </div>
                            @if($workOrder->estimated_downtime_hours > 0)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Estimated Downtime</label>
                                <p class="mb-0">{{ number_format($workOrder->estimated_downtime_hours, 1) }} hours</p>
                            </div>
                            @endif
                            @if($workOrder->actual_downtime_hours)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Actual Downtime</label>
                                <p class="mb-0">{{ number_format($workOrder->actual_downtime_hours, 1) }} hours</p>
                            </div>
                            @endif
                        </div>

                        @if($workOrder->costs && $workOrder->costs->count() > 0)
                        <hr class="my-4">
                        <h6 class="text-purple mb-3"><i class="bx bx-list-ul me-2"></i>Cost Details</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Qty</th>
                                        <th>Unit Cost</th>
                                        <th>Tax</th>
                                        <th>Total</th>
                                        <th>Date</th>
                                        @php
                                            $hasAttachments = $workOrder->costs->filter(function($cost) {
                                                return $cost->attachments && count($cost->attachments) > 0;
                                            })->count() > 0;
                                        @endphp
                                        @if($hasAttachments)
                                        <th>Attachments</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($workOrder->costs as $cost)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ ucfirst($cost->cost_type) }}</span></td>
                                        <td>{{ $cost->description }}</td>
                                        <td>{{ $cost->quantity }} {{ $cost->unit ?? '' }}</td>
                                        <td>TZS {{ number_format($cost->unit_cost, 2) }}</td>
                                        <td>TZS {{ number_format($cost->tax_amount, 2) }}</td>
                                        <td>TZS {{ number_format($cost->total_with_tax, 2) }}</td>
                                        <td>{{ $cost->cost_date->format('M d, Y') }}</td>
                                        @if($hasAttachments)
                                        <td>
                                            @if($cost->attachments && count($cost->attachments) > 0)
                                                @foreach($cost->attachments as $attachment)
                                                    @if(isset($attachment['path']))
                                                    <a href="{{ Storage::url($attachment['path']) }}" target="_blank" class="btn btn-sm btn-outline-info" title="{{ $attachment['original_name'] ?? 'Attachment' }}">
                                                        <i class="bx bx-file"></i>
                                                    </a>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        @if($workOrder->notes)
                        <hr class="my-4">
                        <h6 class="text-purple mb-3"><i class="bx bx-note me-2"></i>Notes</h6>
                        <p class="mb-0">{{ $workOrder->notes }}</p>
                        @endif

                        @if($workOrder->attachments && count($workOrder->attachments) > 0)
                        <hr class="my-4">
                        <h6 class="text-purple mb-3"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                        <div class="list-group">
                            @foreach($workOrder->attachments as $index => $attachment)
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
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Add Cost Card -->
                @if(in_array($workOrder->status, ['draft', 'scheduled', 'in_progress']))
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-money me-2"></i>Add Cost</h6>
                    </div>
                    <div class="card-body">
                        <form id="addCostForm" enctype="multipart/form-data">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Cost Type <span class="text-danger">*</span></label>
                                    <select name="cost_type" id="cost_type" class="form-select" required>
                                        <option value="material">Material</option>
                                        <option value="labor">Labor</option>
                                        <option value="other" selected>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cost Date <span class="text-danger">*</span></label>
                                    <input type="date" name="cost_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description <span class="text-danger">*</span></label>
                                    <input type="text" name="description" class="form-control" placeholder="What did you buy?" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" value="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Unit</label>
                                    <input type="text" name="unit" class="form-control" placeholder="e.g., pcs, hrs">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Unit Cost (TZS) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="unit_cost" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tax Amount (TZS)</label>
                                    <input type="number" step="0.01" min="0" name="tax_amount" class="form-control" value="0">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Attachments</label>
                                    <div id="attachment-lines">
                                        <div class="row g-3 mb-2 attachment-line" data-index="0">
                                            <div class="col-md-11">
                                                <input type="file" name="attachments[]" class="form-control" accept="image/*,.pdf,.doc,.docx">
                                                <div class="form-text">Images, PDF, Word documents (Max 10MB)</div>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-attachment-line" style="display: none;">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="add-attachment-line">
                                        <i class="bx bx-plus me-1"></i>Add More Attachments
                                    </button>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-info w-100">
                                        <i class="bx bx-plus me-1"></i>Add Cost
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                <!-- Status Actions Card -->
                @if($workOrder->status == 'in_progress')
                <div class="card mb-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Work in Progress</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('fleet.maintenance.work-orders.complete', $workOrder->hash_id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Actual Cost (TZS)</label>
                                <input type="number" step="0.01" min="0" name="actual_cost" class="form-control" value="{{ old('actual_cost', $workOrder->actual_cost) }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Work Performed</label>
                                <textarea name="work_performed" class="form-control" rows="3" required>{{ old('work_performed', $workOrder->work_performed) }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Technician Notes</label>
                                <textarea name="technician_notes" class="form-control" rows="2">{{ old('technician_notes', $workOrder->technician_notes) }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bx bx-check me-1"></i>Complete Work Order
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                <!-- Timeline Card -->
                <div class="card">
                    <div class="card-header bg-purple text-white">
                        <h6 class="mb-0"><i class="bx bx-time me-2"></i>Timeline</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-secondary"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Created</h6>
                                    <p class="small text-muted mb-0">
                                        {{ $workOrder->created_at->format('Y-m-d H:i') }}
                                        @if($workOrder->createdBy)
                                            by {{ $workOrder->createdBy->name }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @if($workOrder->approved_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Approved</h6>
                                    <p class="small text-muted mb-0">
                                        {{ $workOrder->approved_at->format('Y-m-d H:i') }}
                                        @if($workOrder->approvedBy)
                                            by {{ $workOrder->approvedBy->name }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @endif
                            @if($workOrder->actual_start_date)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Started</h6>
                                    <p class="small text-muted mb-0">
                                        {{ $workOrder->actual_start_date->format('Y-m-d H:i') }}
                                    </p>
                                </div>
                            </div>
                            @endif
                            @if($workOrder->completed_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Completed</h6>
                                    <p class="small text-muted mb-0">
                                        {{ $workOrder->completed_at->format('Y-m-d H:i') }}
                                        @if($workOrder->completedBy)
                                            by {{ $workOrder->completedBy->name }}
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Start work order with SweetAlert
    $('#start-work-order-form').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Start this work order?',
            text: 'This will change the work order status to "In Progress"',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, start it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Attachment lines management
    let attachmentLineIndex = 1;

    // Add attachment line
    $('#add-attachment-line').on('click', function() {
        const lineHtml = `
            <div class="row g-3 mb-2 attachment-line" data-index="${attachmentLineIndex}">
                <div class="col-md-11">
                    <input type="file" name="attachments[]" class="form-control" accept="image/*,.pdf,.doc,.docx">
                    <div class="form-text">Images, PDF, Word documents (Max 10MB)</div>
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
        attachmentLineIndex++;
    });

    // Remove attachment line
    $(document).on('click', '.remove-attachment-line', function() {
        if ($('.attachment-line').length > 1) {
            $(this).closest('.attachment-line').remove();
            updateAttachmentRemoveButtons();
        } else {
            alert('At least one attachment line is required.');
        }
    });

    // Update remove buttons visibility
    function updateAttachmentRemoveButtons() {
        if ($('.attachment-line').length > 1) {
            $('.remove-attachment-line').show();
        } else {
            $('.remove-attachment-line').hide();
        }
    }

    // Initialize remove buttons
    updateAttachmentRemoveButtons();

    // Add cost form submission
    $('#addCostForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        $.ajax({
            url: '{{ route('fleet.maintenance.work-orders.add-cost', $workOrder->hash_id) }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire('Success!', response.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to add cost';
                Swal.fire('Error!', message, 'error');
            }
        });
    });
});
</script>
@endpush
