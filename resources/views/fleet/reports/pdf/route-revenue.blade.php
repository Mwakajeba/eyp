<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Route Revenue Report</title>
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
        .report-info { margin-bottom: 15px; font-size: 11px; }
        .report-info table { width: 100%; }
        .report-info td { padding: 4px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 6px; }
        table.data-table th { background-color: #17a2b8; color: white; font-weight: bold; text-align: left; }
        table.data-table tfoot { background-color: #f8f9fa; font-weight: bold; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 9px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 5px; }
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
                <h1>Route Revenue Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ $generatedAt->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <table>
            <tr>
                <td><strong>Date Range:</strong> {{ $dateFrom->format('d M Y') }} to {{ $dateTo->format('d M Y') }}</td>
                <td class="text-end"><strong>Generated:</strong> {{ $generatedAt->format('d M Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Route Code</th>
                <th>Route Name</th>
                <th>Origin</th>
                <th>Destination</th>
                <th class="text-center">Trips</th>
                <th class="text-end">Distance (km)</th>
                <th class="text-end">Revenue</th>
                <th class="text-end">Avg Revenue/Trip</th>
                <th class="text-end">Revenue/km</th>
            </tr>
        </thead>
        <tbody>
            @forelse($revenueData as $data)
                <tr>
                    <td>{{ $data['route']->route_code ?? 'N/A' }}</td>
                    <td>{{ $data['route']->route_name }}</td>
                    <td>{{ $data['route']->origin_location ?? 'N/A' }}</td>
                    <td>{{ $data['route']->destination_location ?? 'N/A' }}</td>
                    <td class="text-center">{{ $data['trip_count'] }}</td>
                    <td class="text-end">{{ number_format($data['distance'], 2) }}</td>
                    <td class="text-end"><strong>{{ number_format($data['revenue'], 2) }}</strong></td>
                    <td class="text-end">{{ number_format($data['avg_revenue_per_trip'], 2) }}</td>
                    <td class="text-end">{{ number_format($data['revenue_per_km'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No data found</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-end"><strong>TOTAL:</strong></td>
                <td class="text-center"><strong>{{ $revenueData->sum('trip_count') }}</strong></td>
                <td class="text-end"><strong>{{ number_format($revenueData->sum('distance'), 2) }}</strong></td>
                <td class="text-end"><strong>{{ number_format($totalRevenue, 2) }}</strong></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Route Revenue Report - Generated on {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>
</body>
</html>
