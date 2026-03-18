@extends('layouts.main')

@section('title', 'Create Leave Type')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Leave Management', 'url' => route('hr.leave.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Leave Types', 'url' => route('hr.leave.types.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
            <h6 class="mb-0 text-uppercase">CREATE LEAVE TYPE</h6>
            <hr />

            <form action="{{ route('hr.leave.types.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Basic Information</h5>
                            </div>
                            <div class="card-body">
                                @if ($errors->any())
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <h6 class="alert-heading">Please fix the following errors:</h6>
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="name" class="form-label">Leave Type Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" id="name"
                                            class="form-control @error('name') is-invalid @enderror"
                                            value="{{ old('name') }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="code" class="form-label">Code</label>
                                        <input type="text" name="code" id="code"
                                            class="form-control @error('code') is-invalid @enderror"
                                            value="{{ old('code') }}" maxlength="20">
                                        @error('code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" id="description" rows="3"
                                        class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="annual_entitlement" class="form-label">Annual Entitlement (Days)</label>
                                        <input type="number" name="annual_entitlement" id="annual_entitlement"
                                            class="form-control @error('annual_entitlement') is-invalid @enderror"
                                            value="{{ old('annual_entitlement', 0) }}" min="0">
                                        @error('annual_entitlement')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="accrual_type" class="form-label">Accrual Type <span
                                                class="text-danger">*</span></label>
                                        <select name="accrual_type" id="accrual_type"
                                            class="form-select @error('accrual_type') is-invalid @enderror" required>
                                            <option value="annual" {{ old('accrual_type') == 'annual' ? 'selected' : '' }}>
                                                Annual</option>
                                            <option value="monthly" {{ old('accrual_type') == 'monthly' ? 'selected' : '' }}>
                                                Monthly</option>
                                            <option value="none" {{ old('accrual_type') == 'none' ? 'selected' : '' }}>None
                                            </option>
                                        </select>
                                        @error('accrual_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="notice_days" class="form-label">Notice Days Required</label>
                                        <input type="number" name="notice_days" id="notice_days"
                                            class="form-control @error('notice_days') is-invalid @enderror"
                                            value="{{ old('notice_days', 0) }}" min="0">
                                        @error('notice_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="carryover_cap_days" class="form-label">Carryover Cap (Days)</label>
                                        <input type="number" name="carryover_cap_days" id="carryover_cap_days"
                                            class="form-control @error('carryover_cap_days') is-invalid @enderror"
                                            value="{{ old('carryover_cap_days') }}" min="0">
                                        @error('carryover_cap_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="doc_required_after_days" class="form-label">Document Required After
                                            (Days)</label>
                                        <input type="number" name="doc_required_after_days" id="doc_required_after_days"
                                            class="form-control @error('doc_required_after_days') is-invalid @enderror"
                                            value="{{ old('doc_required_after_days') }}" min="1">
                                        @error('doc_required_after_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_paid" id="is_paid"
                                            value="1" {{ old('is_paid', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_paid">Paid Leave</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="allow_half_day"
                                            id="allow_half_day" value="1" {{ old('allow_half_day', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="allow_half_day">Allow Half Day</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="encashable" id="encashable"
                                            value="1" {{ old('encashable') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="encashable">Encashable</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                            value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Quick Guide</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">Leave Type Settings</h6>
                                    <ul class="small mb-0">
                                        <li><strong>Annual Entitlement:</strong> Total days per year</li>
                                        <li><strong>Accrual Type:</strong> How leave is granted (annual/monthly)</li>
                                        <li><strong>Notice Days:</strong> Days notice required before leave</li>
                                        <li><strong>Carryover Cap:</strong> Max days that can be carried to next year</li>
                                        <li><strong>Document Required:</strong> After how many days document is needed</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save"></i> Create Leave Type
                                    </button>
                                    <a href="{{ route('hr.leave.types.index') }}" class="btn btn-secondary">
                                        <i class="bx bx-x"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection