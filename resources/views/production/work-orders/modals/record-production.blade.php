<!-- Record Production Modal -->
<div class="modal fade" id="recordProductionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('production.work-orders.record-production', $workOrder->encoded_id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Record Production - {{ $workOrder->wo_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="stage" value="{{ $workOrder->status }}">
                    
                    <div class="alert alert-info">
                        <strong>Current Stage:</strong> {{ \App\Models\Production\WorkOrder::getStatuses()[$workOrder->status] }}
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Operator</label>
                                <select class="form-select" name="operator_id">
                                    <option value="">Select Operator</option>
                                    @foreach(\App\Models\User::where('company_id', auth()->user()->company_id)->get() as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Machine</label>
                                <select class="form-select" name="machine_id">
                                    <option value="">Select Machine</option>
                                    @foreach(\App\Models\ProductionMachine::where('production_stage', $workOrder->status)->get() as $machine)
                                        <option value="{{ $machine->id }}">{{ $machine->machine_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Yield Percentage</label>
                                <input type="number" class="form-control" name="yield_percentage" 
                                       step="0.1" min="0" max="100" placeholder="e.g., 95.5">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Operator Time (minutes)</label>
                                <input type="number" class="form-control" name="operator_time_minutes" 
                                       min="1" placeholder="e.g., 120">
                            </div>
                        </div>
                    </div>

                    <!-- Stage-specific fields -->
                    @if($workOrder->status === 'KNITTING')
                        <div class="stage-specific-fields">
                            <h6>Knitting Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Yarn Cones Used (kg)</label>
                                        <input type="number" class="form-control" name="input_materials[yarn_used]" 
                                               step="0.001" placeholder="e.g., 5.250">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Gauge</label>
                                        <input type="text" class="form-control" name="output_data[gauge]" 
                                               placeholder="e.g., 12GG">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Panel Counts by Size</label>
                                        <textarea class="form-control" name="output_data[panel_counts]" rows="2" 
                                                  placeholder="S: 10 panels, M: 15 panels, L: 20 panels"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Wastage (kg)</label>
                                        <input type="number" class="form-control" name="wastage_data[wastage_kg]" 
                                               step="0.001" placeholder="e.g., 0.250">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($workOrder->status === 'CUTTING')
                        <div class="stage-specific-fields">
                            <h6>Cutting Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Pieces per Size</label>
                                        <textarea class="form-control" name="output_data[pieces_per_size]" rows="2" 
                                                  placeholder="S: 10 pieces, M: 15 pieces, L: 20 pieces"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Offcuts (kg)</label>
                                        <input type="number" class="form-control" name="wastage_data[offcuts_kg]" 
                                               step="0.001" placeholder="e.g., 0.150">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($workOrder->status === 'JOINING')
                        <div class="stage-specific-fields">
                            <h6>Joining/Stitching Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Thread Cones Used</label>
                                        <input type="number" class="form-control" name="input_materials[thread_cones]" 
                                               step="0.1" placeholder="e.g., 2.5">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Rework Occurrences</label>
                                        <input type="number" class="form-control" name="wastage_data[rework_count]" 
                                               min="0" placeholder="e.g., 2">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($workOrder->status === 'EMBROIDERY')
                        <div class="stage-specific-fields">
                            <h6>Embroidery Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Stitch Count</label>
                                        <input type="number" class="form-control" name="output_data[stitch_count]" 
                                               min="1" placeholder="e.g., 15000">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Embroidery Thread Used</label>
                                        <input type="number" class="form-control" name="input_materials[embroidery_thread]" 
                                               step="0.001" placeholder="e.g., 0.250">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Rejects/Rework</label>
                                        <input type="number" class="form-control" name="wastage_data[rejects]" 
                                               min="0" placeholder="e.g., 1">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($workOrder->status === 'PACKAGING')
                        <div class="stage-specific-fields">
                            <h6>Packaging Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Polybags Used</label>
                                        <input type="number" class="form-control" name="input_materials[polybags]" 
                                               min="1" placeholder="e.g., 45">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cartons Used</label>
                                        <input type="number" class="form-control" name="input_materials[cartons]" 
                                               min="1" placeholder="e.g., 3">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" 
                                  placeholder="Additional notes about this production stage"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Record Production</button>
                </div>
            </form>
        </div>
    </div>
</div>