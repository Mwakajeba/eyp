<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depreciation Schedule</title>
    <style>
        body {
            font-family: 'Helvetica', 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 15px;
            color: #333;
            background: #fff;
        }
        
        .header {
            margin-bottom: 20px;
            border-bottom: 3px solid #17a2b8;
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
            color: #17a2b8;
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
            border-left: 4px solid #17a2b8;
        }
        
        .report-info h3 {
            margin: 0 0 10px 0;
            color: #17a2b8;
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
            width: 150px;
            color: #555;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 0;
            color: #333;
        }
        
        .summary-stats {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 12px 8px;
            border-right: 1px solid #dee2e6;
            background: #f8f9fa;
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #17a2b8;
            margin: 0;
        }
        
        .stat-label {
            font-size: 9px;
            color: #666;
            margin: 3px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
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
            background: #17a2b8;
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
        
        .data-table th:nth-child(1) { width: 8%; }
        .data-table th:nth-child(2) { width: 12%; }
        .data-table th:nth-child(3) { width: 16%; }
        .data-table th:nth-child(4) { width: 16%; }
        .data-table th:nth-child(5) { width: 16%; }
        .data-table th:nth-child(6) { width: 16%; }
        .data-table th:nth-child(7) { width: 16%; }
        
        .data-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #dee2e6;
            font-size: 10px;
            word-wrap: break-word;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .number {
            text-align: right;
            font-family: 'Courier', 'Courier New', monospace;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals-row {
            background: #fff3cd !important;
            font-weight: bold;
        }
        
        .totals-row td {
            border-top: 2px solid #ffc107 !important;
            padding: 10px 6px !important;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        
        .note {
            background: #e7f3ff;
            padding: 10px;
            border-left: 4px solid #17a2b8;
            margin-bottom: 15px;
            font-size: 11px;
            color: #555;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        
        .page-break {
            page-break-before: always;
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
                <h1>Depreciation Schedule</h1>
                @if($company)
                    <div class="company-name">{{ $company->name }}</div>
                @endif
                @if($branch)
                    <div class="subtitle">{{ $branch->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <h3>Asset Information</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Asset Code:</div>
                <div class="info-value">{{ $assetDetails['code'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Asset Name:</div>
                <div class="info-value">{{ $assetDetails['name'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Category:</div>
                <div class="info-value">{{ $assetDetails['category'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Purchase Cost:</div>
                <div class="info-value">TZS {{ number_format($assetDetails['cost'], 2) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Salvage Value:</div>
                <div class="info-value">TZS {{ number_format($assetDetails['salvage_value'], 2) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Useful Life:</div>
                <div class="info-value">{{ $assetDetails['useful_life'] }} years</div>
            </div>
            <div class="info-row">
                <div class="info-label">Depreciation Method:</div>
                <div class="info-value">{{ $assetDetails['depreciation_method'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Purchase Date:</div>
                <div class="info-value">{{ $assetDetails['purchase_date'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Capitalization Date:</div>
                <div class="info-value">{{ $assetDetails['capitalization_date'] }}</div>
            </div>
        </div>
    </div>

    @if(count($data) > 0)
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_depreciation'], 2) }}</div>
                <div class="stat-label">Total Depreciation (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['current_nbv'], 2) }}</div>
                <div class="stat-label">Current NBV (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_revaluation'], 2) }}</div>
                <div class="stat-label">Total Revaluation (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_impairment'], 2) }}</div>
                <div class="stat-label">Total Impairment (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['period_count']) }}</div>
                <div class="stat-label">Periods Shown</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['remaining_months']) }}</div>
                <div class="stat-label">Remaining Life (Months)</div>
            </div>
        </div>

        <div class="note">
            <strong>Note:</strong> This schedule shows the month-by-month depreciation, revaluation adjustments, and impairment losses over the asset's life. 
            Use for audit recalculation testing, capital projects tracking, and leasehold improvements analysis.
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="text-center">Period</th>
                    <th>Date</th>
                    <th class="number">Opening NBV</th>
                    <th class="number">Depreciation</th>
                    <th class="number">Revaluation Adj.</th>
                    <th class="number">Impairment</th>
                    <th class="number">Closing NBV</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $index => $period)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $period['date'] }}</td>
                    <td class="number">{{ number_format($period['opening_nbv'], 2) }}</td>
                    <td class="number">{{ number_format($period['depreciation'], 2) }}</td>
                    <td class="number">{{ number_format($period['revaluation'], 2) }}</td>
                    <td class="number">{{ number_format($period['impairment'], 2) }}</td>
                    <td class="number">{{ number_format($period['closing_nbv'], 2) }}</td>
                </tr>
                @endforeach
                
                <tr class="totals-row">
                    <td colspan="3" class="number"><strong>TOTAL:</strong></td>
                    <td class="number"><strong>{{ number_format($summary['total_depreciation'], 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($summary['total_revaluation'], 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($summary['total_impairment'], 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($summary['current_nbv'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No depreciation schedule data found for this asset.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Prepared by: {{ $user->name }} | Asset: {{ $assetDetails['code'] }}</p>
    </div>
</body>
</html>
