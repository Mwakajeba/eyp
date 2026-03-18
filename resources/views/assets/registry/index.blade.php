@extends('layouts.main')

@section('title', 'Assets Registry')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets Management', 'url' => route('assets.index'), 'icon' => 'bx bx-clipboard'],
            ['label' => 'Registry', 'url' => '#', 'icon' => 'bx bx-list-ul']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-list-ul me-2"></i>Assets Registry</h5>
                    <div class="text-muted">Master list of capitalised and trackable assets</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#assetImportModal">
                        <i class="bx bx-import me-1"></i> Import Assets
                    </button>
                    <a href="{{ route('assets.registry.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>Add Asset
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label small">HFS Status</label>
                        <select id="filter-hfs-status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="none">Not HFS</option>
                            <option value="pending">Pending</option>
                            <option value="classified">Classified</option>
                            <option value="sold">Sold</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Depreciation</label>
                        <select id="filter-depreciation" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="0">Active</option>
                            <option value="1">Stopped</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-filters">
                            <i class="bx bx-x me-1"></i>Clear Filters
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="assets-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>Asset</th>
                                <th>Category</th>
                                <th>Class</th>
                                <th>Purchase Date</th>
                                <th class="text-end">Cost</th>
                                <th>Status</th>
                                <th>HFS Status</th>
                                <th>Depreciation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <div class="modal fade" id="assetImportModal" tabindex="-1" aria-labelledby="assetImportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assetImportModalLabel">
                            <i class="bx bx-import me-2"></i>Import Assets
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="assetImportForm" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select name="category_id" id="category_id" class="form-select select2-single" required>
                                        <option value="">Select Category</option>
                                        @foreach(($categories ?? []) as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                {{-- departmemt selection --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Department <span class="text-danger">*</span></label>
                                    <select name="department_id" id="department_id" class="form-select select2-single" required>
                                        <option value="">Select Department</option>
                                        @foreach(($departments ?? []) as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">CSV File <span class="text-danger">*</span></label>
                                    <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv" required>
                                    <div class="invalid-feedback"></div>
                                    <small class="text-muted">Upload a CSV file with asset details.</small>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>CSV Format:</strong> Columns should be: <code>name, code, category_name, department_name, tax_pool_class, model, manufacturer, purchase_date, capitalization_date, purchase_cost, salvage_value, serial_number, location, status</code>
                                <br>
                                <small>
                                    - <strong>Category</strong> selected here will be used when a row has no valid <code>category_name</code> in CSV.<br>
                                    - <strong>Department</strong> selected here will be used when a row has no valid <code>department_name</code> in CSV.<br>
                                    - <strong>tax_pool_class</strong> or <strong>tax_class_code</strong> is optional (e.g., Class 1, Class 2, Class 3, Class 5, Class 6, Class 7, Class 8). The system will map the class code to the appropriate TRA tax depreciation class. If omitted, tax class will be left blank.<br>
                                    - <strong>category_name</strong> in CSV must match an existing Asset Category name if provided.<br>
                                    - <strong>department_name</strong> in CSV must match an existing Department name if provided.<br>
                                    - <strong>purchase_date</strong> and <strong>capitalization_date</strong> accept formats like YYYY-MM-DD.<br>
                                    - If <strong>code</strong> is empty, it will be auto-generated from Asset Code Format.<br>
                                </small>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('assets.registry.download-template') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bx bx-download me-1"></i> Download CSV Template
                                </a>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-upload me-1"></i> Import Assets
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- standalone create page now used -->

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(function() {
    const table = $('#assets-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('assets.registry.data') }}',
            error: function(xhr){
                console.error('Assets DT error:', xhr.status, xhr.responseText);
                let msg = 'Failed to load assets. ' + (xhr.status ? 'HTTP ' + xhr.status : '');
                try { const j = JSON.parse(xhr.responseText); if (j.message) msg += ' - ' + j.message; } catch(e) {}
                Swal.fire({ icon: 'error', title: 'Load Error', text: msg });
            }
        },
        order: [[1, 'asc']],
        columns: [
            { data: 'name', name: 'assets.name', render: function(d, type, row){
                const code = row.code || '-';
                const name = row.name || '-';
                return `
                  <div class="d-flex flex-column">
                    <div class="fw-semibold">${name}</div>
                    <div class="small text-muted">Code: <span class="badge bg-light text-dark">${code}</span></div>
                  </div>`;
            }},
            { data: 'category_name', name: 'asset_categories.name' },
            { data: 'tax_class_display', name: 'tax_depreciation_classes.class_code', defaultContent: '<span class="badge bg-secondary">N/A</span>', orderable: true },
            { data: 'purchase_date', name: 'purchase_date' },
            { data: 'purchase_cost', name: 'purchase_cost', className: 'text-end', render: function(d){ d = d || 0; return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(parseFloat(d)); } },
            { data: 'status', name: 'status', render: function(d){ d = (d||'').replaceAll('_',' '); return `<span class="badge bg-info text-dark">${d.charAt(0).toUpperCase()+d.slice(1)}</span>`; } },
            { data: 'hfs_status_display', name: 'hfs_status', orderable: false, searchable: false, defaultContent: '-' },
            { data: 'depreciation_stopped_display', name: 'depreciation_stopped', orderable: false, searchable: false, defaultContent: '-' },
            { data: null, orderable: false, searchable: false, render: function(data, type, row){
                const base = `{{ url('/asset-management/registry') }}`;
                const viewUrl = row.source_view_url || (base + '/' + row.id_hashed);
                const editUrl = row.source_edit_url || (base + '/' + row.id_hashed + '/edit');
                const delForm = row.source_view_url ? '' : `
                    <form method="POST" action="${base}/${row.id_hashed}" class="d-inline asset-delete-form ms-1">
                      @csrf
                      @method('DELETE')
                      <button type="button" class="btn btn-sm btn-outline-danger btn-delete-asset" title="Delete"><i class="bx bx-trash"></i></button>
                    </form>`;
                return `
                  <div class="btn-group" role="group">
                    <a href="${viewUrl}" class="btn btn-sm btn-outline-secondary" title="View"><i class="bx bx-show"></i></a>
                    <a href="${editUrl}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                    ${delForm}
                  </div>`;
            }},
        ]
    });

    $(document).on('click', '.btn-delete-asset', function(){
        const $form = $(this).closest('form.asset-delete-form');
        Swal.fire({
            title: 'Delete this asset?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $form.trigger('submit');
            }
        });
    });

    // Initialize Select2 for category when modal is shown
    $('#assetImportModal').on('shown.bs.modal', function() {
        $('#category_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Category',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#assetImportModal')
        });
    });

    // Destroy Select2 when modal is hidden to avoid conflicts
    $('#assetImportModal').on('hidden.bs.modal', function() {
        if ($('#category_id').hasClass('select2-hidden-accessible')) {
            $('#category_id').select2('destroy');
        }
        $('#assetImportForm')[0].reset();
    });

    // Handle import form submission (like inventory items)
    $('#assetImportForm').on('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();

        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Importing...');

        $.ajax({
            url: '{{ route('assets.registry.import') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response){
                if (response.success) {
                    Swal.fire({ icon: 'success', title: 'Import Successful!', text: response.message, showConfirmButton: false, timer: 2000 });
                    $('#assetImportModal').modal('hide');
                    table.ajax.reload();
                } else {
                    Swal.fire('Error!', response.message || 'Import failed', 'error');
                }
            },
            error: function(xhr){
                let json = xhr.responseJSON; if (!json && xhr.responseText) { try { json = JSON.parse(xhr.responseText); } catch(_) {} }
                if (xhr.status === 422) {
                    const errors = json && json.errors ? json.errors : null;
                    if (errors) {
                        Object.keys(errors).forEach(function(key){
                            const input = $(`[name="${key}"]`);
                            input.addClass('is-invalid');
                            input.next('.invalid-feedback').text(errors[key][0]);
                        });
                    } else {
                        Swal.fire('Error!', (json && json.message) ? json.message : 'Validation failed.', 'error');
                    }
                } else {
                    Swal.fire('Error!', (json && json.message) ? json.message : 'An error occurred during import.', 'error');
                }
            },
            complete: function(){
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Filter handlers
    $('#filter-hfs-status, #filter-depreciation').on('change', function() {
        table.ajax.reload();
    });

    $('#clear-filters').on('click', function() {
        $('#filter-hfs-status, #filter-depreciation').val('').trigger('change');
        table.ajax.reload();
    });
});
</script>
@endpush

@endsection


