@extends('layouts.main')

@section('title', 'Bulk Repayment - Salary Advances')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Salary Advances', 'url' => route('hr.salary-advances.index'), 'icon' => 'bx bx-credit-card'],
                ['label' => 'Bulk Repayment', 'url' => '#', 'icon' => 'bx bx-upload']
            ]" />

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0 text-uppercase">Bulk Repayment</h4>
                        <a href="{{ route('hr.salary-advances.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Salary Advances
                        </a>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('bulk_errors') && count(session('bulk_errors')) > 0)
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bx bx-error-circle me-2"></i>
                    <strong>Row-level errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach(session('bulk_errors') as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bx bx-error-circle me-2"></i>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-primary mb-3">
                                <i class="bx bx-download me-2"></i>Step 1: Download CSV template
                            </h5>
                            <p class="text-muted mb-3">
                                The CSV lists all employees with an active salary advance and remaining balance. 
                                Fill in the <strong>amount_to_repay</strong> column for each advance you want to repay. 
                                Leave it empty or 0 to skip that row.
                            </p>
                            <a href="{{ route('hr.salary-advances.bulk-repayment.download-template') }}" class="btn btn-success">
                                <i class="bx bx-download me-1"></i>Download CSV template
                                @if($activeCount > 0)
                                    <span class="badge bg-light text-dark ms-2">{{ $activeCount }} advance(s)</span>
                                @endif
                            </a>
                            @if($activeCount === 0)
                                <p class="text-muted small mt-2 mb-0">No active advances with remaining balance. Create advances first.</p>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h5 class="card-title text-primary mb-3">
                                <i class="bx bx-upload me-2"></i>Step 2: Fill amounts and upload
                            </h5>
                            <form action="{{ route('hr.salary-advances.bulk-repayment.process') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                        <input type="date" name="date" id="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="bank_account_id" class="form-label">Receive into (Bank/Cash) <span class="text-danger">*</span></label>
                                        <select name="bank_account_id" id="bank_account_id" class="form-select select2-single" required>
                                            <option value="">Select account</option>
                                            @foreach($bankAccounts as $account)
                                                <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                                                    {{ $account->name }} ({{ $account->account_number }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="csv_file" class="form-label">CSV file (with amount_to_repay filled) <span class="text-danger">*</span></label>
                                        <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv,.txt" required>
                                        <div class="form-text">Use the template above. Do not change column headers. Max 2MB.</div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-upload me-1"></i>Process bulk repayment
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="text-uppercase text-muted mb-3">CSV columns</h6>
                            <ul class="list-unstyled small mb-0">
                                <li><strong>advance_reference</strong> — Do not change</li>
                                <li><strong>employee_number</strong> — Informational</li>
                                <li><strong>employee_name</strong> — Informational</li>
                                <li><strong>remaining_balance</strong> — Informational</li>
                                <li><strong>amount_to_repay</strong> — <span class="text-success">Fill this with the amount to repay (e.g. 50000). Leave empty to skip.</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
