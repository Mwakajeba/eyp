@extends('layouts.main')

@section('title', 'Create Training Bond')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Training', 'url' => '#', 'icon' => 'bx bx-book'],
            ['label' => 'Training Bonds', 'url' => route('hr.training-bonds.index'), 'icon' => 'bx bx-lock'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">Create Training Bond</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.training-bonds.store') }}" id="bondForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select select2-single @error('employee_id') is-invalid @enderror" required>
                                <option value="">-- Select Employee --</option>
                                @foreach($employees ?? [] as $employee)
                                    <option value="{{ $employee->id }}" {{ old('employee_id', $employeeId) == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }} ({{ $employee->employee_number }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Training Program <span class="text-danger">*</span></label>
                            <select name="training_program_id" class="form-select select2-single @error('training_program_id') is-invalid @enderror" required>
                                <option value="">-- Select Program --</option>
                                @foreach($programs ?? [] as $program)
                                    <option value="{{ $program->id }}" {{ old('training_program_id', $programId) == $program->id ? 'selected' : '' }}>
                                        {{ $program->program_code }} - {{ $program->program_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('training_program_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Bond Amount <span class="text-danger">*</span></label>
                            <input type="number" name="bond_amount" step="0.01" min="0" 
                                   class="form-control @error('bond_amount') is-invalid @enderror" 
                                   value="{{ old('bond_amount') }}" required placeholder="0.00" />
                            @error('bond_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Bond Period (Months) <span class="text-danger">*</span></label>
                            <input type="number" name="bond_period_months" min="1" 
                                   class="form-control @error('bond_period_months') is-invalid @enderror" 
                                   value="{{ old('bond_period_months') }}" required placeholder="e.g., 12" />
                            @error('bond_period_months')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" 
                                   value="{{ old('start_date') }}" required />
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                                   value="{{ old('end_date') }}" required />
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Auto-calculated from start date and period, or enter manually</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="fulfilled" {{ old('status') == 'fulfilled' ? 'selected' : '' }}>Fulfilled</option>
                                <option value="recovered" {{ old('status') == 'recovered' ? 'selected' : '' }}>Recovered</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Save Bond
                        </button>
                        <a href="{{ route('hr.training-bonds.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Auto-calculate end date when start date or period changes
    $('#start_date, input[name="bond_period_months"]').on('change', function() {
        let startDate = $('#start_date').val();
        let periodMonths = parseInt($('input[name="bond_period_months"]').val()) || 0;
        
        if (startDate && periodMonths > 0) {
            let start = new Date(startDate);
            let end = new Date(start);
            end.setMonth(end.getMonth() + periodMonths);
            
            let endDateStr = end.toISOString().split('T')[0];
            $('#end_date').val(endDateStr);
        }
    });
});
</script>
@endpush

