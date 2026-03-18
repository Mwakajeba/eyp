@extends('layouts.main')

@section('title', 'Edit Pay Group')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Pay Groups', 'url' => route('hr.pay-groups.index'), 'icon' => 'bx bx-group'],
                ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-group me-2"></i>Edit Pay Group</h5>
                    <p class="mb-0 text-muted">Update pay group details</p>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('hr.pay-groups.update', $payGroup->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="alert alert-info">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <strong>Code:</strong> {{ $payGroup->pay_group_code }} (cannot be changed)
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Pay Group Code</label>
                                    <input type="text" class="form-control" 
                                        value="{{ $payGroup->pay_group_code }}" disabled>
                                    <small class="text-muted">Cannot be changed</small>
                                </div>

                                <div class="mb-3">
                                    <label for="pay_group_name" class="form-label">Pay Group Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('pay_group_name') is-invalid @enderror" 
                                        id="pay_group_name" name="pay_group_name" 
                                        value="{{ old('pay_group_name', $payGroup->pay_group_name) }}" required>
                                    @error('pay_group_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                        id="description" name="description" rows="3">{{ old('description', $payGroup->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="payment_frequency" class="form-label">Payment Frequency <span class="text-danger">*</span></label>
                                            <select class="form-select @error('payment_frequency') is-invalid @enderror" 
                                                id="payment_frequency" name="payment_frequency" required>
                                                <option value="monthly" {{ old('payment_frequency', $payGroup->payment_frequency) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                                <option value="daily" {{ old('payment_frequency', $payGroup->payment_frequency) == 'daily' ? 'selected' : '' }}>Daily</option>
                                                <option value="weekly" {{ old('payment_frequency', $payGroup->payment_frequency) == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="bi-weekly" {{ old('payment_frequency', $payGroup->payment_frequency) == 'bi-weekly' ? 'selected' : '' }}>Bi-Weekly</option>
                                            </select>
                                            @error('payment_frequency')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cut_off_day" class="form-label">Cut-off Day</label>
                                            <input type="number" class="form-control @error('cut_off_day') is-invalid @enderror" 
                                                id="cut_off_day" name="cut_off_day" 
                                                value="{{ old('cut_off_day', $payGroup->cut_off_day) }}" 
                                                min="1" max="31">
                                            @error('cut_off_day')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="pay_day" class="form-label">Pay Day</label>
                                            <input type="number" class="form-control @error('pay_day') is-invalid @enderror" 
                                                id="pay_day" name="pay_day" 
                                                value="{{ old('pay_day', $payGroup->pay_day) }}" 
                                                min="1" max="31">
                                            @error('pay_day')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auto_adjust_weekends" 
                                            id="auto_adjust_weekends" value="1" 
                                            {{ old('auto_adjust_weekends', $payGroup->auto_adjust_weekends) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="auto_adjust_weekends">
                                            Auto-adjust for Weekends/Holidays
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" 
                                            id="is_active" value="1" 
                                            {{ old('is_active', $payGroup->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('hr.pay-groups.index') }}" class="btn btn-secondary">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i>Update Pay Group
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

