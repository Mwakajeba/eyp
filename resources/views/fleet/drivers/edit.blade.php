@extends('layouts.main')

@section('title', 'Edit Driver - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Driver Master', 'url' => route('fleet.drivers.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Edit: ' . $driver->full_name, 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Driver</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.drivers.update', $driver->hash_id) }}">
                    @csrf
                    @method('PUT')

                    @include('fleet.drivers.form', ['driver' => $driver, 'users' => $users, 'vehicles' => $vehicles])

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('fleet.drivers.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-save me-1"></i>Update Driver
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
