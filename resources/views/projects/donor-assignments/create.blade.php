@extends('layouts.main')

@section('title', 'Assign Donor to Project')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Project Management', 'url' => route('projects.index'), 'icon' => 'bx bx-briefcase'],
            ['label' => 'Donor Assignment', 'url' => '#', 'icon' => 'bx bx-link']
        ]" />

        <h6 class="mb-0 text-uppercase">Assign Donor (Customer) to Project</h6>
        <hr>

        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('projects.donor-assignments.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Donor Project</label>
                            <select class="form-select" name="project_id" required>
                                <option value="">Select donor project</option>
                                @foreach($donorProjects as $project)
                                <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>
                                    {{ $project->project_code }} - {{ $project->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Donor Customer</label>
                            <select class="form-select" name="customer_id" required>
                                <option value="">Select customer</option>
                                @foreach($donorCustomers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-info"><i class="bx bx-link me-1"></i>Assign Donor</button>
                        <a href="{{ route('projects.project.index') }}" class="btn btn-light">Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
