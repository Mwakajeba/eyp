@extends('layouts.main')
@section('title', 'Edit Donor')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Project Management', 'url' => route('projects.index'), 'icon' => 'bx bx-briefcase'],
            ['label' => 'Donors', 'url' => route('projects.donors.index'), 'icon' => 'bx bx-donate-heart'],
            ['label' => 'Edit Donor', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT DONOR: {{ $donor->name }}</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                @include('projects.donors.form')
            </div>
        </div>
    </div>
</div>
@endsection
