@extends('layouts.main')

@section('title', 'Edit Trip - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Trip Planning', 'url' => route('fleet.trips.index'), 'icon' => 'bx bx-trip'],
            ['label' => 'Edit: ' . $trip->trip_number, 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Trip</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.trips.update', $trip->hash_id) }}" id="trip-form">
                    @csrf
                    @method('PUT')

                    @include('fleet.trips.form', ['trip' => $trip, 'vehicles' => $vehicles, 'drivers' => $drivers, 'customers' => $customers])

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.trips.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bx bx-save me-1"></i>Update Trip
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
