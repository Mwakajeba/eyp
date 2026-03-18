@extends('layouts.main')

@section('title', 'Create Payroll Calendar')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Payroll Calendars', 'url' => route('hr.payroll-calendars.index'), 'icon' => 'bx bx-calendar'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-calendar me-2"></i>Create Payroll Calendar</h5>
                    <p class="mb-0 text-muted">Define payroll period with cut-off and pay dates</p>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('hr.payroll-calendars.store') }}" method="POST">
                                @csrf

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="calendar_year" class="form-label">Calendar Year <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('calendar_year') is-invalid @enderror" 
                                                id="calendar_year" name="calendar_year" 
                                                value="{{ old('calendar_year', date('Y')) }}" 
                                                min="2020" max="2100" required>
                                            @error('calendar_year')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">The year for this payroll period</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="payroll_month" class="form-label">Payroll Month <span class="text-danger">*</span></label>
                                            <select class="form-select @error('payroll_month') is-invalid @enderror" 
                                                id="payroll_month" name="payroll_month" required>
                                                <option value="">Select Month</option>
                                                @for($i = 1; $i <= 12; $i++)
                                                    <option value="{{ $i }}" {{ old('payroll_month') == $i ? 'selected' : '' }}>
                                                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                                    </option>
                                                @endfor
                                            </select>
                                            @error('payroll_month')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">The month for this payroll period</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cut_off_date" class="form-label">Cut-off Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('cut_off_date') is-invalid @enderror" 
                                                id="cut_off_date" name="cut_off_date" 
                                                value="{{ old('cut_off_date') }}" required>
                                            @error('cut_off_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Last date for payroll data inclusion (e.g., 25th of month)</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="pay_date" class="form-label">Pay Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('pay_date') is-invalid @enderror" 
                                                id="pay_date" name="pay_date" 
                                                value="{{ old('pay_date') }}" required>
                                            @error('pay_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Actual payment date (must be after cut-off date)</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                        id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Optional notes about this payroll period</small>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('hr.payroll-calendars.index') }}" class="btn btn-secondary">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i>Create Calendar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

