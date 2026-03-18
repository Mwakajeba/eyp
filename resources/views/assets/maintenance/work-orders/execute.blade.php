@extends('layouts.main')

@section('title', 'Execute Work Order')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Maintenance', 'url' => route('assets.maintenance.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Work Orders', 'url' => route('assets.maintenance.work-orders.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Execute', 'url' => '#', 'icon' => 'bx bx-wrench']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-wrench me-2"></i>Execute Work Order: {{ $workOrder->wo_number }}</h6>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <strong>Asset:</strong> {{ $workOrder->asset->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong> 
                        <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $workOrder->status)) }}</span>
                    </div>
                </div>

                <!-- Add Cost Form -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">Add Cost</h6>
                    </div>
                    <div class="card-body">
                        <form id="addCostForm">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Cost Type <span class="text-danger">*</span></label>
                                    <select name="cost_type" id="cost_type" class="form-select" required>
                                        <option value="material">Material</option>
                                        <option value="labor">Labor</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label">Description <span class="text-danger">*</span></label>
                                    <input type="text" name="description" class="form-control" required>
                                </div>
                                <div class="col-md-6" id="inventory_field" style="display: none;">
                                    <label class="form-label">Inventory Item</label>
                                    <select name="inventory_item_id" class="form-select select2-single">
                                        <option value="">Select Item</option>
                                        @foreach($inventoryItems as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6" id="employee_field" style="display: none;">
                                    <label class="form-label">Employee</label>
                                    <select name="employee_id" class="form-select select2-single">
                                        <option value="">Select Employee</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="supplier_field" style="display: none;">
                                    <label class="form-label">Supplier</label>
                                    <select name="supplier_id" class="form-select select2-single">
                                        <option value="">Select Supplier</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" value="1" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Unit</label>
                                    <input type="text" name="unit" class="form-control" placeholder="e.g., pcs, hrs">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Unit Cost (TZS) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="unit_cost" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tax Amount (TZS)</label>
                                    <input type="number" step="0.01" min="0" name="tax_amount" class="form-control" value="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cost Date <span class="text-danger">*</span></label>
                                    <input type="date" name="cost_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Notes</label>
                                    <input type="text" name="notes" class="form-control">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-plus me-1"></i>Add Cost
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Costs List -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Recorded Costs</h6>
                    </div>
                    <div class="card-body">
                        <div id="costsList">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Qty</th>
                                            <th>Unit Cost</th>
                                            <th>Tax</th>
                                            <th>Total</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="costsTableBody">
                                        @foreach($workOrder->costs as $cost)
                                        <tr>
                                            <td><span class="badge bg-secondary">{{ ucfirst($cost->cost_type) }}</span></td>
                                            <td>{{ $cost->description }}</td>
                                            <td>{{ $cost->quantity }} {{ $cost->unit ?? '' }}</td>
                                            <td>TZS {{ number_format($cost->unit_cost, 2) }}</td>
                                            <td>TZS {{ number_format($cost->tax_amount, 2) }}</td>
                                            <td>TZS {{ number_format($cost->total_with_tax, 2) }}</td>
                                            <td>{{ $cost->cost_date->format('M d, Y') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td colspan="5" class="text-end">Total Actual Cost:</td>
                                            <td>TZS {{ number_format($workOrder->total_actual_cost, 2) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Complete Work Order -->
                @if($workOrder->costs->count() > 0)
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">Complete Work Order</h6>
                    </div>
                    <div class="card-body">
                        <form id="completeForm">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Work Performed <span class="text-danger">*</span></label>
                                    <textarea name="work_performed" class="form-control" rows="4" required>{{ $workOrder->work_performed }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Actual Completion Date <span class="text-danger">*</span></label>
                                    <input type="date" name="actual_completion_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Actual Downtime (Hours)</label>
                                    <input type="number" min="0" name="actual_downtime_hours" class="form-control" value="{{ $workOrder->actual_downtime_hours ?? 0 }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Technician Notes</label>
                                    <textarea name="technician_notes" class="form-control" rows="2">{{ $workOrder->technician_notes }}</textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bx bx-check-circle me-1"></i>Complete Work Order
                                    </button>
                                    <a href="{{ route('assets.maintenance.work-orders.show', $encodedId) }}" class="btn btn-secondary">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Show/hide fields based on cost type
    $('#cost_type').on('change', function() {
        const type = $(this).val();
        $('#inventory_field, #employee_field, #supplier_field').hide();
        if (type === 'material') {
            $('#inventory_field, #supplier_field').show();
        } else if (type === 'labor') {
            $('#employee_field').show();
        } else if (type === 'other') {
            $('#supplier_field').show();
        }
    }).trigger('change');

    // Add cost
    $('#addCostForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route('assets.maintenance.work-orders.add-cost', $encodedId) }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire('Success!', response.message, 'success');
                    location.reload();
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

    // Complete work order
    $('#completeForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Complete this work order?',
            text: 'This will mark the work order as completed and require cost classification review.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, complete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('assets.maintenance.work-orders.complete', $encodedId) }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Completed!', response.message, 'success').then(() => {
                                window.location.href = '{{ route('assets.maintenance.work-orders.show', $encodedId) }}';
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to complete work order';
                        Swal.fire('Error!', message, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush

