<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Disposal Report</title>
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
            border-bottom: 3px solid #dc3545;
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
            color: #dc3545;
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
            border-left: 4px solid #dc3545;
        }
        
        .report-info h3 {
            margin: 0 0 10px 0;
            color: #dc3545;
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
            color: #dc3545;
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
            background: #dc3545;
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
        
        .data-table th:nth-child(1) { width: 10%; }
        .data-table th:nth-child(2) { width: 15%; }
        .data-table th:nth-child(3) { width: 10%; }
        .data-table th:nth-child(4) { width: 10%; }
        .data-table th:nth-child(5) { width: 10%; }
        .data-table th:nth-child(6) { width: 10%; }
        .data-table th:nth-child(7) { width: 10%; }
        .data-table th:nth-child(8) { width: 10%; }
        .data-table th:nth-child(9) { width: 10%; }
        .data-table th:nth-child(10) { width: 5%; }
        
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
        
        .totals-row {
            background: #fff3cd !important;
            font-weight: bold;
        }
        
        .totals-row td {
            border-top: 2px solid #ffc107 !important;
            padding: 10px 6px !important;
        }
        
        .text-success {
            color: #28a745;
        }
        
        .text-danger {
            color: #dc3545;
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
        
        .note {
            background: #fff3cd;
            padding: 10px;
            border-left: 4px solid #ffc107;
            margin-bottom: 15px;
            font-size: 11px;
            color: #856404;
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
                <h1>Asset Disposal Report</h1>
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
        <h3>Report Parameters</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">{{ $fromDate->format('M d, Y') }} - {{ $toDate->format('M d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Report Type:</div>
                <div class="info-value">Asset Disposal Report (Gain/Loss Verification)</div>
            </div>
            @if($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
        </div>
    </div>

    @if(count($data) > 0)
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['count']) }}</div>
                <div class="stat-label">Total Disposals</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_cost'], 2) }}</div>
                <div class="stat-label">Total Cost (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_proceeds'], 2) }}</div>
                <div class="stat-label">Total Proceeds (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: {{ $summary['net_gain_loss'] >= 0 ? '#28a745' : '#dc3545' }};">
                    {{ number_format($summary['net_gain_loss'], 2) }}
                </div>
                <div class="stat-label">Net Gain/(Loss) (TZS)</div>
            </div>
        </div>

        <div class="note">
            <strong>Critical for:</strong> Gain/loss verification, fraud control, and board review. This report tracks all sold, scrapped, or written-off assets.
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Asset Code</th>
                    <th>Asset Name</th>
                    <th>Disposal Date</th>
                    <th>Disposal Method</th>
                    <th class="number">Cost</th>
                    <th class="number">Acc. Dep</th>
                    <th class="number">Carrying Amount</th>
                    <th class="number">Proceeds</th>
                    <th class="number">Gain/(Loss)</th>
                    <th>Approved By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $disposal)
                <tr>
                    <td>{{ $disposal['asset_code'] }}</td>
                    <td>{{ $disposal['asset_name'] }}</td>
                    <td>{{ $disposal['disposal_date'] }}</td>
                    <td>{{ $disposal['disposal_method'] }}</td>
                    <td class="number">{{ number_format($disposal['cost'], 2) }}</td>
                    <td class="number">{{ number_format($disposal['accumulated_depreciation'], 2) }}</td>
                    <td class="number">{{ number_format($disposal['carrying_amount'], 2) }}</td>
                    <td class="number">{{ number_format($disposal['proceeds'], 2) }}</td>
                    <td class="number {{ $disposal['gain_loss'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($disposal['gain_loss'], 2) }}
                    </td>
                    <td>{{ $disposal['approved_by'] }}</td>
                </tr>
                @endforeach
                
                <tr class="totals-row">
                    <td colspan="4"><strong>TOTAL</strong></td>
                    <td class="number"><strong>{{ number_format($summary['total_cost'], 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($summary['total_accumulated_dep'], 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($summary['total_carrying_amount'], 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($summary['total_proceeds'], 2) }}</strong></td>
                    <td class="number {{ $summary['net_gain_loss'] >= 0 ? 'text-success' : 'text-danger' }}">
                        <strong>{{ number_format($summary['net_gain_loss'], 2) }}</strong>
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No asset disposals found for the selected period.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Prepared by: {{ $user->name }} | Period: {{ $fromDate->format('d M Y') }} - {{ $toDate->format('d M Y') }}</p>
    </div>
</body>
</html>
