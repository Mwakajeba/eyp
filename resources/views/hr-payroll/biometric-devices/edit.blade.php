@extends('layouts.main')

@section('title', 'Edit Biometric Device')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Biometric Devices', 'url' => route('hr.biometric-devices.index'), 'icon' => 'bx bx-fingerprint'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">Edit Biometric Device</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.biometric-devices.update', $biometricDevice->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Device Code <span class="text-danger">*</span></label>
                            <input type="text" name="device_code" class="form-control @error('device_code') is-invalid @enderror" 
                                   value="{{ old('device_code', $biometricDevice->device_code) }}" required />
                            @error('device_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Device Name <span class="text-danger">*</span></label>
                            <input type="text" name="device_name" class="form-control @error('device_name') is-invalid @enderror" 
                                   value="{{ old('device_name', $biometricDevice->device_name) }}" required />
                            @error('device_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Device Type <span class="text-danger">*</span></label>
                            <select name="device_type" class="form-select @error('device_type') is-invalid @enderror" required>
                                <option value="fingerprint" {{ old('device_type', $biometricDevice->device_type) == 'fingerprint' ? 'selected' : '' }}>Fingerprint</option>
                                <option value="face" {{ old('device_type', $biometricDevice->device_type) == 'face' ? 'selected' : '' }}>Face Recognition</option>
                                <option value="card" {{ old('device_type', $biometricDevice->device_type) == 'card' ? 'selected' : '' }}>Card Reader</option>
                                <option value="palm" {{ old('device_type', $biometricDevice->device_type) == 'palm' ? 'selected' : '' }}>Palm Print</option>
                            </select>
                            @error('device_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Connection Type <span class="text-danger">*</span></label>
                            <select name="connection_type" class="form-select @error('connection_type') is-invalid @enderror" required>
                                <option value="api" {{ old('connection_type', $biometricDevice->connection_type) == 'api' ? 'selected' : '' }}>API (REST)</option>
                                <option value="tcp" {{ old('connection_type', $biometricDevice->connection_type) == 'tcp' ? 'selected' : '' }}>TCP/IP</option>
                                <option value="udp" {{ old('connection_type', $biometricDevice->connection_type) == 'udp' ? 'selected' : '' }}>UDP</option>
                                <option value="file_import" {{ old('connection_type', $biometricDevice->connection_type) == 'file_import' ? 'selected' : '' }}>File Import</option>
                            </select>
                            @error('connection_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $biometricDevice->branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">IP Address</label>
                            <input type="text" name="ip_address" class="form-control @error('ip_address') is-invalid @enderror" 
                                   value="{{ old('ip_address', $biometricDevice->ip_address) }}" />
                            @error('ip_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Port</label>
                            <input type="number" name="port" min="1" max="65535" 
                                   class="form-control @error('port') is-invalid @enderror" 
                                   value="{{ old('port', $biometricDevice->port) }}" />
                            @error('port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Device Model</label>
                            <input type="text" name="device_model" class="form-control @error('device_model') is-invalid @enderror" 
                                   value="{{ old('device_model', $biometricDevice->device_model) }}" />
                            @error('device_model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="serial_number" class="form-control @error('serial_number') is-invalid @enderror" 
                                   value="{{ old('serial_number', $biometricDevice->serial_number) }}" />
                            @error('serial_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Timezone <span class="text-danger">*</span></label>
                            <select name="timezone" class="form-select @error('timezone') is-invalid @enderror" required>
                                <option value="Africa/Dar_es_Salaam" {{ old('timezone', $biometricDevice->timezone) == 'Africa/Dar_es_Salaam' ? 'selected' : '' }}>Africa/Dar es Salaam (EAT)</option>
                                <option value="UTC" {{ old('timezone', $biometricDevice->timezone) == 'UTC' ? 'selected' : '' }}>UTC</option>
                            </select>
                            @error('timezone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Sync Interval (Minutes) <span class="text-danger">*</span></label>
                            <input type="number" name="sync_interval_minutes" min="1" max="1440" 
                                   class="form-control @error('sync_interval_minutes') is-invalid @enderror" 
                                   value="{{ old('sync_interval_minutes', $biometricDevice->sync_interval_minutes) }}" required />
                            @error('sync_interval_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="auto_sync" id="auto_sync" 
                                       value="1" {{ old('auto_sync', $biometricDevice->auto_sync) ? 'checked' : '' }}>
                                <label class="form-check-label" for="auto_sync">
                                    Enable Auto Sync
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="3">{{ old('description', $biometricDevice->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                       value="1" {{ old('is_active', $biometricDevice->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Device
                        </button>
                        <a href="{{ route('hr.biometric-devices.show', $biometricDevice->id) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

