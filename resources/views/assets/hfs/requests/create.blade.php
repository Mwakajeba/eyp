@extends('layouts.main')

@section('title', 'Create HFS Request')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Held for Sale', 'url' => route('assets.hfs.requests.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Create Held for Sale (HFS) Request</h6>
            </div>
            <div class="card-body">
                <!-- Progress Steps -->
                <div class="wizard-steps mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="step-item active" data-step="1">
                                <div class="step-number">1</div>
                                <div class="step-label">Select Assets</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="step-item" data-step="2">
                                <div class="step-number">2</div>
                                <div class="step-label">Sale Plan</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="step-item" data-step="3">
                                <div class="step-number">3</div>
                                <div class="step-label">Documentation</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="step-item" data-step="4">
                                <div class="step-number">4</div>
                                <div class="step-label">Review & Submit</div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('assets.hfs.requests.store') }}" id="hfs-request-form" enctype="multipart/form-data">
                    @csrf

                    <!-- Step 1: Select Assets -->
                    <div class="wizard-step" id="step-1">
                        <h6 class="text-primary border-bottom pb-2 mb-3">Step 1: Select Assets</h6>
                        
                        <div class="alert alert-info mb-3">
                            <i class="bx bx-info-circle me-2"></i>
                            Select one or more assets to classify as Held for Sale. Assets must be active and not already classified as HFS.
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Select Assets <span class="text-danger">*</span></label>
                            <select name="asset_ids[]" id="asset_ids" class="form-select select2-multiple" multiple required>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" 
                                        data-cost="{{ $asset->purchase_cost ?? 0 }}"
                                        data-nbv="{{ $asset->current_nbv ?? 0 }}"
                                        data-category="{{ $asset->category->name ?? '' }}"
                                        data-location="{{ $asset->location ?? '' }}">
                                        {{ $asset->code }} - {{ $asset->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('asset_ids')<div class="text-danger small">{{ $message }}</div>@enderror
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>Select one or more assets to classify as Held for Sale. 
                                Only active assets that are not already classified as HFS or disposed are available for selection.
                            </div>
                        </div>

                        <!-- Selected Assets Summary -->
                        <div id="selected-assets-summary" class="card border-primary" style="display: none;">
                            <div class="card-header bg-primary bg-opacity-10">
                                <h6 class="mb-0">Selected Assets Summary</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Code</th>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th class="text-end">Cost</th>
                                                <th class="text-end">NBV</th>
                                            </tr>
                                        </thead>
                                        <tbody id="assets-summary-body">
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold">
                                                <td colspan="3">Total</td>
                                                <td class="text-end" id="total-cost">0.00</td>
                                                <td class="text-end" id="total-nbv">0.00</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                                Next: Sale Plan <i class="bx bx-right-arrow-alt ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Sale Plan -->
                    <div class="wizard-step" id="step-2" style="display: none;">
                        <h6 class="text-primary border-bottom pb-2 mb-3">Step 2: Sale Plan</h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Intended Sale Date <span class="text-danger">*</span></label>
                                <input type="date" name="intended_sale_date" id="intended_sale_date" class="form-control" 
                                    value="{{ old('intended_sale_date', date('Y-m-d', strtotime('+6 months'))) }}" required>
                                @error('intended_sale_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i><strong>Required for IFRS 5:</strong> 
                                    Expected date when the sale will be completed. IFRS 5 requires sale within 12 months from classification date. 
                                    If sale is expected beyond 12 months, check the extension box below and provide justification.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expected Close Date</label>
                                <input type="date" name="expected_close_date" id="expected_close_date" class="form-control" 
                                    value="{{ old('expected_close_date') }}">
                                @error('expected_close_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i>Target date for completing the sale transaction. 
                                    Should be on or after the intended sale date. Leave blank if not yet determined.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Buyer (Customer) <span class="text-muted small">(Optional - if identified)</span></label>
                                <div class="customer-group d-flex align-items-stretch">
                                    <select class="form-select select2-single flex-grow-1" id="customer_id" name="customer_id">
                                        <option value="">Select Customer (or leave blank if buyer not yet identified)</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" 
                                                data-name="{{ $customer->name }}"
                                                data-phone="{{ $customer->phone ?? '' }}"
                                                data-address="{{ $customer->company_address ?? '' }}"
                                                {{ (string)old('customer_id') === (string)$customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}@if($customer->phone) - {{ $customer->phone }}@endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-primary ms-2 btn-add-customer" id="open-add-customer" title="Add customer">
                                        <i class="bx bx-plus"></i>
                                    </button>
                                </div>
                                @error('customer_id')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i>Select the customer/buyer if already identified, or leave blank if actively marketing but no buyer yet. 
                                    Click the <i class="bx bx-plus"></i> button to add a new customer.
                                </div>
                                <!-- Hidden fields to store buyer info for backward compatibility -->
                                <input type="hidden" name="buyer_name" id="buyer_name" value="{{ old('buyer_name') }}">
                                <input type="hidden" name="buyer_contact" id="buyer_contact" value="{{ old('buyer_contact') }}">
                                <input type="hidden" name="buyer_address" id="buyer_address" value="{{ old('buyer_address') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Buyer Contact</label>
                                <input type="text" id="buyer_contact_display" class="form-control" readonly>
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i>Automatically populated from selected customer. 
                                    This field is read-only and displays the buyer's contact phone number.
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Buyer Address</label>
                                <textarea id="buyer_address_display" class="form-control" rows="2" readonly></textarea>
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i>Automatically populated from selected customer. 
                                    This field is read-only and displays the buyer's company address.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Expected Fair Value</label>
                                <input type="number" name="expected_fair_value" id="expected_fair_value" class="form-control" 
                                    step="0.01" min="0" value="{{ old('expected_fair_value') }}">
                                @error('expected_fair_value')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i>Estimated fair market value of the asset(s) in current condition. 
                                    This should be based on market research, appraisals, or comparable sales. Used for IFRS 5 measurement.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expected Costs to Sell</label>
                                <input type="number" name="expected_costs_to_sell" id="expected_costs_to_sell" class="form-control" 
                                    step="0.01" min="0" value="{{ old('expected_costs_to_sell', 0) }}">
                                @error('expected_costs_to_sell')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i>Estimated costs directly attributable to the sale (e.g., broker fees, legal fees, transfer taxes, advertising). 
                                    IFRS 5 measures assets at "fair value less costs to sell".
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sale Price Range</label>
                                <input type="text" name="sale_price_range" id="sale_price_range" class="form-control" 
                                    value="{{ old('sale_price_range') }}" placeholder="e.g., 100,000 - 150,000">
                                @error('sale_price_range')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i>Expected price range for the sale. 
                                    Helps demonstrate that a reasonable price is expected, which is required for IFRS 5 classification.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Probability (%)</label>
                                <input type="number" name="probability_pct" id="probability_pct" class="form-control" 
                                    step="0.01" min="0" max="100" value="{{ old('probability_pct', 75) }}">
                                @error('probability_pct')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div class="form-text">Probability of sale (should be >75% for "highly probable")</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Marketing Actions <span class="text-danger">*</span></label>
                                <textarea name="marketing_actions" id="marketing_actions" class="form-control" rows="3" required>{{ old('marketing_actions') }}</textarea>
                                @error('marketing_actions')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <strong>Required for IFRS 5:</strong> Describe your active program to locate a buyer (e.g., "Listed on property website", "Engaged broker", "Advertised in trade publications"). 
                                    A specific buyer is not required, but you must demonstrate an active marketing program.
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="exceeds_12_months" id="exceeds_12_months" value="1" {{ old('exceeds_12_months') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="exceeds_12_months">
                                        Sale expected beyond 12 months (requires board approval)
                                    </label>
                                </div>
                                <div class="form-text ms-4">
                                    <i class="bx bx-info-circle me-1"></i>Check this box if the sale is expected to take longer than 12 months. 
                                    IFRS 5 normally requires sale within 12 months, but exceptions are allowed if the delay is beyond management's control 
                                    and board approval is obtained. You will need to provide justification below.
                                </div>
                            </div>
                            <div class="col-12" id="extension-justification-field" style="display: none;">
                                <label class="form-label">Extension Justification <span class="text-danger">*</span></label>
                                <textarea name="extension_justification" id="extension_justification" class="form-control" rows="3">{{ old('extension_justification') }}</textarea>
                                @error('extension_justification')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i><strong>Required when sale exceeds 12 months:</strong> 
                                    Provide detailed justification for why the sale is expected beyond 12 months. 
                                    This requires board approval and must demonstrate that the delay is beyond management's control 
                                    (e.g., regulatory approval, market conditions, buyer financing delays).
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_disposal_group" id="is_disposal_group" value="1" {{ old('is_disposal_group') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_disposal_group">
                                        This is a disposal group (for discontinued operations)
                                    </label>
                                </div>
                                <div class="form-text ms-4">
                                    <i class="bx bx-info-circle me-1"></i>Check this box if you are disposing of a group of assets together as a single transaction. 
                                    Disposal groups may qualify for discontinued operations treatment if they represent a major line of business or geographical area. 
                                    You will need to provide a description below.
                                </div>
                            </div>
                            <div class="col-12" id="disposal-group-description-field" style="display: none;">
                                <label class="form-label">Disposal Group Description <span class="text-danger">*</span></label>
                                <textarea name="disposal_group_description" id="disposal_group_description" class="form-control" rows="3">{{ old('disposal_group_description') }}</textarea>
                                @error('disposal_group_description')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i><strong>Required for disposal groups:</strong> 
                                    Describe the disposal group and explain why it qualifies as a disposal group. 
                                    A disposal group is a group of assets (and possibly liabilities) to be disposed of together as a single transaction. 
                                    Disposal groups may qualify for discontinued operations treatment if they represent a major line of business or geographical area.
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Justification <span class="text-danger">*</span></label>
                                <textarea name="justification" id="justification" class="form-control" rows="4" required>{{ old('justification') }}</textarea>
                                @error('justification')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i><strong>Required:</strong> 
                                    Provide a detailed explanation of why these assets are being classified as Held for Sale. 
                                    Include business reasons, strategic rationale, or circumstances leading to the decision to sell. 
                                    This justification is important for audit trail and IFRS 5 compliance.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-outline-secondary" onclick="prevStep(1)">
                                <i class="bx bx-left-arrow-alt me-1"></i>Previous
                            </button>
                            <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                                Next: Documentation <i class="bx bx-right-arrow-alt ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Documentation -->
                    <div class="wizard-step" id="step-3" style="display: none;">
                        <h6 class="text-primary border-bottom pb-2 mb-3">Step 3: Documentation</h6>

                        <div class="alert alert-warning mb-3">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Management Commitment Required for Approval:</strong> You must check the commitment box, set the commitment date, and attach management minutes or approval document to submit for approval.
                        </div>

                        <div class="row g-3">
                            <!-- Management Commitment Section -->
                            <div class="col-12">
                                <div class="card border-primary mb-3">
                                    <div class="card-header bg-primary bg-opacity-10">
                                        <h6 class="mb-0"><i class="bx bx-check-circle me-2"></i>Management Commitment <span class="text-danger">*</span></h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="management_committed" id="management_committed" value="1" {{ old('management_committed') ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold" for="management_committed">
                                                Management is committed to the sale
                                            </label>
                                        </div>
                                        <div class="alert alert-info mb-3">
                                            <i class="bx bx-info-circle me-2"></i>
                                            <strong>Required for IFRS 5:</strong> Check this box to confirm that management has formally committed to the sale. 
                                            You must also set the commitment date below and attach evidence (management minutes, board resolution, etc.) in the attachments field.
                                        </div>
                                        
                                        <div id="management-commitment-date-field" style="display: {{ old('management_committed') ? 'block' : 'none' }};">
                                            <label class="form-label">Management Commitment Date <span class="text-danger">*</span></label>
                                            <input type="date" name="management_commitment_date" id="management_commitment_date" class="form-control" 
                                                value="{{ old('management_commitment_date', date('Y-m-d')) }}">
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
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="4">{{ old('notes') }}</textarea>
                                @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i>Additional notes or comments about this HFS request. 
                                    This can include any relevant information that doesn't fit in other fields, such as special conditions, 
                                    negotiations status, or other important details for reviewers.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-outline-secondary" onclick="prevStep(2)">
                                <i class="bx bx-left-arrow-alt me-1"></i>Previous
                            </button>
                            <button type="button" class="btn btn-primary" onclick="nextStep(4)">
                                Next: Review <i class="bx bx-right-arrow-alt ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Review & Submit -->
                    <div class="wizard-step" id="step-4" style="display: none;">
                        <h6 class="text-primary border-bottom pb-2 mb-3">Step 4: Review & Submit</h6>

                        <div id="review-summary">
                            <!-- Summary will be populated by JavaScript -->
                        </div>

                        <div class="alert alert-info mt-3" id="validation-results" style="display: none;">
                            <h6><i class="bx bx-info-circle me-2"></i>Validation Results</h6>
                            <div id="validation-errors"></div>
                            <div id="validation-warnings"></div>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-outline-secondary" onclick="prevStep(3)">
                                <i class="bx bx-left-arrow-alt me-1"></i>Previous
                            </button>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary" onclick="validateRequest()">
                                    <i class="bx bx-check me-1"></i>Validate
                                </button>
                                <button type="submit" class="btn btn-success" id="submit-btn">
                                    <i class="bx bx-save me-1"></i>Save as Draft
                                </button>
                            </div>
                        </div>
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

.wizard-steps {
    position: relative;
    padding: 20px 0;
}

.step-item {
    text-align: center;
    position: relative;
    padding-bottom: 20px;
}

.step-item::after {
    content: '';
    position: absolute;
    top: 20px;
    left: 50%;
    width: 100%;
    height: 2px;
    background: #dee2e6;
    z-index: 0;
}

.step-item:last-child::after {
    display: none;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #dee2e6;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin: 0 auto 10px;
    position: relative;
    z-index: 1;
}

.step-item.active .step-number {
    background: #0d6efd;
    color: white;
}

.step-item.completed .step-number {
    background: #198754;
    color: white;
}

.step-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.step-item.active .step-label {
    color: #0d6efd;
    font-weight: 600;
}

.wizard-step {
    min-height: 400px;
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
let currentStep = 1;
const totalSteps = 4;

$(document).ready(function() {
    // Initialize Select2 for asset selection
    $('#asset_ids').select2({
        placeholder: 'Select assets...',
        width: '100%'
    });

    // Initialize Select2 for customer selection
    $('#customer_id').select2({
        placeholder: 'Select customer...',
        width: '100%',
        theme: 'bootstrap-5'
    });

    // Update assets summary when selection changes
    $('#asset_ids').on('change', function() {
        updateAssetsSummary();
    });

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

    // Load customer details if customer_id is pre-selected
    @if(old('customer_id'))
        $('#customer_id').trigger('change');
    @endif

    // Show/hide extension justification field
    $('#exceeds_12_months').on('change', function() {
        if ($(this).is(':checked')) {
            $('#extension-justification-field').show();
            $('#extension_justification').prop('required', true);
        } else {
            $('#extension-justification-field').hide();
            $('#extension_justification').prop('required', false);
        }
    });

    // Show/hide disposal group description
    $('#is_disposal_group').on('change', function() {
        if ($(this).is(':checked')) {
            $('#disposal-group-description-field').show();
            $('#disposal_group_description').prop('required', true);
        } else {
            $('#disposal-group-description-field').hide();
            $('#disposal_group_description').prop('required', false);
        }
    });

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

    // Calculate FV less costs
    $('#expected_fair_value, #expected_costs_to_sell').on('input', function() {
        calculateFvLessCosts();
    });
});

function nextStep(step) {
    // Validate current step
    if (currentStep === 1) {
        if ($('#asset_ids').val().length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please select at least one asset.'
            });
            return;
        }
    }

    if (currentStep === 2) {
        if (!$('#intended_sale_date').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Intended sale date is required.'
            });
            return;
        }
    }

    // Hide current step
    $(`#step-${currentStep}`).hide();
    
    // Show next step
    $(`#step-${step}`).show();
    
    // Update step indicators
    $(`.step-item[data-step="${currentStep}"]`).removeClass('active').addClass('completed');
    $(`.step-item[data-step="${step}"]`).addClass('active');
    
    currentStep = step;

    // If moving to review step, populate summary
    if (step === 4) {
        populateReviewSummary();
    }
}

function prevStep(step) {
    $(`#step-${currentStep}`).hide();
    $(`#step-${step}`).show();
    
    $(`.step-item[data-step="${currentStep}"]`).removeClass('active');
    $(`.step-item[data-step="${step}"]`).removeClass('completed').addClass('active');
    
    currentStep = step;
}

function updateAssetsSummary() {
    const selectedIds = $('#asset_ids').val() || [];
    if (selectedIds.length === 0) {
        $('#selected-assets-summary').hide();
        return;
    }

    let totalCost = 0;
    let totalNbv = 0;
    let html = '';

    $('#asset_ids option:selected').each(function() {
        const assetId = $(this).val();
        const code = $(this).text().split(' - ')[0];
        const name = $(this).text().split(' - ')[1];
        const category = $(this).data('category');
        const cost = parseFloat($(this).data('cost')) || 0;
        const nbv = parseFloat($(this).data('nbv')) || 0;

        totalCost += cost;
        totalNbv += nbv;

        html += `
            <tr>
                <td>${code}</td>
                <td>${name}</td>
                <td>${category}</td>
                <td class="text-end">${formatNumber(cost)}</td>
                <td class="text-end">${formatNumber(nbv)}</td>
            </tr>
        `;
    });

    $('#assets-summary-body').html(html);
    $('#total-cost').text(formatNumber(totalCost));
    $('#total-nbv').text(formatNumber(totalNbv));
    $('#selected-assets-summary').show();
}

function calculateFvLessCosts() {
    const fairValue = parseFloat($('#expected_fair_value').val()) || 0;
    const costsToSell = parseFloat($('#expected_costs_to_sell').val()) || 0;
    const fvLessCosts = fairValue - costsToSell;
    
    // Display in review step if available
    if (currentStep === 4) {
        $('#fv-less-costs-display').text(formatNumber(fvLessCosts));
    }
}

function populateReviewSummary() {
    const selectedAssets = $('#asset_ids option:selected').map(function() {
        return $(this).text();
    }).get();

    const summary = `
        <div class="card border-primary mb-3">
            <div class="card-header bg-primary bg-opacity-10">
                <h6 class="mb-0">Request Summary</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Selected Assets:</strong>
                        <ul class="mb-0">
                            ${selectedAssets.map(a => `<li>${a}</li>`).join('')}
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Intended Sale Date:</strong> ${$('#intended_sale_date').val()}<br>
                        <strong>Buyer:</strong> ${$('#customer_id option:selected').text() || $('#buyer_name').val() || 'Not specified'}<br>
                        <strong>Expected Fair Value:</strong> ${formatNumber($('#expected_fair_value').val() || 0)}<br>
                        <strong>Expected Costs to Sell:</strong> ${formatNumber($('#expected_costs_to_sell').val() || 0)}<br>
                        <strong>FV Less Costs:</strong> <span id="fv-less-costs-display">${formatNumber((parseFloat($('#expected_fair_value').val()) || 0) - (parseFloat($('#expected_costs_to_sell').val()) || 0))}</span>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#review-summary').html(summary);
    calculateFvLessCosts();
}

function validateRequest() {
    // This would call the validation endpoint
    // For now, just show a message
    Swal.fire({
        icon: 'info',
        title: 'Validation',
        text: 'Validation will be performed on submit. Please ensure all required fields are filled.',
        showConfirmButton: true
    });
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
</script>
@endpush

