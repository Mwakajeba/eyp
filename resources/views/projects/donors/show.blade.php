@extends('layouts.main')
@section('title', 'Donor Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Project Management', 'url' => route('projects.index'), 'icon' => 'bx bx-briefcase'],
            ['label' => 'Donors', 'url' => route('projects.donors.index'), 'icon' => 'bx bx-donate-heart'],
            ['label' => $donor->name, 'url' => '#', 'icon' => 'bx bx-user']
        ]" />
        <h6 class="mb-0 text-uppercase">DONOR DETAILS</h6>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="row">
            <!-- Donor Profile -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="avatar avatar-xl bg-secondary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:80px; height:80px;">
                            <span class="text-white fw-bold" style="font-size:2rem;">{{ strtoupper(substr($donor->name, 0, 1)) }}</span>
                        </div>
                        <h5>{{ $donor->name }}</h5>
                        @php
                            $badgeClass = match($donor->status) {
                                'active' => 'bg-success',
                                'inactive' => 'bg-secondary',
                                'suspended' => 'bg-warning',
                                default => 'bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }} mb-3">{{ ucfirst($donor->status) }}</span>

                        @if($donor->description)
                        <p class="text-muted">{{ $donor->description }}</p>
                        @endif

                        <div class="d-flex justify-content-center gap-2 mt-3">
                            <a href="{{ route('projects.donors.edit', \Vinkla\Hashids\Facades\Hashids::encode($donor->id)) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bx bx-edit me-1"></i> Edit
                            </a>
                            <a href="{{ route('projects.donors.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bx bx-arrow-back me-1"></i> Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Donor Info -->
            <div class="col-lg-8 mb-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Phone:</strong>
                                <p class="mb-0">{{ $donor->phone ?: 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Email:</strong>
                                <p class="mb-0">{{ $donor->email ?: 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Customer No:</strong>
                                <p class="mb-0">{{ $donor->customerNo }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Created:</strong>
                                <p class="mb-0">{{ $donor->created_at->format('Y-m-d H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if($donor->company_name || $donor->tin_number || $donor->vat_number || $donor->company_registration_number)
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bx bx-building me-2"></i>Organization Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Organization Name:</strong>
                                <p class="mb-0">{{ $donor->company_name ?: 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Registration No:</strong>
                                <p class="mb-0">{{ $donor->company_registration_number ?: 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>TIN Number:</strong>
                                <p class="mb-0">{{ $donor->tin_number ?: 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>VAT Number:</strong>
                                <p class="mb-0">{{ $donor->vat_number ?: 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Assigned Projects -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bx bx-briefcase me-2"></i>Assigned Projects ({{ $projects->count() }})</h5>
                    </div>
                    <div class="card-body">
                        @if($projects->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Project Code</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($projects as $project)
                                    <tr>
                                        <td><span class="badge bg-primary">{{ $project->project_code }}</span></td>
                                        <td>{{ $project->name }}</td>
                                        <td><span class="badge bg-info">{{ ucfirst($project->type) }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center text-muted py-3">
                            <i class="bx bx-info-circle fs-3"></i>
                            <p class="mb-0 mt-2">No projects assigned to this donor yet.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
