<!-- Quality Check Modal -->
<div class="modal fade" id="qualityCheckModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('production.work-orders.quality-check', $workOrder->encoded_id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Quality Check - {{ $workOrder->wo_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Product:</strong> {{ $workOrder->product_name }} - {{ $workOrder->style }}<br>
                        <strong>Total Quantity:</strong> {{ $workOrder->total_quantity }} pieces
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Overall Result <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="result" id="result_pass" value="pass" required>
                            <label class="form-check-label" for="result_pass">
                                <span class="badge bg-success">Pass</span> - All items meet quality standards
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="result" id="result_rework" value="rework_required" required>
                            <label class="form-check-label" for="result_rework">
                                <span class="badge bg-warning">Rework Required</span> - Items need minor corrections
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="result" id="result_fail" value="fail" required>
                            <label class="form-check-label" for="result_fail">
                                <span class="badge bg-danger">Fail</span> - Items do not meet standards
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Quality Checklist</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="seam_strength_ok" id="seam_strength_ok" value="1" checked>
                                <label class="form-check-label" for="seam_strength_ok">
                                    Seam Strength OK
                                </label>
                            </div>
                            @if($workOrder->requires_logo)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="logo_position_ok" id="logo_position_ok" value="1" checked>
                                    <label class="form-check-label" for="logo_position_ok">
                                        Logo Position OK
                                    </label>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6>Measurements Check</h6>
                            <div class="mb-3">
                                <label class="form-label">Chest Measurement (cm)</label>
                                <input type="number" class="form-control" name="measurements[chest]" step="0.1" placeholder="e.g., 102.5">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Length Measurement (cm)</label>
                                <input type="number" class="form-control" name="measurements[length]" step="0.1" placeholder="e.g., 68.0">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sleeve Length (cm)</label>
                                <input type="number" class="form-control" name="measurements[sleeve_length]" step="0.1" placeholder="e.g., 60.0">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Defect Codes (if any)</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="defect_codes[]" value="HOLE" id="defect_hole">
                                    <label class="form-check-label" for="defect_hole">Hole/Tear</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="defect_codes[]" value="STAIN" id="defect_stain">
                                    <label class="form-check-label" for="defect_stain">Stain/Mark</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="defect_codes[]" value="SEAM" id="defect_seam">
                                    <label class="form-check-label" for="defect_seam">Seam Issue</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="defect_codes[]" value="SIZE" id="defect_size">
                                    <label class="form-check-label" for="defect_size">Size Issue</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="defect_codes[]" value="COLOR" id="defect_color">
                                    <label class="form-check-label" for="defect_color">Color Variation</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="defect_codes[]" value="LOGO" id="defect_logo">
                                    <label class="form-check-label" for="defect_logo">Logo Issue</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="defect_codes[]" value="SHAPE" id="defect_shape">
                                    <label class="form-check-label" for="defect_shape">Shape/Fit Issue</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="defect_codes[]" value="OTHER" id="defect_other">
                                    <label class="form-check-label" for="defect_other">Other</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="rework_notes_container" style="display: none;">
                        <label class="form-label">Rework Notes</label>
                        <textarea class="form-control" name="rework_notes" rows="3" 
                                  placeholder="Describe what needs to be fixed or corrected"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Submit Quality Check</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
// Show/hide rework notes based on result selection
document.addEventListener('DOMContentLoaded', function() {
    const resultInputs = document.querySelectorAll('input[name="result"]');
    const reworkNotesContainer = document.getElementById('rework_notes_container');

    resultInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            if (this.value === 'rework_required' || this.value === 'fail') {
                reworkNotesContainer.style.display = 'block';
                reworkNotesContainer.querySelector('textarea').required = true;
            } else {
                reworkNotesContainer.style.display = 'none';
                reworkNotesContainer.querySelector('textarea').required = false;
            }
        });
    });
});
</script>