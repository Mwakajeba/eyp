@extends('layouts.main')

@section('title', 'Edit Production Machine')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Production Management', 'url' => '#', 'icon' => 'bx bx-cog'],
            ['label' => 'Production Machines', 'url' => route('production.machines.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Edit Machine', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT PRODUCTION MACHINE</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                @php $hashid = Vinkla\Hashids\Facades\Hashids::encode($machine->id); @endphp
                <form action="{{ route('production.machines.update', $hashid) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group mb-3">
                        <label for="machine_name">Machine Name</label>
                        <input type="text" name="machine_name" id="machine_name" class="form-control" value="{{ $machine->machine_name }}" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="purchased_date">Purchased Date</label>
                        <input type="date" name="purchased_date" id="purchased_date" class="form-control" value="{{ $machine->purchased_date }}" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="new" {{ $machine->status == 'new' ? 'selected' : '' }}>New</option>
                            <option value="used" {{ $machine->status == 'used' ? 'selected' : '' }}>Used</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="location">Location</label>
                        <input type="text" name="location" id="location" class="form-control" value="{{ $machine->location }}" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="production_stage">Production Stage</label>
                        <select name="production_stage" id="production_stage" class="form-control">
                            <option value="">Select Production Stage</option>
                            <option value="KNITTING" {{ $machine->production_stage == 'KNITTING' ? 'selected' : '' }}>Knitting</option>
                            <option value="CUTTING" {{ $machine->production_stage == 'CUTTING' ? 'selected' : '' }}>Cutting</option>
                            <option value="JOINING" {{ $machine->production_stage == 'JOINING' ? 'selected' : '' }}>Joining</option>
                            <option value="EMBROIDERY" {{ $machine->production_stage == 'EMBROIDERY' ? 'selected' : '' }}>Embroidery</option>
                            <option value="IRONING_FINISHING" {{ $machine->production_stage == 'IRONING_FINISHING' ? 'selected' : '' }}>Ironing/Finishing</option>
                            <option value="PACKAGING" {{ $machine->production_stage == 'PACKAGING' ? 'selected' : '' }}>Packaging</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3" id="gauge-field" style="{{ $machine->production_stage != 'KNITTING' ? 'display: none;' : '' }}">
                        <label for="gauge">Gauge (for Knitting Machines)</label>
                        <input type="text" name="gauge" id="gauge" class="form-control" value="{{ $machine->gauge }}" placeholder="e.g., 12GG, 14GG">
                        <small class="text-muted">Required only for knitting machines</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('production.machines.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
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
        var gaugeField = $('#gauge-field');
        
        if (stage === 'KNITTING') {
            gaugeField.show();
            $('#gauge').attr('placeholder', 'e.g., 12GG, 14GG');
        } else {
            gaugeField.hide();
            $('#gauge').val('');
        }
    });
    
    // Trigger change event on page load to set initial state
    $('#production_stage').trigger('change');
});
</script>
@endpush
