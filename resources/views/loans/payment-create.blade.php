@extends('layouts.main')

@section('title', 'Record Loan Payment')

@section('content')
<div class="page-wrapper">
	<div class="page-content">
		<x-breadcrumbs-with-icons :links="[
			['label' => 'Loan Management', 'url' => route('loans.index'), 'icon' => 'bx bx-money'],
			['label' => $loan->loan_number, 'url' => route('loans.show', $loan->encoded_id), 'icon' => 'bx bx-show'],
			['label' => 'Record Payment', 'url' => '#', 'icon' => 'bx bx-credit-card']
		]" />

		<div class="card">
			<div class="card-body">
				<h5 class="card-title mb-3"><i class="bx bx-credit-card me-2"></i>Record Payment - {{ $loan->loan_number }}</h5>

				<form method="POST" action="{{ route('loans.payments.store', $loan->encoded_id) }}" class="row g-3">
					@csrf
					<div class="col-md-4">
						<label class="form-label">Payment Date</label>
						<input type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" class="form-control" required>
						@error('payment_date') <small class="text-danger">{{ $message }}</small> @enderror
					</div>
					<div class="col-md-4">
						<label class="form-label">Bank Account</label>
						<select name="bank_account_id" class="form-select" required>
							<option value="">Select bank account</option>
							@foreach($bankAccounts as $acc)
								<option value="{{ $acc->id }}" {{ old('bank_account_id') == $acc->id ? 'selected' : '' }}>
									{{ $acc->name }} @if($acc->chartAccount) ({{ $acc->chartAccount->code }} - {{ $acc->chartAccount->name }}) @endif
								</option>
							@endforeach
						</select>
						@error('bank_account_id') <small class="text-danger">{{ $message }}</small> @enderror
					</div>
					<div class="col-md-4">
						<label class="form-label">Amount</label>
						<input type="number" step="0.01" name="amount" value="{{ old('amount') }}" class="form-control" required>
						@error('amount') <small class="text-danger">{{ $message }}</small> @enderror
					</div>
					<div class="col-md-6">
						<label class="form-label">Reference</label>
						<input type="text" name="reference" value="{{ old('reference') }}" class="form-control">
						@error('reference') <small class="text-danger">{{ $message }}</small> @enderror
					</div>
					<div class="col-md-6">
						<label class="form-label">Notes</label>
						<input type="text" name="notes" value="{{ old('notes') }}" class="form-control">
						@error('notes') <small class="text-danger">{{ $message }}</small> @enderror
					</div>
					<div class="col-12">
						<button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Save Payment</button>
						<a href="{{ route('loans.show', $loan->encoded_id) }}" class="btn btn-secondary">Cancel</a>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
@endsection


