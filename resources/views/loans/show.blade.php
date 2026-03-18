@extends('layouts.main')

@section('title', 'Loan Details')

@section('content')
<div class="page-wrapper">
	<div class="page-content">
		<x-breadcrumbs-with-icons :links="[
			['label' => 'Loan Management', 'url' => route('loans.index'), 'icon' => 'bx bx-money'],
			['label' => $loan->loan_number, 'url' => '#', 'icon' => 'bx bx-show']
		]" />

		<!-- Loan Header Card with Premium Design -->
		<div class="card shadow-lg border-0 mb-4 overflow-hidden position-relative" style="background: #667eea; border-radius: 20px; min-height: 200px;">
			<!-- Decorative Pattern -->
			<div class="position-absolute top-0 end-0" style="width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); transform: translate(30%, -30%); pointer-events: none;"></div>
			<div class="position-absolute bottom-0 start-0" style="width: 200px; height: 200px; background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%); transform: translate(-30%, 30%); pointer-events: none;"></div>
			
			<div class="card-body text-white p-4 position-relative">
				<div class="row align-items-center g-4">
					<!-- Left Section -->
					<div class="col-lg-8">
						<div class="d-flex align-items-start mb-4">
							<!-- Icon Circle -->
							<div class="flex-shrink-0 me-4">
								<div class="bg-white rounded-circle shadow-lg d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
									<i class="bx bx-money fs-1" style="color: #667eea;"></i>
								</div>
							</div>
							
							<!-- Loan Info -->
							<div class="flex-grow-1">
								<h1 class="mb-2 text-white fw-bold" style="font-size: 2rem; text-shadow: 0 2px 8px rgba(0,0,0,0.15); letter-spacing: -0.5px;">
									{{ $loan->loan_number }}
								</h1>
								
								<div class="mb-3">
									<p class="mb-1 text-white fw-medium" style="font-size: 1rem; opacity: 0.98;">
										<i class="bx bx-building me-2" style="opacity: 0.9;"></i>
										{{ $loan->lender_name ?? ($loan->bank_name ?? ($loan->bankAccount->name ?? 'N/A')) }}
									</p>
									<p class="mb-0 text-white" style="font-size: 0.95rem; opacity: 0.9;">
										{{ $loan->facility_name ?? 'Loan Facility' }}
										@if($loan->facility_type)
											<span class="badge bg-white text-dark border-0 ms-2 px-3 py-1 fw-semibold" style="font-size: 0.75rem;">
												{{ ucfirst(str_replace('_', ' ', $loan->facility_type)) }}
											</span>
										@endif
									</p>
								</div>
								
								<!-- Status & Info Badges -->
								<div class="d-flex flex-wrap gap-2">
									<span class="badge px-3 py-2 border-0 text-white fw-semibold shadow-sm" style="font-size: 0.85rem; background-color: {{ $loan->status == 'active' ? '#28a745' : ($loan->status == 'closed' ? '#6c757d' : ($loan->status == 'disbursed' ? '#17a2b8' : '#ffc107')) }};">
										<i class="bx bx-{{ $loan->status == 'active' ? 'check-circle' : ($loan->status == 'closed' ? 'x-circle' : ($loan->status == 'disbursed' ? 'export' : 'time')) }} me-1"></i>
										{{ ucfirst($loan->status) }}
									</span>
									<span class="badge bg-white text-dark border-0 px-3 py-2 fw-semibold shadow-sm" style="font-size: 0.85rem;">
										<i class="bx bx-calendar me-1"></i>{{ $loan->term_months }} months
									</span>
									<span class="badge bg-white text-dark border-0 px-3 py-2 fw-semibold shadow-sm" style="font-size: 0.85rem;">
										<i class="bx bx-percent me-1"></i>{{ number_format($loan->interest_rate, 2) }}% {{ ucfirst($loan->rate_type) }}
									</span>
								</div>
							</div>
						</div>
					</div>
					
					<!-- Right Section - Financial Summary -->
					<div class="col-lg-4">
						<div class="bg-white rounded-4 p-4 shadow-lg h-100" style="border: 1px solid rgba(255,255,255,0.2);">
							<div class="mb-3">
								<small class="text-muted d-block mb-2 fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
									<i class="bx bx-money me-1"></i>Outstanding Principal
								</small>
								<h2 class="mb-0 text-dark fw-bold" style="font-size: 1.75rem; line-height: 1.2;">
									{{ number_format($loan->outstanding_principal, 2) }}
									<small class="text-muted fw-normal" style="font-size: 0.6em;">TZS</small>
								</h2>
							</div>
							
							@php
								$principalPaidPercent = $loan->principal_amount > 0 ? ($loan->total_principal_paid / $loan->principal_amount) * 100 : 0;
							@endphp
							
							<div class="mb-2">
								<div class="d-flex justify-content-between align-items-center mb-1">
									<small class="text-muted fw-semibold" style="font-size: 0.75rem;">Progress</small>
									<small class="text-dark fw-bold" style="font-size: 0.75rem;">{{ number_format($principalPaidPercent, 1) }}%</small>
								</div>
								<div class="progress" style="height: 8px; background-color: #e9ecef; border-radius: 10px; overflow: hidden;">
									<div class="progress-bar bg-gradient" role="progressbar" style="width: {{ min(100, $principalPaidPercent) }}%; background: #667eea; border-radius: 10px;"></div>
								</div>
							</div>
							
							@if($loan->accrued_interest > 0)
							<div class="pt-3 border-top">
								<small class="text-muted d-block mb-1 fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
									<i class="bx bx-calendar me-1"></i>Accrued Interest
								</small>
								<h5 class="mb-0 text-dark fw-bold" style="font-size: 1.25rem;">
									{{ number_format($loan->accrued_interest, 2) }}
									<small class="text-muted fw-normal" style="font-size: 0.6em;">TZS</small>
								</h5>
							</div>
							@endif
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Action Buttons -->
		<div class="card shadow-sm mb-4">
			<div class="card-body">
				<div class="d-flex flex-wrap gap-2">
					<form method="POST" action="{{ route('loans.schedule.generate', $loan->encoded_id) }}" class="d-inline" id="generateScheduleForm">
						@csrf
						<button type="button" class="btn btn-primary" id="generateScheduleBtn">
							<i class="bx bx-receipt me-1"></i>Generate Schedule
						</button>
					</form>
					@if(in_array($loan->status, ['draft','approved']))
					<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#disburseModal">
						<i class="bx bx-export me-1"></i>Disburse
					</button>
					@endif
					@if($loan->prepayment_allowed && in_array($loan->status, ['disbursed', 'active']))
					<button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#prepaymentModal">
						<i class="bx bx-money me-1"></i>Prepayment
					</button>
					@endif
					<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
						<i class="bx bx-credit-card me-1"></i>Record Payment
					</button>
					<button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#restructureModal">
						<i class="bx bx-refresh me-1"></i>Restructure
					</button>
					@if($loan->capitalise_interest && $loan->capitalised_interest_account_id && in_array($loan->status, ['disbursed','active']))
					<button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#stopCapitalisationModal">
						<i class="bx bx-line-chart me-1"></i>Stop Capitalising Interest
					</button>
					@endif
					@php
						$hasUnpostedEntries = false;
						$disbursement = $loan->disbursements()->whereNull('journal_id')->first();
						$unpostedAccruals = $loan->accruals()->where(function($q) {
							$q->where('posted_flag', false)->orWhereNull('journal_id');
						})->count();
						$unpostedPayments = $loan->payments()->where(function($q) {
							$q->where('posted_flag', false)->orWhereNull('journal_id');
						})->count();
						$hasUnpostedEntries = $disbursement || $unpostedAccruals > 0 || $unpostedPayments > 0;
					@endphp
					@if($hasUnpostedEntries)
					<form method="POST" action="{{ route('loans.post.to.gl', $loan->encoded_id) }}" class="d-inline" id="postToGlForm">
						@csrf
						<button type="button" class="btn btn-outline-success" id="postToGlBtn">
							<i class="bx bx-book me-1"></i>Post to GL
						</button>
					</form>
					@endif
					<a href="{{ route('loans.edit', $loan->encoded_id) }}" class="btn btn-outline-primary">
						<i class="bx bx-edit me-1"></i>Edit
					</a>
					@if($loan->status === 'draft')
					<form method="POST" action="{{ route('loans.destroy', $loan->encoded_id) }}" class="d-inline" id="deleteLoanForm">
						@csrf
						@method('DELETE')
						<button type="button" class="btn btn-outline-danger" id="deleteLoanBtn">
							<i class="bx bx-trash me-1"></i>Delete
						</button>
					</form>
					@endif
				</div>
			</div>
		</div>

		<!-- Key Metrics Row -->
		<div class="row g-3 mb-4">
			<div class="col-md-3">
				<div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #667eea !important;">
					<div class="card-body">
						<div class="d-flex align-items-center">
							<div class="flex-shrink-0">
								<div class="bg-primary bg-opacity-10 rounded-circle p-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
									<i class="bx bx-money text-primary fs-4"></i>
								</div>
							</div>
							<div class="flex-grow-1 ms-3">
								<small class="text-muted d-block mb-1">Principal Amount</small>
													<h5 class="mb-0 fw-bold">{{ number_format($loan->principal_amount, 2) }}</h5>
													<small class="text-muted">TZS</small>
													@if(isset($totalInterestScheduled) && $totalInterestScheduled > 0)
														<div class="mt-1">
															<small class="text-muted">
																Total Scheduled Interest:
																<strong>{{ number_format($totalInterestScheduled, 2) }} TZS</strong>
															</small>
														</div>
													@endif
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #28a745 !important;">
					<div class="card-body">
						<div class="d-flex align-items-center">
							<div class="flex-shrink-0">
								<div class="bg-success bg-opacity-10 rounded-circle p-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
									<i class="bx bx-check-circle text-success fs-4"></i>
								</div>
							</div>
							<div class="flex-grow-1 ms-3">
								<small class="text-muted d-block mb-1">Paid Principal</small>
													<h5 class="mb-0 fw-bold text-success">{{ number_format($loan->total_principal_paid, 2) }}</h5>
													<small class="text-muted">TZS</small>
													@if($loan->total_interest_paid > 0)
														<div class="mt-1">
															<small class="text-muted">
																Interest Paid:
																<strong>{{ number_format($loan->total_interest_paid, 2) }} TZS</strong>
															</small>
														</div>
													@endif
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ffc107 !important;">
					<div class="card-body">
						<div class="d-flex align-items-center">
							<div class="flex-shrink-0">
								<div class="bg-warning bg-opacity-10 rounded-circle p-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
									<i class="bx bx-percent text-warning fs-4"></i>
								</div>
							</div>
							<div class="flex-grow-1 ms-3">
								<small class="text-muted d-block mb-1">Interest Rate</small>
								<h5 class="mb-0 fw-bold">{{ number_format($loan->interest_rate, 2) }}%</h5>
								<small class="text-muted">{{ ucfirst($loan->rate_type) }}</small>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #17a2b8 !important;">
					<div class="card-body">
						<div class="d-flex align-items-center">
							<div class="flex-shrink-0">
								<div class="bg-info bg-opacity-10 rounded-circle p-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
									<i class="bx bx-calendar text-info fs-4"></i>
								</div>
							</div>
							<div class="flex-grow-1 ms-3">
								<small class="text-muted d-block mb-1">Repayment Progress</small>
								<h5 class="mb-0 fw-bold">{{ $totalInstallments > 0 ? round(($paidInstallments / $totalInstallments) * 100, 1) : 0 }}%</h5>
								<small class="text-muted">{{ $paidInstallments }}/{{ $totalInstallments }} installments</small>
								@if($totalInstallments > 0)
								<div class="progress mt-2" style="height: 4px;">
									<div class="progress-bar bg-info" role="progressbar" style="width: {{ ($paidInstallments / $totalInstallments) * 100 }}%"></div>
								</div>
								@endif
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Main Content Tabs -->
		<div class="card shadow-sm border-0">
			<div class="card-header bg-white border-bottom-0 px-4 py-3">
				<ul class="nav nav-tabs nav-tabs-custom card-header-tabs" role="tablist">
					<li class="nav-item" role="presentation">
						<button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
							<i class="bx bx-info-circle me-1"></i>Overview
						</button>
					</li>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="cash-schedule-tab" data-bs-toggle="tab" data-bs-target="#cash-schedule" type="button" role="tab">
							<i class="bx bx-receipt me-1"></i>Contractual Schedule
							@if($loan->cashSchedules->count() > 0)
								<span class="badge bg-primary ms-1">{{ $loan->cashSchedules->count() }}</span>
							@endif
						</button>
					</li>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="ifrs-schedule-tab" data-bs-toggle="tab" data-bs-target="#ifrs-schedule" type="button" role="tab">
							<i class="bx bx-line-chart me-1"></i>IFRS 9 Schedule
							@if($loan->ifrsSchedules->count() > 0)
								<span class="badge bg-info ms-1">{{ $loan->ifrsSchedules->count() }}</span>
							@endif
						</button>
					</li>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
							<i class="bx bx-credit-card me-1"></i>Payments
							@if($loan->payments->count() > 0)
								<span class="badge bg-success ms-1">{{ $loan->payments->count() }}</span>
							@endif
						</button>
					</li>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab">
							<i class="bx bx-transfer me-1"></i>Transactions
						</button>
					</li>
				</ul>
			</div>
			<div class="card-body">
				<div class="tab-content">
					<!-- Overview Tab -->
					<div class="tab-pane fade show active" id="overview" role="tabpanel">
						<div class="row g-4">
							<!-- Loan Details -->
							<div class="col-md-6">
								<div class="card border-0 shadow-sm h-100">
									<div class="card-header bg-primary bg-opacity-10 border-0">
										<h6 class="mb-0 text-primary fw-bold">
											<i class="bx bx-info-circle me-2"></i>Loan Details
										</h6>
									</div>
									<div class="card-body">
										<div class="table-responsive">
											<table class="table table-borderless mb-0">
												<tbody>
													<tr>
														<td class="text-muted" style="width: 40%;"><i class="bx bx-building me-1"></i>Lender:</td>
														<td><strong>{{ $loan->lender_name ?? ($loan->bank_name ?? ($loan->bankAccount->name ?? 'N/A')) }}</strong></td>
													</tr>
													@if($loan->facility_name)
													<tr>
														<td class="text-muted"><i class="bx bx-file me-1"></i>Facility:</td>
														<td><strong>{{ $loan->facility_name }}</strong></td>
													</tr>
													@endif
													@if($loan->disbursed_amount)
													<tr>
														<td class="text-muted"><i class="bx bx-export me-1"></i>Disbursed:</td>
														<td><strong>{{ number_format($loan->disbursed_amount, 2) }} TZS</strong></td>
													</tr>
													@endif
													<tr>
														<td class="text-muted"><i class="bx bx-time me-1"></i>Term:</td>
														<td><strong>{{ $loan->term_months }} months</strong></td>
													</tr>
													<tr>
														<td class="text-muted"><i class="bx bx-percent me-1"></i>Interest Rate:</td>
														<td>
															<strong>{{ number_format($loan->interest_rate, 2) }}%</strong>
															@if($loan->rate_type == 'variable' && $loan->base_rate_source)
																<small class="text-muted">({{ $loan->base_rate_source }}{{ $loan->spread ? ' + ' . $loan->spread . '%' : '' }})</small>
															@endif
														</td>
													</tr>
													<tr>
														<td class="text-muted"><i class="bx bx-calculator me-1"></i>Calculation:</td>
														<td><strong>{{ strtoupper($loan->calculation_basis) }}</strong></td>
													</tr>
													<tr>
														<td class="text-muted"><i class="bx bx-calendar-check me-1"></i>Frequency:</td>
														<td><strong>{{ ucfirst($loan->payment_frequency) }}</strong></td>
													</tr>
													<tr>
														<td class="text-muted"><i class="bx bx-receipt me-1"></i>Method:</td>
														<td><strong>{{ ucfirst(str_replace('_', ' ', $loan->amortization_method ?? 'N/A')) }}</strong></td>
													</tr>
													@if($loan->effective_interest_rate)
													<tr>
														<td class="text-muted"><i class="bx bx-line-chart me-1"></i>Effective Interest Rate (EIR):</td>
														<td>
															<strong class="text-primary">{{ number_format($loan->effective_interest_rate, 2) }}%</strong>
															@if($loan->eir_locked)
																<span class="badge bg-success ms-1" title="EIR locked on {{ $loan->eir_locked_at?->format('d M Y') }}"><i class="bx bx-lock me-1"></i>Locked</span>
															@else
																<span class="badge bg-warning ms-1"><i class="bx bx-time me-1"></i>Not Locked</span>
															@endif
														</td>
													</tr>
													@endif
													@if($loan->initial_amortised_cost)
													<tr>
														<td class="text-muted"><i class="bx bx-money me-1"></i>Initial Amortised Cost:</td>
														<td><strong class="text-info">{{ number_format($loan->initial_amortised_cost, 2) }} TZS</strong></td>
													</tr>
													@endif
													@if($loan->current_amortised_cost !== null)
													<tr>
														<td class="text-muted"><i class="bx bx-trending-up me-1"></i>Current Amortised Cost:</td>
														<td><strong class="text-success">{{ number_format($loan->current_amortised_cost, 2) }} TZS</strong></td>
													</tr>
													@endif
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>

							<!-- Key Dates -->
							<div class="col-md-6">
								<div class="card border-0 shadow-sm h-100">
									<div class="card-header bg-info bg-opacity-10 border-0">
										<h6 class="mb-0 text-info fw-bold">
											<i class="bx bx-calendar me-2"></i>Key Dates
										</h6>
									</div>
									<div class="card-body">
										<div class="table-responsive">
											<table class="table table-borderless mb-0">
												<tbody>
													@if($loan->start_date)
													<tr>
														<td class="text-muted" style="width: 40%;"><i class="bx bx-play-circle me-1"></i>Start Date:</td>
														<td><strong>{{ $loan->start_date->format('d M Y') }}</strong></td>
													</tr>
													@endif
													<tr>
														<td class="text-muted"><i class="bx bx-export me-1"></i>Disbursement:</td>
														<td><strong>{{ optional($loan->disbursement_date)->format('d M Y') ?? 'N/A' }}</strong></td>
													</tr>
													@if($loan->maturity_date)
													<tr>
														<td class="text-muted"><i class="bx bx-flag me-1"></i>Maturity Date:</td>
														<td><strong>{{ $loan->maturity_date->format('d M Y') }}</strong></td>
													</tr>
													@endif
													<tr>
														<td class="text-muted"><i class="bx bx-calendar me-1"></i>First Payment:</td>
														<td><strong>{{ optional($loan->first_payment_date)->format('d M Y') ?? 'N/A' }}</strong></td>
													</tr>
													@if($nextDue)
													<tr>
														<td class="text-muted"><i class="bx bx-time-five me-1"></i>Next Due:</td>
														<td><strong class="text-warning">{{ \Carbon\Carbon::parse($nextDue->due_date)->format('d M Y') }}</strong></td>
													</tr>
													@endif
													@if($loan->grace_period_months > 0)
													<tr>
														<td class="text-muted"><i class="bx bx-time me-1"></i>Grace Period:</td>
														<td><strong>{{ $loan->grace_period_months }} months</strong></td>
													</tr>
													@endif
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>

							<!-- Payment Summary -->
							<div class="col-md-6">
								<div class="card border-0 shadow-sm h-100">
									<div class="card-header bg-success bg-opacity-10 border-0">
										<h6 class="mb-0 text-success fw-bold">
											<i class="bx bx-check-circle me-2"></i>Payment Summary
										</h6>
									</div>
									<div class="card-body">
										<div class="table-responsive">
											<table class="table table-borderless mb-0">
												<tbody>
													<tr>
														<td class="text-muted" style="width: 40%;"><i class="bx bx-list-ul me-1"></i>Total Installments:</td>
														<td><strong>{{ $totalInstallments }}</strong></td>
													</tr>
													<tr>
														<td class="text-muted"><i class="bx bx-check me-1"></i>Paid Installments:</td>
														<td><strong class="text-success">{{ $paidInstallments }}</strong></td>
													</tr>
													<tr>
														<td class="text-muted"><i class="bx bx-money me-1"></i>Total Interest Paid:</td>
														<td><strong>{{ number_format($loan->total_interest_paid, 2) }} TZS</strong></td>
													</tr>
													<tr>
														<td class="text-muted"><i class="bx bx-money me-1"></i>Total Principal Paid:</td>
														<td><strong>{{ number_format($loan->total_principal_paid, 2) }} TZS</strong></td>
													</tr>
													@if($loan->fees_amount > 0)
													<tr>
														<td class="text-muted"><i class="bx bx-dollar me-1"></i>Fees Amount:</td>
														<td><strong>{{ number_format($loan->fees_amount, 2) }} TZS</strong></td>
													</tr>
													@endif
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>

							<!-- Settings & Options -->
							<div class="col-md-6">
								<div class="card border-0 shadow-sm h-100">
									<div class="card-header bg-warning bg-opacity-10 border-0">
										<h6 class="mb-0 text-warning fw-bold">
											<i class="bx bx-cog me-2"></i>Settings & Options
										</h6>
									</div>
									<div class="card-body">
										<div class="table-responsive">
											<table class="table table-borderless mb-0">
												<tbody>
													<tr>
														<td class="text-muted" style="width: 40%;"><i class="bx bx-dollar me-1"></i>Prepayment Allowed:</td>
														<td>
															@if($loan->prepayment_allowed)
																<span class="badge bg-success">Yes</span>
																@if($loan->prepayment_penalty_rate)
																	<small class="text-muted">({{ number_format($loan->prepayment_penalty_rate, 2) }}% penalty)</small>
																@endif
															@else
																<span class="badge bg-secondary">No</span>
															@endif
														</td>
													</tr>
													@if($loan->fees_amount > 0)
													<tr>
														<td class="text-muted"><i class="bx bx-money me-1"></i>Fees Treatment:</td>
														<td>
															@if($loan->capitalise_fees)
																<span class="badge bg-warning">Capitalized</span>
															@else
																<span class="badge bg-secondary">Expensed</span>
															@endif
														</td>
													</tr>
													@endif
													<tr>
														<td class="text-muted"><i class="bx bx-line-chart me-1"></i>Interest Capitalisation:</td>
														<td>
															@if($loan->capitalise_interest && $loan->capitalised_interest_account_id)
																<span class="badge bg-warning">Capitalised (IAS 23)</span>
																@if($loan->capitalisation_end_date)
																	<small class="text-muted d-block">Until {{ $loan->capitalisation_end_date->format('d M Y') }}</small>
																@else
																	<small class="text-muted d-block">No end date set</small>
																@endif
															@else
																<span class="badge bg-secondary">Expensed</span>
															@endif
														</td>
													</tr>
													@if($loan->notes)
													<tr>
														<td class="text-muted align-top"><i class="bx bx-note me-1"></i>Notes:</td>
														<td><small>{{ $loan->notes }}</small></td>
													</tr>
													@endif
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Contractual Cash Schedule Tab -->
					<div class="tab-pane fade" id="cash-schedule" role="tabpanel">
						<div class="alert alert-info mb-3">
							<i class="bx bx-info-circle me-2"></i>
							<strong>Contractual Schedule (Cash):</strong> Shows payment obligations based on nominal interest rate. Used for payment reminders, bank reconciliation, and customer statements.
						</div>
						<div class="table-responsive">
							<table class="table table-hover align-middle mb-0">
								<thead class="table-light">
									<tr>
										<th class="text-center">#</th>
										<th>Due Date</th>
										<th class="text-end">Opening Balance</th>
										<th class="text-end">Interest Due</th>
										<th class="text-end">Principal Due</th>
										<th class="text-end">Total Due</th>
										<th class="text-end">Amount Paid</th>
										<th class="text-end">Closing Balance</th>
										<th class="text-center">Status</th>
										<th class="text-center">Actions</th>
									</tr>
								</thead>
								<tbody>
									@forelse($loan->cashSchedules as $row)
										@php
											$monthKey = \Carbon\Carbon::parse($row->due_date)->format('Y-m');
											$isAccruedMonth = !empty($accruedMonths[$monthKey] ?? false);
										@endphp
										<tr class="{{ $row->status == 'paid' ? 'table-success' : ($row->status == 'overdue' ? 'table-danger' : ($row->status == 'partial' ? 'table-warning' : '')) }}">
											<td class="text-center"><strong>{{ $row->installment_no }}</strong></td>
											<td>
												{{ \Carbon\Carbon::parse($row->due_date)->format('d M Y') }}
												@if($row->status == 'overdue')
													<br><small class="text-danger">Overdue: {{ $row->days_overdue }} days</small>
												@endif
											</td>
											<td class="text-end">{{ number_format($row->opening_principal, 2) }}</td>
											<td class="text-end">{{ number_format($row->interest_due, 2) }}</td>
											<td class="text-end">{{ number_format($row->principal_due, 2) }}</td>
											<td class="text-end"><strong>{{ number_format($row->installment_amount, 2) }}</strong></td>
											<td class="text-end">
												@if($row->amount_paid > 0)
													<span class="text-success">{{ number_format($row->amount_paid, 2) }}</span>
												@else
													<span class="text-muted">0.00</span>
												@endif
											</td>
											<td class="text-end">{{ number_format($row->closing_principal, 2) }}</td>
											<td class="text-center">
												@if($row->status == 'paid')
													<span class="badge bg-success"><i class="bx bx-check me-1"></i>Paid</span>
												@elseif($row->status == 'partial')
													<span class="badge bg-warning"><i class="bx bx-time me-1"></i>Partial</span>
												@elseif($row->status == 'overdue')
													<span class="badge bg-danger"><i class="bx bx-error me-1"></i>Overdue</span>
												@else
													<span class="badge bg-secondary"><i class="bx bx-time-five me-1"></i>Due</span>
												@endif
											</td>
											<td class="text-center">
												@if(in_array($loan->status, ['disbursed', 'active']))
													@if($isAccruedMonth)
														<span class="badge bg-info"><i class="bx bx-lock me-1"></i>Accrued</span>
													@else
													<form method="POST"
														action="{{ route('loans.accrue.interest', $loan->encoded_id) }}"
														class="d-inline schedule-accrual-form"
														data-due-date="{{ \Carbon\Carbon::parse($row->due_date)->format('Y-m-d') }}"
														data-interest="{{ number_format($row->interest_due, 2) }}">
														@csrf
														<input type="hidden" name="accrual_date" value="{{ \Carbon\Carbon::parse($row->due_date)->format('Y-m-d') }}">
														<button type="button" class="btn btn-sm btn-outline-info schedule-accrual-btn">
															<i class="bx bx-calendar me-1"></i>Accrue
														</button>
													</form>
													@endif
												@endif
											</td>
										</tr>
									@empty
										<tr>
											<td colspan="10" class="text-center py-5">
												<i class="bx bx-receipt fs-1 text-muted d-block mb-2"></i>
												<p class="text-muted mb-0">No contractual schedule yet. Click "Generate Schedule" to create repayment schedule.</p>
											</td>
										</tr>
									@endforelse
								</tbody>
							</table>
						</div>
					</div>

					<!-- IFRS 9 Amortised Cost Schedule Tab -->
					<div class="tab-pane fade" id="ifrs-schedule" role="tabpanel">
						<div class="alert alert-warning mb-3">
							<i class="bx bx-info-circle me-2"></i>
							<strong>IFRS 9 Schedule (Accounting):</strong> Shows how the loan is accounted for using Effective Interest Rate (EIR). This schedule is system-generated and used for General Ledger, Financial Statements, and Audit compliance.
							@if($loan->effective_interest_rate)
								<br><small class="mt-1 d-block"><strong>EIR:</strong> {{ number_format($loan->effective_interest_rate, 2) }}% | 
								<strong>Initial AC:</strong> {{ number_format($loan->initial_amortised_cost ?? 0, 2) }} TZS | 
								<strong>Current AC:</strong> {{ number_format($loan->current_amortised_cost ?? 0, 2) }} TZS</small>
							@endif
						</div>
						<div class="table-responsive">
							<table class="table table-hover align-middle mb-0">
								<thead class="table-light">
									<tr>
										<th class="text-center">#</th>
										<th>Period</th>
										<th class="text-end">Opening Amortised Cost</th>
										<th class="text-end">IFRS Interest Expense</th>
										<th class="text-end">Cash Paid</th>
										<th class="text-end">Closing Amortised Cost</th>
										<th class="text-end">Deferred Costs Amortized</th>
										<th class="text-center">GL Status</th>
									</tr>
								</thead>
								<tbody>
									@forelse($loan->ifrsSchedules as $row)
										<tr>
											<td class="text-center"><strong>{{ $row->period_no }}</strong></td>
											<td>
												{{ \Carbon\Carbon::parse($row->period_start)->format('d M Y') }} - 
												{{ \Carbon\Carbon::parse($row->period_end)->format('d M Y') }}
												<br><small class="text-muted">Due: {{ \Carbon\Carbon::parse($row->due_date)->format('d M Y') }}</small>
											</td>
											<td class="text-end">{{ number_format($row->opening_amortised_cost, 2) }}</td>
											<td class="text-end">
												<strong class="text-primary">{{ number_format($row->ifrs_interest_expense, 2) }}</strong>
												<br><small class="text-muted">EIR: {{ number_format($row->effective_interest_rate, 2) }}%</small>
											</td>
											<td class="text-end">
												{{ number_format($row->cash_paid, 2) }}
												@if($row->cash_interest_paid > 0 || $row->cash_principal_paid > 0)
													<br><small class="text-muted">
														Int: {{ number_format($row->cash_interest_paid, 2) }} | 
														Prin: {{ number_format($row->cash_principal_paid, 2) }}
													</small>
												@endif
											</td>
											<td class="text-end">
												<strong class="text-success">{{ number_format($row->closing_amortised_cost, 2) }}</strong>
											</td>
											<td class="text-end">
												@if($row->deferred_costs_amortized > 0)
													{{ number_format($row->deferred_costs_amortized, 2) }}
												@else
													<span class="text-muted">-</span>
												@endif
											</td>
											<td class="text-center">
												@if($row->posted_to_gl)
													<span class="badge bg-success"><i class="bx bx-check me-1"></i>Posted</span>
													@if($row->posted_date)
														<br><small class="text-muted">{{ \Carbon\Carbon::parse($row->posted_date)->format('d M Y') }}</small>
													@endif
												@else
													<span class="badge bg-warning"><i class="bx bx-time me-1"></i>Pending</span>
												@endif
											</td>
										</tr>
									@empty
										<tr>
											<td colspan="8" class="text-center py-5">
												<i class="bx bx-line-chart fs-1 text-muted d-block mb-2"></i>
												<p class="text-muted mb-0">No IFRS 9 schedule yet. Generate schedule to create both contractual and IFRS 9 schedules.</p>
											</td>
										</tr>
									@endforelse
								</tbody>
							</table>
						</div>
					</div>

					<!-- Payments Tab -->
					<div class="tab-pane fade" id="payments" role="tabpanel">
						@if($loan->payments->count() > 0)
						<div class="table-responsive">
							<table class="table table-hover align-middle mb-0">
										<thead class="table-light">
											<tr>
												<th>Payment Date</th>
												<th class="text-end">Amount</th>
												<th class="text-end">Interest</th>
												<th class="text-end">Principal</th>
												<th class="text-end">Fees</th>
												<th class="text-end">Penalty</th>
												<th>Reference</th>
												<th class="text-center">Status</th>
											</tr>
										</thead>
										<tbody>
											@foreach($loan->payments as $payment)
												<tr>
													<td>{{ $payment->payment_date->format('d M Y') }}</td>
													<td class="text-end"><strong>{{ number_format($payment->amount, 2) }}</strong></td>
													<td class="text-end">{{ number_format($payment->allocation_interest, 2) }}</td>
													<td class="text-end">{{ number_format($payment->allocation_principal, 2) }}</td>
													<td class="text-end">{{ number_format($payment->allocation_fees, 2) }}</td>
													<td class="text-end">{{ number_format($payment->allocation_penalty, 2) }}</td>
													<td>{{ $payment->reference ?? $payment->payment_ref ?? '-' }}</td>
													<td class="text-center">
														@if($payment->posted_flag)
															<span class="badge bg-success"><i class="bx bx-check me-1"></i>Posted</span>
														@else
															<span class="badge bg-warning"><i class="bx bx-time me-1"></i>Pending</span>
														@endif
													</td>
												</tr>
											@endforeach
										</tbody>
										<tfoot class="table-light">
											<tr>
												<th>Total</th>
												<th class="text-end">{{ number_format($loan->payments->sum('amount'), 2) }}</th>
												<th class="text-end">{{ number_format($loan->payments->sum('allocation_interest'), 2) }}</th>
												<th class="text-end">{{ number_format($loan->payments->sum('allocation_principal'), 2) }}</th>
												<th class="text-end">{{ number_format($loan->payments->sum('allocation_fees'), 2) }}</th>
												<th class="text-end">{{ number_format($loan->payments->sum('allocation_penalty'), 2) }}</th>
												<th colspan="2"></th>
											</tr>
										</tfoot>
									</table>
						</div>
						@else
						<div class="text-center py-5">
							<i class="bx bx-credit-card fs-1 text-muted d-block mb-2"></i>
							<p class="text-muted mb-0">No payments recorded yet.</p>
						</div>
						@endif
					</div>

					<!-- Transactions Tab -->
					<div class="tab-pane fade" id="transactions" role="tabpanel">
						<div class="row g-3">
							<!-- Disbursements -->
							@if($loan->disbursements->count() > 0)
							<div class="col-12">
								<div class="card border-0 shadow-sm">
									<div class="card-header bg-success bg-opacity-10 border-0">
										<h6 class="mb-0 fw-bold"><i class="bx bx-export me-2"></i>Disbursements</h6>
									</div>
									<div class="card-body">
										<div class="table-responsive">
											<table class="table table-hover align-middle mb-0">
												<thead class="table-light">
													<tr>
														<th>Date</th>
														<th class="text-end">Amount Received</th>
														<th class="text-end">Net Proceeds</th>
														<th class="text-end">Bank Charges</th>
														<th>Reference</th>
														<th class="text-center">Status</th>
													</tr>
												</thead>
												<tbody>
													@foreach($loan->disbursements as $disb)
														<tr>
															<td>{{ $disb->disb_date->format('d M Y') }}</td>
															<td class="text-end"><strong>{{ number_format($disb->amount_received, 2) }}</strong></td>
															<td class="text-end">{{ number_format($disb->net_proceeds, 2) }}</td>
															<td class="text-end">{{ number_format($disb->bank_charges, 2) }}</td>
															<td>{{ $disb->ref_number ?? '-' }}</td>
															<td class="text-center">
																@if($disb->journal_id)
																	<span class="badge bg-success"><i class="bx bx-check me-1"></i>Posted</span>
																@else
																	<span class="badge bg-warning"><i class="bx bx-time me-1"></i>Pending</span>
																@endif
															</td>
														</tr>
													@endforeach
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
							@endif

							<!-- Accruals -->
							@if($loan->accruals->count() > 0)
							<div class="col-12">
								<div class="card border-0 shadow-sm">
									<div class="card-header bg-info bg-opacity-10 border-0">
										<h6 class="mb-0 fw-bold"><i class="bx bx-calendar me-2"></i>Interest Accruals</h6>
									</div>
									<div class="card-body">
										<div class="table-responsive">
											<table class="table table-hover align-middle mb-0">
												<thead class="table-light">
													<tr>
														<th>Accrual Date</th>
														<th class="text-end">Opening Balance</th>
														<th class="text-end">Interest Rate</th>
														<th class="text-end">Days</th>
														<th class="text-end">Interest Accrued</th>
														<th class="text-center">Status</th>
													</tr>
												</thead>
												<tbody>
													@foreach($loan->accruals as $accrual)
														<tr>
															<td>{{ $accrual->accrual_date->format('d M Y') }}</td>
															<td class="text-end">{{ number_format($accrual->opening_balance, 2) }}</td>
															<td class="text-end">{{ number_format($accrual->interest_rate, 2) }}%</td>
															<td class="text-end">{{ $accrual->days_in_period }}</td>
															<td class="text-end"><strong>{{ number_format($accrual->interest_accrued, 2) }}</strong></td>
															<td class="text-center">
																@if($accrual->posted_flag)
																	<span class="badge bg-success"><i class="bx bx-check me-1"></i>Posted</span>
																@else
																	<span class="badge bg-warning"><i class="bx bx-time me-1"></i>Pending</span>
																@endif
															</td>
														</tr>
													@endforeach
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
							@endif

							<!-- Fees -->
							@if($loan->fees->count() > 0)
							<div class="col-12">
								<div class="card border-0 shadow-sm">
									<div class="card-header bg-warning bg-opacity-10 border-0">
										<h6 class="mb-0 fw-bold"><i class="bx bx-dollar me-2"></i>Loan Fees</h6>
									</div>
									<div class="card-body">
										<div class="table-responsive">
											<table class="table table-hover align-middle mb-0">
												<thead class="table-light">
													<tr>
														<th>#</th>
														<th>Type</th>
														<th>Name</th>
														<th class="text-end">Amount (TZS)</th>
														<th>Treatment</th>
														<th>Recognized On</th>
													</tr>
												</thead>
												<tbody>
													@foreach($loan->fees as $index => $fee)
														<tr>
															<td>{{ $index + 1 }}</td>
															<td><span class="badge bg-info">{{ ucfirst($fee->type) }}</span></td>
															<td>{{ $fee->name }}</td>
															<td class="text-end"><strong>{{ number_format($fee->amount, 2) }}</strong></td>
															<td>
																@if($fee->treatment == 'capitalize')
																	<span class="badge bg-warning">Capitalize</span>
																@else
																	<span class="badge bg-secondary">Expense</span>
																@endif
															</td>
															<td>{{ optional($fee->recognized_on)->format('d M Y') ?? 'N/A' }}</td>
														</tr>
													@endforeach
												</tbody>
												<tfoot class="table-light">
													<tr>
														<th colspan="3" class="text-end">Total:</th>
														<th class="text-end">{{ number_format($loan->fees->sum('amount'), 2) }}</th>
														<th colspan="2"></th>
													</tr>
												</tfoot>
											</table>
										</div>
									</div>
								</div>
							</div>
							@endif

							<!-- Restructure History -->
							@if($loan->restructureHistory->count() > 0)
							<div class="col-12">
								<div class="card border-0 shadow-sm">
									<div class="card-header bg-secondary bg-opacity-10 border-0">
										<h6 class="mb-0 fw-bold"><i class="bx bx-refresh me-2"></i>Restructure History</h6>
									</div>
									<div class="card-body">
										<div class="table-responsive">
											<table class="table table-hover align-middle mb-0">
												<thead class="table-light">
													<tr>
														<th>Date</th>
														<th>Reason</th>
														<th>New Terms Summary</th>
														<th>Approved By</th>
													</tr>
												</thead>
												<tbody>
													@foreach($loan->restructureHistory as $restructure)
														<tr>
															<td>{{ $restructure->restructure_date->format('d M Y') }}</td>
															<td>{{ $restructure->reason }}</td>
															<td>{{ $restructure->new_terms_summary }}</td>
															<td>{{ $restructure->approvedBy->name ?? 'N/A' }}</td>
														</tr>
													@endforeach
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
							@endif

							<!-- Covenants -->
							@if($loan->covenants->count() > 0)
							<div class="col-12">
								<div class="card border-0 shadow-sm">
									<div class="card-header bg-primary bg-opacity-10 border-0">
										<h6 class="mb-0 fw-bold"><i class="bx bx-check-circle me-2"></i>Loan Covenants</h6>
									</div>
									<div class="card-body">
										<div class="table-responsive">
											<table class="table table-hover align-middle mb-0">
												<thead class="table-light">
													<tr>
														<th>Covenant Name</th>
														<th>Period</th>
														<th class="text-end">Threshold</th>
														<th class="text-end">Actual</th>
														<th class="text-center">Status</th>
													</tr>
												</thead>
												<tbody>
													@foreach($loan->covenants as $covenant)
														<tr>
															<td><strong>{{ $covenant->covenant_name }}</strong></td>
															<td>{{ $covenant->period->format('d M Y') }}</td>
															<td class="text-end">{{ number_format($covenant->threshold_value, 2) }}</td>
															<td class="text-end">{{ number_format($covenant->actual_value, 2) }}</td>
															<td class="text-center">
																@if($covenant->status == 'compliant')
																	<span class="badge bg-success"><i class="bx bx-check me-1"></i>Compliant</span>
																@elseif($covenant->status == 'non_compliant')
																	<span class="badge bg-danger"><i class="bx bx-x me-1"></i>Non-Compliant</span>
																@else
																	<span class="badge bg-warning">{{ ucfirst(str_replace('_', ' ', $covenant->status)) }}</span>
																@endif
															</td>
														</tr>
													@endforeach
												</tbody>
											</table>
										</div>
									</div>
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

<!-- Disbursement Modal -->
<div class="modal fade" id="disburseModal" tabindex="-1" aria-labelledby="disburseModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h5 class="modal-title" id="disburseModalLabel"><i class="bx bx-export me-2"></i>Disburse Loan</h5>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form method="POST" action="{{ route('loans.disburse', $loan->encoded_id) }}">
				@csrf
				<div class="modal-body">
					<div class="row g-3">
						<div class="col-md-6">
							<label class="form-label">Disbursement Date</label>
							<input type="date" class="form-control" name="disb_date" value="{{ now()->format('Y-m-d') }}" required>
						</div>
						<div class="col-md-6">
							<label class="form-label">Bank Account</label>
							<select name="bank_account_id" class="form-select" required>
								<option value="">Select bank</option>
								@foreach($bankAccounts as $acc)
									<option value="{{ $acc->id }}" {{ $loan->bank_account_id == $acc->id ? 'selected' : '' }}>
										{{ $acc->name }} @if($acc->chartAccount) ({{ $acc->chartAccount->account_code }}) @endif
									</option>
								@endforeach
							</select>
						</div>
						<div class="col-md-6">
							<label class="form-label">Amount Received (Gross)</label>
							<input type="number" step="0.01" class="form-control" name="amount_received" id="amount_received" value="{{ number_format($loan->principal_amount, 2, '.', '') }}" required>
							<small class="text-muted">Total amount received from lender</small>
						</div>
						<div class="col-md-6">
							<label class="form-label">Net Proceeds</label>
							<input type="number" step="0.01" class="form-control" name="net_proceeds" id="net_proceeds" value="{{ number_format($loan->disbursed_amount ?? $loan->principal_amount, 2, '.', '') }}" required>
							<small class="text-muted">Net cash into bank (after deductions)</small>
						</div>
						<div class="col-md-6">
							<label class="form-label">Bank Charges</label>
							<input type="number" step="0.01" class="form-control" name="bank_charges" value="0" placeholder="0.00">
							<small class="text-muted">Bank charges deducted</small>
						</div>
						@if($loan->fees_amount > 0)
						<div class="col-md-12">
							<div class="alert alert-info mb-0">
								<strong>Fees Amount:</strong> {{ number_format($loan->fees_amount, 2) }} TZS.
								These fees will be recognised on disbursement and are treated as a deduction from the loan proceeds.
							</div>
						</div>
						@endif
						<div class="col-md-6">
							<label class="form-label">Reference Number</label>
							<input type="text" class="form-control" name="ref_number" placeholder="Bank reference">
						</div>
						<div class="col-md-12">
							<label class="form-label">Narration</label>
							<textarea class="form-control" name="narration" rows="2" placeholder="Additional notes"></textarea>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="submit" class="btn btn-primary"><i class="bx bx-export me-1"></i>Confirm Disbursement</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Prepayment Modal -->
<div class="modal fade" id="prepaymentModal" tabindex="-1" aria-labelledby="prepaymentModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header bg-warning text-dark">
				<h5 class="modal-title" id="prepaymentModalLabel"><i class="bx bx-money me-2"></i>Prepayment</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form method="POST" action="{{ route('loans.prepayment', $loan->encoded_id) }}">
				@csrf
				<div class="modal-body">
					<div class="row g-3">
						<div class="col-md-12">
							<label class="form-label">Prepayment Date</label>
							<input type="date" class="form-control" name="prepayment_date" value="{{ now()->format('Y-m-d') }}" required>
						</div>
						<div class="col-md-12">
							<label class="form-label">Prepayment Amount</label>
							<input type="number" step="0.01" class="form-control" name="prepayment_amount" required>
							<small class="text-muted">Outstanding: {{ number_format($loan->outstanding_principal, 2) }} TZS</small>
						</div>
						<div class="col-md-12">
							<label class="form-label">Bank Account</label>
							<select name="bank_account_id" class="form-select" required>
								<option value="">Select bank</option>
								@foreach($bankAccounts as $acc)
									<option value="{{ $acc->id }}">{{ $acc->name }}</option>
								@endforeach
							</select>
						</div>
						<div class="col-md-12">
							<label class="form-label">Reference</label>
							<input type="text" class="form-control" name="reference" placeholder="Payment reference">
						</div>
						<div class="col-md-12">
							<label class="form-label">Notes</label>
							<textarea class="form-control" name="notes" rows="2" placeholder="Additional notes"></textarea>
						</div>
						@if($loan->prepayment_penalty_rate)
						<div class="col-md-12">
							<div class="alert alert-warning">
								<strong>Note:</strong> Prepayment penalty rate: {{ number_format($loan->prepayment_penalty_rate, 2) }}% will be applied.
							</div>
						</div>
						@endif
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="submit" class="btn btn-warning"><i class="bx bx-money me-1"></i>Process Prepayment</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Stop Capitalisation Modal -->
<div class="modal fade" id="stopCapitalisationModal" tabindex="-1" aria-labelledby="stopCapitalisationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header bg-warning text-dark">
				<h5 class="modal-title" id="stopCapitalisationModalLabel"><i class="bx bx-line-chart me-2"></i>Stop Capitalising Interest (IAS 23)</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form method="POST" action="{{ route('loans.stop-capitalisation', $loan->encoded_id) }}">
				@csrf
				<div class="modal-body">
					<div class="alert alert-warning">
						<strong>Note:</strong> After the capitalisation end date, all future interest accruals will be posted to
						<span class="fw-bold">Interest Expense</span> instead of the capitalised interest asset account.
						Existing capitalised interest will remain in the asset account.
					</div>
					<div class="mb-3">
						<label class="form-label">Capitalisation End Date</label>
						<input type="date" name="capitalisation_end_date" class="form-control"
							   value="{{ optional($loan->capitalisation_end_date)->format('Y-m-d') ?? now()->format('Y-m-d') }}" required>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-warning">
						<i class="bx bx-line-chart me-1"></i>Confirm
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Restructure Modal -->
<div class="modal fade" id="restructureModal" tabindex="-1" aria-labelledby="restructureModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header bg-secondary text-white">
				<h5 class="modal-title" id="restructureModalLabel"><i class="bx bx-refresh me-2"></i>Restructure Loan</h5>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form method="POST" action="{{ route('loans.restructure', $loan->encoded_id) }}">
				@csrf
				<div class="modal-body">
					<div class="row g-3">
						<div class="col-md-6">
							<label class="form-label">Restructure Date</label>
							<input type="date" class="form-control" name="restructure_date" value="{{ now()->format('Y-m-d') }}" required>
						</div>
						<div class="col-md-12">
							<label class="form-label">Reason <span class="text-danger">*</span></label>
							<textarea class="form-control" name="reason" rows="2" required placeholder="Reason for restructuring"></textarea>
						</div>
						<div class="col-md-12">
							<label class="form-label">New Terms Summary <span class="text-danger">*</span></label>
							<textarea class="form-control" name="new_terms_summary" rows="3" required placeholder="Summary of new terms"></textarea>
						</div>
						<div class="col-md-6">
							<label class="form-label">New Interest Rate (%)</label>
							<input type="number" step="0.01" class="form-control" name="new_interest_rate" value="{{ $loan->interest_rate }}" placeholder="Leave blank to keep current">
						</div>
						<div class="col-md-6">
							<label class="form-label">New Term (Months)</label>
							<input type="number" class="form-control" name="new_term_months" value="{{ $loan->term_months }}" placeholder="Leave blank to keep current">
						</div>
						<div class="col-md-6">
							<label class="form-label">New Payment Frequency</label>
							<select name="new_payment_frequency" class="form-select">
								<option value="">Keep Current</option>
								@foreach(['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'semi-annual' => 'Semi-Annual', 'annual' => 'Annual'] as $key => $label)
									<option value="{{ $key }}" {{ $loan->payment_frequency == $key ? 'selected' : '' }}>{{ $label }}</option>
								@endforeach
							</select>
						</div>
						<div class="col-md-12">
							<label class="form-label">Approval Notes</label>
							<textarea class="form-control" name="approval_notes" rows="2" placeholder="Approval notes"></textarea>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="submit" class="btn btn-secondary"><i class="bx bx-refresh me-1"></i>Restructure Loan</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header bg-success text-white">
				<h5 class="modal-title" id="paymentModalLabel"><i class="bx bx-credit-card me-2"></i>Record Loan Payment</h5>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form method="POST" action="{{ route('loans.payments.store', $loan->encoded_id) }}">
				@csrf
				<div class="modal-body">
					<div class="alert alert-info">
						<strong>Loan:</strong> {{ $loan->loan_number }} | 
						<strong>Outstanding:</strong> {{ number_format($loan->outstanding_principal, 2) }} TZS |
						<strong>Accrued Interest:</strong> {{ number_format($loan->accrued_interest, 2) }} TZS
					</div>
					<div class="row g-3">
						<div class="col-md-6">
							<label class="form-label">Payment Date <span class="text-danger">*</span></label>
							<input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" class="form-control" required>
							@error('payment_date') <small class="text-danger d-block">{{ $message }}</small> @enderror
						</div>
						<div class="col-md-6">
							<label class="form-label">Bank Account <span class="text-danger">*</span></label>
							<select name="bank_account_id" class="form-select" required>
								<option value="">Select bank account</option>
								@foreach($bankAccounts as $acc)
									<option value="{{ $acc->id }}" {{ old('bank_account_id') == $acc->id ? 'selected' : '' }}>
										{{ $acc->name }} @if($acc->chartAccount) ({{ $acc->chartAccount->account_code }}) @endif
									</option>
								@endforeach
							</select>
							@error('bank_account_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
						</div>
						<div class="col-md-12">
							<label class="form-label">Select Schedule (Optional)</label>
							<select name="loan_schedule_id" id="loan_schedule_id" class="form-select">
								<option value="">Auto-allocate to next due schedules</option>
								@foreach($loan->cashSchedules->whereIn('status', ['due', 'partial', 'overdue']) as $schedule)
									@php
										$interestDue = $schedule->interest_due - $schedule->interest_paid;
										$principalDue = $schedule->principal_due - $schedule->principal_paid;
										$totalDue = $interestDue + $principalDue;
									@endphp
									<option value="{{ $schedule->id }}" 
										data-due-date="{{ $schedule->due_date->format('Y-m-d') }}"
										data-interest-due="{{ $interestDue }}"
										data-principal-due="{{ $principalDue }}"
										data-total-due="{{ $totalDue }}"
										data-amount-paid="{{ $schedule->amount_paid }}"
										data-installment-amount="{{ $schedule->installment_amount }}">
										Installment #{{ $schedule->installment_no }} - Due: {{ $schedule->due_date->format('d M Y') }} 
										({{ number_format($totalDue, 2) }} TZS due)
										@if($schedule->status == 'partial')
											- Partial: {{ number_format($schedule->amount_paid, 2) }} paid
										@endif
									</option>
								@endforeach
							</select>
							<small class="text-muted">Select a specific schedule or leave blank to auto-allocate</small>
						</div>
						<div id="scheduleDetails" class="col-md-12" style="display: none;">
							<div class="card border-info bg-light">
								<div class="card-body">
									<h6 class="card-title text-info mb-3"><i class="bx bx-info-circle me-1"></i>Schedule Details</h6>
									<div class="row">
										<div class="col-md-3">
											<small class="text-muted d-block">Due Date</small>
											<strong id="schedule_due_date">-</strong>
										</div>
										<div class="col-md-3">
											<small class="text-muted d-block">Interest Due</small>
											<strong id="schedule_interest_due">0.00</strong> TZS
										</div>
										<div class="col-md-3">
											<small class="text-muted d-block">Principal Due</small>
											<strong id="schedule_principal_due">0.00</strong> TZS
										</div>
										<div class="col-md-3">
											<small class="text-muted d-block">Total Due</small>
											<strong id="schedule_total_due" class="text-primary">0.00</strong> TZS
										</div>
									</div>
									<div class="row mt-2">
										<div class="col-md-6">
											<small class="text-muted d-block">Amount Already Paid</small>
											<strong id="schedule_amount_paid">0.00</strong> TZS
										</div>
										<div class="col-md-6">
											<small class="text-muted d-block">Installment Amount</small>
											<strong id="schedule_installment_amount">0.00</strong> TZS
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<label class="form-label">Payment Amount <span class="text-danger">*</span></label>
							<input type="number" step="0.01" name="amount" id="payment_amount" value="{{ old('amount') }}" class="form-control" required placeholder="0.00">
							<small class="text-muted">Total payment amount</small>
							@error('amount') <small class="text-danger d-block">{{ $message }}</small> @enderror
						</div>
						<div class="col-md-6">
							<label class="form-label">Reference</label>
							<input type="text" name="reference" value="{{ old('reference') }}" class="form-control" placeholder="Payment reference number">
							<small class="text-muted">Transaction reference (optional)</small>
							@error('reference') <small class="text-danger d-block">{{ $message }}</small> @enderror
						</div>
						<div class="col-md-12">
							<div id="overpaymentWarning" class="alert alert-warning" style="display: none;">
								<i class="bx bx-info-circle me-1"></i>
								<strong>Overpayment Detected:</strong> The payment amount exceeds the schedule amount due. 
								Excess amount will be automatically allocated to the next due schedules.
							</div>
						</div>
						<div class="col-md-12">
							<label class="form-label">Notes</label>
							<textarea name="notes" class="form-control" rows="2" placeholder="Additional notes (optional)">{{ old('notes') }}</textarea>
							@error('notes') <small class="text-danger d-block">{{ $message }}</small> @enderror
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="submit" class="btn btn-success"><i class="bx bx-credit-card me-1"></i>Record Payment</button>
				</div>
			</form>
		</div>
	</div>
</div>

@push('styles')
<style>
	.nav-tabs-custom .nav-link {
		border: none;
		border-bottom: 3px solid transparent;
		color: #6c757d;
		padding: 0.75rem 1.25rem;
		transition: all 0.3s ease;
	}
	
	.nav-tabs-custom .nav-link:hover {
		border-bottom-color: #dee2e6;
		color: #495057;
	}
	
	.nav-tabs-custom .nav-link.active {
		border-bottom-color: #667eea;
		color: #667eea;
		font-weight: 600;
		background-color: transparent;
	}
	
	.card {
		transition: transform 0.2s ease, box-shadow 0.2s ease;
	}
	
	.card:hover {
		transform: translateY(-2px);
		box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
	}
	
	.table-hover tbody tr:hover {
		background-color: rgba(102, 126, 234, 0.05);
	}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
	// Auto-calculate net proceeds in disbursement modal (including fees amount)
	$(document).ready(function() {
		const feesAmount = parseFloat('{{ $loan->fees_amount ?? 0 }}') || 0;

		$('#disburseModal').on('show.bs.modal', function() {
			const amountReceived = parseFloat($('#amount_received').val()) || 0;
			let netProceeds = parseFloat($('#net_proceeds').val()) || 0;
			const bankChargesInput = $('input[name="bank_charges"]');
			let bankCharges = parseFloat(bankChargesInput.val()) || 0;

			// If there are configured fees and this is the first open (no manual changes yet),
			// default net proceeds to gross - fees - bank charges
			if (feesAmount > 0 && amountReceived > 0) {
				// Consider "no manual change" when netProceeds equals amountReceived or zero
				if (netProceeds === amountReceived || netProceeds === 0) {
					netProceeds = amountReceived - feesAmount - bankCharges;
					if (netProceeds < 0) {
						netProceeds = 0;
					}
					$('#net_proceeds').val(netProceeds.toFixed(2));
				}
			} else {
				// Legacy behaviour: infer bank charges from difference
				if (netProceeds < amountReceived && netProceeds > 0 && bankCharges === 0) {
					const calculatedCharges = amountReceived - netProceeds;
					bankChargesInput.val(calculatedCharges.toFixed(2));
				}
			}
		});

		$('#amount_received').on('input', function() {
			const amountReceived = parseFloat($(this).val()) || 0;
			const bankCharges = parseFloat($('input[name="bank_charges"]').val()) || 0;
			let netProceeds = amountReceived - bankCharges - feesAmount;
			if (netProceeds < 0) {
				netProceeds = 0;
			}
			$('#net_proceeds').val(netProceeds.toFixed(2));
		});

		$('input[name="bank_charges"]').on('input', function() {
			const amountReceived = parseFloat($('#amount_received').val()) || 0;
			const bankCharges = parseFloat($(this).val()) || 0;
			let netProceeds = amountReceived - bankCharges - feesAmount;
			if (netProceeds < 0) {
				netProceeds = 0;
			}
			$('#net_proceeds').val(netProceeds.toFixed(2));
		});
	});

	// Payment Modal - Schedule Selection
	$('#loan_schedule_id').on('change', function() {
		const selectedOption = $(this).find('option:selected');
		const scheduleDetails = $('#scheduleDetails');
		
		if ($(this).val()) {
			const dueDate = selectedOption.data('due-date');
			const interestDue = parseFloat(selectedOption.data('interest-due')) || 0;
			const principalDue = parseFloat(selectedOption.data('principal-due')) || 0;
			const totalDue = parseFloat(selectedOption.data('total-due')) || 0;
			const amountPaid = parseFloat(selectedOption.data('amount-paid')) || 0;
			const installmentAmount = parseFloat(selectedOption.data('installment-amount')) || 0;
			
			// Format date
			const dateObj = new Date(dueDate);
			const formattedDate = dateObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
			
			$('#schedule_due_date').text(formattedDate);
			$('#schedule_interest_due').text(interestDue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
			$('#schedule_principal_due').text(principalDue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
			$('#schedule_total_due').text(totalDue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
			$('#schedule_amount_paid').text(amountPaid.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
			$('#schedule_installment_amount').text(installmentAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
			
			scheduleDetails.slideDown();
			
			// Set payment amount to total due as default if empty
			if (!$('#payment_amount').val() || $('#payment_amount').val() == '0') {
				$('#payment_amount').val(totalDue.toFixed(2));
			}
			
			checkOverpayment();
		} else {
			scheduleDetails.slideUp();
			$('#overpaymentWarning').hide();
		}
	});

	// Check for overpayment
	function checkOverpayment() {
		const scheduleId = $('#loan_schedule_id').val();
		if (!scheduleId) {
			$('#overpaymentWarning').hide();
			return;
		}
		
		const selectedOption = $('#loan_schedule_id').find('option:selected');
		const totalDue = parseFloat(selectedOption.data('total-due')) || 0;
		const paymentAmount = parseFloat($('#payment_amount').val()) || 0;
		
		if (paymentAmount > totalDue) {
			$('#overpaymentWarning').slideDown();
		} else {
			$('#overpaymentWarning').slideUp();
		}
	}

	$('#payment_amount').on('input', function() {
		checkOverpayment();
	});

	// SweetAlert confirmation for schedule generation
	$('#generateScheduleBtn').on('click', function(e) {
		e.preventDefault();
		Swal.fire({
			title: 'Generate Repayment Schedule?',
			text: 'This will (re)generate the repayment schedule. Any existing schedule without payments will be replaced.',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#3085d6',
			cancelButtonColor: '#d33',
			confirmButtonText: 'Yes, generate',
			cancelButtonText: 'Cancel'
		}).then((result) => {
			if (result.isConfirmed) {
				document.getElementById('generateScheduleForm').submit();
			}
		});
	});

	// SweetAlert confirmation for deleting draft loan
	$('#deleteLoanBtn').on('click', function(e) {
		e.preventDefault();
		Swal.fire({
			title: 'Delete Draft Loan?',
			text: 'This will permanently delete this draft loan and all its related data (schedules, disbursements, etc.). This action cannot be undone.',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#d33',
			cancelButtonColor: '#6c757d',
			confirmButtonText: 'Yes, delete',
			cancelButtonText: 'Cancel'
		}).then((result) => {
			if (result.isConfirmed) {
				document.getElementById('deleteLoanForm').submit();
			}
		});
	});

	// SweetAlert confirmation for schedule-based interest accrual
	$('.schedule-accrual-btn').on('click', function(e) {
		e.preventDefault();
		const form = $(this).closest('form');
		const dueDate = form.data('due-date');
		const interest = form.data('interest');

		Swal.fire({
			title: 'Accrue Interest for this Installment?',
			text: 'This will accrue interest for the month of the selected schedule (due date: ' + dueDate + ') and post ' + interest + ' TZS to GL.',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#3085d6',
			cancelButtonColor: '#6c757d',
			confirmButtonText: 'Yes, accrue',
			cancelButtonText: 'Cancel'
		}).then((result) => {
			if (result.isConfirmed) {
				form.submit();
			}
		});
	});

	// SweetAlert confirmation for Post to GL
	$('#postToGlBtn').on('click', function(e) {
		e.preventDefault();
		Swal.fire({
			title: 'Post to GL?',
			text: 'This will create GL entries for all unposted transactions (disbursement, accruals, payments). Continue?',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#28a745',
			cancelButtonColor: '#6c757d',
			confirmButtonText: 'Yes, post to GL',
			cancelButtonText: 'Cancel'
		}).then((result) => {
			if (result.isConfirmed) {
				document.getElementById('postToGlForm').submit();
			}
		});
	});

	// SweetAlert for backend flash messages
	@if(session('error'))
	Swal.fire({
		icon: 'error',
		title: 'Error',
		text: @json(session('error')),
	});
	@endif

	@if(session('success'))
	Swal.fire({
		icon: 'success',
		title: 'Success',
		text: @json(session('success')),
	});
	@endif
</script>
@endpush
@endsection