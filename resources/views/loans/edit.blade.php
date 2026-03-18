@extends('layouts.main')

@section('title', 'Edit Loan')

@section('content')
<div class="page-wrapper">
	<div class="page-content">
		<x-breadcrumbs-with-icons :links="[
			['label' => 'Loan Management', 'url' => route('loans.index'), 'icon' => 'bx bx-money'],
			['label' => $loan->loan_number, 'url' => route('loans.show', $loan->encoded_id), 'icon' => 'bx bx-show'],
			['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
		]" />

		<div class="card shadow-sm">
			<div class="card-header bg-warning text-dark">
				<h5 class="card-title mb-0"><i class="bx bx-edit me-2"></i>Edit Loan Facility: {{ $loan->loan_number }}</h5>
			</div>
			<div class="card-body">
				<form method="POST" action="{{ route('loans.update', $loan->encoded_id) }}" id="loanForm">
					@csrf
					@method('PUT')

					<!-- Lender Information Section -->
					<div class="card border-primary mb-4">
						<div class="card-header bg-light">
							<h6 class="mb-0 text-primary"><i class="bx bx-building me-2"></i>Lender Information</h6>
						</div>
						<div class="card-body">
							<div class="row g-3">
								<div class="col-md-4">
									<label class="form-label">
										Lender ID
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Unique identifier for the lender (optional)"></i>
									</label>
									<input type="number" class="form-control" name="lender_id" value="{{ old('lender_id', $loan->lender_id) }}" placeholder="e.g., 1001">
									<small class="text-muted">Optional unique identifier</small>
									@error('lender_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Lender Name
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Name of the financial institution or lender"></i>
									</label>
									<input type="text" class="form-control" name="lender_name" value="{{ old('lender_name', $loan->lender_name) }}" placeholder="e.g., ABC Bank Ltd">
									<small class="text-muted">Name of the lending institution</small>
									@error('lender_name') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Bank Account
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Bank account where loan funds will be received"></i>
									</label>
									<select name="bank_account_id" class="form-select select2-single" data-placeholder="Select bank account">
										<option value="">Select bank account</option>
										@foreach($bankAccounts as $acc)
											<option value="{{ $acc->id }}" {{ old('bank_account_id', $loan->bank_account_id) == $acc->id ? 'selected' : '' }}>
												{{ $acc->name }} @if($acc->chartAccount) ({{ $acc->chartAccount->account_code }}) @endif
											</option>
										@endforeach
									</select>
									<small class="text-muted">Account where loan proceeds will be deposited</small>
									@error('bank_account_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-6">
									<label class="form-label">
										Bank Name
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Name of the bank or financial institution"></i>
									</label>
									<input type="text" class="form-control" name="bank_name" value="{{ old('bank_name', $loan->bank_name) }}" placeholder="e.g., ABC Bank Ltd">
									<small class="text-muted">Name of the lending bank</small>
									@error('bank_name') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-6">
									<label class="form-label">
										Bank Contact
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Contact person or department at the bank"></i>
									</label>
									<input type="text" class="form-control" name="bank_contact" value="{{ old('bank_contact', $loan->bank_contact) }}" placeholder="e.g., John Doe, +255 123 456 789">
									<small class="text-muted">Contact person or phone number</small>
									@error('bank_contact') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
							</div>
						</div>
					</div>

					<!-- Facility Information Section -->
					<div class="card border-info mb-4">
						<div class="card-header bg-light">
							<h6 class="mb-0 text-info"><i class="bx bx-file me-2"></i>Facility Information</h6>
						</div>
						<div class="card-body">
							<div class="row g-3">
								<div class="col-md-6">
									<label class="form-label">
										Facility Name
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Name or description of the loan facility"></i>
									</label>
									<input type="text" class="form-control" name="facility_name" value="{{ old('facility_name', $loan->facility_name) }}" placeholder="e.g., Working Capital Loan 2025">
									<small class="text-muted">Descriptive name for this loan facility</small>
									@error('facility_name') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-6">
									<label class="form-label">
										Facility Type
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Type of loan facility being obtained"></i>
									</label>
									<select name="facility_type" class="form-select">
										<option value="">Select facility type</option>
										@foreach(['term_loan' => 'Term Loan', 'revolving' => 'Revolving', 'overdraft' => 'Overdraft', 'line_of_credit' => 'Line of Credit', 'other' => 'Other'] as $key => $label)
											<option value="{{ $key }}" {{ old('facility_type', $loan->facility_type) == $key ? 'selected' : '' }}>{{ $label }}</option>
										@endforeach
									</select>
									<small class="text-muted">Type of credit facility (Term Loan, Revolving, etc.)</small>
									@error('facility_type') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
							</div>
						</div>
					</div>

					<!-- Loan Amount & Dates Section -->
					<div class="card border-success mb-4">
						<div class="card-header bg-light">
							<h6 class="mb-0 text-success"><i class="bx bx-money me-2"></i>Loan Amount & Dates</h6>
						</div>
						<div class="card-body">
							<div class="row g-3">
								<div class="col-md-4">
									<label class="form-label">
										Principal Amount <span class="text-danger">*</span>
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Total loan amount borrowed from the lender"></i>
									</label>
									<input type="number" step="0.01" class="form-control" name="principal_amount" value="{{ old('principal_amount', $loan->principal_amount) }}" required placeholder="0.00">
									<small class="text-muted">Total loan amount in TZS</small>
									@error('principal_amount') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Disbursed Amount
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Actual amount received (may differ from principal if fees deducted)"></i>
									</label>
									<input type="number" step="0.01" class="form-control" name="disbursed_amount" value="{{ old('disbursed_amount', $loan->disbursed_amount) }}" placeholder="Same as principal if not specified">
									<small class="text-muted">Net amount received after deductions</small>
									@error('disbursed_amount') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Disbursement Date
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Date when loan funds are received"></i>
									</label>
									<input type="date" class="form-control" name="disbursement_date" value="{{ old('disbursement_date', $loan->disbursement_date ? $loan->disbursement_date->format('Y-m-d') : '') }}">
									<small class="text-muted">Date funds are received</small>
									@error('disbursement_date') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Start Date
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Loan start date (usually same as disbursement date)"></i>
									</label>
									<input type="date" class="form-control" name="start_date" value="{{ old('start_date', $loan->start_date ? $loan->start_date->format('Y-m-d') : '') }}">
									<small class="text-muted">Loan commencement date</small>
									@error('start_date') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Maturity Date
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Date when loan must be fully repaid"></i>
									</label>
									<input type="date" class="form-control" name="maturity_date" value="{{ old('maturity_date', $loan->maturity_date ? $loan->maturity_date->format('Y-m-d') : '') }}">
									<small class="text-muted">Final repayment due date</small>
									@error('maturity_date') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										First Payment Date
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Date of the first installment payment"></i>
									</label>
									<input type="date" class="form-control" name="first_payment_date" value="{{ old('first_payment_date', $loan->first_payment_date ? $loan->first_payment_date->format('Y-m-d') : '') }}">
									<small class="text-muted">First installment due date</small>
									@error('first_payment_date') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
							</div>
						</div>
					</div>

					<!-- Interest Rate & Calculation Section -->
					<div class="card border-warning mb-4">
						<div class="card-header bg-light">
							<h6 class="mb-0 text-warning"><i class="bx bx-percent me-2"></i>Interest Rate & Calculation</h6>
						</div>
						<div class="card-body">
							<div class="row g-3">
								<div class="col-md-4">
									<label class="form-label">
										Interest Rate (%) <span class="text-danger">*</span>
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Annual interest rate percentage"></i>
									</label>
									<input type="number" step="0.01" class="form-control" name="interest_rate" value="{{ old('interest_rate', $loan->interest_rate) }}" required placeholder="e.g., 10.5">
									<small class="text-muted">Annual interest rate (e.g., 10.5 for 10.5%)</small>
									@error('interest_rate') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Rate Type <span class="text-danger">*</span>
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Fixed rate remains constant; Variable rate changes with market"></i>
									</label>
									<select name="rate_type" class="form-select" id="rate_type" required>
										@foreach(['fixed' => 'Fixed', 'variable' => 'Variable'] as $key => $label)
											<option value="{{ $key }}" {{ old('rate_type', $loan->rate_type) == $key ? 'selected' : '' }}>{{ $label }}</option>
										@endforeach
									</select>
									<small class="text-muted">Fixed: constant rate | Variable: changes with market</small>
									@error('rate_type') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4" id="base_rate_source_field" style="display: {{ old('rate_type', $loan->rate_type) == 'variable' ? 'block' : 'none' }};">
									<label class="form-label">
										Base Rate Source
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Reference rate for variable loans (e.g., Treasury Bill Rate)"></i>
									</label>
									<input type="text" class="form-control" name="base_rate_source" value="{{ old('base_rate_source', $loan->base_rate_source) }}" placeholder="e.g., Treasury Bill Rate">
									<small class="text-muted">Base rate for variable interest calculation</small>
									@error('base_rate_source') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4" id="spread_field" style="display: {{ old('rate_type', $loan->rate_type) == 'variable' ? 'block' : 'none' }};">
									<label class="form-label">
										Spread (%)
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Additional percentage added to base rate"></i>
									</label>
									<input type="number" step="0.01" class="form-control" name="spread" value="{{ old('spread', $loan->spread) }}" placeholder="e.g., 2.5">
									<small class="text-muted">Spread over base rate (e.g., Base + 2.5%)</small>
									@error('spread') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Calculation Basis <span class="text-danger">*</span>
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Method for calculating interest days"></i>
									</label>
									<select name="calculation_basis" class="form-select" required>
										@foreach(['30/360' => '30/360', 'actual/365' => 'Actual/365', 'actual/360' => 'Actual/360'] as $key => $label)
											<option value="{{ $key }}" {{ old('calculation_basis', $loan->calculation_basis) == $key ? 'selected' : '' }}>{{ $label }}</option>
										@endforeach
									</select>
									<small class="text-muted">Interest calculation method (30/360, Actual/365, Actual/360)</small>
									@error('calculation_basis') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
							</div>
						</div>
					</div>

					<!-- Repayment Terms Section -->
					<div class="card border-secondary mb-4">
						<div class="card-header bg-light">
							<h6 class="mb-0 text-secondary"><i class="bx bx-calendar-check me-2"></i>Repayment Terms</h6>
						</div>
						<div class="card-body">
							<div class="row g-3">
								<div class="col-md-4">
									<label class="form-label">
										Term (Months) <span class="text-danger">*</span>
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Total loan duration in months"></i>
									</label>
									<input type="number" class="form-control" name="term_months" value="{{ old('term_months', $loan->term_months) }}" required placeholder="e.g., 12">
									<small class="text-muted">Total loan period in months</small>
									@error('term_months') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Payment Frequency <span class="text-danger">*</span>
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="How often payments are made"></i>
									</label>
									<select name="payment_frequency" class="form-select" required>
										@foreach(['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'semi-annual' => 'Semi-Annual', 'annual' => 'Annual'] as $key => $label)
											<option value="{{ $key }}" {{ old('payment_frequency', $loan->payment_frequency) == $key ? 'selected' : '' }}>{{ $label }}</option>
										@endforeach
									</select>
									<small class="text-muted">Frequency of installment payments</small>
									@error('payment_frequency') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Amortization Method <span class="text-danger">*</span>
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Method for calculating principal and interest payments"></i>
									</label>
									<select name="amortization_method" class="form-select" required id="amortization_method">
										@foreach([
											'annuity' => 'Annuity (Equal Installment)',
											'straight_principal' => 'Straight Principal',
											'interest_only' => 'Interest Only',
											'flat_rate' => 'Flat Rate',
										] as $key => $label)
											<option value="{{ $key }}" {{ old('amortization_method', $loan->amortization_method) == $key ? 'selected' : '' }}>{{ $label }}</option>
										@endforeach
									</select>
									<small class="text-muted" id="amortization_help_text">
										Annuity: equal payments | Straight: equal principal | Interest Only: interest only | Flat Rate: equal principal + equal interest on original principal
									</small>
									@error('amortization_method') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Repayment Method
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Alternative repayment method (if different from amortization)"></i>
									</label>
									<select name="repayment_method" class="form-select" id="repayment_method">
										<option value="">Same as Amortization Method</option>
										@foreach([
											'annuity' => 'Annuity',
											'equal_principal' => 'Equal Principal',
											'interest_only' => 'Interest Only',
											'bullet' => 'Bullet',
											'flat_rate' => 'Flat Rate',
										] as $key => $label)
											<option value="{{ $key }}" {{ old('repayment_method', $loan->repayment_method) == $key ? 'selected' : '' }}>{{ $label }}</option>
										@endforeach
									</select>
									<small class="text-muted" id="repayment_help_text">
										Leave blank to use amortization method. Flat Rate: equal principal + equal interest on original principal.
									</small>
									@error('repayment_method') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Grace Period (Months)
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Period where only interest is paid, no principal"></i>
									</label>
									<input type="number" class="form-control" name="grace_period_months" value="{{ old('grace_period_months', $loan->grace_period_months ?? 0) }}" placeholder="0">
									<small class="text-muted">Months with interest-only payments (0 = no grace period)</small>
									@error('grace_period_months') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
							</div>
						</div>
					</div>

					<!-- Fees, Borrowing Costs & Prepayment Section -->
					<div class="card border-danger mb-4">
						<div class="card-header bg-light">
							<h6 class="mb-0 text-danger"><i class="bx bx-dollar me-2"></i>Fees, Borrowing Costs & Prepayment Settings</h6>
						</div>
						<div class="card-body">
							<div class="row g-3">
								<div class="col-md-4">
									<label class="form-label">
										Fees Amount
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Total loan origination or processing fees"></i>
									</label>
									<input type="number" step="0.01" class="form-control" name="fees_amount" value="{{ old('fees_amount', $loan->fees_amount ?? 0) }}" placeholder="0.00">
									<small class="text-muted">Total fees (arrangement, processing, legal, etc.)</small>
									@error('fees_amount') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Capitalise Fees
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="If Yes, fees are added to loan asset; If No, expensed immediately"></i>
									</label>
									<select name="capitalise_fees" class="form-select">
										<option value="0" {{ old('capitalise_fees', $loan->capitalise_fees ? 1 : 0) == 0 ? 'selected' : '' }}>No (Expense Immediately)</option>
										<option value="1" {{ old('capitalise_fees', $loan->capitalise_fees ? 1 : 0) == 1 ? 'selected' : '' }}>Yes (Capitalize)</option>
									</select>
									<small class="text-muted">Capitalize: add to asset | Expense: charge immediately</small>
									@error('capitalise_fees') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Capitalise Interest (IAS 23)
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="If Yes, interest accruals will be capitalised to a qualifying asset instead of expensed."></i>
									</label>
									<select name="capitalise_interest" class="form-select">
										<option value="0" {{ old('capitalise_interest', $loan->capitalise_interest ? 1 : 0) == 0 ? 'selected' : '' }}>No (Expense Interest)</option>
										<option value="1" {{ old('capitalise_interest', $loan->capitalise_interest ? 1 : 0) == 1 ? 'selected' : '' }}>Yes (Capitalise Interest)</option>
									</select>
									<small class="text-muted">Use for borrowing costs on qualifying assets under IAS 23</small>
									@error('capitalise_interest') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Prepayment Allowed
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Whether early loan repayment is permitted"></i>
									</label>
									<select name="prepayment_allowed" class="form-select">
										<option value="1" {{ old('prepayment_allowed', $loan->prepayment_allowed ? 1 : 0) == 1 ? 'selected' : '' }}>Yes</option>
										<option value="0" {{ old('prepayment_allowed', $loan->prepayment_allowed ? 1 : 0) == 0 ? 'selected' : '' }}>No</option>
									</select>
									<small class="text-muted">Allow early repayment before maturity</small>
									@error('prepayment_allowed') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-4">
									<label class="form-label">
										Prepayment Penalty Rate (%)
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Penalty percentage charged on prepayment amount"></i>
									</label>
									<input type="number" step="0.01" class="form-control" name="prepayment_penalty_rate" value="{{ old('prepayment_penalty_rate', $loan->prepayment_penalty_rate) }}" placeholder="e.g., 2.0">
									<small class="text-muted">Penalty rate if prepayment is made (e.g., 2% of prepaid amount)</small>
									@error('prepayment_penalty_rate') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
							</div>
						</div>
					</div>

					<!-- Chart of Accounts Section -->
					<div class="card border-dark mb-4">
						<div class="card-header bg-light">
							<h6 class="mb-0 text-dark"><i class="bx bx-book me-2"></i>Chart of Accounts Mapping</h6>
							<small class="text-muted">Select GL accounts for automatic journal entries</small>
						</div>
						<div class="card-body">
							<div class="row g-3">
								<div class="col-md-6">
									<label class="form-label">
										Loan Payable Account
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Liability account for the loan principal"></i>
									</label>
									<select name="loan_payable_account_id" class="form-select select2-single" data-placeholder="Select payable account">
										<option value="">Select account</option>
										@foreach($loanLiabilityAccounts as $acc)
											<option value="{{ $acc->id }}" {{ old('loan_payable_account_id', $loan->loan_payable_account_id) == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
										@endforeach
									</select>
									<small class="text-muted">Liability account for loan principal balance</small>
									@error('loan_payable_account_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-6">
									<label class="form-label">
										Interest Expense Account
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Expense account for interest charges"></i>
									</label>
									<select name="interest_expense_account_id" class="form-select select2-single" data-placeholder="Select interest expense account">
										<option value="">Select account</option>
										@foreach($interestExpenseAccounts as $acc)
											<option value="{{ $acc->id }}" {{ old('interest_expense_account_id', $loan->interest_expense_account_id) == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
										@endforeach
									</select>
									<small class="text-muted">Expense account for interest charges</small>
									@error('interest_expense_account_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-6">
									<label class="form-label">
										Interest Payable Account
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Liability account for accrued interest not yet paid"></i>
									</label>
									<select name="interest_payable_account_id" class="form-select select2-single" data-placeholder="Select interest payable account">
										<option value="">Select account</option>
										@foreach($interestPayableAccounts as $acc)
											<option value="{{ $acc->id }}" {{ old('interest_payable_account_id', $loan->interest_payable_account_id) == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
										@endforeach
									</select>
									<small class="text-muted">Liability account for accrued interest</small>
									@error('interest_payable_account_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-6">
									<label class="form-label">
										Deferred Loan Costs Account
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Asset account for capitalized loan fees"></i>
									</label>
									<select name="deferred_loan_costs_account_id" class="form-select select2-single" data-placeholder="Select deferred costs account">
										<option value="">Select account</option>
										@foreach($deferredCostAccounts as $acc)
											<option value="{{ $acc->id }}" {{ old('deferred_loan_costs_account_id', $loan->deferred_loan_costs_account_id) == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
										@endforeach
									</select>
									<small class="text-muted">Asset account for capitalized fees (if applicable)</small>
									@error('deferred_loan_costs_account_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-6">
									<label class="form-label">
										Capitalised Interest Asset Account
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Asset / CIP account where capitalised borrowing costs (IAS 23) will be posted."></i>
									</label>
									<select name="capitalised_interest_account_id" class="form-select select2-single" data-placeholder="Select capitalised interest asset account">
										<option value="">Select account</option>
										@foreach($deferredCostAccounts as $acc)
											<option value="{{ $acc->id }}" {{ old('capitalised_interest_account_id', $loan->capitalised_interest_account_id ?? null) == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
										@endforeach
									</select>
									<small class="text-muted">Typically a Construction in Progress / CIP or similar asset account</small>
									@error('capitalised_interest_account_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-6">
									<label class="form-label">
										Bank Charges Account
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Expense account for bank charges and fees"></i>
									</label>
									<select name="bank_charges_account_id" class="form-select select2-single" data-placeholder="Select bank charges account">
										<option value="">Select account</option>
										@foreach($bankChargeAccounts as $acc)
											<option value="{{ $acc->id }}" {{ old('bank_charges_account_id', $loan->bank_charges_account_id) == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
										@endforeach
									</select>
									<small class="text-muted">Expense account for bank charges and penalties</small>
									@error('bank_charges_account_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
								<div class="col-md-6">
									<label class="form-label">
										Loan Processing Fee Account
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Expense account for loan processing / arrangement fees"></i>
									</label>
									<select name="loan_processing_fee_account_id" class="form-select select2-single" data-placeholder="Select loan processing fee account">
										<option value="">Select account</option>
										@foreach($loanProcessingFeeAccounts as $acc)
											<option value="{{ $acc->id }}" {{ old('loan_processing_fee_account_id', $loan->loan_processing_fee_account_id ?? null) == $acc->id ? 'selected' : '' }}>
												{{ $acc->account_code }} - {{ $acc->account_name }}
											</option>
										@endforeach
									</select>
									<small class="text-muted">Expense account for loan processing / arrangement fees</small>
									@error('loan_processing_fee_account_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
							</div>
						</div>
					</div>

					<!-- Additional Notes Section -->
					<div class="card border-secondary mb-4">
						<div class="card-header bg-light">
							<h6 class="mb-0 text-secondary"><i class="bx bx-note me-2"></i>Additional Information</h6>
						</div>
						<div class="card-body">
							<div class="row g-3">
								<div class="col-md-12">
									<label class="form-label">
										Notes
										<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Additional notes or comments about the loan"></i>
									</label>
									<textarea name="notes" class="form-control" rows="4" placeholder="Enter any additional notes, terms, or special conditions...">{{ old('notes', $loan->notes) }}</textarea>
									<small class="text-muted">Additional notes, terms, or special conditions</small>
									@error('notes') <small class="text-danger d-block">{{ $message }}</small> @enderror
								</div>
							</div>
						</div>
					</div>

					<!-- Form Actions -->
					<div class="d-flex justify-content-between align-items-center">
						<a href="{{ route('loans.show', $loan->encoded_id) }}" class="btn btn-secondary">
							<i class="bx bx-x me-1"></i>Cancel
						</a>
						<button type="submit" class="btn btn-warning btn-lg">
							<i class="bx bx-save me-1"></i>Update Loan
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
	$(function(){
		// Initialize Select2
		if ($.fn.select2) {
			$('.select2-single').select2({
				theme: 'bootstrap-5',
				width: '100%'
			});
		}

		// Initialize Bootstrap tooltips
		var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
		var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
			return new bootstrap.Tooltip(tooltipTriggerEl);
		});

		// Show/hide base rate source and spread fields based on rate type
		$('#rate_type').on('change', function() {
			if ($(this).val() === 'variable') {
				$('#base_rate_source_field').slideDown();
				$('#spread_field').slideDown();
			} else {
				$('#base_rate_source_field').slideUp();
				$('#spread_field').slideUp();
			}
		});

		// Trigger on page load
		$('#rate_type').trigger('change');

		// Auto-calculate maturity date if term_months is entered
		$('input[name="term_months"]').on('change', function() {
			const startDate = $('input[name="start_date"]').val() || $('input[name="disbursement_date"]').val();
			if (startDate && $(this).val()) {
				const start = new Date(startDate);
				const months = parseInt($(this).val());
				start.setMonth(start.getMonth() + months);
				$('input[name="maturity_date"]').val(start.toISOString().split('T')[0]);
			}
		});

		// Auto-calculate maturity date when start date changes
		$('input[name="start_date"], input[name="disbursement_date"]').on('change', function() {
			const termMonths = $('input[name="term_months"]').val();
			if (termMonths) {
				const startDate = $('input[name="start_date"]').val() || $('input[name="disbursement_date"]').val();
				if (startDate) {
					const start = new Date(startDate);
					const months = parseInt(termMonths);
					start.setMonth(start.getMonth() + months);
					$('input[name="maturity_date"]').val(start.toISOString().split('T')[0]);
				}
			}
		});

		// Auto-calculate term_months from start_date and maturity_date
		function recalculateTermMonths() {
			const startVal = $('input[name="start_date"]').val();
			const maturityVal = $('input[name="maturity_date"]').val();
			if (!startVal || !maturityVal) {
				return;
			}
			const start = new Date(startVal);
			const end = new Date(maturityVal);
			if (isNaN(start.getTime()) || isNaN(end.getTime()) || end <= start) {
				return;
			}
			let months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
			if (end.getDate() > start.getDate()) {
				months += 1;
			}
			if (months > 0) {
				$('input[name="term_months"]').val(months);
			}
		}

		$('input[name="start_date"], input[name="maturity_date"]').on('change', recalculateTermMonths);

		// Auto-fill Bank Name from Lender Name on edit (only when bank_name is empty)
		const lenderInput = $('input[name="lender_name"]');
		const bankNameInput = $('input[name="bank_name"]');

		lenderInput.on('blur change', function() {
			const lenderName = $(this).val();
			if (!bankNameInput.val()) {
				bankNameInput.val(lenderName);
			}
		});

		// Dynamic helper text for Amortization Method
		const amortizationSelect = $('#amortization_method');
		const amortizationHelp = $('#amortization_help_text');

		const amortizationDescriptions = {
			'annuity': 'Annuity: equal total installment (principal + interest) each period (reducing balance).',
			'straight_principal': 'Straight Principal: equal principal each period, interest on reducing balance.',
			'interest_only': 'Interest Only: pay only interest during the term; principal repaid at maturity.',
			'flat_rate': 'Flat Rate: equal principal each period + equal interest on original principal (non-reducing).'
		};

		function updateAmortizationHelp() {
			const method = amortizationSelect.val();
			if (method && amortizationDescriptions[method]) {
				amortizationHelp.text(amortizationDescriptions[method]);
			}
		}

		amortizationSelect.on('change', updateAmortizationHelp);
		updateAmortizationHelp();

		// Dynamic helper text for Repayment Method
		const repaymentSelect = $('#repayment_method');
		const repaymentHelp = $('#repayment_help_text');

		const repaymentDescriptions = {
			'': 'Same as Amortization Method (default).',
			'annuity': 'Annuity (Repayment): equal total installment each period, based on reducing balance.',
			'equal_principal': 'Equal Principal (Repayment): equal principal each period, interest on reducing balance.',
			'interest_only': 'Interest Only (Repayment): pay only interest during term; principal at maturity.',
			'bullet': 'Bullet: interest during term, full principal and any remaining interest at maturity.',
			'flat_rate': 'Flat Rate (Repayment): equal principal each period + equal interest on original principal.'
		};

		function updateRepaymentHelp() {
			const method = repaymentSelect.val() || '';
			if (repaymentDescriptions.hasOwnProperty(method)) {
				repaymentHelp.text(repaymentDescriptions[method]);
			}
		}

		repaymentSelect.on('change', updateRepaymentHelp);
		updateRepaymentHelp();
	});
</script>
@endpush

