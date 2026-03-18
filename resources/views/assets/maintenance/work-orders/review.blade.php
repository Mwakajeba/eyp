@extends('layouts.main')

@section('title', 'Review Work Order')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Maintenance', 'url' => route('assets.maintenance.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Work Orders', 'url' => route('assets.maintenance.work-orders.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Review', 'url' => '#', 'icon' => 'bx bx-check-circle']
        ]" />

        <div class="card">
            <div class="card-header bg-warning text-white">
                <h6 class="mb-0"><i class="bx bx-check-circle me-2"></i>Review & Classify Work Order: {{ $workOrder->wo_number }}</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Total Actual Cost:</strong> TZS {{ number_format($workOrder->total_actual_cost, 2) }}<br>
                    <strong>Capitalization Threshold:</strong> TZS {{ number_format($capitalizationThreshold, 2) }}<br>
                    <strong>Life Extension Threshold:</strong> {{ $lifeExtensionThreshold }} months
                </div>

                <form id="classifyForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cost Classification <span class="text-danger">*</span></label>
                            <select name="cost_classification" id="cost_classification" class="form-select" required>
                                <option value="expense" {{ old('cost_classification', $workOrder->cost_classification) == 'expense' ? 'selected' : '' }}>Expense</option>
                                <option value="capitalized" {{ old('cost_classification', $workOrder->cost_classification) == 'capitalized' ? 'selected' : '' }}>Capitalized</option>
                            </select>
                            <div class="form-text">
                                <strong>Expense:</strong> Routine maintenance (restores to original condition)<br>
                                <strong>Capitalized:</strong> Major overhaul (extends life, increases capacity)
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Is Capital Improvement?</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_capital_improvement" value="1" id="is_capital_improvement" {{ old('is_capital_improvement', $workOrder->is_capital_improvement) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_capital_improvement">Yes, this is a capital improvement</label>
                            </div>
                        </div>
                        <div class="col-md-6" id="life_extension_field" style="display: none;">
                            <label class="form-label">Life Extension (Months)</label>
                            <input type="number" min="0" name="life_extension_months" class="form-control" value="{{ old('life_extension_months', $workOrder->life_extension_months) }}">
                            <div class="form-text">How many months does this maintenance extend the asset's useful life?</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Review Notes</label>
                            <textarea name="review_notes" class="form-control" rows="3">{{ old('review_notes', $workOrder->review_notes) }}</textarea>
                            <div class="form-text">Add any notes about the classification decision</div>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <strong>Note:</strong> Once classified, the system will automatically:
                                <ul class="mb-0 mt-2">
                                    <li>Post journal entries to GL</li>
                                    <li>Update asset cost if capitalized</li>
                                    <li>Create maintenance history record</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Classify & Post to GL
                            </button>
                            <a href="{{ route('assets.maintenance.work-orders.show', $encodedId) }}" class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i>Cancel
                            </a>
                        </div>
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
    // Show/hide life extension field
    $('#cost_classification, #is_capital_improvement').on('change', function() {
        if ($('#cost_classification').val() === 'capitalized' || $('#is_capital_improvement').is(':checked')) {
            $('#life_extension_field').show();
        } else {
            $('#life_extension_field').hide();
        }
    }).trigger('change');

    // Submit form
    $('#classifyForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Classify and post to GL?',
            text: 'This will post journal entries and update the asset if capitalized. This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('assets.maintenance.work-orders.classify', $encodedId) }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Success!', response.message, 'success').then(() => {
                                window.location.href = '{{ route('assets.maintenance.work-orders.show', $encodedId) }}';
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to classify work order';
                        Swal.fire('Error!', message, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush

