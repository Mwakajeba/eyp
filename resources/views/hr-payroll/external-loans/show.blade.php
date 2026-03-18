@extends('layouts.main')

@section('title', 'External Loan Details')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endpush

@section('content')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function () {
        $('.select2-single').select2({
            width: '100%',
            placeholder: 'Select an option',
            allowClear: true
        });
    });
</script>
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'External Loans', 'url' => route('hr.external-loans.index'), 'icon' => 'bx bx-credit-card-alt'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">External Loan Details</h6>
        <hr />
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-credit-card-alt me-2"></i>Loan Information & Schedule</h6>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Employee</dt>
                            <dd class="col-sm-8">{{ $loan->employee->full_name ?? 'N/A' }}</dd>
                            <dt class="col-sm-4">Institution</dt>
                            <dd class="col-sm-8">{{ $loan->institution_name }}</dd>
                            <dt class="col-sm-4">Total Loan</dt>
                            <dd class="col-sm-8">TZS {{ number_format($loan->total_loan, 2) }}</dd>
                            <dt class="col-sm-4">Monthly Deduction</dt>
                            <dd class="col-sm-8">TZS {{ number_format($loan->monthly_deduction, 2) }}</dd>
                            <dt class="col-sm-4">Start Date</dt>
                            <dd class="col-sm-8">{{ $loan->date ? $loan->date->format('M d, Y') : 'N/A' }}</dd>
                            <dt class="col-sm-4">End Date</dt>
                            <dd class="col-sm-8">{{ $loan->date_end_of_loan ? $loan->date_end_of_loan->format('M d, Y') : 'N/A' }}</dd>
                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-{{ $loan->is_active ? 'success' : 'secondary' }}">
                                    {{ $loan->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </dd>
                        </dl>
                        <hr>
                        <h6 class="mb-2"><i class="bx bx-calendar-check me-1"></i>Repayment Schedule</h6>
                        <div class="border-start border-3 border-primary ps-3 mb-2">
                            <span class="small">Repayment period: <strong>{{ ceil($loan->total_loan / max(1, $loan->monthly_deduction)) }} months</strong></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-money text-info me-2"></i>Repayments & Deductions</h6>
                    </div>
                    <div class="card-body">
                        <h6 class="mb-2">Total Repaid</h6>
                        <div class="border-start border-3 border-success ps-3 mb-3">
                            <span class="h6">TZS {{ number_format($loan->total_repaid ?? 0, 2) }}</span>
                        </div>
                        <h6 class="mb-2">Current Balance</h6>
                        <div class="border-start border-3 border-warning ps-3 mb-3">
                            <span class="h6">TZS {{ number_format($loan->balance ?? ($loan->total_loan - ($loan->total_repaid ?? 0)), 2) }}</span>
                        </div>
                        <h6 class="mb-2">Recent Deductions</h6>
                        <ul class="list-unstyled small mb-0">
                            @forelse($loan->deductions ?? [] as $deduction)
                                <li class="mb-1">
                                    <i class="bx bx-calendar me-1"></i>
                                    {{ $deduction->date->format('M Y') }}: TZS {{ number_format($deduction->amount, 2) }}
                                </li>
                            @empty
                                <li class="text-muted">No deductions recorded.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deduction History -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-history me-2"></i>Deduction History</h6>
                    </div>
                    <div class="card-body">
                        @if($deductionHistory && $deductionHistory->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="deductionHistoryTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Payroll Period</th>
                                            <th>Payroll Reference</th>
                                            <th class="text-end">Amount Deducted</th>
                                            <th>Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($deductionHistory as $index => $payrollEmployee)
                                            @php
                                                $payroll = $payrollEmployee->payroll;
                                                $monthName = \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F');
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <strong>{{ $monthName }} {{ $payroll->year }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ $payroll->reference ?? 'N/A' }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <strong class="text-danger">TZS {{ number_format($payrollEmployee->loans, 2) }}</strong>
                                                </td>
                                                <td>
                                                    @if($payroll->status === 'completed')
                                                        <span class="badge bg-success">Completed</span>
                                                    @elseif($payroll->status === 'paid')
                                                        <span class="badge bg-primary">Paid</span>
                                                    @elseif($payroll->status === 'processing')
                                                        <span class="badge bg-warning">Processing</span>
                                                    @elseif($payroll->status === 'draft')
                                                        <span class="badge bg-secondary">Draft</span>
                                                    @else
                                                        <span class="badge bg-danger">{{ ucfirst($payroll->status) }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('hr.payrolls.show', $payroll->hash_id) }}" 
                                                       class="btn btn-sm btn-primary" title="View Payroll">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-info">
                                            <td colspan="3" class="text-end"><strong>Total Deducted:</strong></td>
                                            <td class="text-end">
                                                <strong>TZS {{ number_format($deductionHistory->sum('loans'), 2) }}</strong>
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info mb-0">
                                <i class="bx bx-info-circle me-2"></i>No deduction history found for this loan.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($deductionHistory && $deductionHistory->count() > 0)
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        var table = $('#deductionHistoryTable').DataTable({
            order: [[1, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="bx bx-spreadsheet"></i> Excel',
                    className: 'btn btn-success btn-sm',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4],
                        format: {
                            body: function(data, row, column, node) {
                                // Remove HTML tags and badges for export
                                if (typeof data === 'string') {
                                    var $data = $('<div>').html(data);
                                    return $data.text().trim() || data;
                                }
                                return data;
                            }
                        }
                    },
                    title: 'External Loan Deduction History - {{ $loan->institution_name }}',
                    filename: 'external_loan_deduction_history_{{ $loan->institution_name }}'
                },
                {
                    extend: 'pdf',
                    text: '<i class="bx bx-file"></i> PDF',
                    className: 'btn btn-danger btn-sm',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4],
                        format: {
                            body: function(data, row, column, node) {
                                if (typeof data === 'string') {
                                    var $data = $('<div>').html(data);
                                    return $data.text().trim() || data;
                                }
                                return data;
                            }
                        }
                    },
                    title: 'External Loan Deduction History - {{ $loan->institution_name }}',
                    filename: 'external_loan_deduction_history_{{ $loan->institution_name }}',
                    orientation: 'landscape',
                    pageSize: 'A4'
                },
                {
                    extend: 'print',
                    text: '<i class="bx bx-printer"></i> Print',
                    className: 'btn btn-info btn-sm',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4],
                        format: {
                            body: function(data, row, column, node) {
                                if (typeof data === 'string') {
                                    var $data = $('<div>').html(data);
                                    return $data.text().trim() || data;
                                }
                                return data;
                            }
                        }
                    }
                }
            ],
            language: {
                processing: "Loading deduction history...",
                emptyTable: "No deduction history found",
                zeroRecords: "No matching records found"
            }
        });

        // Place buttons in the card header after DataTable is initialized
        setTimeout(function() {
            var cardHeader = $('#deductionHistoryTable').closest('.card').find('.card-header');
            var buttonsContainer = table.buttons().container();
            if (buttonsContainer.length && cardHeader.length) {
                buttonsContainer
                    .addClass('btn-group')
                    .css('margin-left', 'auto')
                    .appendTo(cardHeader);
            }
        }, 100);
    });
</script>
@endif
@endpush
@endsection
