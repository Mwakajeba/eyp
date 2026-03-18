@extends('layouts.main')

@section('title', 'Edit Accounting Document')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Documents', 'url' => route('accounting.documents.index'), 'icon' => 'bx bx-folder'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Edit Document</h6>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('accounting.documents.update', $document->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">File Type</label>
                            <select name="file_type_id" class="form-select" required>
                                <option value="">Select file type</option>
                                @foreach($fileTypes as $fileType)
                                    <option value="{{ $fileType->id }}" @selected(old('file_type_id', $document->file_type_id) == $fileType->id)>{{ $fileType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Replace File (Optional)</label>
                            <input type="file" name="file" class="form-control">
                            <small class="text-muted">Current: {{ $document->file_name }}</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control" required>{{ old('description', $document->description) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="{{ route('accounting.documents.index') }}" class="btn btn-light">Back</a>
                        <a href="{{ route('accounting.documents.view', $document->id) }}" target="_blank" class="btn btn-info">View File</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
