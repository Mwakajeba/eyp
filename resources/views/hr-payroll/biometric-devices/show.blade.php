@extends('layouts.main')

@section('title', 'Biometric Device Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Biometric Devices', 'url' => route('hr.biometric-devices.index'), 'icon' => 'bx bx-fingerprint'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
            <h6 class="mb-0 text-uppercase">Biometric Device: {{ $biometricDevice->device_name }}</h6>
            <hr />

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Device Information</h6>
                            <p><strong>Code:</strong> {{ $biometricDevice->device_code }}</p>
                            <p><strong>Name:</strong> {{ $biometricDevice->device_name }}</p>
                            <p><strong>Type:</strong> <span
                                    class="badge bg-info">{{ ucfirst($biometricDevice->device_type) }}</span></p>
                            <p><strong>Model:</strong> {{ $biometricDevice->device_model ?? 'N/A' }}</p>
                            <p><strong>Serial:</strong> {{ $biometricDevice->serial_number ?? 'N/A' }}</p>
                            <p><strong>Branch:</strong>
                                {{ $biometricDevice->branch ? $biometricDevice->branch->name : 'All Branches' }}</p>
                            <p><strong>Connection:</strong> {{ strtoupper($biometricDevice->connection_type) }}</p>
                            @if($biometricDevice->ip_address)
                                <p><strong>IP Address:</strong>
                                    {{ $biometricDevice->ip_address }}{{ $biometricDevice->port ? ':' . $biometricDevice->port : '' }}
                                </p>
                            @endif
                            <p><strong>Timezone:</strong> {{ $biometricDevice->timezone }}</p>
                            <p><strong>Status:</strong>
                                <span class="badge bg-{{ $biometricDevice->is_active ? 'success' : 'secondary' }}">
                                    {{ $biometricDevice->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </p>
                            <div class="d-flex gap-2 mt-3">
                                <a href="{{ route('hr.biometric-devices.edit', $biometricDevice->id) }}"
                                    class="btn btn-sm btn-primary">
                                    <i class="bx bx-edit me-1"></i>Edit
                                </a>
                                <button class="btn btn-sm btn-success" onclick="syncDevice()">
                                    <i class="bx bx-sync me-1"></i>Sync Now
                                </button>
                            </div>
                        </div>
                    </div>

                    @if($biometricDevice->connection_type === 'tcp' && $biometricDevice->ip_address)
                        <div class="card mt-3">
                            <div class="card-body">
                                <h6 class="card-title">ZKTeco Operations</h6>
                                <p class="text-muted small">Direct device operations for ZKTeco devices connected via TCP/IP.
                                </p>

                                <!-- Connection Status -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="small"><strong>Connection Status:</strong></span>
                                        <span id="connectionStatus" class="badge bg-secondary">
                                            <i class="bx bx-loader bx-spin me-1"></i>Checking...
                                        </span>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <!-- Connect/Disconnect Buttons -->
                                    <div id="connectButtonGroup" style="display: none;">
                                        <button class="btn btn-sm btn-success w-100" onclick="connectZKTecoDevice()">
                                            <i class="bx bx-plug me-1"></i>Connect
                                        </button>
                                    </div>
                                    <div id="disconnectButtonGroup" style="display: none;">
                                        <button class="btn btn-sm btn-danger w-100" onclick="disconnectZKTecoDevice()">
                                            <i class="bx bx-unlink me-1"></i>Disconnect
                                        </button>
                                    </div>

                                    <!-- Restart Button -->
                                    <button class="btn btn-sm btn-warning" onclick="restartZKTecoDevice()">
                                        <i class="bx bx-reset me-1"></i>Restart Device
                                    </button>

                                    <hr class="my-2">

                                    <button class="btn btn-sm btn-outline-primary" onclick="testZKTecoConnection()">
                                        <i class="bx bx-wifi me-1"></i>Test Connection
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="syncAllEmployees()">
                                        <i class="bx bx-user-plus me-1"></i>Sync All Employees
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="pullZKTecoLogs()">
                                        <i class="bx bx-download me-1"></i>Pull Attendance Logs
                                    </button>
                                </div>
                                <div id="zktecoQuickResult" class="mt-2"></div>
                            </div>
                        </div>
                    @endif

                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-title">API Credentials</h6>
                            <div class="alert alert-warning">
                                <small><strong>Keep these credentials secure!</strong> They are used to authenticate device
                                    API calls.</small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">API Key:</label>
                                <div class="input-group">
                                    <input type="text" id="api_key" class="form-control form-control-sm"
                                        value="{{ $biometricDevice->api_key }}" readonly>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('api_key')">
                                        <i class="bx bx-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">API Secret:</label>
                                <div class="input-group">
                                    <input type="password" id="api_secret" class="form-control form-control-sm"
                                        value="{{ $biometricDevice->api_secret }}" readonly>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleSecret()">
                                        <i class="bx bx-show" id="toggleIcon"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary"
                                        onclick="copyToClipboard('api_secret')">
                                        <i class="bx bx-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-warning" onclick="regenerateApiKey()">
                                    <i class="bx bx-refresh me-1"></i>Regenerate API Key
                                </button>
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <strong>API Endpoint:</strong><br>
                                    <code>{{ url('/api/biometric/punch') }}</code><br><br>
                                    <strong>Headers:</strong><br>
                                    <code>X-API-Key: [API Key]</code><br>
                                    <code>X-API-Secret: [API Secret]</code>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-2">
                                    <div class="border rounded p-3">
                                        <h4 class="mb-0">{{ $stats['total_logs'] }}</h4>
                                        <small class="text-muted">Total Logs</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="border rounded p-3">
                                        <h4 class="mb-0 text-warning">{{ $stats['pending_logs'] }}</h4>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="border rounded p-3">
                                        <h4 class="mb-0 text-success">{{ $stats['processed_logs'] }}</h4>
                                        <small class="text-muted">Processed</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="border rounded p-3">
                                        <h4 class="mb-0 text-danger">{{ $stats['failed_logs'] }}</h4>
                                        <small class="text-muted">Failed</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="border rounded p-3">
                                        <h4 class="mb-0 text-info">{{ $stats['attendance_records'] ?? 0 }}</h4>
                                        <small class="text-muted">Attendance Records</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Recent Logs</h6>
                            <div>
                                <button class="btn btn-sm btn-success" onclick="processPendingLogs()">
                                    <i class="bx bx-check me-1"></i>Process Pending
                                </button>
                                @if($stats['failed_logs'] > 0)
                                    <button class="btn btn-sm btn-warning" onclick="reprocessFailedLogs()">
                                        <i class="bx bx-refresh me-1"></i>Reprocess Failed ({{ $stats['failed_logs'] }})
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Employee</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($logs as $log)
                                            <tr>
                                                <td>{{ $log->punch_time->format('d M Y H:i') }}</td>
                                                <td>
                                                    @if($log->employee)
                                                        {{ $log->employee->full_name }}
                                                    @else
                                                        <span class="text-danger">
                                                            Unknown (Device ID: {{ $log->device_user_id }})
                                                            @if($log->error_message)
                                                                <br><small
                                                                    class="text-muted">{{ \Illuminate\Support\Str::limit($log->error_message, 50) }}</small>
                                                            @endif
                                                        </span>
                                                    @endif
                                                </td>
                                                <td><span
                                                        class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $log->punch_type)) }}</span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ $log->status == 'processed' ? 'success' : ($log->status == 'failed' ? 'danger' : 'warning') }}">
                                                        {{ ucfirst($log->status) }}
                                                    </span>
                                                    @if($log->status == 'failed' && $log->error_message)
                                                        <br><small class="text-danger"
                                                            title="{{ $log->error_message }}">{{ \Illuminate\Support\Str::limit($log->error_message, 40) }}</small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">No logs yet</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        $(document).ready(function () {
            $('.select2-single').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            // Check connection status on page load
            @if($biometricDevice->connection_type === 'tcp' && $biometricDevice->ip_address)
                checkConnectionStatus();
            @endif

                });

        function syncDevice() {
            $.ajax({
                url: '{{ route("hr.biometric-devices.sync", $biometricDevice->id) }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({ icon: 'success', title: 'Synced!', text: response.message, timer: 3000, showConfirmButton: false });
                        setTimeout(() => location.reload(), 2000);
                    }
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Something went wrong.' });
                }
            });
        }

        function regenerateApiKey() {
            Swal.fire({
                title: 'Regenerate API Key?',
                text: 'This will invalidate the current API key. The device will need to be reconfigured.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, regenerate!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("hr.biometric-devices.regenerate-api-key", $biometricDevice->id) }}',
                        type: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function (response) {
                            if (response.success) {
                                $('#api_key').val(response.api_key);
                                Swal.fire({ icon: 'success', title: 'Regenerated!', text: response.message });
                            }
                        },
                        error: function (xhr) {
                            Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Something went wrong.' });
                        }
                    });
                }
            });
        }

        function deleteOrphanedLogs() {
            Swal.fire({
                title: 'Delete Orphaned Logs?',
                html: 'This will permanently delete attendance logs for device user IDs that no longer exist on the device.<br><br><strong>This action cannot be undone!</strong>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("hr.biometric-devices.delete-orphaned-logs", $biometricDevice->id) }}',
                        type: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function (response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message || 'Orphaned logs deleted successfully.',
                                timer: 3000,
                                showConfirmButton: false
                            });
                            setTimeout(() => location.reload(), 2000);
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: xhr.responseJSON?.message || 'Failed to delete orphaned logs.'
                            });
                        }
                    });
                }
            });
        }

        function testZKTecoConnection() {
            const resultDiv = document.getElementById('zktecoQuickResult');
            resultDiv.innerHTML = '<div class="alert alert-info"><i class="bx bx-loader bx-spin me-1"></i>Testing connection...</div>';

            $.ajax({
                url: '{{ route("hr.biometric-devices.test-connection", $biometricDevice->id) }}',
                type: 'GET',
                success: function (response) {
                    if (response.success) {
                        resultDiv.innerHTML = '<div class="alert alert-success"><strong>Connected!</strong> ' + response.message + '</div>';
                    } else {
                        let errorHtml = '<div class="alert alert-danger"><strong>Failed!</strong> ' + response.message + '</div>';

                        if (response.troubleshooting && response.troubleshooting.length > 0) {
                            errorHtml += '<div class="mt-2"><small><strong>Troubleshooting:</strong><ul class="mb-0 mt-1">';
                            response.troubleshooting.forEach(tip => {
                                errorHtml += '<li>' + tip + '</li>';
                            });
                            errorHtml += '</ul></small></div>';
                        }

                        resultDiv.innerHTML = errorHtml;
                    }
                },
                error: function (xhr) {
                    let errorMsg = xhr.responseJSON?.message || 'Connection test failed';
                    let errorHtml = '<div class="alert alert-danger"><strong>Error!</strong> ' + errorMsg + '</div>';

                    if (xhr.responseJSON?.troubleshooting && xhr.responseJSON.troubleshooting.length > 0) {
                        errorHtml += '<div class="mt-2"><small><strong>Troubleshooting:</strong><ul class="mb-0 mt-1">';
                        xhr.responseJSON.troubleshooting.forEach(tip => {
                            errorHtml += '<li>' + tip + '</li>';
                        });
                        errorHtml += '</ul></small></div>';
                    }

                    resultDiv.innerHTML = errorHtml;
                }
            });
        }

        function pullZKTecoLogs() {
            const resultDiv = document.getElementById('zktecoQuickResult');
            resultDiv.innerHTML = '<div class="alert alert-info"><i class="bx bx-loader bx-spin me-1"></i>Pulling attendance logs...</div>';

            $.ajax({
                url: '{{ route("hr.biometric-devices.pull-logs", $biometricDevice->id) }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    if (response.success) {
                        resultDiv.innerHTML = '<div class="alert alert-success"><strong>Success!</strong> ' + response.message + '</div>';
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        resultDiv.innerHTML = '<div class="alert alert-danger"><strong>Failed!</strong> ' + response.message + '</div>';
                    }
                },
                error: function (xhr) {
                    resultDiv.innerHTML = '<div class="alert alert-danger"><strong>Error!</strong> ' + (xhr.responseJSON?.message || 'Failed to pull logs') + '</div>';
                }
            });
        }

        function processPendingLogs() {
            Swal.fire({
                title: 'Process Pending Logs?',
                text: 'This will process all pending attendance logs and create attendance records.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, process!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("hr.biometric-devices.process-logs", $biometricDevice->id) }}',
                        type: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Processed!',
                                    text: response.message,
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                                setTimeout(() => location.reload(), 2000);
                            }
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: xhr.responseJSON?.message || 'Failed to process logs.'
                            });
                        }
                    });
                }
            });
        }

        function reprocessFailedLogs() {
            Swal.fire({
                title: 'Reprocess Failed Logs?',
                text: 'This will attempt to process failed logs again.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, reprocess!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("hr.biometric-devices.reprocess-failed-logs", $biometricDevice->id) }}',
                        type: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function (response) {
                            Swal.fire({
                                icon: response.success ? 'success' : 'warning',
                                title: response.success ? 'Success!' : 'Partial Success',
                                text: response.message,
                                timer: 4000,
                                showConfirmButton: false
                            });
                            setTimeout(() => location.reload(), 2500);
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: xhr.responseJSON?.message || 'Failed to reprocess logs.'
                            });
                        }
                    });
                }
            });
        }

        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            document.execCommand('copy');
            Swal.fire({ icon: 'success', title: 'Copied!', text: 'API ' + (elementId === 'api_key' ? 'Key' : 'Secret') + ' copied to clipboard', timer: 2000, showConfirmButton: false });
        }

        function toggleSecret() {
            const input = document.getElementById('api_secret');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bx-show');
                icon.classList.add('bx-hide');
            } else {
                input.type = 'password';
                icon.classList.remove('bx-hide');
                icon.classList.add('bx-show');
            }
        }

        // ZKTeco Connection Functions
        function checkConnectionStatus() {
            $.ajax({
                url: '{{ route("hr.biometric-devices.device-data", $biometricDevice->id) }}',
                type: 'GET',
                success: function (response) {
                    updateConnectionUI(response.connected);
                },
                error: function () {
                    updateConnectionUI(false);
                }
            });
        }

        function updateConnectionUI(isConnected) {
            const statusEl = document.getElementById('connectionStatus');
            const connectBtn = document.getElementById('connectButtonGroup');
            const disconnectBtn = document.getElementById('disconnectButtonGroup');

            if (isConnected) {
                statusEl.innerHTML = '<i class="bx bx-check-circle me-1"></i>Connected';
                statusEl.className = 'badge bg-success';
                connectBtn.style.display = 'none';
                disconnectBtn.style.display = 'block';
            } else {
                statusEl.innerHTML = '<i class="bx bx-x-circle me-1"></i>Disconnected';
                statusEl.className = 'badge bg-secondary';
                connectBtn.style.display = 'block';
                disconnectBtn.style.display = 'none';
            }
        }

        function connectZKTecoDevice() {
            const resultDiv = document.getElementById('zktecoQuickResult');
            resultDiv.innerHTML = '<div class="alert alert-info"><i class="bx bx-loader bx-spin me-1"></i>Connecting to device...</div>';

            $.ajax({
                url: '{{ route("hr.biometric-devices.connect", $biometricDevice->id) }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    if (response.success) {
                        resultDiv.innerHTML = '<div class="alert alert-success"><strong>Connected!</strong> ' + response.message + '</div>';
                        updateConnectionUI(true);
                        setTimeout(() => {
                            resultDiv.innerHTML = '';
                        }, 3000);
                    } else {
                        resultDiv.innerHTML = '<div class="alert alert-danger"><strong>Failed!</strong> ' + response.message + '</div>';
                        updateConnectionUI(false);
                    }
                },
                error: function (xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Connection failed';
                    resultDiv.innerHTML = '<div class="alert alert-danger"><strong>Error!</strong> ' + errorMsg + '</div>';
                    updateConnectionUI(false);
                }
            });
        }

        function disconnectZKTecoDevice() {
            const resultDiv = document.getElementById('zktecoQuickResult');
            resultDiv.innerHTML = '<div class="alert alert-info"><i class="bx bx-loader bx-spin me-1"></i>Disconnecting from device...</div>';

            $.ajax({
                url: '{{ route("hr.biometric-devices.disconnect", $biometricDevice->id) }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    if (response.success) {
                        resultDiv.innerHTML = '<div class="alert alert-success"><strong>Disconnected!</strong> ' + response.message + '</div>';
                        updateConnectionUI(false);
                        setTimeout(() => {
                            resultDiv.innerHTML = '';
                        }, 3000);
                    } else {
                        resultDiv.innerHTML = '<div class="alert alert-danger"><strong>Failed!</strong> ' + response.message + '</div>';
                    }
                },
                error: function (xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Disconnect failed';
                    resultDiv.innerHTML = '<div class="alert alert-danger"><strong>Error!</strong> ' + errorMsg + '</div>';
                }
            });
        }

        function syncAllEmployees() {
            Swal.fire({
                title: 'Sync All Employees?',
                text: 'This will create users on the device for all active employees in your system. This may take a few minutes.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, sync all!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const resultDiv = document.getElementById('zktecoQuickResult');
                    resultDiv.innerHTML = '<div class="alert alert-info"><i class="bx bx-loader bx-spin me-1"></i>Syncing all employees to device...</div>';

                    $.ajax({
                        url: '{{ route("hr.biometric-devices.sync-all-employees", $biometricDevice->id) }}',
                        type: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function (response) {
                            if (response.success) {
                                let message = response.message;
                                if (response.errors && response.errors.length > 0) {
                                    message += '<br><small>Errors: ' + response.errors.slice(0, 5).join(', ') + '</small>';
                                }
                                resultDiv.innerHTML = '<div class="alert alert-success"><strong>Success!</strong> ' + message + '</div>';
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sync Completed!',
                                    html: message,
                                    timer: 5000,
                                    showConfirmButton: false
                                });
                                setTimeout(() => location.reload(), 3000);
                            } else {
                                resultDiv.innerHTML = '<div class="alert alert-warning"><strong>Partial Success:</strong> ' + response.message + '</div>';
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Sync Completed',
                                    text: response.message
                                });
                                setTimeout(() => location.reload(), 2000);
                            }
                        },
                        error: function (xhr) {
                            const errorMsg = xhr.responseJSON?.message || 'Sync failed';
                            resultDiv.innerHTML = '<div class="alert alert-danger"><strong>Error!</strong> ' + errorMsg + '</div>';
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: errorMsg
                            });
                        }
                    });
                }
            });
        }

        function restartZKTecoDevice() {
            Swal.fire({
                title: 'Restart Device?',
                text: 'This will restart the ZKTeco device. The device will be unavailable for a few moments.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, restart!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const resultDiv = document.getElementById('zktecoQuickResult');
                    resultDiv.innerHTML = '<div class="alert alert-info"><i class="bx bx-loader bx-spin me-1"></i>Sending restart command...</div>';

                    $.ajax({
                        url: '{{ route("hr.biometric-devices.restart", $biometricDevice->id) }}',
                        type: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function (response) {
                            if (response.success) {
                                resultDiv.innerHTML = '<div class="alert alert-success"><strong>Success!</strong> ' + response.message + '</div>';
                                updateConnectionUI(false);
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Restart Command Sent!',
                                    text: response.message,
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            } else {
                                resultDiv.innerHTML = '<div class="alert alert-danger"><strong>Failed!</strong> ' + response.message + '</div>';
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message
                                });
                            }
                        },
                        error: function (xhr) {
                            const errorMsg = xhr.responseJSON?.message || 'Restart command failed';
                            resultDiv.innerHTML = '<div class="alert alert-danger"><strong>Error!</strong> ' + errorMsg + '</div>';
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: errorMsg
                            });
                        }
                    });
                }
            });
        }
    </script>
@endpush