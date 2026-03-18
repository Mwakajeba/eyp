@extends('layouts.main')

@section('title', 'Create Onboarding Checklist')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Onboarding', 'url' => '#', 'icon' => 'bx bx-list-check'],
                ['label' => 'Checklists', 'url' => route('hr.onboarding-checklists.index'), 'icon' => 'bx bx-check-square'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-list-check me-1"></i>Create Onboarding Checklist</h6>
                <a href="{{ route('hr.onboarding-checklists.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr.onboarding-checklists.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="checklist_name" class="form-label">Checklist Name <span class="text-danger">*</span></label>
                                <input type="text" name="checklist_name" id="checklist_name" class="form-control @error('checklist_name') is-invalid @enderror" value="{{ old('checklist_name') }}" required>
                                @error('checklist_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="applicable_to" class="form-label">Applicable To <span class="text-danger">*</span></label>
                                <select name="applicable_to" id="applicable_to" class="form-select @error('applicable_to') is-invalid @enderror" required>
                                    <option value="all" {{ old('applicable_to', 'all') == 'all' ? 'selected' : '' }}>All</option>
                                    <option value="department" {{ old('applicable_to') == 'department' ? 'selected' : '' }}>Department</option>
                                    <option value="position" {{ old('applicable_to') == 'position' ? 'selected' : '' }}>Position</option>
                                    <option value="role" {{ old('applicable_to') == 'role' ? 'selected' : '' }}>Role</option>
                                </select>
                                @error('applicable_to')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3" id="department_div" style="display: none;">
                                <label for="department_id" class="form-label">Department</label>
                                <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3" id="position_div" style="display: none;">
                                <label for="position_id" class="form-label">Position</label>
                                <select name="position_id" id="position_id" class="form-select @error('position_id') is-invalid @enderror">
                                    <option value="">Select Position</option>
                                    @foreach($positions as $position)
                                        <option value="{{ $position->id }}" {{ old('position_id') == $position->id ? 'selected' : '' }}>
                                            {{ $position->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('position_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="mb-3">Checklist Items</h6>
                        <div id="checklist_items">
                            <div class="checklist-item-row mb-3 p-3 border rounded">
                                <div class="row">
                                    <div class="col-md-5 mb-2">
                                        <input type="text" name="checklist_items[0][item_title]" class="form-control" placeholder="Item Title" required>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <select name="checklist_items[0][item_type]" class="form-select" required>
                                            <option value="task">Task</option>
                                            <option value="document_upload">Document Upload</option>
                                            <option value="policy_acknowledgment">Policy Acknowledgment</option>
                                            <option value="system_access">System Access</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <input type="number" name="checklist_items[0][sequence_order]" class="form-control" value="0" min="0">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="checklist_items[0][is_mandatory]" value="1" checked>
                                            <label class="form-check-label">Mandatory</label>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-2">
                                        <textarea name="checklist_items[0][item_description]" class="form-control" rows="2" placeholder="Item Description"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="add_item_btn">
                            <i class="bx bx-plus me-1"></i>Add Item
                        </button>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('hr.onboarding-checklists.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Create Checklist
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let itemIndex = 1;

    $('#applicable_to').on('change', function() {
        if ($(this).val() === 'department') {
            $('#department_div').show();
            $('#position_div').hide();
            $('#department_id').prop('required', true);
            $('#position_id').prop('required', false);
        } else if ($(this).val() === 'position') {
            $('#department_div').hide();
            $('#position_div').show();
            $('#department_id').prop('required', false);
            $('#position_id').prop('required', true);
        } else {
            $('#department_div').hide();
            $('#position_div').hide();
            $('#department_id').prop('required', false);
            $('#position_id').prop('required', false);
        }
    });

    $('#add_item_btn').on('click', function() {
        const itemHtml = `
            <div class="checklist-item-row mb-3 p-3 border rounded">
                <div class="row">
                    <div class="col-md-5 mb-2">
                        <input type="text" name="checklist_items[${itemIndex}][item_title]" class="form-control" placeholder="Item Title" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="checklist_items[${itemIndex}][item_type]" class="form-select" required>
                            <option value="task">Task</option>
                            <option value="document_upload">Document Upload</option>
                            <option value="policy_acknowledgment">Policy Acknowledgment</option>
                            <option value="system_access">System Access</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="number" name="checklist_items[${itemIndex}][sequence_order]" class="form-control" value="${itemIndex}" min="0">
                    </div>
                    <div class="col-md-2 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="checklist_items[${itemIndex}][is_mandatory]" value="1" checked>
                            <label class="form-check-label">Mandatory</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger mt-1 remove-item-btn">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                    <div class="col-md-12 mb-2">
                        <textarea name="checklist_items[${itemIndex}][item_description]" class="form-control" rows="2" placeholder="Item Description"></textarea>
                    </div>
                </div>
            </div>
        `;
        $('#checklist_items').append(itemHtml);
        itemIndex++;
    });

    $(document).on('click', '.remove-item-btn', function() {
        $(this).closest('.checklist-item-row').remove();
    });
});
</script>
@endpush

