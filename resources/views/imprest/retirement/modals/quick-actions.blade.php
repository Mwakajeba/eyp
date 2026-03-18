<!-- Check Modal -->
<div class="modal fade" id="checkModal" tabindex="-1" aria-labelledby="checkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkModalLabel">
                    <i class="bx bx-check-circle me-2"></i>Check Retirement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="checkForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        Review the retirement details and choose your action.
                    </div>
                    
                    <div class="mb-3">
                        <label for="checkAction" class="form-label">Action <span class="text-danger">*</span></label>
                        <select class="form-select" id="checkAction" name="action" required>
                            <option value="">Select Action</option>
                            <option value="approve">Forward for Approval</option>
                            <option value="reject">Reject Retirement</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="checkComments" class="form-label">Comments</label>
                        <textarea class="form-control" id="checkComments" name="comments" rows="3" 
                                  placeholder="Add your comments (optional for approval, required for rejection)"></textarea>
                        <small class="form-text text-muted">Comments are required when rejecting a retirement.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info" id="checkSubmitBtn">
                        <i class="bx bx-check me-1"></i>Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalLabel">
                    <i class="bx bx-check-double me-2"></i>Approve Retirement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approveForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="bx bx-info-circle me-1"></i>
                        Final approval will complete the retirement process.
                    </div>
                    
                    <div class="mb-3">
                        <label for="approveAction" class="form-label">Action <span class="text-danger">*</span></label>
                        <select class="form-select" id="approveAction" name="action" required>
                            <option value="">Select Action</option>
                            <option value="approve">Approve Retirement</option>
                            <option value="reject">Reject Retirement</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="approveComments" class="form-label">Comments</label>
                        <textarea class="form-control" id="approveComments" name="comments" rows="3" 
                                  placeholder="Add your comments (optional for approval, required for rejection)"></textarea>
                        <small class="form-text text-muted">Comments are required when rejecting a retirement.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="approveSubmitBtn">
                        <i class="bx bx-check-double me-1"></i>Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Validation for check action
    $('#checkAction').change(function() {
        const action = $(this).val();
        const commentsField = $('#checkComments');
        
        if (action === 'reject') {
            commentsField.attr('required', true);
            commentsField.closest('.mb-3').find('.form-text').addClass('text-danger').removeClass('text-muted');
        } else {
            commentsField.removeAttr('required');
            commentsField.closest('.mb-3').find('.form-text').removeClass('text-danger').addClass('text-muted');
        }
    });

    // Validation for approve action
    $('#approveAction').change(function() {
        const action = $(this).val();
        const commentsField = $('#approveComments');
        
        if (action === 'reject') {
            commentsField.attr('required', true);
            commentsField.closest('.mb-3').find('.form-text').addClass('text-danger').removeClass('text-muted');
        } else {
            commentsField.removeAttr('required');
            commentsField.closest('.mb-3').find('.form-text').removeClass('text-danger').addClass('text-muted');
        }
    });

    // Handle check form submission
    $('#checkForm').on('submit', async function(e) {
        e.preventDefault();
        
        const form = $(this);
        const action = $('#checkAction').val();
        const comments = $('#checkComments').val().trim();
        const submitBtn = $('#checkSubmitBtn');
        const originalText = submitBtn.html();
        
        // Validation
        if (!action) {
            Swal.fire({
                icon: 'warning',
                title: 'Action Required',
                text: 'Please select an action.',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        if (action === 'reject' && !comments) {
            Swal.fire({
                icon: 'warning',
                title: 'Comments Required',
                text: 'Comments are required when rejecting a retirement.',
                confirmButtonText: 'OK'
            }).then(() => {
                $('#checkComments').focus();
            });
            return;
        }
        
        // Confirm action
        const actionText = action === 'approve' ? 'forward for approval' : 'reject';
        const confirmResult = await Swal.fire({
            icon: 'question',
            title: 'Confirm Action',
            text: `Are you sure you want to ${actionText} this retirement?`,
            showCancelButton: true,
            confirmButtonColor: action === 'approve' ? '#198754' : '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: action === 'approve' ? 'Yes, Forward' : 'Yes, Reject',
            cancelButtonText: 'Cancel'
        });
        
        if (!confirmResult.isConfirmed) {
            return;
        }
        
        // Submit form
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Processing...');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#checkModal').modal('hide');
                
                // Show success message with SweetAlert
                if (response.success) {
                    const actionText = action === 'approve' ? 'forwarded for approval' : 'rejected';
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || `Retirement ${actionText} successfully!`,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Reload the table
                        if (typeof table !== 'undefined') {
                            table.ajax.reload();
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'An error occurred',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while processing the retirement.';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join(', ');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Handle approve form submission
    $('#approveForm').on('submit', async function(e) {
        e.preventDefault();
        
        const form = $(this);
        const action = $('#approveAction').val();
        const comments = $('#approveComments').val().trim();
        const submitBtn = $('#approveSubmitBtn');
        const originalText = submitBtn.html();
        
        // Validation
        if (!action) {
            Swal.fire({
                icon: 'warning',
                title: 'Action Required',
                text: 'Please select an action.',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        if (action === 'reject' && !comments) {
            Swal.fire({
                icon: 'warning',
                title: 'Comments Required',
                text: 'Comments are required when rejecting a retirement.',
                confirmButtonText: 'OK'
            }).then(() => {
                $('#approveComments').focus();
            });
            return;
        }
        
        // Confirm action
        const actionText = action === 'approve' ? 'approve' : 'reject';
        const confirmResult = await Swal.fire({
            icon: 'question',
            title: 'Confirm Action',
            text: `Are you sure you want to ${actionText} this retirement?`,
            showCancelButton: true,
            confirmButtonColor: action === 'approve' ? '#198754' : '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: action === 'approve' ? 'Yes, Approve' : 'Yes, Reject',
            cancelButtonText: 'Cancel'
        });
        
        if (!confirmResult.isConfirmed) {
            return;
        }
        
        // Submit form
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Processing...');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#approveModal').modal('hide');
                
                // Show success message with SweetAlert
                if (response.success) {
                    const actionText = action === 'approve' ? 'approved' : 'rejected';
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || `Retirement ${actionText} successfully!`,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Reload the table
                        if (typeof table !== 'undefined') {
                            table.ajax.reload();
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'An error occurred',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while processing the retirement.';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join(', ');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Reset forms when modals are hidden
    $('#checkModal, #approveModal').on('hidden.bs.modal', function() {
        const modal = $(this);
        modal.find('form')[0].reset();
        modal.find('select').val('');
        modal.find('textarea').removeAttr('required');
        modal.find('.form-text').removeClass('text-danger').addClass('text-muted');
    });
});
</script>