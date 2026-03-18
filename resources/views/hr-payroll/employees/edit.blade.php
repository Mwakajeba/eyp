@extends('layouts.main')

@section('title', 'Edit Employee')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Employees', 'url' => route('hr.employees.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">Edit Employee</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.employees.update', $employee) }}">
                    @csrf
                    @method('PUT')
                    @include('hr-payroll.employees._form')
                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-primary"><i class="bx bx-save me-1"></i>Save Changes</button>
                        <a href="{{ route('hr.employees.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
