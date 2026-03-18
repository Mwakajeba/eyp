<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Activity Report</title>
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
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            table-layout: fixed;
            font-size: 9px;
        }
        
        .data-table thead {
            background: #17a2b8;
            color: white;
        }
        
        .data-table th {
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            word-wrap: break-word;
        }
        
        .data-table td {
            padding: 6px;
            border-bottom: 1px solid #dee2e6;
            font-size: 8px;
            word-wrap: break-word;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tfoot {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .data-table tfoot td {
            border-top: 2px solid #17a2b8;
            padding: 10px 6px;
            font-size: 9px;
        }
        
        .number {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .text-center {
            text-align: center;
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
        
        .summary-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: table;
            width: 100%;
        }
        
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 0 10px;
            border-right: 1px solid #dee2e6;
        }
        
        .summary-item:last-child {
            border-right: none;
        }
        
        .summary-label {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #17a2b8;
        }
        
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }
        
        .badge-danger {
            background-color: #dc3545;
            color: white;
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
                <h1>Driver Activity Report</h1>
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

    @if($activityData->count() > 0)
        <!-- Summary -->
        <div class="summary-box">
            <div class="summary-item">
                <div class="summary-label">Active Drivers</div>
                <div class="summary-value">{{ $activityData->count() }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Trips</div>
                <div class="summary-value">{{ $activityData->sum('total_trips') }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Completed Trips</div>
                <div class="summary-value">{{ $activityData->sum('completed_trips') }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Distance (km)</div>
                <div class="summary-value">{{ number_format($activityData->sum('distance'), 2) }}</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Driver Code</th>
                    <th style="width: 25%;">Driver Name</th>
                    <th style="width: 12%;" class="text-center">Total Trips</th>
                    <th style="width: 12%;" class="text-center">Completed</th>
                    <th style="width: 12%;" class="text-center">In Progress</th>
                    <th style="width: 12%;" class="number">Distance (km)</th>
                    <th style="width: 15%;" class="text-center">Completion Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activityData as $data)
                <tr>
                    <td>{{ $data['driver']->driver_code ?? 'N/A' }}</td>
                    <td>{{ $data['driver']->full_name }}</td>
                    <td class="text-center">{{ $data['total_trips'] }}</td>
                    <td class="text-center">{{ $data['completed_trips'] }}</td>
                    <td class="text-center">{{ $data['in_progress_trips'] }}</td>
                    <td class="number">{{ number_format($data['distance'], 2) }}</td>
                    <td class="text-center">
                        @php
                            $badgeClass = $data['completion_rate'] >= 80 ? 'badge-success' : ($data['completion_rate'] >= 50 ? 'badge-warning' : 'badge-danger');
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ number_format($data['completion_rate'], 2) }}%</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right; font-weight: bold;">TOTAL:</td>
                    <td class="text-center" style="font-weight: bold;">{{ $activityData->sum('total_trips') }}</td>
                    <td class="text-center" style="font-weight: bold;">{{ $activityData->sum('completed_trips') }}</td>
                    <td class="text-center" style="font-weight: bold;">{{ $activityData->sum('in_progress_trips') }}</td>
                    <td class="number" style="font-weight: bold;">{{ number_format($activityData->sum('distance'), 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No driver activity data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
        <p style="font-size: 10px; margin-top: 5px;">Driver activity data is retrieved from Fleet Trip Management module.</p>
    </div>
</body>
</html>
