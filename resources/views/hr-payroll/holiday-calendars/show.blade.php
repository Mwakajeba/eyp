@extends('layouts.main')

@section('title', 'Holiday Calendar Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Holiday Calendars', 'url' => route('hr.holiday-calendars.index'), 'icon' => 'bx bx-calendar-heart'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">Holiday Calendar: {{ $holidayCalendar->calendar_name }}</h6>
        <hr />
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Calendar Information</h6>
                        <p><strong>Name:</strong> {{ $holidayCalendar->calendar_name }}</p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-{{ $holidayCalendar->is_active ? 'success' : 'secondary' }}">
                                {{ $holidayCalendar->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </p>
                        <p><strong>Total Holidays:</strong> {{ $holidays->count() }}</p>
                        <div class="d-flex gap-2 mt-3">
                            <a href="{{ route('hr.holiday-calendars.edit', $holidayCalendar->id) }}" class="btn btn-sm btn-primary">
                                <i class="bx bx-edit me-1"></i>Edit
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Holidays ({{ $holidays->count() }})</h6>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#seedHolidaysModal">
                                <i class="bx bx-download me-1"></i>Seed Tanzania Holidays
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
                                <i class="bx bx-plus me-1"></i>Add Holiday
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Holiday Name</th>
                                        <th>Type</th>
                                        <th>Paid</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($holidays as $holiday)
                                    <tr>
                                        <td>{{ $holiday->holiday_date->format('d M Y') }}</td>
                                        <td>{{ $holiday->holiday_name }}</td>
                                        <td><span class="badge bg-info">{{ ucfirst($holiday->holiday_type) }}</span></td>
                                        <td><span class="badge bg-{{ $holiday->is_paid ? 'success' : 'warning' }}">{{ $holiday->is_paid ? 'Yes' : 'No' }}</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-danger delete-holiday-btn" data-id="{{ $holiday->id }}" data-name="{{ $holiday->holiday_name }}">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No holidays added yet</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Holiday Modal -->
<div class="modal fade" id="addHolidayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addHolidayForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Holiday Date <span class="text-danger">*</span></label>
                        <input type="date" name="holiday_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Holiday Name <span class="text-danger">*</span></label>
                        <input type="text" name="holiday_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Holiday Type <span class="text-danger">*</span></label>
                        <select name="holiday_type" class="form-select select2-single" required>
                            <option value="public">Public Holiday</option>
                            <option value="company">Company Holiday</option>
                            <option value="regional">Regional Holiday</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_paid" id="is_paid" value="1" checked>
                            <label class="form-check-label" for="is_paid">Paid Holiday</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Holiday</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Seed Tanzania Holidays Modal -->
<div class="modal fade" id="seedHolidaysModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seed Tanzania Public Holidays</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="seedHolidaysForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        This will import Tanzania public holidays from the seeder. Existing holidays for the selected year will be skipped unless you choose to overwrite them.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year <span class="text-danger">*</span></label>
                        <select name="year" class="form-select select2-single" required>
                            @for($y = now()->year - 1; $y <= now()->year + 2; $y++)
                                <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="overwrite_existing" id="overwrite_existing" value="1">
                            <label class="form-check-label" for="overwrite_existing">
                                Overwrite existing holidays for this year
                            </label>
                        </div>
                        <div class="form-text">If checked, existing holidays for the selected year will be updated. Otherwise, they will be skipped.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-download me-1"></i>Seed Holidays
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#addHolidayModal, #seedHolidaysModal'),
        placeholder: function() {
            return $(this).data('placeholder') || '-- Select --';
        },
        allowClear: true
    });

    // Seed Holidays Form
    $('#seedHolidaysForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        const form = $(this);
        
        console.log('Seed form submitted');
        console.log('Form data:', form.serialize());
        console.log('Route URL:', '{{ route("hr.holiday-calendars.seed-tanzania", $holidayCalendar->id) }}');
        
        submitBtn.html('<i class="bx bx-loader-alt bx-spin me-1"></i>Seeding...');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: '{{ route("hr.holiday-calendars.seed-tanzania", $holidayCalendar->id) }}',
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function(response) {
                console.log('Success response:', response);
                if (response.success) {
                    $('#seedHolidaysModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Holidays seeded successfully.',
                        timer: 3000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning!',
                        text: response.message || 'Seeding completed but may have issues.'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText,
                    statusCode: xhr.status
                });
                
                let errorMessage = 'Something went wrong while seeding holidays.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        errorMessage = xhr.responseText.substring(0, 200);
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: '<div>' + errorMessage + '</div><div class="mt-2 text-muted small">Check the browser console and Laravel logs for details.</div>',
                    width: '600px'
                });
            },
            complete: function() {
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    });

    // Add Holiday Form
    $('#addHolidayForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("hr.holiday-calendars.add-holiday", $holidayCalendar->id) }}',
            type: 'POST',
            data: $(this).serialize(),
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            },
            error: function(xhr) {
                Swal.fire({icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Something went wrong.'});
            }
        });
    });

    $(document).on('click', '.delete-holiday-btn', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        Swal.fire({
            title: 'Delete Holiday',
            text: `Are you sure you want to delete "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('hr.holiday-calendars.index') }}/holidays/${id}`,
                    type: 'DELETE',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Something went wrong.'});
                    }
                });
            }
        });
    });
});
</script>
@endpush

