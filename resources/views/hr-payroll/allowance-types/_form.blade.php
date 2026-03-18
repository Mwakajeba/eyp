<form action="{{ $allowanceType ? route('hr.allowance-types.update', $allowanceType->encoded_id) : route('hr.allowance-types.store') }}" method="POST">
    @csrf
    @if($allowanceType)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $allowanceType?->name) }}" required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Code</label>
            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                   value="{{ old('code', $allowanceType?->code) }}">
            @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                      rows="3">{{ old('description', $allowanceType?->description) }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Type <span class="text-danger">*</span></label>
            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                <option value="">-- Select Type --</option>
                <option value="fixed" {{ old('type', $allowanceType?->type) === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                <option value="percentage" {{ old('type', $allowanceType?->type) === 'percentage' ? 'selected' : '' }}>Percentage</option>
            </select>
            @error('type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Taxable</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="is_taxable" name="is_taxable" value="1"
                       {{ old('is_taxable', $allowanceType?->is_taxable) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_taxable">This allowance is taxable</label>
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label">Status</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                       {{ old('is_active', $allowanceType?->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save"></i> {{ $allowanceType ? 'Update' : 'Create' }} Allowance Type
                </button>
                <a href="{{ route('hr.allowance-types.index') }}" class="btn btn-secondary">
                    <i class="bx bx-x"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</form>

