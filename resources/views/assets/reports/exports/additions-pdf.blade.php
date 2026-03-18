<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Asset Addition Report</title>
    <style>
        body { font-family: 'Helvetica', 'DejaVu Sans', sans-serif; margin: 0; padding: 15px; color: #333; }
        .header { margin-bottom: 20px; border-bottom: 3px solid #28a745; padding-bottom: 15px; }
        .header-content { display: flex; align-items: center; justify-content: center; gap: 20px; }
        .logo-section { flex-shrink: 0; }
        .company-logo { max-height: 80px; max-width: 120px; object-fit: contain; }
        .title-section { text-align: center; flex-grow: 1; }
        .header h1 { color: #28a745; margin: 0; font-size: 24px; font-weight: bold; }
        .company-name { color: #333; margin: 5px 0; font-size: 16px; font-weight: 600; }
        .header .subtitle { color: #666; margin: 5px 0 0 0; font-size: 14px; }
        .report-info { background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #28a745; }
        .report-info h3 { margin: 0 0 10px 0; color: #28a745; font-size: 16px; }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; font-weight: bold; padding: 5px 15px 5px 0; width: 120px; color: #555; }
        .info-value { display: table-cell; padding: 5px 0; color: #333; }
        .summary-stats { display: table; width: 100%; margin-bottom: 15px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; }
        .stat-item { display: table-cell; text-align: center; padding: 12px 8px; border-right: 1px solid #dee2e6; background: #f8f9fa; }
        .stat-item:last-child { border-right: none; }
        .stat-value { font-size: 16px; font-weight: bold; color: #28a745; margin: 0; }
        .stat-label { font-size: 9px; color: #666; margin: 3px 0 0 0; text-transform: uppercase; letter-spacing: 0.3px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; table-layout: fixed; }
        .data-table thead { background: #28a745; color: white; }
        .data-table th { padding: 8px 6px; text-align: left; font-weight: bold; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; word-wrap: break-word; }
        .data-table td { padding: 8px 6px; border-bottom: 1px solid #dee2e6; font-size: 10px; word-wrap: break-word; }
        .data-table tbody tr:hover { background: #f8f9fa; }
        .number { text-align: right; font-family: 'Courier', 'Courier New', monospace; }
        .totals-row { background: #fff3cd !important; font-weight: bold; }
        .totals-row td { border-top: 2px solid #ffc107 !important; padding: 10px 6px !important; }
        .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #dee2e6; padding-top: 20px; }
        .note { background: #d4edda; padding: 10px; border-left: 4px solid #28a745; margin-bottom: 15px; font-size: 11px; color: #155724; }
        .no-data { text-align: center; padding: 40px; color: #666; font-style: italic; }
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
                <h1>Asset Addition Report</h1>
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
                <div class="stat-label">Total Additions</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_amount'], 2) }}</div>
                <div class="stat-label">Total Capitalized (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['average_amount'], 2) }}</div>
                <div class="stat-label">Average Value (TZS)</div>
            </div>
        </div>

        <div class="note">
            <strong>Helps auditors test:</strong> Capitalization vs expense decisions and cut-off accuracy for new assets.
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Asset Code</th>
                    <th>Asset Name</th>
                    <th>Category</th>
                    <th>Invoice No</th>
                    <th>Vendor</th>
                    <th>Purchase Date</th>
                    <th>Capitalized Date</th>
                    <th class="number">Amount</th>
                    <th>Approved By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $addition)
                <tr>
                    <td>{{ $addition['asset_code'] }}</td>
                    <td>{{ $addition['asset_name'] }}</td>
                    <td>{{ $addition['category'] }}</td>
                    <td>{{ $addition['invoice_no'] }}</td>
                    <td>{{ $addition['vendor'] }}</td>
                    <td>{{ $addition['purchase_date'] }}</td>
                    <td>{{ $addition['capitalized_date'] }}</td>
                    <td class="number">{{ number_format($addition['amount'], 2) }}</td>
                    <td>{{ $addition['approved_by'] }}</td>
                </tr>
                @endforeach
                
                <tr class="totals-row">
                    <td colspan="7"><strong>TOTAL</strong></td>
                    <td class="number"><strong>{{ number_format($summary['total_amount'], 2) }}</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No asset additions found for the selected period.</p>
        </div>
    @endif>

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Prepared by: {{ $user->name }}</p>
    </div>
</body>
</html>
