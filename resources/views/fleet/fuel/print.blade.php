<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Log - {{ $fuelLog->id }}</title>
    <style>
        @php
            $docPageSize = \App\Models\SystemSetting::getValue('document_page_size', 'A5');
            $docOrientation = \App\Models\SystemSetting::getValue('document_orientation', 'portrait');
            $docMarginTop = \App\Models\SystemSetting::getValue('document_margin_top', '2.54cm');
            $docMarginRight = \App\Models\SystemSetting::getValue('document_margin_right', '2.54cm');
            $docMarginBottom = \App\Models\SystemSetting::getValue('document_margin_bottom', '2.54cm');
            $docMarginLeft = \App\Models\SystemSetting::getValue('document_margin_left', '2.54cm');
            $docFontFamily = \App\Models\SystemSetting::getValue('document_font_family', 'DejaVu Sans');
            $docFontSize = (int) (\App\Models\SystemSetting::getValue('document_base_font_size', 10));
            $docLineHeight = \App\Models\SystemSetting::getValue('document_line_height', '1.4');
            $docTextColor = \App\Models\SystemSetting::getValue('document_text_color', '#000000');
            $docBgColor = \App\Models\SystemSetting::getValue('document_background_color', '#FFFFFF');
            $docHeaderColor = \App\Models\SystemSetting::getValue('document_header_color', '#000000');
            $docAccentColor = \App\Models\SystemSetting::getValue('document_accent_color', '#b22222');
            $docTableHeaderBg = \App\Models\SystemSetting::getValue('document_table_header_bg', '#f2f2f2');
            $docTableHeaderText = \App\Models\SystemSetting::getValue('document_table_header_text', '#000000');
            $pageSizeCss = $docPageSize . ' ' . $docOrientation;
        @endphp
        @page {
            size: {{ $pageSizeCss }};
            margin: {{ $docMarginTop }} {{ $docMarginRight }} {{ $docMarginBottom }} {{ $docMarginLeft }};
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: '{{ $docFontFamily }}', sans-serif;
            font-size: {{ $docFontSize }}px;
            line-height: {{ $docLineHeight }};
            color: {{ $docTextColor }};
            background-color: {{ $docBgColor }};
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100vh;
        }

        .print-container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
            position: relative;
            top: 0;
            left: 0;
        }

        @media print {
            @page {
                size: {{ $pageSizeCss }};
                margin: {{ $docMarginTop }} {{ $docMarginRight }} {{ $docMarginBottom }} {{ $docMarginLeft }};
            }
            
            body {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .print-container {
                margin: 0 !important;
                padding: 0 !important;
                position: relative !important;
                top: 0 !important;
                left: 0 !important;
            }
        }

        /* === HEADER === */
        .header {
            text-align: center;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            padding-bottom: 5px;
            margin-top: 0;
            padding-top: 0;
        }

        .company-name {
            color: {{ $docAccentColor }};
            font-size: 15px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .company-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 3px;
        }

        .company-logo img {
            width: 150px;
            height: 70px;
            margin-right: 8px;
        }

        .company-details {
            font-size: 8px;
            line-height: 1.3;
            margin-top: 2px;
        }

        /* === FUEL LOG TITLE === */
        .fuel-title {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin: 8px 0;
            text-transform: uppercase;
        }

        /* === INFO SECTION === */
        .fuel-details {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            margin-bottom: 8px;
        }

        .vehicle-info {
            flex: 1;
        }

        .fuel-info {
            flex: 1;
            text-align: right;
        }

        .fuel-info table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .fuel-info td {
            border: 1px solid #000;
            padding: 2px;
        }

        .fuel-info td:first-child {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .field-label {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .field-value {
            margin-bottom: 5px;
        }

        /* === FUEL DETAILS TABLE === */
        .fuel-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .fuel-table th,
        .fuel-table td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 8px;
        }

        .fuel-table th {
            background-color: {{ $docTableHeaderBg }};
            font-weight: bold;
            text-align: center;
            color: {{ $docTableHeaderText }};
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* === SUMMARY === */
        .summary {
            margin-top: 5px;
            font-size: 9px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .summary-row.total {
            border-top: 1px solid #000;
            font-weight: bold;
            padding-top: 3px;
        }

        /* === FOOTER === */
        .footer {
            font-size: 8px;
            margin-top: 10px;
        }

        .signature-line {
            margin-top: 8px;
        }

        .page-info {
            text-align: center;
            font-size: 8px;
            margin-top: 8px;
        }

        /* === ODOMETER SECTION === */
        .odometer-section {
            margin-top: 8px;
            font-size: 9px;
        }

        .odometer-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        /* === NOTES SECTION === */
        .notes-section {
            font-size: 8px;
            margin-top: 8px;
        }

        .notes-section h6 {
            margin: 0 0 2px 0;
            font-weight: bold;
            text-transform: uppercase;
        }

    </style>

</head>
<body>
    <div class="print-container">
        <div class="header">
        <div class="company-info">
            <div class="company-logo">
                @if($fuelLog->company && $fuelLog->company->logo)
                <img src="{{ asset('storage/' . $fuelLog->company->logo) }}" alt="Logo">
                @endif
                <div>
                    <h1 class="company-name">{{ $fuelLog->company->name ?? 'SMARTACCOUNTING' }}</h1>
                </div>
            </div>
            <div class="company-details">
                <div><strong>P.O. Box:</strong> {{ $fuelLog->company->address ?? 'P.O.BOX 00000, City, Country' }}</div>
                <div><strong>Phone:</strong> {{ $fuelLog->company->phone ?? '+255 000 000 000' }}</div>
                <div><strong>Email:</strong> {{ $fuelLog->company->email ?? 'company@email.com' }}</div>
            </div>
        </div>
    </div>

    <div class="fuel-title">Fuel Log</div>

    <div class="fuel-details">
        <div class="vehicle-info">
            <div class="field-label"><strong>Vehicle:</strong></div>
            <div class="field-value">{{ $fuelLog->vehicle->name ?? 'N/A' }} ({{ $fuelLog->vehicle->registration_number ?? 'N/A' }})</div>
            @if($fuelLog->trip)
            <div class="field-label" style="margin-top: 5px;"><strong>Trip:</strong></div>
            <div class="field-value">{{ $fuelLog->trip->trip_number ?? 'N/A' }}</div>
            @endif
            @if($fuelLog->trip && $fuelLog->trip->route)
            <div class="field-label" style="margin-top: 5px;"><strong>Route:</strong></div>
            <div class="field-value">{{ $fuelLog->trip->route->origin_location ?? '' }} → {{ $fuelLog->trip->route->destination_location ?? '' }}</div>
            @endif
        </div>
        <div class="fuel-info">
            <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                <tr>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold; width: 30%;">Date Filled:</td>
                    <td style="padding: 2px; border: 1px solid #000; width: 70%;">{{ $fuelLog->date_filled ? $fuelLog->date_filled->format('d/m/Y') : 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Time Filled:</td>
                    <td style="padding: 2px; border: 1px solid #000;">{{ $fuelLog->time_filled ? \Carbon\Carbon::parse($fuelLog->time_filled)->format('H:i') : 'N/A' }}</td>
                </tr>
                @if($fuelLog->receipt_number)
                <tr>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Receipt No:</td>
                    <td style="padding: 2px; border: 1px solid #000;">{{ $fuelLog->receipt_number }}</td>
                </tr>
                @endif
                @if($fuelLog->company)
                <tr>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">TIN:</td>
                    <td style="padding: 2px; border: 1px solid #000;">{{ $fuelLog->company->tin ?? 'N/A' }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Time:</td>
                    <td style="padding: 2px; border: 1px solid #000;">{{ $fuelLog->created_at->format('h:i:s A') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <table class="fuel-table">
        <thead>
            <tr>
                <th style="width: 20%;">Fuel Type</th>
                <th style="width: 20%;">Fuel Station</th>
                <th style="width: 15%;">Liters</th>
                <th style="width: 15%;">Cost/Liter</th>
                <th style="width: 15%;">Total Cost</th>
                <th style="width: 15%;">GL Account</th>
            </tr>
        </thead>
        <tbody>
            @if($costLines->count() > 0)
                @foreach($costLines as $line)
                <tr>
                    <td class="text-center">{{ $fuelLog->fuel_type ? ucfirst($fuelLog->fuel_type) : 'N/A' }}</td>
                    <td>{{ $fuelLog->fuel_station ?? 'N/A' }}</td>
                    <td class="text-center">{{ $costLines->count() === 1 ? number_format($fuelLog->liters_filled ?? 0, 2) : '-' }} L</td>
                    <td class="text-right">{{ $costLines->count() === 1 ? number_format($fuelLog->cost_per_liter ?? 0, 2) : '-' }}</td>
                    <td class="text-right">{{ number_format($line->amount ?? 0, 2) }}</td>
                    <td style="font-size: 7px;">{{ $line->chartAccount->account_code ?? '' }} - {{ $line->chartAccount->account_name ?? 'N/A' }}</td>
                </tr>
                @endforeach
            @else
            <tr>
                <td class="text-center">{{ $fuelLog->fuel_type ? ucfirst($fuelLog->fuel_type) : 'N/A' }}</td>
                <td>{{ $fuelLog->fuel_station ?? 'N/A' }}</td>
                <td class="text-center">{{ number_format($fuelLog->liters_filled ?? 0, 2) }} L</td>
                <td class="text-right">{{ number_format($fuelLog->cost_per_liter ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($fuelLog->total_cost ?? 0, 2) }}</td>
                <td style="font-size: 7px;">{{ $fuelLog->glAccount->account_code ?? 'N/A' }} - {{ $fuelLog->glAccount->account_name ?? 'N/A' }}</td>
            </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right" style="font-weight: bold;">Total Cost:</td>
                <td class="text-right" style="font-weight: bold;">{{ number_format($fuelLog->total_cost ?? 0, 2) }} TZS</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary">
        <div class="summary-row total">
            <span>Total Cost:</span>
            <span>{{ number_format($fuelLog->total_cost ?? 0, 2) }} TZS</span>
        </div>
    </div>

    @if($fuelLog->odometer_reading || $fuelLog->previous_odometer || $fuelLog->km_since_last_fill || $fuelLog->fuel_efficiency_km_per_liter || $fuelLog->cost_per_km)
    <div class="odometer-section">
        <div style="font-weight: bold; margin-bottom: 3px; text-align: center; border-bottom: 1px solid #000; padding-bottom: 2px;">ODOMETER INFORMATION</div>
        <div class="odometer-row">
            <span>Current Odometer:</span>
            <span>{{ number_format($fuelLog->odometer_reading ?? 0, 2) }}</span>
        </div>
        @if($fuelLog->previous_odometer !== null)
        <div class="odometer-row">
            <span>Previous Odometer:</span>
            <span>{{ number_format($fuelLog->previous_odometer, 2) }}</span>
        </div>
        @endif
        @if($fuelLog->km_since_last_fill)
        <div class="odometer-row">
            <span>Km Since Last Fill:</span>
            <span>{{ number_format($fuelLog->km_since_last_fill, 2) }} km</span>
        </div>
        @endif
        @if($fuelLog->fuel_efficiency_km_per_liter)
        <div class="odometer-row">
            <span>Fuel Efficiency:</span>
            <span>{{ number_format($fuelLog->fuel_efficiency_km_per_liter, 2) }} km/L</span>
        </div>
        @endif
        @if($fuelLog->cost_per_km)
        <div class="odometer-row">
            <span>Cost Per Km:</span>
            <span>{{ number_format($fuelLog->cost_per_km, 2) }} TZS</span>
        </div>
        @endif
    </div>
    @endif

    @if($fuelLog->fuel_card_used)
    <div class="odometer-section" style="margin-top: 8px;">
        <div style="font-weight: bold; margin-bottom: 3px; text-align: center; border-bottom: 1px solid #000; padding-bottom: 2px;">FUEL CARD INFORMATION</div>
        @if($fuelLog->fuel_card_number)
        <div class="odometer-row">
            <span>Fuel Card Number:</span>
            <span>{{ $fuelLog->fuel_card_number }}</span>
        </div>
        @endif
        @if($fuelLog->fuel_card_type)
        <div class="odometer-row">
            <span>Fuel Card Type:</span>
            <span>{{ ucfirst(str_replace('_', ' ', $fuelLog->fuel_card_type)) }}</span>
        </div>
        @endif
    </div>
    @endif

    @if($paidFromAccount)
    <div class="odometer-section" style="margin-top: 8px;">
        <div style="font-weight: bold; margin-bottom: 3px; text-align: center; border-bottom: 1px solid #000; padding-bottom: 2px;">PAYMENT INFORMATION</div>
        <div class="odometer-row">
            <span>Paid From:</span>
            <span>{{ $paidFromAccount->name ?? 'N/A' }}{{ $paidFromAccount->account_number ? ' - ' . $paidFromAccount->account_number : '' }}</span>
        </div>
    </div>
    @endif

    @if($fuelLog->notes)
    <div class="notes-section">
        <h6>NOTES</h6>
        <div>{{ $fuelLog->notes }}</div>
    </div>
    @endif

    @if($fuelLog->approval_status)
    <div class="notes-section" style="margin-top: 5px;">
        <h6>APPROVAL STATUS</h6>
        <div>{{ ucfirst($fuelLog->approval_status) }}
            @if($fuelLog->approval_status === 'approved' && $fuelLog->approvedBy)
                - Approved by {{ $fuelLog->approvedBy->name ?? 'N/A' }} on {{ $fuelLog->approved_at ? $fuelLog->approved_at->format('d/m/Y H:i') : '' }}
            @endif
        </div>
    </div>
    @endif

    <div class="footer">
        <div class="signature-line">
            <strong>Signature................................................</strong>
        </div>

        <div class="page-info">
            <div>Fuel Log ID: {{ $fuelLog->id }}</div>
            <div>Page 1 of 1</div>
        </div>
    </div>
    </div> <!-- Close print-container -->

    <script nonce="{{ $cspNonce ?? '' }}">
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
