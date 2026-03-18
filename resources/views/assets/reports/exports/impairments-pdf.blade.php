<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Impairment Report (IAS 36)</title>
    <style>
        body { font-family: 'Helvetica', 'DejaVu Sans', sans-serif; margin: 0; padding: 15px; color: #333; }
        .header { margin-bottom: 20px; border-bottom: 3px solid #ffc107; padding-bottom: 15px; }
        .header-content { display: flex; align-items: center; justify-content: center; gap: 20px; }
        .logo-section { flex-shrink: 0; }
        .company-logo { max-height: 80px; max-width: 120px; object-fit: contain; }
        .title-section { text-align: center; flex-grow: 1; }
        .header h1 { color: #ffc107; margin: 0; font-size: 24px; font-weight: bold; }
        .company-name { color: #333; margin: 5px 0; font-size: 16px; font-weight: 600; }
        .header .subtitle { color: #666; margin: 5px 0 0 0; font-size: 14px; }
        .report-info { background: #fff3cd; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #ffc107; }
        .report-info h3 { margin: 0 0 10px 0; color: #856404; font-size: 16px; }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; font-weight: bold; padding: 5px 15px 5px 0; width: 120px; color: #555; }
        .info-value { display: table-cell; padding: 5px 0; color: #333; }
        .summary-stats { display: table; width: 100%; margin-bottom: 15px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; }
        .stat-item { display: table-cell; text-align: center; padding: 12px 8px; border-right: 1px solid #dee2e6; background: #f8f9fa; }
        .stat-item:last-child { border-right: none; }
        .stat-value { font-size: 16px; font-weight: bold; color: #ffc107; margin: 0; }
        .stat-label { font-size: 9px; color: #666; margin: 3px 0 0 0; text-transform: uppercase; letter-spacing: 0.3px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        .data-table thead { background: #ffc107; color: #333; }
        .data-table th { padding: 8px 6px; text-align: left; font-weight: bold; font-size: 10px; word-wrap: break-word; }
        .data-table td { padding: 8px 6px; border-bottom: 1px solid #dee2e6; font-size: 10px; }
        .number { text-align: right; font-family: 'Courier', 'Courier New', monospace; }
        .totals-row { background: #fff3cd !important; font-weight: bold; }
        .totals-row td { border-top: 2px solid #ffc107 !important; padding: 10px 6px !important; }
        .text-danger { color: #dc3545; }
        .text-success { color: #28a745; }
        .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #dee2e6; padding-top: 20px; }
        .note { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin-bottom: 15px; font-size: 11px; color: #856404; }
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
                <h1>Impairment Report (IAS 36)</h1>
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
        <h3>Report Parameters - IAS 36 Compliance</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">{{ $fromDate->format('M d, Y') }} - {{ $toDate->format('M d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Standard:</div>
                <div class="info-value">IAS 36 - Impairment of Assets</div>
            </div>
        </div>
    </div>

    @if(count($data) > 0)
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['count']) }}</div>
                <div class="stat-label">Total Impairments</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #dc3545;">{{ number_format($summary['total_loss'], 2) }}</div>
                <div class="stat-label">Impairment Loss (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #28a745;">{{ number_format($summary['total_reversals'], 2) }}</div>
                <div class="stat-label">Reversals (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['net_impact'], 2) }}</div>
                <div class="stat-label">Net Impact (TZS)</div>
            </div>
        </div>

        <div class="note">
            <strong>IAS 36 Compliance:</strong> This report shows impairment testing results including recoverable amounts and carrying values per IAS 36 requirements.
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Asset Code</th>
                    <th>CGU</th>
                    <th class="number">Carrying Amount Before</th>
                    <th class="number">Recoverable Amount</th>
                    <th class="number">Impairment Loss</th>
                    <th class="number">Reversal</th>
                    <th class="number">Carrying Amount After</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $imp)
                <tr>
                    <td>{{ $imp['asset_code'] }}</td>
                    <td>{{ $imp['cgu'] }}</td>
                    <td class="number">{{ number_format($imp['carrying_amount_before'], 2) }}</td>
                    <td class="number">{{ number_format($imp['recoverable_amount'], 2) }}</td>
                    <td class="number text-danger">{{ number_format($imp['impairment_loss'], 2) }}</td>
                    <td class="number text-success">{{ number_format($imp['reversal'], 2) }}</td>
                    <td class="number"><strong>{{ number_format($imp['carrying_amount_after'], 2) }}</strong></td>
                </tr>
                @endforeach
                
                <tr class="totals-row">
                    <td colspan="4"><strong>TOTAL</strong></td>
                    <td class="number text-danger"><strong>{{ number_format($summary['total_loss'], 2) }}</strong></td>
                    <td class="number text-success"><strong>{{ number_format($summary['total_reversals'], 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($summary['total_carrying_after'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No impairments found for the selected period.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Prepared by: {{ $user->name }}</p>
    </div>
</body>
</html>
