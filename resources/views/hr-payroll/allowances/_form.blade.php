<form action="{{ $allowance ? route('hr.allowances.update', $allowance->encoded_id) : route('hr.allowances.store') }}" method="POST">
    @csrf
    @if($allowance)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Employee <span class="text-danger">*</span></label>
            <select name="employee_id" class="form-select select2-single @error('employee_id') is-invalid @enderror" required>
                <option value="">-- Select Employee --</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ old('employee_id', $allowance?->employee_id) == $employee->id ? 'selected' : '' }}>
                        {{ $employee->full_name }} ({{ $employee->employee_number }})
                    </option>
                @endforeach
            </select>
            @error('employee_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Allowance Type <span class="text-danger">*</span></label>
            <select name="allowance_type_id" class="form-select select2-single @error('allowance_type_id') is-invalid @enderror" required>
                <option value="">-- Select Allowance Type --</option>
                @foreach($allowanceTypes as $allowanceType)
                    <option value="{{ $allowanceType->id }}" {{ old('allowance_type_id', $allowance?->allowance_type_id) == $allowanceType->id ? 'selected' : '' }}>
                        {{ $allowanceType->name }} ({{ ucfirst($allowanceType->type) }})
                    </option>
                @endforeach
            </select>
            @error('allowance_type_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Date <span class="text-danger">*</span></label>
            <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                   value="{{ old('date', $allowance?->date?->format('Y-m-d')) }}" required>
            @error('date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Amount (TZS) <span class="text-danger">*</span></label>
            <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                   value="{{ old('amount', $allowance?->amount) }}" step="0.01" min="0" required>
            @error('amount')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                      rows="3">{{ old('description', $allowance?->description) }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Status</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                       {{ old('is_active', $allowance?->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save"></i> {{ $allowance ? 'Update' : 'Create' }} Allowance
                </button>
                <a href="{{ route('hr.allowances.index') }}" class="btn btn-secondary">
                    <i class="bx bx-x"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</form>
