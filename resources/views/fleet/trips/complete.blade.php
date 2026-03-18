@extends('layouts.main')

@section('title', 'Complete Trip: ' . $trip->trip_number)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Trip Planning', 'url' => route('fleet.trips.index'), 'icon' => 'bx bx-trip'],
            ['label' => 'Complete: ' . $trip->trip_number, 'url' => '#', 'icon' => 'bx bx-check']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-check me-2"></i>Complete Trip</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.trips.complete.store', $trip->hash_id) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Actual End Date & Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="actual_end_date" class="form-control" value="{{ old('actual_end_date', now()->format('Y-m-d\TH:i')) }}" required>
                                @error('actual_end_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Odometer (km) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="end_odometer" id="end_odometer" class="form-control" value="{{ old('end_odometer') }}" required>
                                @error('end_odometer')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Actual Distance (km)</label>
                                <input type="number" step="0.01" min="0" name="actual_distance_km" id="actual_distance_km" class="form-control" value="{{ old('actual_distance_km') }}" readonly>
                                <div class="form-text">Auto-calculated from start and end odometer</div>
                                @error('actual_distance_km')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Notes / Trip Remarks</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes or remarks for this trip completion">{{ old('notes', $trip->notes) }}</textarea>
                                @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.trips.show', $trip->hash_id) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Complete Trip</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    var startOdometer = {{ json_encode($trip->start_odometer ?? null) }};
    var endInput = document.getElementById('end_odometer');
    var distanceInput = document.getElementById('actual_distance_km');
    if (!endInput || !distanceInput) return;
    function calc() {
        var end = parseFloat(endInput.value) || 0;
        if (startOdometer != null && !isNaN(startOdometer) && end >= startOdometer) {
            distanceInput.value = (end - startOdometer).toFixed(2);
        } else {
            distanceInput.value = '';
        }
    }
    endInput.addEventListener('input', calc);
    endInput.addEventListener('change', calc);
    if (endInput.value) calc();
});
</script>
@endpush
