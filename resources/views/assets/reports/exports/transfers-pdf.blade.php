<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Asset Transfer Report</title>
    <style>
        body { font-family: 'Helvetica', 'DejaVu Sans', sans-serif; margin: 0; padding: 15px; color: #333; }
        .header { margin-bottom: 20px; border-bottom: 3px solid #17a2b8; padding-bottom: 15px; }
        .header-content { display: flex; align-items: center; justify-content: center; gap: 20px; }
        .logo-section { flex-shrink: 0; }
        .company-logo { max-height: 80px; max-width: 120px; object-fit: contain; }
        .title-section { text-align: center; flex-grow: 1; }
        .header h1 { color: #17a2b8; margin: 0; font-size: 24px; font-weight: bold; }
        .company-name { color: #333; margin: 5px 0; font-size: 16px; font-weight: 600; }
        .header .subtitle { color: #666; margin: 5px 0 0 0; font-size: 14px; }
        .report-info { background: #d1ecf1; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #17a2b8; }
        .report-info h3 { margin: 0 0 10px 0; color: #0c5460; font-size: 16px; }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; font-weight: bold; padding: 5px 15px 5px 0; width: 120px; color: #555; }
        .info-value { display: table-cell; padding: 5px 0; color: #333; }
        .summary-stats { display: table; width: 100%; margin-bottom: 15px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; }
        .stat-item { display: table-cell; text-align: center; padding: 12px 8px; background: #f8f9fa; }
        .stat-value { font-size: 18px; font-weight: bold; color: #17a2b8; margin: 0; }
        .stat-label { font-size: 10px; color: #666; margin: 3px 0 0 0; text-transform: uppercase; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        .data-table thead { background: #17a2b8; color: white; }
        .data-table th { padding: 8px 6px; text-align: left; font-weight: bold; font-size: 10px; word-wrap: break-word; }
        .data-table td { padding: 8px 6px; border-bottom: 1px solid #dee2e6; font-size: 10px; }
        .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #dee2e6; padding-top: 20px; }
        .note { background: #d1ecf1; padding: 10px; border-left: 4px solid #17a2b8; margin-bottom: 15px; font-size: 11px; color: #0c5460; }
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
                <h1>Asset Transfer Report</h1>
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
                <div class="info-value">Asset Location Transfers & Movements</div>
            </div>
        </div>
    </div>

    @if(count($data) > 0)
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['count']) }}</div>
                <div class="stat-label">Total Transfers</div>
            </div>
        </div>

        <div class="note">
            <strong>Purpose:</strong> Track asset movements between branches/locations for custody and responsibility tracking.
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Asset Code</th>
                    <th>From Location</th>
                    <th>To Location</th>
                    <th>Transfer Date</th>
                    <th>Approved By</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $transfer)
                <tr>
                    <td>{{ $transfer['asset_code'] }}</td>
                    <td>{{ $transfer['from_location'] }}</td>
                    <td>{{ $transfer['to_location'] }}</td>
                    <td>{{ $transfer['transfer_date'] }}</td>
                    <td>{{ $transfer['approved_by'] }}</td>
                    <td>{{ $transfer['remarks'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No asset transfers found for the selected period.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Prepared by: {{ $user->name }}</p>
    </div>
</body>
</html>
