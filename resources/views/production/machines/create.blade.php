@extends('layouts.main')

@section('title', 'Add Production Machine')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Production Management', 'url' => '#', 'icon' => 'bx bx-cog'],
            ['label' => 'Production Machines', 'url' => route('production.machines.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Add Machine', 'url' => route('production.machines.create'), 'icon' => 'bx bx-plus']
        ]" />

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('production.machines.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="machine_name" class="form-label">Machine Name <span class="text-danger">*</span></label>
                                    <input type="text" name="machine_name" id="machine_name" 
                                           class="form-control @error('machine_name') is-invalid @enderror" 
                                           value="{{ old('machine_name') }}" required>
                                    @error('machine_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="production_stage" class="form-label">Production Stage</label>
                                    <select name="production_stage" id="production_stage" 
                                            class="form-select @error('production_stage') is-invalid @enderror">
                                        <option value="">Select Stage</option>
                                        @foreach(\App\Models\ProductionMachine::getProductionStages() as $value => $label)
                                            <option value="{{ $value }}" {{ old('production_stage') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('production_stage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                    <input type="text" name="location" id="location" 
                                           class="form-control @error('location') is-invalid @enderror" 
                                           value="{{ old('location') }}" required 
                                           placeholder="e.g., Knitting Section A">
                                    @error('location')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="gauge" class="form-label">Gauge</label>
                                    <input type="text" name="gauge" id="gauge" 
                                           class="form-control @error('gauge') is-invalid @enderror" 
                                           value="{{ old('gauge') }}" 
                                           placeholder="e.g., 12GG (for knitting machines)">
                                    <small class="text-muted">Only applicable for knitting machines</small>
                                    @error('gauge')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status" 
                                            class="form-select @error('status') is-invalid @enderror" required>
                                        <option value="">Select Status</option>
                                        <option value="new" {{ old('status') == 'new' ? 'selected' : '' }}>New</option>
                                        <option value="used" {{ old('status') == 'used' ? 'selected' : '' }}>Used</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="purchased_date" class="form-label">Purchased Date <span class="text-danger">*</span></label>
                                    <input type="date" name="purchased_date" id="purchased_date" 
                                           class="form-control @error('purchased_date') is-invalid @enderror" 
                                           value="{{ old('purchased_date') }}" required>
                                    @error('purchased_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-success">
                                        <i class="mdi mdi-check me-1"></i> Save Machine
                                    </button>
                                    <a href="{{ route('production.machines.index') }}" class="btn btn-light ms-2">
                                        <i class="mdi mdi-arrow-left me-1"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Production Stages</h5>
                    <div class="stage-info">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-primary me-2">KNITTING</span>
                            <span class="text-muted">Yarn to panels</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-warning me-2">CUTTING</span>
                            <span class="text-muted">Shape panels</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-info me-2">JOINING</span>
                            <span class="text-muted">Stitch pieces</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-purple me-2">EMBROIDERY</span>
                            <span class="text-muted">Add logos</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-secondary me-2">IRONING_FINISHING</span>
                            <span class="text-muted">Press & finish</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-dark me-2">PACKAGING</span>
                            <span class="text-muted">Final packaging</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Show/hide gauge field based on production stage
    $('#production_stage').change(function() {
        var stage = $(this).val();
        var gaugeGroup = $('#gauge').closest('.mb-3');
        
        if (stage === 'KNITTING') {
            gaugeGroup.show();
            $('#gauge').attr('placeholder', 'e.g., 12GG, 14GG');
        } else {
            gaugeGroup.hide();
            $('#gauge').val('');
        }
    });
    
    // Trigger change event on page load
    $('#production_stage').trigger('change');
});
</script>
@endpush
