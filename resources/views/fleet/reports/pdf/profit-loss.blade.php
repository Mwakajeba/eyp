<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profit & Loss Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 15px; color: #333; }
        .header { margin-bottom: 20px; border-bottom: 3px solid #17a2b8; padding-bottom: 15px; }
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
        .header h1 { color: #17a2b8; margin: 0; font-size: 24px; font-weight: bold; }
        .company-name { color: #333; margin: 5px 0; font-size: 16px; font-weight: 600; }
        .header .subtitle { color: #666; margin: 5px 0 0 0; font-size: 14px; }
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
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; }
        .summary-table td { border-bottom: 1px solid #ddd; }
        .text-end { text-align: right; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .total-row { background-color: #f8f9fa; font-weight: bold; border-top: 2px solid #333; }
        .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #dee2e6; padding-top: 20px; }
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
                <h1>Profit & Loss Report</h1>
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
                <div class="info-value">{{ $dateFrom->format('M d, Y') }} - {{ $dateTo->format('M d, Y') }}</div>
            </div>
            @elseif($dateFrom)
            <div class="info-row">
                <div class="info-label">Date From:</div>
                <div class="info-value">{{ $dateFrom->format('M d, Y') }}</div>
            </div>
            @elseif($dateTo)
            <div class="info-row">
                <div class="info-label">Date To:</div>
                <div class="info-value">{{ $dateTo->format('M d, Y') }}</div>
            </div>
            @endif
            @if($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
        </div>
    </div>

    <table class="summary-table">
        <tr>
            <td><strong>REVENUE</strong></td>
            <td class="text-end"></td>
        </tr>
        <tr>
            <td style="padding-left: 20px;">Total Revenue</td>
            <td class="text-end text-success"><strong>{{ number_format($summary['total_revenue'], 2) }}</strong></td>
        </tr>
        <tr>
            <td colspan="2">&nbsp;</td>
        </tr>
        <tr>
            <td><strong>EXPENSES</strong></td>
            <td class="text-end"></td>
        </tr>
        <tr>
            <td style="padding-left: 20px;">Maintenance Cost</td>
            <td class="text-end text-danger">{{ number_format($summary['maintenance_cost'], 2) }}</td>
        </tr>
        <tr>
            <td style="padding-left: 20px;">Fuel Cost</td>
            <td class="text-end text-danger">{{ number_format($summary['fuel_cost'], 2) }}</td>
        </tr>
        <tr>
            <td style="padding-left: 20px;">Trip Cost</td>
            <td class="text-end text-danger">{{ number_format($summary['trip_cost'], 2) }}</td>
        </tr>
        <tr class="total-row">
            <td><strong>Total Expenses</strong></td>
            <td class="text-end text-danger"><strong>{{ number_format($summary['total_expenses'], 2) }}</strong></td>
        </tr>
        <tr>
            <td colspan="2">&nbsp;</td>
        </tr>
        <tr class="total-row">
            <td><strong>NET PROFIT / LOSS</strong></td>
            <td class="text-end {{ $summary['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                <strong>{{ number_format($summary['net_profit'], 2) }}</strong>
            </td>
        </tr>
        <tr>
            <td><strong>Profit Margin</strong></td>
            <td class="text-end {{ $summary['profit_margin'] >= 0 ? 'text-success' : 'text-danger' }}">
                <strong>{{ number_format($summary['profit_margin'], 2) }}%</strong>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
        <p style="font-size: 10px; margin-top: 5px;">Profit & Loss data is calculated from revenue and expense reports.</p>
    </div>
</body>
</html>
