<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Revaluation Report (IFRS)</title>
    <style>
        body { font-family: 'Helvetica', 'DejaVu Sans', sans-serif; margin: 0; padding: 15px; color: #333; }
        .header { margin-bottom: 20px; border-bottom: 3px solid #0d6efd; padding-bottom: 15px; }
        .header-content { display: flex; align-items: center; justify-content: center; gap: 20px; }
        .logo-section { flex-shrink: 0; }
        .company-logo { max-height: 80px; max-width: 120px; object-fit: contain; }
        .title-section { text-align: center; flex-grow: 1; }
        .header h1 { color: #0d6efd; margin: 0; font-size: 24px; font-weight: bold; }
        .company-name { color: #333; margin: 5px 0; font-size: 16px; font-weight: 600; }
        .header .subtitle { color: #666; margin: 5px 0 0 0; font-size: 14px; }
        .report-info { background: #cfe2ff; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #0d6efd; }
        .report-info h3 { margin: 0 0 10px 0; color: #084298; font-size: 16px; }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; font-weight: bold; padding: 5px 15px 5px 0; width: 120px; color: #555; }
        .info-value { display: table-cell; padding: 5px 0; color: #333; }
        .summary-stats { display: table; width: 100%; margin-bottom: 15px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; }
        .stat-item { display: table-cell; text-align: center; padding: 12px 8px; border-right: 1px solid #dee2e6; background: #f8f9fa; }
        .stat-item:last-child { border-right: none; }
        .stat-value { font-size: 16px; font-weight: bold; color: #0d6efd; margin: 0; }
        .stat-label { font-size: 9px; color: #666; margin: 3px 0 0 0; text-transform: uppercase; letter-spacing: 0.3px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        .data-table thead { background: #0d6efd; color: white; }
        .data-table th { padding: 8px 6px; text-align: left; font-weight: bold; font-size: 10px; word-wrap: break-word; }
        .data-table td { padding: 8px 6px; border-bottom: 1px solid #dee2e6; font-size: 10px; }
        .number { text-align: right; font-family: 'Courier', 'Courier New', monospace; }
        .totals-row { background: #fff3cd !important; font-weight: bold; }
        .totals-row td { border-top: 2px solid #ffc107 !important; padding: 10px 6px !important; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #dee2e6; padding-top: 20px; }
        .note { background: #cfe2ff; padding: 10px; border-left: 4px solid #0d6efd; margin-bottom: 15px; font-size: 11px; color: #084298; }
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
                <h1>Revaluation Report (IFRS)</h1>
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
        <h3>Report Parameters - IFRS Compliance</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">{{ $fromDate->format('M d, Y') }} - {{ $toDate->format('M d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Standard:</div>
                <div class="info-value">IAS 16 - Property, Plant and Equipment (Revaluation Model)</div>
            </div>
        </div>
    </div>

    @if(count($data) > 0)
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['count']) }}</div>
                <div class="stat-label">Total Revaluations</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #28a745;">{{ number_format($summary['total_increase'], 2) }}</div>
                <div class="stat-label">Total Increase (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #dc3545;">{{ number_format($summary['total_decrease'], 2) }}</div>
                <div class="stat-label">Total Decrease (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: {{ $summary['net_movement'] >= 0 ? '#28a745' : '#dc3545' }};">
                    {{ number_format($summary['net_movement'], 2) }}
                </div>
                <div class="stat-label">Net Movement (TZS)</div>
            </div>
        </div>

        <div class="note">
            <strong>IFRS Compliance:</strong> This report shows asset revaluations per IAS 16, including surplus/deficit and revaluation reserve movements.
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Asset Code</th>
                    <th class="number">Old Carrying Amount</th>
                    <th class="number">Revalued Amount</th>
                    <th class="number">Surplus/(Deficit)</th>
                    <th class="number">Reserve Movement</th>
                    <th>Valuer</th>
                    <th>Valuation Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $rev)
                <tr>
                    <td>{{ $rev['asset_code'] }}</td>
                    <td class="number">{{ number_format($rev['old_carrying_amount'], 2) }}</td>
                    <td class="number">{{ number_format($rev['revalued_amount'], 2) }}</td>
                    <td class="number {{ $rev['surplus_deficit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        <strong>{{ number_format($rev['surplus_deficit'], 2) }}</strong>
                    </td>
                    <td class="number">{{ number_format($rev['revaluation_reserve_movement'], 2) }}</td>
                    <td>{{ $rev['valuer'] }}</td>
                    <td>{{ $rev['valuation_date'] }}</td>
                </tr>
                @endforeach
                
                <tr class="totals-row">
                    <td><strong>TOTAL</strong></td>
                    <td class="number"><strong>{{ number_format(array_sum(array_column($data, 'old_carrying_amount')), 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format(array_sum(array_column($data, 'revalued_amount')), 2) }}</strong></td>
                    <td class="number {{ $summary['net_movement'] >= 0 ? 'text-success' : 'text-danger' }}">
                        <strong>{{ number_format($summary['net_movement'], 2) }}</strong>
                    </td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No revaluations found for the selected period.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Prepared by: {{ $user->name }}</p>
    </div>
</body>
</html>
