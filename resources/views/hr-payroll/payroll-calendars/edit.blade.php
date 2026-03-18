@extends('layouts.main')

@section('title', 'Edit Payroll Calendar')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Payroll Calendars', 'url' => route('hr.payroll-calendars.index'), 'icon' => 'bx bx-calendar'],
                ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-calendar me-2"></i>Edit Payroll Calendar</h5>
                    <p class="mb-0 text-muted">Update payroll period details</p>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('hr.payroll-calendars.update', $payrollCalendar->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="alert alert-info">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <strong>Period:</strong> {{ $payrollCalendar->period_label }} (Year and Month cannot be changed)
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Calendar Year</label>
                                            <input type="text" class="form-control" 
                                                value="{{ $payrollCalendar->calendar_year }}" disabled>
                                            <small class="text-muted">Cannot be changed</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Payroll Month</label>
                                            <input type="text" class="form-control" 
                                                value="{{ $payrollCalendar->month_name }}" disabled>
                                            <small class="text-muted">Cannot be changed</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cut_off_date" class="form-label">Cut-off Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('cut_off_date') is-invalid @enderror" 
                                                id="cut_off_date" name="cut_off_date" 
                                                value="{{ old('cut_off_date', $payrollCalendar->cut_off_date->format('Y-m-d')) }}" required>
                                            @error('cut_off_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="pay_date" class="form-label">Pay Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('pay_date') is-invalid @enderror" 
                                                id="pay_date" name="pay_date" 
                                                value="{{ old('pay_date', $payrollCalendar->pay_date->format('Y-m-d')) }}" required>
                                            @error('pay_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                        id="notes" name="notes" rows="3">{{ old('notes', $payrollCalendar->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('hr.payroll-calendars.index') }}" class="btn btn-secondary">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i>Update Calendar
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

