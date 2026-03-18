@extends('layouts.main')

@section('title', 'Dispatch Trip: ' . $trip->trip_number)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Trip Planning', 'url' => route('fleet.trips.index'), 'icon' => 'bx bx-trip'],
            ['label' => 'Dispatch: ' . $trip->trip_number, 'url' => '#', 'icon' => 'bx bx-send']
        ]" />

        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bx bx-send me-2"></i>Dispatch Trip</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.trips.dispatch.store', $trip->hash_id) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Actual Start Date & Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="actual_start_date" class="form-control" value="{{ old('actual_start_date', now()->format('Y-m-d\TH:i')) }}" required>
                                @error('actual_start_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Odometer (km)</label>
                                <input type="number" step="0.01" min="0" name="start_odometer" class="form-control" value="{{ old('start_odometer') }}">
                                @error('start_odometer')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Fuel Level (Liters)</label>
                                <input type="number" step="0.01" min="0" name="start_fuel_level" class="form-control" value="{{ old('start_fuel_level') }}" placeholder="e.g. 45.5">
                                <div class="form-text">Enter the fuel tank reading in liters (from vehicle gauge or last fuel log). Used with end fuel on completion to estimate consumption.</div>
                                @error('start_fuel_level')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.trips.show', $trip->hash_id) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success">Dispatch Trip</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
