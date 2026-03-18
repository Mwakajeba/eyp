@extends('layouts.main')

@section('title', 'Edit Trip Cost - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Cost Management', 'url' => route('fleet.trip-costs.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Edit Cost', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Trip Cost</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.trip-costs.update', $cost->hash_id) }}" id="cost-form" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <h6 class="text-danger mb-3"><i class="bx bx-info-circle me-2"></i>Trip & Date</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Trip</label>
                                <input type="text" class="form-control" value="{{ $cost->trip->trip_number ?? 'N/A' }}" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date Incurred <span class="text-danger">*</span></label>
                                <input type="date" name="date_incurred" class="form-control" value="{{ old('date_incurred', $cost->date_incurred->format('Y-m-d')) }}" required>
                                @error('date_incurred')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="text-danger mb-3"><i class="bx bx-money me-2"></i>Cost Lines</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="cost-lines-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 25%;">GL Account <span class="text-danger">*</span></th>
                                    <th style="width: 10%;">Qty</th>
                                    <th style="width: 15%;">Amount (TZS) <span class="text-danger">*</span></th>
                                    <th style="width: 20%;">Cost Category</th>
                                    <th style="width: 25%;">Description</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="cost-lines-container">
                                <!-- Lines will be populated from existing cost -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-line-btn">
                                            <i class="bx bx-plus me-1"></i>Add Line
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total Amount:</td>
                                    <td class="fw-bold" id="total-amount">0.00 TZS</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <hr class="my-4">
                    <h6 class="text-danger mb-3"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Attach Files</label>
                                <input type="file" name="attachments[]" class="form-control" multiple accept="image/*,.pdf,.doc,.docx">
                                <div class="form-text">You can select multiple files (Images, PDF, Word documents). Existing attachments will be replaced.</div>
                                @if($cost->attachments)
                                <div class="mt-2">
                                    <small class="text-muted">Current attachments:</small>
                                    <ul class="list-unstyled">
                                        @foreach($cost->attachments as $attachment)
                                        <li><i class="bx bx-file me-1"></i>{{ $attachment['original_name'] ?? 'File' }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                                @error('attachments.*')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="text-danger mb-3"><i class="bx bx-wallet me-2"></i>Payment Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Paid From (Bank Account) <span class="text-danger">*</span></label>
                                <select name="paid_from_account_id" id="paid_from_account_id" class="form-select select2-single" required>
                                    <option value="">Select Bank Account</option>
                                    @foreach($bankAccounts as $bankAccount)
                                        <option value="{{ $bankAccount->id }}" {{ old('paid_from_account_id', $selectedBankAccountId) == $bankAccount->id ? 'selected' : '' }}>
                                            {{ $bankAccount->name }}@if($bankAccount->account_number) - {{ $bankAccount->account_number }}@endif@if($bankAccount->currency) ({{ $bankAccount->currency }})@endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select the cash or bank account from which this cost was paid</div>
                                @error('paid_from_account_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Receipt Number</label>
                                <input type="text" name="receipt_number" class="form-control" value="{{ old('receipt_number', $cost->receipt_number) }}">
                                @error('receipt_number')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="text-danger mb-3"><i class="bx bx-receipt me-2"></i>Additional Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Billable to Customer</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_billable_to_customer" value="1" {{ old('is_billable_to_customer', $cost->is_billable_to_customer) ? 'checked' : '' }}>
                                    <label class="form-check-label">Yes, bill this cost to customer</label>
                                </div>
                                @error('is_billable_to_customer')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes...">{{ old('notes', $cost->notes) }}</textarea>
                                @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.trip-costs.show', $cost->hash_id) }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-save me-1"></i>Update Cost
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
    let lineIndex = 0;
    const glAccounts = @json($glAccounts);
    const costCategories = @json($costCategories);
    const existingLines = @json($costLines ?? []);

    // Initialize Select2
    function initSelect2($element) {
        if ($element.length && !$element.hasClass('select2-hidden-accessible')) {
            $element.select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        }
    }

    // Add cost line
    function addCostLine(lineData = null) {
        lineIndex++;
        const lineHtml = `
            <tr class="cost-line-row" data-index="${lineIndex}">
                <td>
                    <select name="cost_lines[${lineIndex}][gl_account_id]" class="form-select gl-account-select" required>
                        <option value="">Select GL Account</option>
                        ${glAccounts.map(acc => `<option value="${acc.id}" ${(lineData && lineData.gl_account_id == acc.id) ? 'selected' : ''}>${acc.account_code} - ${acc.account_name}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="cost_lines[${lineIndex}][qty]" class="form-control qty-input" placeholder="1.00" value="${lineData ? (lineData.qty ?? 1) : 1}">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="cost_lines[${lineIndex}][amount]" class="form-control amount-input" placeholder="0.00" value="${lineData ? (lineData.amount ?? 0) : 0}" required>
                </td>
                <td>
                    <select name="cost_lines[${lineIndex}][cost_category_id]" class="form-select cost-category-select">
                        <option value="">Select Category</option>
                        ${costCategories.map(cat => `<option value="${cat.id}" ${(lineData && lineData.cost_category_id == cat.id) ? 'selected' : ''}>${cat.name}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <input type="text" name="cost_lines[${lineIndex}][description]" class="form-control" placeholder="Description" value="${lineData ? (lineData.description ?? '') : ''}">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn" title="Remove Line">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#cost-lines-container').append(lineHtml);
        
        // Initialize Select2 for new line
        const $newRow = $('#cost-lines-container tr').last();
        initSelect2($newRow.find('.gl-account-select'));
        initSelect2($newRow.find('.cost-category-select'));
        
        // Calculate total
        calculateTotal();
    }

    // Remove cost line
    $(document).on('click', '.remove-line-btn', function() {
        if ($('.cost-line-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotal();
        } else {
            alert('At least one cost line is required.');
        }
    });

    // Calculate total amount
    function calculateTotal() {
        let total = 0;
        $('.amount-input').each(function() {
            const amount = parseFloat($(this).val()) || 0;
            total += amount;
        });
        $('#total-amount').text(total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' TZS');
    }

    // Recalculate total when amount changes
    $(document).on('input', '.amount-input', function() {
        calculateTotal();
    });

    // Load existing cost lines
    if (existingLines && existingLines.length > 0) {
        existingLines.forEach(line => {
            addCostLine(line);
        });
    } else {
        // Add first line by default
        addCostLine();
    }

    // Add line button
    $('#add-line-btn').on('click', function() {
        addCostLine();
    });

    // Initialize Select2 for existing elements
    initSelect2($('.gl-account-select'));
    initSelect2($('.cost-category-select'));
    initSelect2($('#paid_from_account_id'));
});
</script>
@endpush
