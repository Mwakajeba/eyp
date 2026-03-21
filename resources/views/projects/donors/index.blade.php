@extends('layouts.main')
@section('title', 'Donor Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Project Management', 'url' => route('projects.index'), 'icon' => 'bx bx-briefcase'],
            ['label' => 'Donors', 'url' => '#', 'icon' => 'bx bx-donate-heart']
        ]" />
        <h6 class="mb-0 text-uppercase">DONOR MANAGEMENT</h6>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-top border-0 border-4 border-secondary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-light-secondary rounded p-2 me-3">
                                <i class="bx bx-donate-heart font-22 text-secondary"></i>
                            </div>
                            <div>
                                <h4 class="my-1">{{ $totalDonors }}</h4>
                                <p class="mb-0 text-muted">Total Donors</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Donor List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bx bx-group me-2"></i>Donor List</h5>
                <a href="{{ route('projects.donors.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i> Add Donor
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="donorsTable" class="table table-striped table-hover" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>Donor</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Company</th>
                                <th>Projects</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('#donorsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("projects.donors.index") }}',
        columns: [
            { data: 'donor_avatar', name: 'name', orderable: true, searchable: true },
            { data: 'formatted_phone', name: 'phone' },
            { data: 'email', name: 'email' },
            { data: 'company_name', name: 'company_name', defaultContent: 'N/A' },
            { data: 'projects_count', name: 'projects_count', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: true, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        responsive: true
    });

    $(document).on('submit', '.delete-form', function(e) {
        e.preventDefault();
        var name = $(this).find('button').data('name');
        if (confirm('Are you sure you want to delete donor "' + name + '"?')) {
            this.submit();
        }
    });
});
</script>
@endpush
