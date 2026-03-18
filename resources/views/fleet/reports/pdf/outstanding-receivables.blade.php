<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outstanding Receivables Report</title>
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
            font-size: 8px;
        }
        
        .data-table thead {
            background: #17a2b8;
            color: white;
        }
        
        .data-table th {
            padding: 8px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            word-wrap: break-word;
        }
        
        .data-table td {
            padding: 5px 4px;
            border-bottom: 1px solid #dee2e6;
            font-size: 7px;
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
            padding: 10px 4px;
            font-size: 8px;
        }
        
        .number {
            text-align: right;
            font-family: 'Courier New', monospace;
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
                <h1>Outstanding Receivables Report</h1>
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
            @if($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($receivables->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Invoice No</th>
                    <th style="width: 8%;">Inv. Date</th>
                    <th style="width: 8%;">Due Date</th>
                    <th style="width: 6%;">Days Overdue</th>
                    <th style="width: 7%;">Aging</th>
                    @php
                        $hasCustomer = $receivables->contains(fn($r) => $r['invoice']->customer);
                    @endphp
                    @if($hasCustomer)
                    <th style="width: 12%;">Customer</th>
                    @endif
                    <th style="width: 10%;">Vehicle</th>
                    <th style="width: 10%;">Driver</th>
                    <th style="width: 10%;" class="number">Total</th>
                    <th style="width: 9%;" class="number">Paid</th>
                    <th style="width: 10%;" class="number">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receivables as $item)
                    @php
                        $invoice = $item['invoice'];
                        // Get from invoice first, then fallback to first item's trip
                        $displayVehicle = $invoice->vehicle;
                        $displayDriver = $invoice->driver;
                        
                        if (!$displayVehicle || !$displayDriver) {
                            $firstItem = $invoice->items->first();
                            if ($firstItem && $firstItem->trip) {
                                $displayVehicle = $displayVehicle ?? $firstItem->trip->vehicle;
                                $displayDriver = $displayDriver ?? $firstItem->trip->driver;
                            }
                        }
                    @endphp
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : 'N/A' }}</td>
                    <td>{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'N/A' }}</td>
                    <td style="text-align: center;">
                        @if($item['days_overdue'] > 0)
                            {{ (int)$item['days_overdue'] }}
                        @else
                            {{ (int)abs($item['days_overdue']) }} left
                        @endif
                    </td>
                    <td>{{ $item['aging_category'] }}</td>
                    @if($hasCustomer)
                    <td>{{ $invoice->customer->name ?? 'N/A' }}</td>
                    @endif
                    <td>{{ $displayVehicle->name ?? 'N/A' }}@if($displayVehicle && $displayVehicle->registration_number) ({{ $displayVehicle->registration_number }})@endif</td>
                    <td>{{ $displayDriver->full_name ?? $displayDriver->name ?? 'N/A' }}</td>
                    <td class="number">{{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                    <td class="number">{{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                    <td class="number"><strong>{{ number_format($invoice->balance_due ?? 0, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ $hasCustomer ? '8' : '7' }}" style="text-align: right; font-weight: bold;">TOTAL:</td>
                    <td class="number" style="font-weight: bold;">{{ number_format($receivables->sum(fn($r) => $r['invoice']->total_amount), 2) }}</td>
                    <td class="number" style="font-weight: bold;">{{ number_format($receivables->sum(fn($r) => $r['invoice']->paid_amount), 2) }}</td>
                    <td class="number" style="font-weight: bold;">{{ number_format($totalOutstanding, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No outstanding receivables found.</p>
        </div>
    @endif>

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
