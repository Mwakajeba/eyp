<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Expiry Report</title>
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
        }
        
        .data-table td {
            padding: 6px;
            border-bottom: 1px solid #dee2e6;
            font-size: 8px;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
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
        
        .badge-secondary {
            background-color: #6c757d;
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
                <h1>Insurance Expiry Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ $generatedAt->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    @if($expiryData->count() > 0)
        <!-- Summary -->
        <div class="summary-box">
            <div class="summary-item">
                <div class="summary-label">Expired</div>
                <div class="summary-value" style="color: #dc3545;">{{ $expired->count() }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Expiring Soon (30 days)</div>
                <div class="summary-value" style="color: #ffc107;">{{ $expiringSoon->count() }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Active</div>
                <div class="summary-value" style="color: #28a745;">{{ $expiryData->filter(fn($d) => $d['status'] == 'Active')->count() }}</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Vehicle</th>
                    <th style="width: 15%;">Registration</th>
                    <th style="width: 20%;">Insurance Expiry Date</th>
                    <th style="width: 15%;" class="text-center">Days to Expiry</th>
                    <th style="width: 25%;" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expiryData as $data)
                @php
                    $badgeColor = match($data['status']) {
                        'Expired' => 'badge-danger',
                        'Expiring Soon' => 'badge-warning',
                        default => 'badge-success'
                    };
                @endphp
                <tr>
                    <td>{{ $data['vehicle']->name }}</td>
                    <td>{{ $data['vehicle']->registration_number ?? 'N/A' }}</td>
                    <td>{{ $data['insurance_expiry'] ? \Carbon\Carbon::parse($data['insurance_expiry'])->format('Y-m-d') : 'N/A' }}</td>
                    <td class="text-center">
                        @if($data['days_to_expiry'] !== null)
                            {{ abs($data['days_to_expiry']) }} days {{ $data['days_to_expiry'] < 0 ? 'overdue' : 'remaining' }}
                        @else
                            <span class="badge badge-secondary">N/A</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $badgeColor }}">{{ $data['status'] }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No vehicles found.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
        <p style="font-size: 10px; margin-top: 5px;">Insurance expiry data is retrieved from vehicle asset records.</p>
    </div>
</body>
</html>
