@extends('layouts.main')

@section('title', 'Intangible Amortisation')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Intangible Assets', 'url' => route('assets.intangible.index'), 'icon' => 'bx bx-brain'],
            ['label' => 'Amortisation', 'url' => '#', 'icon' => 'bx bx-time']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1"><i class="bx bx-time me-2"></i>Intangible Amortisation Run</h5>
                    <p class="mb-0 text-muted small">Run monthly straight-line amortisation for all eligible finite-life intangible assets.</p>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('assets.intangible.amortisation.process') }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label small">Amortisation Period (any date in month)<span class="text-danger">*</span></label>
                        <input type="date" name="period" value="{{ old('period', now()->toDateString()) }}" class="form-control form-control-sm @error('period') is-invalid @enderror">
                        @error('period')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text small">System will use the first day of the selected month for posting.</div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bx bx-play-circle me-1"></i>Run Amortisation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection


