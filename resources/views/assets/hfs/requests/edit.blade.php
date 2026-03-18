@extends('layouts.main')

@section('title', 'Edit HFS Request')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Held for Sale', 'url' => route('assets.hfs.requests.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit HFS Request - {{ $hfsRequest->request_no }}</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.hfs.requests.update', $encodedId) }}" id="hfs-request-form" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Intended Sale Date <span class="text-danger">*</span></label>
                            <input type="date" name="intended_sale_date" class="form-control" 
                                value="{{ old('intended_sale_date', $hfsRequest->intended_sale_date->format('Y-m-d')) }}" required>
                            @error('intended_sale_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected Close Date</label>
                            <input type="date" name="expected_close_date" class="form-control" 
                                value="{{ old('expected_close_date', $hfsRequest->expected_close_date ? $hfsRequest->expected_close_date->format('Y-m-d') : '') }}">
                            @error('expected_close_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Buyer (Customer) <span class="text-muted small">(Optional - if identified)</span></label>
                            <div class="customer-group d-flex align-items-stretch">
                                <select class="form-select select2-single flex-grow-1" id="customer_id" name="customer_id">
                                    <option value="">Select Customer (or leave blank if buyer not yet identified)</option>
                                    @php
                                        $selectedCustomerId = old('customer_id', $hfsRequest->customer_id);
                                    @endphp
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" 
                                            data-name="{{ $customer->name }}"
                                            data-phone="{{ $customer->phone ?? '' }}"
                                            data-address="{{ $customer->company_address ?? '' }}"
                                            {{ $selectedCustomerId && (string)$selectedCustomerId === (string)$customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }}@if($customer->phone) - {{ $customer->phone }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary ms-2 btn-add-customer" id="open-add-customer" title="Add customer">
                                    <i class="bx bx-plus"></i>
                                </button>
                            </div>
                            @error('customer_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            <!-- Hidden fields to store buyer info for backward compatibility -->
                            <input type="hidden" name="buyer_name" id="buyer_name" value="{{ old('buyer_name', $hfsRequest->buyer_name) }}">
                            <input type="hidden" name="buyer_contact" id="buyer_contact" value="{{ old('buyer_contact', $hfsRequest->buyer_contact) }}">
                            <input type="hidden" name="buyer_address" id="buyer_address" value="{{ old('buyer_address', $hfsRequest->buyer_address) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Buyer Contact</label>
                            <input type="text" id="buyer_contact_display" class="form-control" readonly value="{{ old('buyer_contact', $hfsRequest->buyer_contact) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Buyer Address</label>
                            <textarea id="buyer_address_display" class="form-control" rows="2" readonly>{{ old('buyer_address', $hfsRequest->buyer_address) }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Expected Fair Value</label>
                            <input type="number" name="expected_fair_value" class="form-control" 
                                step="0.01" min="0" value="{{ old('expected_fair_value', $hfsRequest->expected_fair_value) }}">
                            @error('expected_fair_value')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected Costs to Sell</label>
                            <input type="number" name="expected_costs_to_sell" class="form-control" 
                                step="0.01" min="0" value="{{ old('expected_costs_to_sell', $hfsRequest->expected_costs_to_sell) }}">
                            @error('expected_costs_to_sell')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sale Price Range</label>
                            <input type="text" name="sale_price_range" class="form-control" 
                                value="{{ old('sale_price_range', $hfsRequest->sale_price_range) }}">
                            @error('sale_price_range')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Probability (%)</label>
                            <input type="number" name="probability_pct" class="form-control" 
                                step="0.01" min="0" max="100" value="{{ old('probability_pct', $hfsRequest->probability_pct) }}">
                            @error('probability_pct')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Marketing Actions</label>
                            <textarea name="marketing_actions" class="form-control" rows="3">{{ old('marketing_actions', $hfsRequest->marketing_actions) }}</textarea>
                            @error('marketing_actions')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="exceeds_12_months" id="exceeds_12_months" value="1" 
                                    {{ old('exceeds_12_months', $hfsRequest->exceeds_12_months) ? 'checked' : '' }}>
                                <label class="form-check-label" for="exceeds_12_months">
                                    Sale expected beyond 12 months
                                </label>
                            </div>
                        </div>
                        <div class="col-12" id="extension-justification-field" style="display: {{ $hfsRequest->exceeds_12_months ? 'block' : 'none' }};">
                            <label class="form-label">Extension Justification</label>
                            <textarea name="extension_justification" class="form-control" rows="3">{{ old('extension_justification', $hfsRequest->extension_justification) }}</textarea>
                            @error('extension_justification')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_disposal_group" id="is_disposal_group" value="1" 
                                    {{ old('is_disposal_group', $hfsRequest->is_disposal_group) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_disposal_group">
                                    This is a disposal group
                                </label>
                            </div>
                        </div>
                        <div class="col-12" id="disposal-group-description-field" style="display: {{ $hfsRequest->is_disposal_group ? 'block' : 'none' }};">
                            <label class="form-label">Disposal Group Description</label>
                            <textarea name="disposal_group_description" class="form-control" rows="3">{{ old('disposal_group_description', $hfsRequest->disposal_group_description) }}</textarea>
                            @error('disposal_group_description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Justification <span class="text-danger">*</span></label>
                            <textarea name="justification" class="form-control" rows="4" required>{{ old('justification', $hfsRequest->justification) }}</textarea>
                            @error('justification')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <!-- Management Commitment Section -->
                        <div class="col-12">
                            <div class="alert alert-warning mb-3">
                                <i class="bx bx-error-circle me-2"></i>
                                <strong>Management Commitment Required for Approval:</strong> You must check the commitment box, set the commitment date, and attach management minutes or approval document to submit for approval.
                            </div>
                            
                            <div class="card border-primary mb-3">
                                <div class="card-header bg-primary bg-opacity-10">
                                    <h6 class="mb-0"><i class="bx bx-check-circle me-2"></i>Management Commitment <span class="text-danger">*</span></h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="management_committed" id="management_committed" value="1" 
                                            {{ old('management_committed', $hfsRequest->management_committed) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold" for="management_committed">
                                            Management is committed to the sale
                                        </label>
                                    </div>
                                    <div class="alert alert-info mb-3">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>Required for IFRS 5:</strong> Check this box to confirm that management has formally committed to the sale. 
                                        You must also set the commitment date below and attach evidence (management minutes, board resolution, etc.) in the attachments field.
                                    </div>
                                    
                                    <div id="management-commitment-date-field" style="display: {{ old('management_committed', $hfsRequest->management_committed) ? 'block' : 'none' }};">
                                        <label class="form-label">Management Commitment Date <span class="text-danger">*</span></label>
                                        <input type="date" name="management_commitment_date" id="management_commitment_date" class="form-control" 
                                            value="{{ old('management_commitment_date', $hfsRequest->management_commitment_date ? $hfsRequest->management_commitment_date->format('Y-m-d') : date('Y-m-d')) }}">
                                        @error('management_commitment_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                        <div class="form-text">
                                            <i class="bx bx-info-circle me-1"></i>Date when management formally committed to the sale. 
                                            This should match the date on the management minutes or board resolution document you attach.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Attachments <span class="text-danger">*</span> <span class="text-muted small">(Required for approval)</span></label>
                            <input type="file" name="attachments[]" id="attachments" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i><strong>Required:</strong> Attach management minutes or board resolution document as evidence of management commitment. 
                                You may also attach: Valuer report (optional), Marketing evidence (optional). 
                                Maximum file size: 10MB per file. Accepted formats: PDF, DOC, DOCX, JPG, JPEG, PNG.
                            </div>
                            @error('attachments')<div class="text-danger small">{{ $message }}</div>@enderror
                            
                            @if($hfsRequest->attachments && count($hfsRequest->attachments) > 0)
                                <div class="mt-2">
                                    <strong>Current Attachments:</strong>
                                    <ul class="list-unstyled mt-2">
                                        @foreach($hfsRequest->attachments as $attachment)
                                            <li>
                                                <i class="bx bx-file me-1"></i>
                                                @if(is_array($attachment))
                                                    @if(isset($attachment['path']) && !empty($attachment['path']))
                                                        <a href="{{ Storage::url($attachment['path']) }}" target="_blank">
                                                            {{ $attachment['original_name'] ?? basename($attachment['path']) }}
                                                        </a>
                                                        @if(isset($attachment['size']))
                                                            <small class="text-muted">({{ number_format($attachment['size'] / 1024, 2) }} KB)</small>
                                                        @endif
                                                    @else
                                                        {{ $attachment['original_name'] ?? 'Attachment' }}
                                                    @endif
                                                @elseif(is_string($attachment))
                                                    @if(Storage::exists($attachment))
                                                        <a href="{{ Storage::url($attachment) }}" target="_blank">
                                                            {{ basename($attachment) }}
                                                        </a>
                                                    @else
                                                        {{ basename($attachment) }}
                                                    @endif
                                                @else
                                                    {{ $attachment }}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                    <small class="text-muted">New files will be added to existing attachments.</small>
                                </div>
                            @endif
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="4">{{ old('notes', $hfsRequest->notes) }}</textarea>
                            @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('assets.hfs.requests.show', $encodedId) }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update HFS Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="add-customer-errors" class="alert alert-danger d-none"></div>
                <div class="mb-3">
                    <label class="form-label" for="ac_name">Name<span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ac_name" placeholder="Customer name">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="ac_phone">Phone<span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ac_phone" placeholder="07XXXXXXXXX">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="ac_email">Email</label>
                    <input type="email" class="form-control" id="ac_email" placeholder="email@example.com">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="ac_address">Address</label>
                    <textarea class="form-control" id="ac_address" rows="2" placeholder="Company address"></textarea>
                </div>
                <input type="hidden" id="ac_status" value="active">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-customer-btn">
                    <i class="bx bx-save me-1"></i>Save Customer
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Customer group styling */
.customer-group .select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
}
.customer-group .btn-add-customer {
    height: 38px;
    padding-left: 12px;
    padding-right: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
@media (min-width: 768px){
    .customer-group .select2-container {
        flex: 1 1 auto !important;
        width: 1% !important;
    }
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Get the pre-selected customer ID
    const preselectedCustomerId = @json(old('customer_id', $hfsRequest->customer_id));
    
    // Initialize Select2 for customer selection
    $('#customer_id').select2({
        placeholder: 'Select customer...',
        width: '100%',
        theme: 'bootstrap-5'
    });
    
    // Set the value after Select2 is initialized (if there's a preselected value)
    if (preselectedCustomerId) {
        // Wait a bit for Select2 to fully initialize, then set the value
        setTimeout(function() {
            $('#customer_id').val(preselectedCustomerId).trigger('change');
        }, 100);
    }

    // Handle customer selection change
    $('#customer_id').on('change', function() {
        const customerId = $(this).val();
        if (customerId) {
            // Get customer data from the selected option's data attributes
            const selectedOption = $(this).find('option:selected');
            let customerName = selectedOption.data('name');
            let customerPhone = selectedOption.data('phone') || '';
            let customerAddress = selectedOption.data('address') || '';
            
            // If data attributes are not available, try to parse from option text
            if (!customerName) {
                const optionText = selectedOption.text();
                customerName = optionText.split(' - ')[0];
            }
            
            // If phone is in the option text but not in data attribute, try to extract it
            if (!customerPhone) {
                const optionText = selectedOption.text();
                const parts = optionText.split(' - ');
                if (parts.length > 1) {
                    customerPhone = parts[1].trim();
                }
            }
            
            // Populate hidden fields for backward compatibility
            $('#buyer_name').val(customerName);
            $('#buyer_contact').val(customerPhone);
            $('#buyer_address').val(customerAddress);
            
            // Populate display fields
            $('#buyer_contact_display').val(customerPhone);
            $('#buyer_address_display').val(customerAddress);
        } else {
            // Clear all fields if no customer selected
            $('#buyer_name, #buyer_contact, #buyer_address').val('');
            $('#buyer_contact_display, #buyer_address_display').val('');
        }
    });

    // Add Customer Modal logic
    $('#open-add-customer').on('click', function(){
        $('#add-customer-errors').addClass('d-none').empty();
        $('#ac_name, #ac_phone, #ac_email, #ac_address').val('');
        $('#addCustomerModal').modal('show');
    });

    $('#save-customer-btn').on('click', function(){
        // Normalize phone input client-side before sending
        function normalizePhoneClient(phone){
            let p = (phone || '').replace(/[^0-9+]/g, '');
            if (p.startsWith('+255')) { p = '255' + p.slice(4); }
            else if (p.startsWith('0')) { p = '255' + p.slice(1); }
            else if (/^\d{9}$/.test(p)) { p = '255' + p; }
            return p;
        }
        const payload = {
            name: $('#ac_name').val().trim(),
            phone: normalizePhoneClient($('#ac_phone').val().trim()),
            email: $('#ac_email').val().trim(),
            company_address: $('#ac_address').val().trim(),
            status: $('#ac_status').val(),
            _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val()
        };
        if (!payload.name) {
            $('#add-customer-errors').removeClass('d-none').html('<div>Name is required.</div>');
            return;
        }
        if (!payload.phone) {
            $('#add-customer-errors').removeClass('d-none').html('<div>Phone is required.</div>');
            return;
        }
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        $.ajax({
            url: '{{ route("customers.store") }}',
            method: 'POST',
            data: payload,
            headers: { 'Accept': 'application/json' },
        }).done(function(res){
            // Append and select the new customer
            const id = res?.customer?.id;
            const customer = res?.customer || {};
            const customerName = customer.name || payload.name;
            const customerPhone = customer.phone || payload.phone || '';
            const customerAddress = customer.company_address || payload.company_address || '';
            const label = customerName + (customerPhone ? (' - ' + customerPhone) : '');
            
            if (id) {
                // Create new option with data attributes
                const newOption = $('<option></option>')
                    .attr('value', id)
                    .attr('data-name', customerName)
                    .attr('data-phone', customerPhone)
                    .attr('data-address', customerAddress)
                    .text(label)
                    .prop('selected', true);
                
                $('#customer_id').append(newOption);
                
                // Refresh Select2 to recognize the new option
                $('#customer_id').trigger('change.select2');
                
                // Manually populate fields immediately with the customer data
                $('#buyer_name').val(customerName);
                $('#buyer_contact').val(customerPhone);
                $('#buyer_address').val(customerAddress);
                $('#buyer_contact_display').val(customerPhone);
                $('#buyer_address_display').val(customerAddress);
            }
            $('#addCustomerModal').modal('hide');
            Swal.fire('Success','Customer created','success');
        }).fail(function(xhr){
            let msg = 'Failed to create customer';
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                const list = Object.values(errors).flat().map(e=>`<div>${e}</div>`).join('');
                $('#add-customer-errors').removeClass('d-none').html(list);
            } else {
                $('#add-customer-errors').removeClass('d-none').text((xhr.responseJSON && xhr.responseJSON.message) || msg);
            }
        }).always(function(){
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Customer');
        });
    });

    // Load customer details if customer_id is pre-selected (already handled above)
    // The change event will be triggered automatically when we set the value

    // Show/hide management commitment date
    $('#management_committed').on('change', function() {
        if ($(this).is(':checked')) {
            $('#management-commitment-date-field').slideDown();
            $('#management_commitment_date').prop('required', true);
        } else {
            $('#management-commitment-date-field').slideUp();
            $('#management_commitment_date').prop('required', false);
        }
    });
    
    // Initialize on page load if checkbox is already checked
    if ($('#management_committed').is(':checked')) {
        $('#management-commitment-date-field').show();
        $('#management_commitment_date').prop('required', true);
    }

    $('#exceeds_12_months').on('change', function() {
        if ($(this).is(':checked')) {
            $('#extension-justification-field').show();
        } else {
            $('#extension-justification-field').hide();
        }
    });

    $('#is_disposal_group').on('change', function() {
        if ($(this).is(':checked')) {
            $('#disposal-group-description-field').show();
        } else {
            $('#disposal-group-description-field').hide();
        }
    });
});
</script>
@endpush

