@extends('layouts.main')

@section('title', 'Add New Driver - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Driver Master', 'url' => route('fleet.drivers.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Add New Driver', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Add Driver</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.drivers.store') }}">
                    @csrf

                    @include('fleet.drivers.form', ['driver' => null, 'users' => $users, 'vehicles' => $vehicles])

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.drivers.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-save me-1"></i>Save Driver
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
