@extends('layouts.main')

@section('title', 'Create Holiday Calendar')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Holiday Calendars', 'url' => route('hr.holiday-calendars.index'), 'icon' => 'bx bx-calendar-heart'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">Create Holiday Calendar</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.holiday-calendars.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Calendar Name <span class="text-danger">*</span></label>
                            <input type="text" name="calendar_name" class="form-control @error('calendar_name') is-invalid @enderror" 
                                   value="{{ old('calendar_name') }}" required placeholder="e.g., Tanzania Public Holidays 2024" />
                            @error('calendar_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Enter a descriptive name for this holiday calendar</div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-1"></i>
                                <strong>Note:</strong> After creating the calendar, you can add holidays manually or use the "Seed Tanzania Holidays" feature from the calendar details page.
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                       value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                            <div class="form-text">Active calendars are used for holiday checking in attendance and leave management</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Create Calendar
                        </button>
                        <a href="{{ route('hr.holiday-calendars.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

