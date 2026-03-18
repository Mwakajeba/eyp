@extends('layouts.main')

@section('title', 'Batch Edit Trip Costs - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Cost Management', 'url' => route('fleet.trip-costs.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Batch Edit Costs', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Batch Edit Trip Costs ({{ $costs->count() }} items)</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.trip-costs.batch-update') }}" id="cost-form" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="cost_ids" value="{{ implode(',', $costIds) }}">

                    <h6 class="text-danger mb-3"><i class="bx bx-info-circle me-2"></i>Trip & Date</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Trip</label>
                                <input type="text" class="form-control" value="{{ $trip->trip_number ?? 'N/A' }}" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date Incurred <span class="text-danger">*</span></label>
                                <input type="date" name="date_incurred" class="form-control" value="{{ old('date_incurred', $costs->first()->date_incurred->format('Y-m-d')) }}" required>
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
                                    <th style="width: 22%;">GL Account <span class="text-danger">*</span></th>
                                    <th style="width: 8%;">Qty</th>
                                    <th style="width: 12%;">Amount (TZS) <span class="text-danger">*</span></th>
                                    <th style="width: 18%;">Cost Category</th>
                                    <th style="width: 22%;">Description</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="cost-lines-container">
                                @foreach($costLines as $idx => $line)
                                <tr class="cost-line-row" data-index="{{ $idx + 1 }}">
                                    <td>
                                        <input type="hidden" name="cost_lines[{{ $idx + 1 }}][id]" value="{{ $line['id'] }}">
                                        <select name="cost_lines[{{ $idx + 1 }}][gl_account_id]" class="form-select gl-account-select" required>
                                            <option value="">Select GL Account</option>
                                            @foreach($glAccounts as $acc)
                                            <option value="{{ $acc->id }}" {{ ($line['gl_account_id'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="cost_lines[{{ $idx + 1 }}][qty]" class="form-control qty-input" value="{{ $line['qty'] ?? 1 }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="cost_lines[{{ $idx + 1 }}][amount]" class="form-control amount-input" value="{{ $line['amount'] ?? 0 }}" required>
                                    </td>
                                    <td>
                                        <select name="cost_lines[{{ $idx + 1 }}][cost_category_id]" class="form-select cost-category-select">
                                            <option value="">Select Category</option>
                                            @foreach($costCategories as $cat)
                                            <option value="{{ $cat->id }}" {{ ($line['cost_category_id'] ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="cost_lines[{{ $idx + 1 }}][description]" class="form-control" value="{{ $line['description'] ?? '' }}">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn" title="Remove Line">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                                @if(empty($costLines))
                                <tr class="cost-line-row placeholder-row" data-index="0">
                                    <td colspan="6" class="text-center text-muted">No cost lines. Click Add Line below.</td>
                                </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Grand Total:</td>
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
                            @if(!empty($existingAttachments))
                            <div class="mb-3">
                                <label class="form-label">Current Attachments</label>
                                <div class="list-group mb-3">
                                    @foreach($existingAttachments as $index => $attachment)
                                    <div class="list-group-item d-flex justify-content-between align-items-center existing-attachment-item" data-index="{{ $index }}">
                                        <div>
                                            <i class="bx bx-file me-2"></i>
                                            <strong>{{ $attachment['original_name'] ?? 'File' }}</strong>
                                            @if(isset($attachment['size']))
                                                <small class="text-muted ms-2">({{ number_format($attachment['size'] / 1024, 2) }} KB)</small>
                                            @endif
                                        </div>
                                        @if(isset($attachment['path']))
                                            @php
                                                $filePath = str_replace('storage/', '', $attachment['path']);
                                                $fileUrl = asset('storage/' . $filePath);
                                            @endphp
                                            <div>
                                                <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary me-1" title="View">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ $fileUrl }}" download="{{ $attachment['original_name'] ?? 'attachment' }}" class="btn btn-sm btn-outline-success me-1" title="Download">
                                                    <i class="bx bx-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-attachment-btn" data-index="{{ $index }}" title="Delete">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                    @endforeach
                                    <input type="hidden" name="deleted_attachments" id="deleted-attachments" value="">
                                </div>
                                <div class="alert alert-info">
                                    <small><i class="bx bx-info-circle me-1"></i>You can delete individual attachments or upload new files to add more.</small>
                                </div>
                            </div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">Attach Files</label>
                                <input type="file" name="attachments[]" class="form-control" multiple accept="image/*,.pdf,.doc,.docx">
                                <div class="form-text">You can select multiple files (Images, PDF, Word documents). @if(!empty($existingAttachments))New files will replace existing attachments.@else Leave empty to keep existing attachments.@endif</div>
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
                                @php
                                    $bankList = $bankAccounts;
                                    if (isset($selectedBankAccount) && $selectedBankAccount && !$bankAccounts->contains('id', $selectedBankAccount->id)) {
                                        $bankList = collect([$selectedBankAccount])->merge($bankAccounts);
                                    }
                                @endphp
                                <select name="paid_from_account_id" id="paid_from_account_id" class="form-select select2-single" required>
                                    <option value="">Select Bank Account</option>
                                    @foreach($bankList as $bankAccount)
                                        @php
                                            $isSelected = (old('paid_from_account_id', $selectedBankAccountId) == $bankAccount->id);
                                        @endphp
                                        <option value="{{ $bankAccount->id }}" {{ $isSelected ? 'selected="selected"' : '' }}>
                                            {{ $bankAccount->name }}@if($bankAccount->account_number ?? null) - {{ $bankAccount->account_number }}@endif@if($bankAccount->currency ?? null) ({{ $bankAccount->currency }})@endif
                                        </option>
                                    @endforeach
                                </select>
                                @if($selectedBankAccountId)
                                    <input type="hidden" id="selected-bank-account-id" value="{{ $selectedBankAccountId }}">
                                @endif
                                <div class="form-text text-muted">
                                    <small><i class="bx bx-info-circle me-1"></i>Select the bank account used for this payment. Saved value is pre-selected when available.</small>
                                </div>
                                @error('paid_from_account_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Receipt Number</label>
                                <input type="text" name="receipt_number" class="form-control" value="{{ old('receipt_number', $costs->first()->receipt_number) }}">
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
                                    <input class="form-check-input" type="checkbox" name="is_billable_to_customer" value="1" {{ old('is_billable_to_customer', $costs->first()->is_billable_to_customer) ? 'checked' : '' }}>
                                    <label class="form-check-label">Yes, bill this cost to customer</label>
                                </div>
                                @error('is_billable_to_customer')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes...">{{ old('notes', $costs->first()->notes) }}</textarea>
                                @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.trip-costs.view-group', ['cost_ids' => implode(',', $costIds)]) }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-save me-1"></i>Update All Costs
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
    let lineIndex = {{ count($costLines ?? []) }};
    const glAccounts = @json($glAccounts);
    const costCategories = @json($costCategories);

    // Initialize Select2
    function initSelect2($element) {
        if ($element.length && !$element.hasClass('select2-hidden-accessible')) {
            $element.select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        }
    }

    // Add cost line (for Add Line button - new empty line)
    function addCostLine(lineData = null) {
        $('.placeholder-row').remove();
        lineIndex++;
        const lineHtml = `
            <tr class="cost-line-row" data-index="${lineIndex}">
                <td>
                    <input type="hidden" name="cost_lines[${lineIndex}][id]" value="${lineData ? (lineData.id ?? '') : ''}">
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

    // Cost lines are server-rendered; init Select2 and calculate total on load
    initSelect2($('.gl-account-select'));
    initSelect2($('.cost-category-select'));
    calculateTotal();

    // Add line button
    $('#add-line-btn').on('click', function() {
        addCostLine();
    });

    // Paid From bank select: set value then init Select2 so saved bank is visible and selectable
    const $paidFromSelect = $('#paid_from_account_id');
    const selectedBankAccountId = $('#selected-bank-account-id').val() || @json($selectedBankAccountId ?? null);
    
    if (selectedBankAccountId && $paidFromSelect.find('option[value="' + selectedBankAccountId + '"]').length) {
        $paidFromSelect.val(selectedBankAccountId);
    }
    
    initSelect2($paidFromSelect);
    
    if (selectedBankAccountId) {
        $paidFromSelect.val(selectedBankAccountId).trigger('change');
    }
    
    // Handle attachment deletion
    let deletedAttachmentIndices = [];
    
    $(document).on('click', '.remove-attachment-btn', function() {
        const index = $(this).data('index');
        const attachmentItem = $(this).closest('.existing-attachment-item');
        const fileName = attachmentItem.find('strong').text() || 'this attachment';
        
        Swal.fire({
            title: 'Delete Attachment?',
            html: `Are you sure you want to delete <strong>${fileName}</strong>?<br><br>` +
                  '<small class="text-muted">This will remove the attachment from the cost record.</small><br><br>' +
                  '<strong class="text-danger">This action cannot be undone!</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bx bx-trash me-1"></i>Yes, Delete',
            cancelButtonText: '<i class="bx bx-x me-1"></i>Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                deletedAttachmentIndices.push(index);
                attachmentItem.fadeOut(300, function() {
                    $(this).remove();
                });
                
                // Update hidden input
                $('#deleted-attachments').val(deletedAttachmentIndices.join(','));
                
                Swal.fire({
                    title: 'Deleted!',
                    text: 'Attachment removed successfully.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    });
});
</script>
@endpush
