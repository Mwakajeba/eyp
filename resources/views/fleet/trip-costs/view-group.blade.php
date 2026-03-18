@extends('layouts.main')

@section('title', 'View Grouped Costs - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Cost Management', 'url' => route('fleet.trip-costs.index'), 'icon' => 'bx bx-money'],
            ['label' => 'View Grouped Costs', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-money me-2"></i>Grouped Trip Costs</h6>
                        <div>
                            @php
                                $costIds = $costs->pluck('hash_id')->toArray();
                                $allPending = $costs->every(function($cost) {
                                    return $cost->approval_status === 'pending';
                                });
                            @endphp
                            @if($allPending)
                                <button type="button" class="btn btn-success btn-sm me-2" id="approve-all-btn" data-cost-ids="{{ implode(',', $costIds) }}" data-cost-count="{{ count($costIds) }}">
                                    <i class="bx bx-check-circle me-1"></i>Approve All
                                </button>
                                <a href="{{ route('fleet.trip-costs.batch-edit', ['cost_ids' => implode(',', $costIds)]) }}" class="btn btn-light btn-sm me-2">
                                    <i class="bx bx-edit me-1"></i>Edit All
                                </a>
                            @endif
                            <a href="{{ route('fleet.trip-costs.print', ['cost_ids' => implode(',', $costIds)]) }}" target="_blank" class="btn btn-light btn-sm me-2">
                                <i class="bx bx-printer me-1"></i>Print
                            </a>
                            @if($trip)
                                <a href="{{ route('fleet.trip-costs.index', ['trip_id' => $trip->hash_id]) }}" class="btn btn-light btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i>Back to Costs
                                </a>
                            @else
                                <a href="{{ route('fleet.trip-costs.index') }}" class="btn btn-light btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i>Back to Costs
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @if($trip)
                        <div class="alert alert-info mb-3">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Trip:</strong> {{ $trip->trip_number }} | 
                            <strong>Total Amount:</strong> {{ number_format($totalAmount, 2) }} TZS | 
                            <strong>Number of Costs:</strong> {{ $costs->count() }}
                        </div>
                        @endif

                        @php
                            $costsByTrip = $costs->groupBy('trip_id');
                            $totalByTrip = $costsByTrip->map(fn($tripCosts) => $tripCosts->sum('amount'));
                        @endphp
                        <div class="alert alert-light border mb-3">
                            <h6 class="mb-2"><i class="bx bx-list-ul me-2"></i>Cost per trip</h6>
                            @foreach($totalByTrip as $tripId => $tripTotal)
                                @php $t = $costs->firstWhere('trip_id', $tripId)->trip ?? null; @endphp
                                <div class="mb-1"><strong>{{ $t ? $t->trip_number : 'Trip #' . $tripId }}:</strong> {{ number_format($tripTotal, 2) }} TZS</div>
                            @endforeach
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Trip</th>
                                        <th>Date</th>
                                        <th>Cost Type</th>
                                        <th>GL Account</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($costs as $cost)
                                    <tr>
                                        <td>
                                            @if($cost->trip)
                                                <a href="{{ route('fleet.trips.show', $cost->trip->hash_id) }}">{{ $cost->trip->trip_number }}</a>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>{{ $cost->date_incurred->format('Y-m-d') }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $cost->cost_type)) }}</span>
                                        </td>
                                        <td>{{ $cost->glAccount ? $cost->glAccount->account_code . ' - ' . $cost->glAccount->account_name : 'N/A' }}</td>
                                        <td>{{ $cost->description ?? 'N/A' }}</td>
                                        <td><strong>{{ number_format($cost->amount, 2) }} {{ $cost->currency ?? 'TZS' }}</strong></td>
                                        <td>
                                            @php
                                                $statusColors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                                                $color = $statusColors[$cost->approval_status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">{{ ucfirst($cost->approval_status) }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('fleet.trip-costs.show', $cost->hash_id) }}" class="btn btn-sm btn-outline-info">
                                                <i class="bx bx-show"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <th colspan="5" class="text-end">Total:</th>
                                        <th>{{ number_format($totalAmount, 2) }} TZS</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Approve all costs button
    $('#approve-all-btn').on('click', function() {
        const costIds = $(this).data('cost-ids');
        const costCount = $(this).data('cost-count');
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Approve All Costs?',
                text: `Are you sure you want to approve all ${costCount} cost(s)? This action cannot be undone.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve All',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                input: 'textarea',
                inputPlaceholder: 'Approval notes (optional)...',
                inputAttributes: {
                    'aria-label': 'Approval notes'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Approving costs...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit approval via AJAX
                    $.ajax({
                        url: '{{ route("fleet.trip-costs.batch-approve") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            cost_ids: costIds,
                            approval_notes: result.value || null
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                if (response.redirect) {
                                    window.location.href = response.redirect;
                                } else {
                                    window.location.reload();
                                }
                            });
                        },
                        error: function(xhr) {
                            let errorMessage = 'An error occurred while approving the costs.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMessage
                            });
                        }
                    });
                }
            });
        } else {
            const notes = prompt('Approval notes (optional):');
            if (notes !== null) {
                if (confirm(`Are you sure you want to approve all ${costCount} cost(s)?`)) {
                    // Create and submit form
                    const form = $('<form>', {
                        'method': 'POST',
                        'action': '{{ route("fleet.trip-costs.batch-approve") }}'
                    });
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_token',
                        'value': '{{ csrf_token() }}'
                    }));
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'cost_ids',
                        'value': costIds
                    }));
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'approval_notes',
                        'value': notes
                    }));
                    $('body').append(form);
                    form.submit();
                }
            }
        }
    });
});
</script>
@endpush
