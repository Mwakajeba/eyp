<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Efficiency Report</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 15px;
            color: #333;
            background: #fff;
        }
        
        .header {
            margin-bottom: 20px;
            border-bottom: 3px solid #fd7e14;
            padding-bottom: 15px;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
        
        .logo-section {
            flex-shrink: 0;
        }
        
        .company-logo {
            max-height: 80px;
            max-width: 120px;
            object-fit: contain;
        }
        
        .title-section {
            text-align: center;
            flex-grow: 1;
        }
        
        .header h1 {
            color: #fd7e14;
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        
        .company-name {
            color: #333;
            margin: 5px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .header .subtitle {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        
        .report-info {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #fd7e14;
        }
        
        .report-info h3 {
            margin: 0 0 10px 0;
            color: #fd7e14;
            font-size: 16px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 15px 5px 0;
            width: 120px;
            color: #555;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 0;
            color: #333;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            table-layout: fixed;
        }
        
        .data-table thead {
            background: #fd7e14;
            color: white;
        }
        
        .data-table th {
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            word-wrap: break-word;
        }
        
        .data-table th:nth-child(1) { width: 25%; }
        .data-table th:nth-child(2) { width: 12%; }
        .data-table th:nth-child(3) { width: 12%; }
        .data-table th:nth-child(4) { width: 12%; }
        .data-table th:nth-child(5) { width: 12%; }
        .data-table th:nth-child(6) { width: 12%; }
        .data-table th:nth-child(7) { width: 10%; }
        
        .data-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #dee2e6;
            font-size: 9px;
            word-wrap: break-word;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tfoot {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .data-table tfoot td {
            border-top: 2px solid #fd7e14;
            padding: 10px 6px;
        }
        
        .number {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .text-success {
            color: #28a745;
            font-weight: 600;
        }
        
        .text-danger {
            color: #dc3545;
            font-weight: 600;
        }
        
        .text-info {
            color: #fd7e14;
            font-weight: 600;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        
        .reference-info {
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @if($company && $company->logo)
                <div class="logo-section">
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="{{ $company->name }}" class="company-logo">
                </div>
            @endif
            <div class="title-section">
                <h1>Fuel Efficiency Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ $generatedAt->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <h3>Report Parameters</h3>
        <div class="info-grid">
            @if($dateFrom && $dateTo)
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</div>
            </div>
            @elseif($dateFrom)
            <div class="info-row">
                <div class="info-label">Date From:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }}</div>
            </div>
            @elseif($dateTo)
            <div class="info-row">
                <div class="info-label">Date To:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</div>
            </div>
            @endif
            @if($vehicle)
            <div class="info-row">
                <div class="info-label">Vehicle:</div>
                <div class="info-value">{{ $vehicle->name }} ({{ $vehicle->registration_number ?? 'N/A' }})</div>
            </div>
            @endif
        </div>
    </div>

    @if($data->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Vehicle</th>
                    <th class="number">Total Liters</th>
                    <th class="number">Total Cost (TZS)</th>
                    <th class="number">Total Distance (km)</th>
                    <th class="number">Avg Efficiency (km/L)</th>
                    <th class="number">Cost per km (TZS)</th>
                    <th class="number">Fill Count</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grandTotalLiters = 0;
                    $grandTotalCost = 0;
                    $grandTotalDistance = 0;
                    $grandTotalFills = 0;
                @endphp
                @foreach($data as $row)
                @php
                    $grandTotalLiters += $row->total_liters ?? 0;
                    $grandTotalCost += $row->total_cost ?? 0;
                    $grandTotalDistance += $row->total_distance ?? 0;
                    $grandTotalFills += $row->fill_count ?? 0;
                @endphp
                <tr>
                    <td>{{ $row->vehicle ? ($row->vehicle->name . ' (' . ($row->vehicle->registration_number ?? 'N/A') . ')') : 'N/A' }}</td>
                    <td class="number">{{ number_format($row->total_liters ?? 0, 2) }}</td>
                    <td class="number">{{ number_format($row->total_cost ?? 0, 2) }}</td>
                    <td class="number">{{ number_format($row->total_distance ?? 0, 2) }}</td>
                    <td class="number">{{ $row->avg_efficiency ? number_format($row->avg_efficiency, 2) : 'N/A' }}</td>
                    <td class="number">{{ $row->avg_cost_per_km ? number_format($row->avg_cost_per_km, 2) : 'N/A' }}</td>
                    <td class="number">{{ $row->fill_count ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td style="text-align: right; font-weight: bold;">TOTAL:</td>
                    <td class="number" style="font-weight: bold;">{{ number_format($grandTotalLiters, 2) }}</td>
                    <td class="number" style="font-weight: bold;">{{ number_format($grandTotalCost, 2) }}</td>
                    <td class="number" style="font-weight: bold;">{{ number_format($grandTotalDistance, 2) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $grandTotalDistance > 0 && $grandTotalLiters > 0 ? number_format($grandTotalDistance / $grandTotalLiters, 2) : 'N/A' }}</td>
                    <td class="number" style="font-weight: bold;">{{ $grandTotalDistance > 0 && $grandTotalCost > 0 ? number_format($grandTotalCost / $grandTotalDistance, 2) : 'N/A' }}</td>
                    <td class="number" style="font-weight: bold;">{{ $grandTotalFills }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No fuel efficiency data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
        <p style="font-size: 10px; margin-top: 5px;">Efficiency is calculated as distance (km) divided by liters consumed.</p>
    </div>
</body>
</html>
