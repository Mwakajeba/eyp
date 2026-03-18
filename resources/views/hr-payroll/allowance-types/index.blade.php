@extends('layouts.main')

@section('title', 'Allowance Types')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Payroll Settings', 'url' => route('hr.payroll-settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Allowance Types', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Allowance Types</h6>
                            <a href="{{ route('hr.allowance-types.create') }}" class="btn btn-primary">
                                <i class="bx bx-plus"></i> Add Allowance Type
                            </a>
                        </div>
                        <div class="card-body">
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Code</th>
                                            <th>Type</th>
                                            <th>Default Value</th>
                                            <th>Taxable</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($allowanceTypes as $index => $allowanceType)
                                            <tr>
                                                <td>{{ $allowanceTypes->firstItem() + $index }}</td>
                                                <td>
                                                    <div>
                                                        <strong>{{ $allowanceType->name }}</strong>
                                                        @if($allowanceType->description)
                                                            <br><small
                                                                class="text-muted">{{ Str::limit($allowanceType->description, 50) }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($allowanceType->code)
                                                        <span class="badge bg-light text-dark">{{ $allowanceType->code }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ $allowanceType->type === 'fixed' ? 'primary' : 'info' }}">
                                                        {{ ucfirst($allowanceType->type) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($allowanceType->type === 'fixed')
                                                        TZS {{ number_format($allowanceType->default_amount, 2) }}
                                                    @else
                                                        {{ $allowanceType->default_percentage }}%
                                                    @endif
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ $allowanceType->is_taxable ? 'success' : 'secondary' }}">
                                                        {{ $allowanceType->is_taxable ? 'Yes' : 'No' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ $allowanceType->is_active ? 'success' : 'secondary' }}">
                                                        {{ $allowanceType->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('hr.allowance-types.edit', $allowanceType->encoded_id) }}"
                                                            class="btn btn-sm btn-outline-primary" title="Edit">
                                                            <i class="bx bx-edit"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteAllowanceType('{{ $allowanceType->encoded_id }}', '{{ $allowanceType->name }}')"
                                                            title="Delete">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="bx bx-money" style="font-size: 3rem; opacity: 0.3;"></i>
                                                        <p class="mt-2 mb-0">No allowance types found</p>
                                                        <small>Click "Add Allowance Type" to create your first allowance
                                                            type</small>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if($allowanceTypes->hasPages())
                                <div class="d-flex justify-content-center mt-3">
                                    {{ $allowanceTypes->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="delete-form" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        function deleteAllowanceType(allowanceTypeId, allowanceTypeName) {
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete the allowance type "${allowanceTypeName}". This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('delete-form');
                    form.action = `/hr-payroll/allowance-types/${allowanceTypeId}`;
                    form.submit();
                }
            });
        }
    </script>
@endsection