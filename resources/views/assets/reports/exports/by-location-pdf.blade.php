<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assets by Location Report</title>
    <style>
        body { font-family: 'Helvetica', 'DejaVu Sans', sans-serif; margin: 0; padding: 15px; color: #333; }
        .header { margin-bottom: 20px; border-bottom: 3px solid #17a2b8; padding-bottom: 15px; text-align: center; }
        .header h1 { color: #17a2b8; margin: 0; font-size: 24px; font-weight: bold; }
        .header .subtitle { color: #666; margin: 5px 0 0 0; font-size: 14px; }
        .summary-stats { display: table; width: 100%; margin-bottom: 15px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; }
        .stat-item { display: table-cell; text-align: center; padding: 12px 8px; border-right: 1px solid #dee2e6; background: #f8f9fa; }
        .stat-item:last-child { border-right: none; }
        .stat-value { font-size: 16px; font-weight: bold; color: #17a2b8; margin: 0; }
        .stat-label { font-size: 10px; color: #666; margin: 3px 0 0 0; text-transform: uppercase; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        .data-table thead { background: #17a2b8; color: white; }
        .data-table th { padding: 8px 6px; text-align: left; font-weight: bold; font-size: 10px; }
        .data-table td { padding: 8px 6px; border-bottom: 1px solid #dee2e6; font-size: 10px; }
        .number { text-align: right; font-family: 'Courier', 'Courier New', monospace; }
        .location-header { background: #e9ecef !important; font-weight: bold; }
        .subtotal-row { background: #f8f9fa !important; font-weight: bold; font-style: italic; }
        .totals-row { background: #fff3cd !important; font-weight: bold; }
        .totals-row td { border-top: 2px solid #ffc107 !important; padding: 10px 6px !important; }
        .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #dee2e6; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Assets by Location Report</h1>
        <div class="subtitle">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
    </div>

    @if(count($data) > 0)
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['count']) }}</div>
                <div class="stat-label">Total Assets</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_cost'], 2) }}</div>
                <div class="stat-label">Total Cost (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_nbv'], 2) }}</div>
                <div class="stat-label">Total NBV (TZS)</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Asset Code</th>
                    <th>Asset Name</th>
                    <th>Category</th>
                    <th class="number">Cost</th>
                    <th class="number">NBV</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grouped = collect($data)->groupBy('location');
                @endphp
                
                @foreach($grouped as $location => $assets)
                    @php
                        $locationCost = $assets->sum('cost');
                        $locationNbv = $assets->sum('nbv');
                    @endphp
                    
                    @foreach($assets as $index => $asset)
                    <tr>
                        @if($index === 0)
                        <td rowspan="{{ count($assets) }}" class="location-header">{{ $location }}</td>
                        @endif
                        <td>{{ $asset['asset_code'] }}</td>
                        <td>{{ $asset['asset_name'] }}</td>
                        <td>{{ $asset['category'] }}</td>
                        <td class="number">{{ number_format($asset['cost'], 2) }}</td>
                        <td class="number">{{ number_format($asset['nbv'], 2) }}</td>
                    </tr>
                    @endforeach
                    
                    <tr class="subtotal-row">
                        <td colspan="4" class="number">Subtotal - {{ $location }}</td>
                        <td class="number">{{ number_format($locationCost, 2) }}</td>
                        <td class="number">{{ number_format($locationNbv, 2) }}</td>
                    </tr>
                @endforeach
                
                <tr class="totals-row">
                    <td colspan="4" class="number"><strong>GRAND TOTAL</strong></td>
                    <td class="number"><strong>{{ number_format($summary['total_cost'], 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($summary['total_nbv'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 40px; color: #666; font-style: italic;">
            <h3>No Data Available</h3>
            <p>No assets found.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
    </div>
</body>
</html>
