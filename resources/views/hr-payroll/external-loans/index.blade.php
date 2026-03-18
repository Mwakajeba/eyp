@extends('layouts.main')

@section('title', 'External Loans')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'External Loans', 'url' => '#', 'icon' => 'bx bx-credit-card-alt']
        ]" />
            <h6 class="mb-0 text-uppercase">External Loans</h6>
            <hr />

            <div class="d-flex justify-content-between mb-3">
                <div></div>
                <a href="{{ route('hr.external-loans.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>New
                    External Loan</a>
            </div>

            <div class="card">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bx bx-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table id="external-loans-table" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Institution</th>
                                    <th>Total Loan</th>
                                    <th>Monthly Deduction</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($loans as $index => $loan)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $loan->employee->full_name ?? '—' }}</td>
                                        <td>{{ $loan->institution_name }}</td>
                                        <td>{{ number_format($loan->total_loan, 2) }}</td>
                                        <td>
                                            @php
                                                $deductionType = $loan->deduction_type ?? 'fixed';
                                                $displayValue = number_format($loan->monthly_deduction, 2);
                                                $suffix = $deductionType === 'percentage' ? '%' : 'TZS';
                                                $badgeClass = $deductionType === 'percentage' ? 'bg-info' : 'bg-primary';
                                                $badgeText = $deductionType === 'percentage' ? 'Percentage' : 'Fixed';
                                            @endphp
                                            <div>
                                                <span class="fw-bold">{{ $displayValue }}</span>
                                                <span class="text-muted ms-1">{{ $suffix }}</span>
                                            </div>
                                            <small>
                                                <span class="badge {{ $badgeClass }}">{{ $badgeText }}</span>
                                            </small>
                                        </td>
                                        <td>{{ optional($loan->date)->format('Y-m-d') }}</td>
                                        <td>{{ optional($loan->date_end_of_loan)->format('Y-m-d') ?: '—' }}</td>
                                        <td>
                                            @if($loan->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('hr.external-loans.show', $loan->encoded_id) }}"
                                                    class="btn btn-sm btn-outline-info" title="View">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                            <a href="{{ route('hr.external-loans.edit', $loan->encoded_id) }}"
                                                    class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                            <button
                                                onclick="deleteLoan('{{ $loan->encoded_id }}', '{{ $loan->institution_name }}')"
                                                    class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <form id="delete-form" method="POST" style="display:none">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        $(function () {
            $('#external-loans-table').DataTable({
                order: [[0, 'asc']],
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                stateSave: true
            });
        });

        function deleteLoan(id, institutionName) {
            Swal.fire({
                title: 'Delete external loan?',
                html: `You are about to delete loan from <strong>${institutionName}</strong>. This cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel'
            }).then((res) => {
                if (res.isConfirmed) {
                    const form = document.getElementById('delete-form');
                    form.action = `/hr-payroll/external-loans/${id}`;
                    form.submit();
                }
            });
        }
    </script>
@endsection