@extends('layouts.main')

@section('title', 'Allowances')

@section('content')
    <style>
        /* Redesigned Icon-Only Action Buttons */
        .icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            padding: 0;
            border: none;
            background: #f8fafc;
            color: #0d6efd;
            box-shadow: 0 1px 4px rgba(13, 110, 253, 0.08);
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .icon-btn:hover {
            background: #0d6efd;
            color: #fff;
            box-shadow: 0 2px 8px rgba(13, 110, 253, 0.18);
        }
        .icon-btn:active {
            background: #0a58ca;
            color: #fff;
        }
        .icon-btn.bg-danger {
            color: #dc3545;
        }
        .icon-btn.bg-danger:hover {
            background: #dc3545;
            color: #fff;
        }
        .icon-btn.bg-primary {
            color: #0d6efd;
        }
        .icon-btn.bg-primary:hover {
            background: #0d6efd;
            color: #fff;
        }
    </style>
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Allowances', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Allowances</h6>
                            <a href="{{ route('hr.allowances.create') }}" class="btn btn-primary">
                                <i class="bx bx-plus"></i> Add Allowance
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
                                            <th>Employee</th>
                                            <th>Allowance Type</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($allowances as $index => $allowance)
                                            <tr>
                                                <td>{{ $allowances->firstItem() + $index }}</td>
                                                <td>
                                                    <div>
                                                        <strong>{{ $allowance->employee->full_name }}</strong>
                                                        <br><small
                                                            class="text-muted">{{ $allowance->employee->employee_number }}</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong>{{ $allowance->allowanceType->name }}</strong>
                                                        @if($allowance->allowanceType->code)
                                                            <br><small
                                                                class="text-muted">{{ $allowance->allowanceType->code }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>{{ $allowance->date->format('M d, Y') }}</td>
                                                <td>
                                                    <strong>{{ $allowance->formatted_amount }}</strong>
                                                    @if($allowance->description)
                                                        <br><small
                                                            class="text-muted">{{ Str::limit($allowance->description, 30) }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ $allowance->is_active ? 'success' : 'secondary' }}">
                                                        {{ $allowance->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group" role="group">
                                                            <a href="{{ route('hr.allowances.edit', $allowance->encoded_id) }}"
                                                                class="icon-btn bg-primary" title="Edit Allowance">
                                                                <i class="bx bx-edit"></i>
                                                            </a>
                                                            <button class="icon-btn bg-danger"
                                                                onclick="deleteAllowance('{{ $allowance->encoded_id }}', '{{ $allowance->employee->full_name }} - {{ $allowance->allowanceType->name }}')"
                                                                title="Delete Allowance">
                                                                <i class="bx bx-trash"></i>
                                                            </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="bx bx-money" style="font-size: 3rem; opacity: 0.3;"></i>
                                                        <p class="mt-2 mb-0">No allowances found</p>
                                                        <small>Click "Add Allowance" to create your first allowance</small>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if($allowances->hasPages())
                                <div class="d-flex justify-content-center mt-3">
                                    {{ $allowances->links() }}
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
        function deleteAllowance(allowanceId, allowanceName) {
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete the allowance "${allowanceName}". This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('delete-form');
                    form.action = `/hr-payroll/allowances/${allowanceId}`;
                    form.submit();
                }
            });
        }
    </script>
@endsection