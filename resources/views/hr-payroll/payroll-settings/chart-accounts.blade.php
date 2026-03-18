@extends('layouts.main')

@section('title', 'Payroll Chart Accounts')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Payroll Settings', 'url' => route('hr.payroll-settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Payroll Chart Accounts', 'url' => '#', 'icon' => 'bx bx-calculator']
        ]" />

            <h6 class="mb-0 text-uppercase">Payroll Chart Accounts</h6>
            <hr />

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('hr.payroll.chart-accounts.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <!-- Salary Accounts Section -->
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bx bx-money me-2"></i>Salary Accounts
                                </h6>
                            </div>
                            
                            @php
                                $salaryFields = [
                                    'salary_advance_receivable_account_id' => 'Salary Advance Receivable Account',
                                    'external_loan_payable_account_id' => 'External Loan Payable Account',
                                    'salary_payable_account_id' => 'Salary Payable Account', 
                                    'salary_expense_account_id' => 'Salary Expense Account',
                                    'allowance_expense_account_id' => 'Allowance Expense Account',
                                ];
                            @endphp

                            @foreach($salaryFields as $name => $label)
                                <div class="col-md-6">
                                    <label class="form-label">{{ $label }}</label>
                                    <select name="{{ $name }}" class="form-select select2-single">
                                        <option value="">-- Select Account --</option>
                                        @foreach($chartAccounts as $acc)
                                            <option value="{{ $acc->id }}" {{ (int) old($name, $settings->$name) === (int) $acc->id ? 'selected' : '' }}>
                                                {{ $acc->account_code }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error($name)
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endforeach

                            <!-- Statutory Deductions Section -->
                            <div class="col-12 mt-4">
                                <h6 class="text-warning border-bottom pb-2 mb-3">
                                    <i class="bx bx-calculator me-2"></i>Statutory Deductions
                                </h6>
                            </div>

                            @php
                                $statutoryFields = [
                                    'heslb_expense_account_id' => 'HESLB Expense Account',
                                    'heslb_payable_account_id' => 'HESLB Payable Account',
                                    'pension_expense_account_id' => 'Pension Expense Account',
                                    'pension_payable_account_id' => 'Pension Payable Account',
                                    'payee_expense_account_id' => 'PAYE Expense Account',
                                    'payee_payable_account_id' => 'PAYE Payable Account',
                                    'insurance_expense_account_id' => 'Insurance Expense Account',
                                    'insurance_payable_account_id' => 'Insurance Payable Account',
                                    'wcf_expense_account_id' => 'WCF Expense Account',
                                    'wcf_payable_account_id' => 'WCF Payable Account',
                                    'sdl_expense_account_id' => 'SDL Expense Account',
                                    'sdl_payable_account_id' => 'SDL Payable Account',
                                ];
                            @endphp

                            @foreach($statutoryFields as $name => $label)
                                <div class="col-md-6">
                                    <label class="form-label">{{ $label }}</label>
                                    <select name="{{ $name }}" class="form-select select2-single">
                                        <option value="">-- Select Account --</option>
                                        @foreach($chartAccounts as $acc)
                                            <option value="{{ $acc->id }}" {{ (int) old($name, $settings->$name) === (int) $acc->id ? 'selected' : '' }}>
                                                {{ $acc->account_code }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error($name)
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endforeach

                            <!-- Other Deductions Section -->
                            <div class="col-12 mt-4">
                                <h6 class="text-info border-bottom pb-2 mb-3">
                                    <i class="bx bx-list-ul me-2"></i>Other Deductions
                                </h6>
                            </div>

                            @php
                                $otherFields = [
                                    'trade_union_expense_account_id' => 'Trade Union Expense Account',
                                    'trade_union_payable_account_id' => 'Trade Union Payable Account',
                                    'other_payable_account_id' => 'Other Payable Account',
                                ];
                            @endphp

                            @foreach($otherFields as $name => $label)
                                <div class="col-md-6">
                                    <label class="form-label">{{ $label }}</label>
                                    <select name="{{ $name }}" class="form-select select2-single">
                                        <option value="">-- Select Account --</option>
                                        @foreach($chartAccounts as $acc)
                                            <option value="{{ $acc->id }}" {{ (int) old($name, $settings->$name) === (int) $acc->id ? 'selected' : '' }}>
                                                {{ $acc->account_code }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error($name)
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Save
                                Settings</button>
                            <a href="{{ route('hr.payroll-settings.index') }}" class="btn btn-secondary"><i
                                    class="bx bx-arrow-back me-1"></i> Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection