@extends('layouts.main')

@section('title', 'Create Overtime Request')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Overtime Requests', 'url' => route('hr.overtime-requests.index'), 'icon' => 'bx bx-time'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">Create Overtime Request</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.overtime-requests.store') }}" id="overtimeRequestForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" id="employee_id" class="form-select select2-single @error('employee_id') is-invalid @enderror" required>
                                <option value="">Select Employee</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id', $employee?->id) == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->full_name }} ({{ $emp->employee_number }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Overtime Date <span class="text-danger">*</span></label>
                            <input type="date" name="overtime_date" id="overtime_date" class="form-control @error('overtime_date') is-invalid @enderror" 
                                   value="{{ old('overtime_date', $date) }}" required />
                            @error('overtime_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($attendance)
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <strong>Linked Attendance:</strong> Found attendance record for this date. 
                                <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                                Clock In: {{ $attendance->clock_in ? date('H:i', strtotime($attendance->clock_in)) : 'N/A' }}, 
                                Clock Out: {{ $attendance->clock_out ? date('H:i', strtotime($attendance->clock_out)) : 'N/A' }}
                            </div>
                        </div>
                        @endif

                        <!-- Overtime Lines Section -->
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">Overtime Entries <span class="text-danger">*</span></label>
                               
                        </div>

                            <div class="table-responsive">
                                <table class="table table-bordered" id="overtimeLinesTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%;">Overtime Hours <span class="text-danger">*</span></th>
                                            <th style="width: 30%;">Day Type <span class="text-danger">*</span></th>
                                            <th style="width: 25%;">Overtime Rate</th>
                                            <th style="width: 15%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="overtimeLinesBody">
                                        <!-- Lines will be added here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" id="addOvertimeLine">
                                <i class="bx bx-plus me-1"></i>Add Line
                            </button>
                            <div class="mt-3">
                                <div class="alert alert-info mb-0">
                                    <strong>Total Overtime Hours: <span id="totalHours">0.00</span> hrs</strong>
                                </div>
                            </div>
                            <div id="overtimeLinesError" class="text-danger mt-2" style="display: none;"></div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" 
                                      rows="3">{{ old('reason') }}</textarea>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Reason for overtime work</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Submit Request
                        </button>
                        <a href="{{ route('hr.overtime-requests.index') }}" class="btn btn-secondary">Cancel</a>
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
    // Initialize Select2 for employee selection
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder') || '-- Select --';
        },
        allowClear: true
    });

    const overtimeRules = @json($overtimeRules ?? []);
    const employeeId = {{ $employee?->id ?? 'null' }};

    // Day type options
    const dayTypeOptions = [
        { value: 'weekday', label: 'Weekday (Monday - Friday)' },
        { value: 'weekend', label: 'Weekend (Saturday - Sunday)' },
        { value: 'holiday', label: 'Holiday' }
    ];

    // Add initial line if none exist
    if ($('#overtimeLinesBody tr').length === 0) {
        addOvertimeLine();
    }

    // Add overtime line
    $('#addOvertimeLine').on('click', function() {
        addOvertimeLine();
    });

    function addOvertimeLine() {
        // Get the maximum existing index and add 1
        let maxIndex = -1;
        $('#overtimeLinesBody tr').each(function() {
            const index = parseInt($(this).attr('data-line-index') || -1);
            if (index > maxIndex) maxIndex = index;
        });
        const lineIndex = maxIndex + 1;
        const row = `
            <tr data-line-index="${lineIndex}">
                <td>
                    <input type="number" 
                           name="overtime_lines[${lineIndex}][overtime_hours]" 
                           class="form-control overtime-hours" 
                           step="0.01" 
                           min="0.01" 
                           max="24" 
                           required 
                           placeholder="0.00">
                </td>
                <td>
                    <select name="overtime_lines[${lineIndex}][day_type]" 
                            class="form-select day-type-select" 
                            required>
                        <option value="">-- Select Day Type --</option>
                        ${dayTypeOptions.map(opt => 
                            `<option value="${opt.value}">${opt.label}</option>`
                        ).join('')}
                    </select>
                </td>
                <td>
                    <input type="number" 
                           name="overtime_lines[${lineIndex}][overtime_rate]" 
                           class="form-control overtime-rate" 
                           step="0.01" 
                           min="1" 
                           max="5" 
                           readonly 
                           placeholder="0.00">
                    <small class="text-muted">Auto-calculated</small>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-line" title="Remove Line">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#overtimeLinesBody').append(row);
        updateLineNumbers();
        updateTotalHours();
    }

    // Remove overtime line
    $(document).on('click', '.remove-line', function() {
        const row = $(this).closest('tr');
        row.remove();
        updateLineNumbers();
        updateTotalHours();
        
        // Ensure at least one line exists
        if ($('#overtimeLinesBody tr').length === 0) {
            addOvertimeLine();
        } else {
            // Re-index remaining rows
            reindexRows();
        }
    });

    function reindexRows() {
        // Remove empty rows first
        $('#overtimeLinesBody tr').each(function() {
            const hours = $(this).find('.overtime-hours').val();
            const dayType = $(this).find('.day-type-select').val();
            const rate = $(this).find('.overtime-rate').val();
            
            if (!hours || !dayType || !rate) {
                $(this).remove();
            }
        });
        
        // Re-index remaining rows sequentially starting from 0
        $('#overtimeLinesBody tr').each(function(index) {
            $(this).attr('data-line-index', index);
            $(this).find('input[name*="[overtime_hours]"]').attr('name', `overtime_lines[${index}][overtime_hours]`);
            $(this).find('select[name*="[day_type]"]').attr('name', `overtime_lines[${index}][day_type]`);
            $(this).find('input[name*="[overtime_rate]"]').attr('name', `overtime_lines[${index}][overtime_rate]`);
        });
    }

    // Calculate and update total hours
    function updateTotalHours() {
        let total = 0;
        $('.overtime-hours').each(function() {
            const hours = parseFloat($(this).val()) || 0;
            total += hours;
        });
        $('#totalHours').text(total.toFixed(2));
    }

    // Handle overtime hours change - update total
    $(document).on('input', '.overtime-hours', function() {
        updateTotalHours();
    });

    // Handle day type change - fetch overtime rate
    $(document).on('change', '.day-type-select', function() {
        const row = $(this).closest('tr');
        const dayType = $(this).val();
        const employeeIdSelect = $('#employee_id').val();
        const rateInput = row.find('.overtime-rate');

        if (!dayType) {
            rateInput.val('');
            return;
        }

        if (!employeeIdSelect) {
            Swal.fire({
                icon: 'warning',
                title: 'Employee Required',
                text: 'Please select an employee first to calculate the overtime rate.',
            });
            $(this).val('');
            rateInput.val('');
            return;
        }

        // Show loading
        rateInput.val('Loading...');

        // Fetch overtime rate from server
        $.ajax({
            url: "{{ route('hr.overtime-requests.get-overtime-rate') }}",
            type: 'GET',
            data: {
                day_type: dayType,
                employee_id: employeeIdSelect
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    rateInput.val(response.overtime_rate);
                } else {
                    rateInput.val('');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch overtime rate.'
                    });
                }
            },
            error: function(xhr) {
                rateInput.val('');
                let message = 'Failed to fetch overtime rate.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    });

    // Handle employee change - update rates for all lines
    $('#employee_id').on('change', function() {
        $('.day-type-select').each(function() {
            if ($(this).val()) {
                $(this).trigger('change');
            }
        });
    });

    // Form submission validation
    $('#overtimeRequestForm').on('submit', function(e) {
        // Re-index rows before submission to ensure proper array structure
        reindexRows();
        
        const linesCount = $('#overtimeLinesBody tr').length;
        const validLines = $('#overtimeLinesBody tr').filter(function() {
            const hours = parseFloat($(this).find('.overtime-hours').val()) || 0;
            const dayType = $(this).find('.day-type-select').val();
            const rate = parseFloat($(this).find('.overtime-rate').val()) || 0;
            return hours > 0 && dayType && rate > 0;
        }).length;

        if (linesCount === 0 || validLines === 0) {
            e.preventDefault();
            $('#overtimeLinesError').text('Please add at least one valid overtime entry with hours, day type, and rate.').show();
            return false;
        }

        // Ensure all required fields are filled
        let hasError = false;
        $('#overtimeLinesBody tr').each(function() {
            const hours = parseFloat($(this).find('.overtime-hours').val()) || 0;
            const dayType = $(this).find('.day-type-select').val();
            const rate = parseFloat($(this).find('.overtime-rate').val()) || 0;
            
            if (hours <= 0 || !dayType || rate <= 0) {
                hasError = true;
                $(this).addClass('border-danger');
            } else {
                $(this).removeClass('border-danger');
            }
        });

        if (hasError) {
            e.preventDefault();
            $('#overtimeLinesError').text('Please fill all fields in each overtime entry.').show();
            return false;
        }
        
        $('#overtimeLinesError').hide();
        return true;
    });

    function updateLineNumbers() {
        // This function can be used to update line numbers if needed
    }

    // Initialize rates for existing day types if employee is already selected
    if (employeeId) {
        setTimeout(function() {
            $('.day-type-select').each(function() {
                if ($(this).val()) {
                    $(this).trigger('change');
                }
            });
        }, 500);
    }
});
</script>
@endpush
