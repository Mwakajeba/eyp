<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $fileType->name ?? '') }}" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Code</label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $fileType->code ?? '') }}">
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                  rows="3">{{ old('description', $fileType->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Allowed Extensions</label>
        <input type="text" name="allowed_extensions" class="form-control @error('allowed_extensions') is-invalid @enderror"
               value="{{ old('allowed_extensions', $fileType->allowed_extensions_string ?? '') }}"
               placeholder="e.g., pdf, doc, docx, jpg, png">
        <small class="text-muted">Separate multiple extensions with commas</small>
        @error('allowed_extensions')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Max File Size (KB)</label>
        <input type="number" name="max_file_size" class="form-control @error('max_file_size') is-invalid @enderror"
               value="{{ old('max_file_size', $fileType->max_file_size ?? '') }}"
               placeholder="e.g., 1024 for 1MB">
        <small class="text-muted">Leave empty for no limit</small>
        @error('max_file_size')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_required" name="is_required" value="1"
                   {{ old('is_required', $fileType->is_required ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_required">Required Document</label>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                   {{ old('is_active', $fileType->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
</div>
