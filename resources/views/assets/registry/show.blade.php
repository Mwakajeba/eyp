@extends('layouts.main')

@section('title', 'Asset Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.registry.index'), 'icon' => 'bx bx-clipboard'],
            ['label' => 'Asset Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">Asset Details</h4>
                        <p class="text-muted mb-0">{{ $asset->code }} - {{ $asset->name }}</p>
                    </div>
                    <div class="page-title-right d-flex gap-2">
                        <a href="{{ route('assets.registry.edit', $encodedId) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i>Edit Asset
                        </a>
                        <a href="{{ route('assets.registry.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Registry
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asset Header -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Asset Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="180"><strong>Asset Code:</strong></td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $asset->code }}</span>
                                            @if($asset->barcode)
                                                <span class="ms-2 small text-muted">Barcode: {{ $asset->barcode }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Asset Name:</strong></td>
                                        <td>{{ $asset->name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Category:</strong></td>
                                        <td>{{ $asset->category->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Depreciation Method:</strong></td>
                                        <td>
                                            @if($asset->category)
                                                @php
                                                    $method = $asset->category->default_depreciation_method ?? 'straight_line';
                                                    $methodNames = [
                                                        'straight_line' => 'Straight Line',
                                                        'declining_balance' => 'Declining Balance',
                                                        'syd' => 'Sum of Years\' Digits',
                                                        'units' => 'Units of Production',
                                                    ];
                                                    $methodName = $methodNames[$method] ?? ucfirst(str_replace('_', ' ', $method));
                                                @endphp
                                                <span class="badge bg-secondary">{{ $methodName }}</span>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Useful Life:</strong></td>
                                        <td>
                                            @if($asset->category)
                                                @php
                                                    $usefulLifeMonths = $asset->category->default_useful_life_months ?? null;
                                                    if ($usefulLifeMonths) {
                                                        $usefulLifeYears = round($usefulLifeMonths / 12, 1);
                                                    }
                                                @endphp
                                                @if($usefulLifeMonths)
                                                    {{ number_format($usefulLifeMonths) }} months
                                                    @if($usefulLifeYears > 0)
                                                        ({{ number_format($usefulLifeYears, 1) }} years)
                                                    @endif
                                                @else
                                                    N/A
                                                @endif
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <span class="badge bg-info text-dark">{{ Str::of($asset->status)->replace('_',' ')->title() }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Purchase Date:</strong></td>
                                        <td>{{ optional($asset->purchase_date)->format('d M Y') ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Capitalization Date:</strong></td>
                                        <td>{{ $asset->capitalization_date ? \Carbon\Carbon::parse($asset->capitalization_date)->format('d M Y') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Model:</strong></td>
                                        <td>{{ $asset->model ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Manufacturer:</strong></td>
                                        <td>{{ $asset->manufacturer ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Serial Number:</strong></td>
                                        <td>{{ $asset->serial_number ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tag / RFID:</strong></td>
                                        <td>{{ $asset->tag ?? 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Location & Custodian</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="180"><strong>Physical Location:</strong></td>
                                        <td>{{ $asset->location ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Building Reference:</strong></td>
                                        <td>{{ $asset->building_reference ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>GPS Coordinates:</strong></td>
                                        <td>
                                            @if($asset->gps_lat && $asset->gps_lng)
                                                {{ number_format($asset->gps_lat, 7) }}, {{ number_format($asset->gps_lng, 7) }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Department:</strong></td>
                                        <td>
                                            @if($asset->department_id)
                                                @php
                                                    $dept = \App\Models\Hr\Department::find($asset->department_id);
                                                @endphp
                                                {{ $dept->name ?? 'N/A' }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Custodian:</strong></td>
                                        <td>
                                            @if($asset->custodian_user_id)
                                                @php
                                                    $custodian = \App\Models\User::find($asset->custodian_user_id);
                                                @endphp
                                                {{ $custodian->name ?? 'N/A' }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cost & Valuation -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-dollar me-2"></i>Cost & Valuation
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end">Amount (TZS)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Original Cost</strong></td>
                                        <td class="text-end"><strong>{{ number_format($asset->purchase_cost, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Residual Value</td>
                                        <td class="text-end">{{ number_format($asset->salvage_value, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Depreciable Amount</td>
                                        <td class="text-end">{{ number_format($asset->purchase_cost - $asset->salvage_value, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Current Net Book Value (NBV)</strong></td>
                                        <td class="text-end">
                                            @php
                                                $calculatedNBV = \App\Models\Assets\AssetDepreciation::getCurrentBookValue($asset->id, null, $asset->company_id);
                                                $currentNBV = $calculatedNBV ?? $asset->current_nbv ?? $asset->purchase_cost;
                                            @endphp
                                            <strong class="text-primary">{{ number_format($currentNBV, 2) }}</strong>
                                            <a href="{{ route('assets.registry.depreciation-history', $encodedId) }}" class="btn btn-sm btn-outline-primary ms-2" title="View Depreciation History">
                                                <i class="bx bx-history me-1"></i>View History
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-info ms-1" onclick="showDepreciationForecast('{{ $encodedId }}')" title="View Depreciation Forecast">
                                                <i class="bx bx-line-chart me-1"></i>Forecast
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Accumulated Depreciation</td>
                                        <td class="text-end">
                                            @php
                                                $calculatedAccumDepr = \App\Models\Assets\AssetDepreciation::getAccumulatedDepreciation($asset->id, null, $asset->company_id);
                                                $accumDepr = $calculatedAccumDepr > 0 ? $calculatedAccumDepr : ($asset->purchase_cost - $currentNBV);
                                            @endphp
                                            {{ number_format($accumDepr, 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-shield me-2"></i>Warranty & Insurance
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Warranty Period:</span>
                                <span>{{ $asset->warranty_months ? $asset->warranty_months.' months' : 'N/A' }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Warranty Expiry:</span>
                                <span>
                                    @if($asset->warranty_expiry_date)
                                        {{ \Carbon\Carbon::parse($asset->warranty_expiry_date)->format('d M Y') }}
                                        @if(\Carbon\Carbon::parse($asset->warranty_expiry_date) < now())
                                            <span class="badge bg-danger ms-1">Expired</span>
                                        @elseif(\Carbon\Carbon::parse($asset->warranty_expiry_date) < now()->addDays(30))
                                            <span class="badge bg-warning ms-1">Expiring Soon</span>
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Insurance Policy:</span>
                                <span>{{ $asset->insurance_policy_no ?? 'N/A' }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Insured Value:</span>
                                <span>{{ $asset->insured_value ? number_format($asset->insured_value, 2) : 'N/A' }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Insurance Expiry:</span>
                                <span>
                                    @if($asset->insurance_expiry_date)
                                        {{ \Carbon\Carbon::parse($asset->insurance_expiry_date)->format('d M Y') }}
                                        @if(\Carbon\Carbon::parse($asset->insurance_expiry_date) < now())
                                            <span class="badge bg-danger ms-1">Expired</span>
                                        @elseif(\Carbon\Carbon::parse($asset->insurance_expiry_date) < now()->addDays(30))
                                            <span class="badge bg-warning ms-1">Expiring Soon</span>
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-qr me-2"></i>QR Code
                        </h5>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary" id="btn-print-sticker">
                                <i class="bx bx-printer me-1"></i> Print Sticker
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <div id="qr-preview" class="d-inline-block p-2 border rounded"></div>
                            <div class="mt-2 small text-muted">{{ $asset->code }} @if($asset->name) - {{ Str::limit($asset->name, 40) }} @endif</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description & Attachments -->
        <div class="row">
            @if($asset->description)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-note me-2"></i>Description
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $asset->description }}</p>
                    </div>
                </div>
            </div>
            @endif
            @php
                $attachments = $asset->attachments ? json_decode($asset->attachments, true) : [];
            @endphp
            @if(!empty($attachments))
            <div class="col-md-{{ $asset->description ? '6' : '12' }}">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-paperclip me-2"></i>Attachments
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            @foreach($attachments as $path)
                            <div class="col-md-6">
                                <div class="border rounded p-2 d-flex align-items-center justify-content-between">
                                    <span class="small text-truncate" style="max-width: 70%">{{ basename($path) }}</span>
                                    <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ Storage::disk('public')->url($path) }}" title="View">
                                        <i class="bx bx-link-external"></i>
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    function generateQRCode() {
        const data = {
            code: @json($asset->code),
            name: @json($asset->name),
            category: @json(optional($asset->category)->name),
            id: @json($encodedId ?? \Vinkla\Hashids\Facades\Hashids::encode($asset->id))
        };

        const qrContainer = document.getElementById('qr-preview');
        if (!qrContainer) return;

        if (typeof QRCode === 'undefined') {
            // Wait for library to load
            setTimeout(generateQRCode, 100);
            return;
        }

        try {
            qrContainer.innerHTML = '';
            const qrText = JSON.stringify({ 
                t: 'asset', 
                c: data.code, 
                n: data.name, 
                g: data.category, 
                i: data.id 
            });
            
            new QRCode(qrContainer, {
                text: qrText,
                width: 140,
                height: 140,
                correctLevel: QRCode.CorrectLevel.M
            });
        } catch (e) {
            console.error('QR Code generation error:', e);
            qrContainer.innerHTML = '<div class="text-danger small p-2">Failed to generate QR code. Please refresh the page.</div>';
        }
    }

    // Try to generate immediately, or wait for DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', generateQRCode);
    } else {
        generateQRCode();
    }

    // Print sticker handler
    const printBtn = document.getElementById('btn-print-sticker');
    if (printBtn) {
        printBtn.addEventListener('click', function(){
            const assetData = {
                code: @json($asset->code),
                name: @json($asset->name),
                category: @json(optional($asset->category)->name),
                id: @json($encodedId ?? \Vinkla\Hashids\Facades\Hashids::encode($asset->id))
            };
            
            const qrData = JSON.stringify({ 
                t: 'asset', 
                c: assetData.code, 
                n: assetData.name, 
                g: assetData.category, 
                i: assetData.id 
            });
            
            const stickerHtml = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Asset Sticker</title><style>@page { size: A4; margin: 8mm; }html, body { padding:0; margin:0; }.sticker { width: 58mm; height: 38mm; border: 1px dashed #999; padding: 3mm; display: flex; align-items: center; gap: 6px; font-family: Arial, Helvetica, sans-serif; }.qr { width: 24mm; height: 24mm; }.meta { flex: 1; min-width: 0; }.meta .line { font-size: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }.meta .code { font-weight: 700; font-size: 11px; }.footer { font-size: 9px; color: #555; }</style></head><body><div class="sticker"><div class="qr" id="qr-print"></div><div class="meta"><div class="code">' + (assetData.code || '') + '</div><div class="line">' + (assetData.name || '') + '</div><div class="line">' + (assetData.category || '') + '</div><div class="footer">' + (new Date()).getFullYear() + '</div></div></div><script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"><\/script><script nonce="{{ $cspNonce ?? '' }}">setTimeout(function(){if(typeof QRCode!==\'undefined\'){new QRCode(document.getElementById(\'qr-print\'),{text:' + qrData + ',width:120,height:120,correctLevel:QRCode.CorrectLevel.M});setTimeout(function(){window.print();},300);}else{setTimeout(arguments.callee,100);}},100);<\/script></body></html>';
            
            const win = window.open('', '_blank');
            if (!win) {
                // Fallback using Blob URL
                try {
                    const blob = new Blob([stickerHtml], { type: 'text/html' });
                    const url = URL.createObjectURL(blob);
                    window.open(url, '_blank');
                    // Note: browser print will be triggered by the in-page script
                } catch(e) {
                    alert('Popup blocked. Please allow popups for this site and try again.');
                }
                return;
            }
            // Primary path
            win.document.open();
            win.document.write(stickerHtml);
            win.document.close();
        });
    }
})();

// Depreciation Forecast
function showDepreciationForecast(assetId) {
    $.ajax({
        url: '{{ url('/asset-management/depreciation/forecast') }}/' + assetId,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                let tableHtml = `
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Period</th>
                                    <th class="text-end">Book Value Before</th>
                                    <th class="text-end">Depreciation</th>
                                    <th class="text-end">Accumulated Depr</th>
                                    <th class="text-end">Book Value After</th>
                                </tr>
                            </thead>
                            <tbody>`;
                
                response.forecast.forEach(function(item) {
                    tableHtml += `
                        <tr>
                            <td>${item.period}</td>
                            <td class="text-end">TZS ${parseFloat(item.book_value_before).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td class="text-end text-danger">-${parseFloat(item.depreciation_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td class="text-end">TZS ${parseFloat(item.accumulated_depreciation).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td class="text-end text-primary fw-semibold">TZS ${parseFloat(item.book_value_after).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        </tr>`;
                });
                
                tableHtml += `</tbody></table></div>`;
                
                Swal.fire({
                    title: `Depreciation Forecast - ${response.asset.name}`,
                    html: tableHtml,
                    width: '900px',
                    showCloseButton: true,
                    showConfirmButton: true,
                    confirmButtonText: 'Close'
                });
            }
        },
        error: function() {
            Swal.fire('Error!', 'Failed to load depreciation forecast', 'error');
        }
    });
}
</script>
@endpush
