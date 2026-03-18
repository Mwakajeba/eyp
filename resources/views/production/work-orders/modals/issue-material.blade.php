<!-- Issue Material Modal -->
<div class="modal fade" id="issueMaterialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('production.work-orders.issue-materials', $workOrder->encoded_id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Issue Materials - {{ $workOrder->wo_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Issue materials from stores to production line/WIP location.</p>
                    
                    <div id="material-issues-container">
                        @foreach($workOrder->bom as $index => $bomItem)
                            <div class="border rounded p-3 mb-3 material-issue-row">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>{{ $bomItem->materialItem->name }}</h6>
                                        <p class="text-muted mb-2">{{ $bomItem->materialItem->code }}</p>
                                        <p class="mb-2">
                                            <strong>Required:</strong> {{ number_format($bomItem->required_quantity, 3) }} {{ $bomItem->unit_of_measure }}<br>
                                            <small class="text-muted">Variance: ±{{ $bomItem->variance_allowed }}%</small>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="hidden" name="issues[{{ $index }}][material_id]" value="{{ $bomItem->material_item_id }}">
                                        <input type="hidden" name="issues[{{ $index }}][unit_of_measure]" value="{{ $bomItem->unit_of_measure }}">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Quantity to Issue <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="issues[{{ $index }}][quantity]" 
                                                   step="0.001" min="0.001" value="{{ $bomItem->required_quantity }}" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Lot Number</label>
                                            <input type="text" class="form-control" name="issues[{{ $index }}][lot_number]" 
                                                   placeholder="Optional lot/batch number">
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Received By <span class="text-danger">*</span></label>
                                                    <select class="form-select" name="issues[{{ $index }}][received_by]" required>
                                                        <option value="">Select User</option>
                                                        @foreach(\App\Models\User::where('company_id', auth()->user()->company_id)->get() as $user)
                                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Line Location</label>
                                                    <input type="text" class="form-control" name="issues[{{ $index }}][line_location]" 
                                                           placeholder="e.g., Line 1, Knitting">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Bin Location</label>
                                            <input type="text" class="form-control" name="issues[{{ $index }}][bin_location]" 
                                                   placeholder="Storage bin/rack location">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Notes</label>
                                            <textarea class="form-control" name="issues[{{ $index }}][notes]" rows="2" 
                                                      placeholder="Additional notes"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Issue Materials</button>
                </div>
            </form>
        </div>
    </div>
</div>