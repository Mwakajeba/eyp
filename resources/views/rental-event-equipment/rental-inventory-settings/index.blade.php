@extends('layouts.main')

@section('title', 'Rental Inventory Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Inventory Settings', 'url' => '#', 'icon' => 'bx bx-store']
        ]" />
        <h6 class="mb-0 text-uppercase">RENTAL INVENTORY SETTINGS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bx bx-store me-2"></i>Link rental to inventory</h5>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <p class="text-muted">
                            When equipment is linked to an inventory item (in Equipment master), confirming a dispatch moves stock from <strong>Default storage</strong> to <strong>Out on Rent</strong>. Recording a return moves it back (or writes off lost items). Configure the two locations below.
                        </p>

                        <form method="POST" action="{{ route('rental-event-equipment.rental-inventory-settings.store') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Default storage location (store)</label>
                                    <select name="default_storage_location_id" class="form-select">
                                        <option value="">— Select location —</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}" {{ ($settings && $settings->default_storage_location_id == $loc->id) ? 'selected' : '' }}>
                                                {{ $loc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Stock is taken from here when dispatch is confirmed.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Out on Rent location</label>
                                    <select name="out_on_rent_location_id" class="form-select">
                                        <option value="">— Select location —</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}" {{ ($settings && $settings->out_on_rent_location_id == $loc->id) ? 'selected' : '' }}>
                                                {{ $loc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Stock is moved here when items go out on rent; back to store when returned.</div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 justify-content-end mt-4">
                                <a href="{{ route('rental-event-equipment.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-info text-white">
                                    <i class="bx bx-save me-1"></i>Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
