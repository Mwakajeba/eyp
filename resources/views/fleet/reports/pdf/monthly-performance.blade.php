<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Performance Report</title>
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
            border-bottom: 3px solid #17a2b8;
            padding-bottom: 15px;
        }
        
        .header-content {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 30px;
        }
        
        .logo-section {
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }
        
        .company-logo {
            height: 100px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
        }
        
        .title-section {
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
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
            width: 120px;
            color: #555;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 0;
            color: #333;
        }
        
        .summary-section {
            margin-top: 20px;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .summary-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .summary-table .label {
            font-weight: bold;
            width: 50%;
        }
        
        .summary-table .value {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .text-success {
            color: #28a745;
        }
        
        .text-danger {
            color: #dc3545;
        }
        
        .text-primary {
            color: #17a2b8;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                @if($company && $company->logo)
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="{{ $company->name }}" class="company-logo">
                @endif
            </div>
            <div class="title-section">
                <h1>Monthly Performance Report</h1>
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
            <div class="info-row">
                <div class="info-label">Month:</div>
                <div class="info-value">{{ $month->format('F Y') }}</div>
            </div>
            @if($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="summary-section">
        <h3 style="color: #17a2b8; margin-bottom: 15px;">Performance Summary</h3>
        <table class="summary-table">
            <tr>
                <td class="label">Total Revenue</td>
                <td class="value text-success"><strong>{{ number_format($summary['revenue'], 2) }}</strong></td>
            </tr>
            <tr>
                <td class="label">Total Trips</td>
                <td class="value">{{ $summary['total_trips'] }}</td>
            </tr>
            <tr>
                <td class="label">Completed Trips</td>
                <td class="value text-success">{{ $summary['completed_trips'] }}</td>
            </tr>
            <tr>
                <td class="label">Completion Rate</td>
                <td class="value">{{ number_format($summary['completion_rate'], 2) }}%</td>
            </tr>
            <tr>
                <td class="label">Distance Covered (km)</td>
                <td class="value text-primary">{{ number_format($summary['distance'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Maintenance Cost</td>
                <td class="value text-danger">{{ number_format($summary['maintenance_cost'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Fuel Cost</td>
                <td class="value text-danger">{{ number_format($summary['fuel_cost'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total Expenses</td>
                <td class="value text-danger"><strong>{{ number_format($summary['total_expenses'], 2) }}</strong></td>
            </tr>
            <tr style="border-top: 2px solid #333; background-color: #f8f9fa;">
                <td class="label"><strong>Net Profit / Loss</strong></td>
                <td class="value">
                    <strong class="{{ $summary['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($summary['net_profit'], 2) }}
                    </strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
        <p style="font-size: 10px; margin-top: 5px;">Monthly performance data is calculated from trips, revenue, and expenses for the selected month.</p>
    </div>
</body>
</html>
