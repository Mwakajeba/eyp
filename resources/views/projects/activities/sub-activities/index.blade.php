@extends('layouts.main')

@section('title', 'Project Sub Activities')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Project Management', 'url' => route('projects.index'), 'icon' => 'bx bx-briefcase'],
            ['label' => 'Project Activities', 'url' => route('projects.activities.index'), 'icon' => 'bx bx-task'],
            ['label' => 'Sub Activities', 'url' => '#', 'icon' => 'bx bx-sitemap']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h6 class="mb-1 text-uppercase">Sub Activities Setup</h6>
                <div class="text-muted small">
                    <span class="fw-semibold">Project:</span> {{ $activity->project->project_code ?? '-' }} - {{ $activity->project->name ?? '-' }}
                    <span class="mx-2">|</span>
                    <span class="fw-semibold">Activity:</span>
                    <span title="{{ $activity->description }}" style="cursor: help;">{{ $activity->activity_code }}</span>
                </div>
            </div>
            <a href="{{ route('projects.activities.index') }}" class="btn btn-light">Back to Activities</a>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="row">
            <div class="col-lg-5 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bx bx-plus-circle font-22 text-success me-2"></i>
                            <h6 class="mb-0 text-uppercase">Create Sub Activity</h6>
                        </div>
                        <hr>

                        <form method="POST" action="{{ route('projects.activities.sub-activities.store', $activity->id) }}" id="sub-activity-create-form">
                            @csrf

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="sub_activity_name" class="form-label">Sub Activity Name <span class="text-danger">*</span></label>
                                    <input type="text" name="sub_activity_name" id="sub_activity_name" class="form-control" value="{{ old('sub_activity_name') }}" maxlength="255" required>
                                </div>

                                <div class="col-12">
                                    <label for="chart_account_id" class="form-label">Chart Account <span class="text-danger">*</span></label>
                                    <select name="chart_account_id" id="chart_account_id" class="form-select select2-single" required>
                                        <option value="">Search chart account</option>
                                        @foreach($chartAccounts as $account)
                                        <option value="{{ $account->id }}" {{ (string) old('chart_account_id') === (string) $account->id ? 'selected' : '' }}>
                                            {{ $account->account_code }} - {{ $account->account_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="text" name="amount" id="amount" class="form-control amount-input" value="{{ old('amount') }}" inputmode="decimal" placeholder="0.00" required>
                                </div>

                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bx bx-save me-1"></i>Save Sub Activity
                                    </button>
                                    <button type="reset" class="btn btn-light">Reset</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-uppercase">Sub Activities List</h6>
                            <span class="badge bg-light text-dark">{{ $subActivities->count() }} Record(s)</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Sub Activity</th>
                                        <th>Chart Account</th>
                                        <th class="text-end">Amount</th>
                                        <th>Created By</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($subActivities as $subActivity)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $subActivity->sub_activity_name }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $subActivity->chartAccount->account_code ?? '-' }}</div>
                                            <small class="text-muted">{{ $subActivity->chartAccount->account_name ?? '-' }}</small>
                                        </td>
                                        <td class="text-end">{{ number_format((float) $subActivity->amount, 2) }}</td>
                                        <td>{{ $subActivity->creator->name ?? 'System' }}</td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-1">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-warning edit-sub-activity-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editSubActivityModal"
                                                    data-id="{{ $subActivity->id }}"
                                                    data-name="{{ $subActivity->sub_activity_name }}"
                                                    data-chart-account-id="{{ $subActivity->chart_account_id }}"
                                                    data-amount="{{ number_format((float) $subActivity->amount, 2, '.', ',') }}"
                                                    title="Edit Sub Activity"
                                                >
                                                    <i class="bx bx-edit"></i>
                                                </button>

                                                <form method="POST" action="{{ route('projects.activities.sub-activities.destroy', [$activity->id, $subActivity->id]) }}" onsubmit="return confirm('Delete this sub activity?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Sub Activity">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">No sub activities created yet.</div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <th colspan="3" class="text-end">Total Amount</th>
                                        <th class="text-end">{{ number_format($totalAmount, 2) }}</th>
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

<div class="modal fade" id="editSubActivityModal" tabindex="-1" aria-labelledby="editSubActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editSubActivityModalLabel">
                    <i class="bx bx-edit me-2"></i>Edit Sub Activity
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="edit-sub-activity-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_sub_activity_id" id="edit_sub_activity_id" value="{{ old('edit_sub_activity_id') }}">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit_sub_activity_name" class="form-label">Sub Activity Name <span class="text-danger">*</span></label>
                            <input type="text" name="sub_activity_name" id="edit_sub_activity_name" class="form-control" value="{{ old('edit_sub_activity_id') ? old('sub_activity_name') : '' }}" maxlength="255" required>
                        </div>

                        <div class="col-12">
                            <label for="edit_chart_account_id" class="form-label">Chart Account <span class="text-danger">*</span></label>
                            <select name="chart_account_id" id="edit_chart_account_id" class="form-select" required>
                                <option value="">Search chart account</option>
                                @foreach($chartAccounts as $account)
                                <option value="{{ $account->id }}" {{ (string) old('chart_account_id') === (string) $account->id ? 'selected' : '' }}>
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="edit_amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="text" name="amount" id="edit_amount" class="form-control amount-input" value="{{ old('edit_sub_activity_id') ? old('amount') : '' }}" inputmode="decimal" placeholder="0.00" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bx bx-save me-1"></i>Update Sub Activity
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    function formatAmount(value) {
        const digits = value.replace(/[^0-9.]/g, '');
        const parts = digits.split('.');
        const integerPart = parts[0] ? Number(parts[0]).toLocaleString('en-US') : '';

        if (parts.length === 1) {
            return integerPart;
        }

        return `${integerPart}.${parts.slice(1).join('').slice(0, 2)}`;
    }

    function stripFormattedAmounts(form) {
        form.querySelectorAll('.amount-input').forEach((input) => {
            input.value = input.value.replace(/,/g, '');
        });
    }

    $(document).ready(function () {
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('#chart_account_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Search and select chart account...',
                allowClear: true
            });

            $('#edit_chart_account_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Search and select chart account...',
                allowClear: true,
                dropdownParent: $('#editSubActivityModal')
            });
        }

        document.querySelectorAll('.amount-input').forEach((input) => {
            input.addEventListener('input', function () {
                this.value = formatAmount(this.value);
            });
        });

        document.getElementById('sub-activity-create-form')?.addEventListener('submit', function () {
            stripFormattedAmounts(this);
        });

        document.getElementById('edit-sub-activity-form')?.addEventListener('submit', function () {
            stripFormattedAmounts(this);
        });

        const updateRouteTemplate = '{{ route('projects.activities.sub-activities.update', [$activity->id, '__SUB_ACTIVITY__']) }}';

        $('.edit-sub-activity-btn').on('click', function () {
            const subActivityId = $(this).data('id');
            const name = $(this).data('name');
            const chartAccountId = String($(this).data('chart-account-id'));
            const amount = $(this).data('amount');

            $('#edit_sub_activity_id').val(subActivityId);
            $('#edit_sub_activity_name').val(name);
            $('#edit_amount').val(amount);
            $('#edit-sub-activity-form').attr('action', updateRouteTemplate.replace('__SUB_ACTIVITY__', subActivityId));
            $('#edit_chart_account_id').val(chartAccountId).trigger('change');
        });

        @if(old('edit_sub_activity_id'))
        const previousSubActivityId = '{{ old('edit_sub_activity_id') }}';
        $('#edit-sub-activity-form').attr('action', updateRouteTemplate.replace('__SUB_ACTIVITY__', previousSubActivityId));
        const editModal = new bootstrap.Modal(document.getElementById('editSubActivityModal'));
        editModal.show();
        @endif
    });
</script>
@endpush