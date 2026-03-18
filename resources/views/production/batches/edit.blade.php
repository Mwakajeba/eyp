@extends('layouts.main')
@section('title', 'Edit Production Batch')
@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Production Batches', 'url' => route('production.batches.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Edit Batch', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT PRODUCTION BATCH</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                @include('production.batches.form')
            </div>
        </div>
    </div>
</div>
@endsection
