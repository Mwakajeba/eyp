@extends('layouts.main')

@section('title', 'File Types')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'File Types', 'url' => '#', 'icon' => 'bx bx-file']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">File Types Management</h6>
            <a href="{{ route('hr.file-types.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i>New File Type
            </a>
        </div>

        <div class="row">
            <!-- File Types Table -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-list-ul me-2"></i>All File Types
                        </h6>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bx bx-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th width="60">#</th>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Extensions</th>
                                        <th>Max Size</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($fileTypes as $index => $fileType)
                                    <tr>
                                        <td>{{ ($fileTypes->currentPage() - 1) * $fileTypes->perPage() + $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bx bx-file me-2 text-primary"></i>
                                                <div>
                                                    <strong>{{ $fileType->name }}</strong>
                                                    @if($fileType->description)
                                                        <br><small class="text-muted">{{ Str::limit($fileType->description, 50) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($fileType->code)
                                                <span class="badge bg-light text-dark">{{ $fileType->code }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($fileType->allowed_extensions_string)
                                                <span class="text-muted small">{{ $fileType->allowed_extensions_string }}</span>
                                            @else
                                                <span class="text-success">All types</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($fileType->max_file_size_human)
                                                {{ $fileType->max_file_size_human }}
                                            @else
                                                <span class="text-success">No limit</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <span class="badge bg-{{ $fileType->is_active ? 'success' : 'secondary' }}">
                                                    {{ $fileType->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                                @if($fileType->is_required)
                                                    <span class="badge bg-warning">Required</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('hr.file-types.edit', $fileType) }}" 
                                                   class="btn btn-sm btn-warning" 
                                                   title="Edit">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="deleteFileType({{ $fileType->id }}, '{{ $fileType->name }}')"
                                                        title="Delete">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bx bx-file-blank" style="font-size: 3rem; opacity: 0.3;"></i>
                                                <p class="mt-2 mb-0">No file types configured yet</p>
                                                <small>Click "New File Type" to create your first file type</small>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        @if($fileTypes->hasPages())
                        <div class="mt-3">
                            {{ $fileTypes->links() }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Information Panel -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="bx bx-info-circle me-2"></i>File Types Overview
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Quick Stats -->
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="text-center">
                                    <h4 class="text-primary mb-1">{{ $fileTypes->total() }}</h4>
                                    <small class="text-muted">Total Types</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    @php
                                        $activeCount = $fileTypes->where('is_active', true)->count();
                                    @endphp
                                    <h4 class="text-success mb-1">{{ $activeCount }}</h4>
                                    <small class="text-muted">Active</small>
                                </div>
                            </div>
                        </div>

                        <!-- What are File Types? -->
                        <div class="mb-4">
                            <h6 class="text-dark mb-3">
                                <i class="bx bx-help-circle me-2 text-info"></i>What are File Types?
                            </h6>
                            <p class="text-muted small">
                                File types define the categories of documents that can be uploaded in the HR system. 
                                They control file extensions, size limits, and whether certain documents are mandatory.
                            </p>
                        </div>

                        <!-- Best Practices -->
                        <div class="mb-4">
                            <h6 class="text-dark mb-3">
                                <i class="bx bx-bulb me-2 text-warning"></i>Best Practices
                            </h6>
                            <ul class="list-unstyled small text-muted">
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Use clear, descriptive names (e.g., "Employment Contract", "ID Copy")
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Set appropriate file size limits to prevent large uploads
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Specify allowed extensions for security (pdf, doc, jpg, png)
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Mark critical documents as "Required"
                                </li>
                            </ul>
                        </div>

                        <!-- Common File Types -->
                        <div>
                            <h6 class="text-dark mb-3">
                                <i class="bx bx-bookmark me-2 text-primary"></i>Common HR Documents
                            </h6>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark">CV/Resume</span>
                                <span class="badge bg-light text-dark">ID Copy</span>
                                <span class="badge bg-light text-dark">Contract</span>
                                <span class="badge bg-light text-dark">Certificates</span>
                                <span class="badge bg-light text-dark">Medical Report</span>
                                <span class="badge bg-light text-dark">References</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
function deleteFileType(id, name) {
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete "${name}". This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('delete-form');
            form.action = `/hr-payroll/file-types/${id}`;
            form.submit();
        }
    });
}
</script>
@endsection
