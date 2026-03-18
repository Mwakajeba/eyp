<!-- Modals for Offer Letter Workflow -->

<!-- Submit for Approval Modal -->
<div class="modal fade" id="submitApprovalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Submit Offer for Approval</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to submit this offer letter for approval? This will notify the authorized approvers.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processStatusChange('pending_approval')">Submit Now</button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    function submitForApproval(id) {
        updateOfferStatus(id, 'pending_approval', 'Submit for Approval?', 'This will lock the offer and notify approvers.', 'question', '#0d6efd');
    }
</script>
