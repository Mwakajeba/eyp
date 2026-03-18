@extends('layouts.main')

@section('title', 'Opening Assets')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Opening Assets', 'url' => route('assets.openings.index'), 'icon' => 'bx bx-book-open']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <div>
                    <h5 class="mb-1">Opening Assets</h5>
                    <div class="text-muted">Create opening balances for existing assets</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#openingImportModal"><i class="bx bx-upload me-1"></i> Import</button>
                    <a href="{{ route('assets.openings.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> New Opening</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="openings-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Asset</th>
                                <th>Category</th>
                                <th class="text-end">Cost</th>
                                <th class="text-end">Accum. Depr</th>
                                <th class="text-end">NBV</th>
                                <th>GL</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="openingImportModal" tabindex="-1" aria-labelledby="openingImportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="openingImportModalLabel">Import Opening Assets</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="openingImportForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        Upload a CSV file with opening asset balances. Download the template for the correct format.
                    </div>
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">CSV File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv,.txt" required>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Required columns: asset_name, opening_date, opening_cost</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('assets.openings.download-template') }}" class="btn btn-outline-secondary"><i class="bx bx-download me-1"></i> CSV Template</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(function(){
    const table = $('#openings-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: '{{ route('assets.openings.data') }}' },
        order: [[0, 'desc']],
        columns: [
            { data: 'opening_date', name: 'opening_date' },
            { data: null, name: 'asset_name', render: function(d){
                const code = d.asset_code ? ` <small class="text-muted">(${d.asset_code})</small>` : '';
                return `${d.asset_name || ''}${code}`;
            }},
            { data: 'category_name', name: 'asset_category_id', defaultContent: '' },
            { data: 'opening_cost', name: 'opening_cost', className: 'text-end', render: function(d){ d=d||0; return new Intl.NumberFormat('en-US',{minimumFractionDigits:2}).format(parseFloat(d)); } },
            { data: 'opening_accum_depr', name: 'opening_accum_depr', className: 'text-end', render: function(d){ d=d||0; return new Intl.NumberFormat('en-US',{minimumFractionDigits:2}).format(parseFloat(d)); } },
            { data: 'opening_nbv', name: 'opening_nbv', className: 'text-end', render: function(d){ d=d||0; return new Intl.NumberFormat('en-US',{minimumFractionDigits:2}).format(parseFloat(d)); } },
            { data: 'gl_status', name: 'gl_posted', orderable: false, searchable: false },
            { data: null, orderable: false, searchable: false, render: function(d){
                const base = `{{ url('/asset-management/openings') }}`;
                return `
                    <div class="btn-group" role="group">
                        <a href="${base}/${d.id_hashed}" class="btn btn-sm btn-outline-secondary" title="View"><i class="bx bx-show"></i></a>
                        <form method="POST" action="${base}/${d.id_hashed}" class="d-inline ms-1 form-delete-opening">
                          @csrf
                          @method('DELETE')
                          <button type="button" class="btn btn-sm btn-outline-danger btn-delete" title="Delete"><i class="bx bx-trash"></i></button>
                        </form>
                    </div>`;
            }},
        ]
    });

    $(document).on('click', '.btn-delete', function(){
        const form = $(this).closest('form.form-delete-opening');
        Swal.fire({
            title: 'Delete this opening?',
            text: 'This will also remove related GL entries.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (result.isConfirmed) {
                form.trigger('submit');
            }
        });
    });

    // Import form submission
    $('#openingImportForm').on('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();

        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Importing...');

        $.ajax({
            url: '{{ route('assets.openings.import') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response){
                if (response.success) {
                    Swal.fire({ icon: 'success', title: 'Import Successful!', text: response.message, showConfirmButton: false, timer: 2000 });
                    $('#openingImportModal').modal('hide');
                    table.ajax.reload();
                    $('#openingImportForm')[0].reset();
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
});
</script>
@endpush


