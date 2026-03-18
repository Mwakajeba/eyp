<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book vs Tax Reconciliation</title>
    <style>
        body {
            font-family: 'Helvetica', 'DejaVu Sans', sans-serif;
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
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
        
        .logo-section {
            flex-shrink: 0;
        }
        
        .company-logo {
            max-height: 80px;
            max-width: 120px;
            object-fit: contain;
        }
        
        .title-section {
            text-align: center;
            flex-grow: 1;
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
        
        .summary-stats {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 12px 8px;
            border-right: 1px solid #dee2e6;
            background: #f8f9fa;
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        .stat-value {
            font-size: 14px;
            font-weight: bold;
            color: #17a2b8;
            margin: 0;
        }
        
        .stat-label {
            font-size: 8px;
            color: #666;
            margin: 3px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .note {
            background: #fff3cd;
            padding: 10px;
            border-left: 4px solid #ffc107;
            margin-bottom: 15px;
            font-size: 11px;
            color: #856404;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            table-layout: fixed;
        }
        
        .data-table thead {
            background: #17a2b8;
            color: white;
        }
        
        .data-table th {
            padding: 7px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            word-wrap: break-word;
        }
        
        .data-table th:nth-child(1) { width: 12%; }
        .data-table th:nth-child(2) { width: 20%; }
        .data-table th:nth-child(3) { width: 15%; }
        .data-table th:nth-child(4) { width: 15%; }
        .data-table th:nth-child(5) { width: 15%; }
        .data-table th:nth-child(6) { width: 10%; }
        .data-table th:nth-child(7) { width: 13%; }
        
        .data-table td {
            padding: 6px 4px;
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
        
        .number {
            text-align: right;
            font-family: 'Courier', 'Courier New', monospace;
        }
        
        .totals-row {
            background: #fff3cd !important;
            font-weight: bold;
        }
        
        .totals-row td {
            border-top: 2px solid #ffc107 !important;
            padding: 8px 4px !important;
        }
        
        .text-danger {
            color: #dc3545;
        }
        
        .text-success {
            color: #28a745;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 10px;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        
        .page-break {
            page-break-before: always;
        }
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
                <h1>Book vs Tax Reconciliation</h1>
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
                <div class="info-label">As Of Date:</div>
                <div class="info-value">{{ $asOfDate->format('F d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Deferred Tax Rate:</div>
                <div class="info-value">{{ number_format($taxRate, 2) }}%</div>
            </div>
            <div class="info-row">
                <div class="info-label">Report Type:</div>
                <div class="info-value">Deferred Tax Calculation Support</div>
            </div>
            @if($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
        </div>
    </div>

    @if(count($reconciliation) > 0)
        @php
            $taxRateDecimal = floatval($taxRate) / 100;
            $totalBookNbv = collect($reconciliation)->sum('book_nbv');
            $totalTaxWdv = collect($reconciliation)->sum('tax_wdv');
            $totalTempDiff = $totalBookNbv - $totalTaxWdv;
            $totalDeferredTax = $totalTempDiff * $taxRateDecimal;
        @endphp

        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalBookNbv, 2) }}</div>
                <div class="stat-label">Total Book Carrying Amount (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalTaxWdv, 2) }}</div>
                <div class="stat-label">Total Tax Base (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: {{ $totalTempDiff >= 0 ? '#dc3545' : '#28a745' }};">
                    {{ number_format($totalTempDiff, 2) }}
                </div>
                <div class="stat-label">Total Temp Difference</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: {{ $totalDeferredTax >= 0 ? '#dc3545' : '#28a745' }};">
                    {{ number_format(abs($totalDeferredTax), 2) }}
                </div>
                <div class="stat-label">Total {{ $totalDeferredTax >= 0 ? 'DTL' : 'DTA' }} (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ count($reconciliation) }}</div>
                <div class="stat-label">Total Assets</div>
            </div>
        </div>

        <div class="note">
            <strong>Note:</strong> This reconciliation report is used for deferred tax calculation. Temporary differences arise when book and tax carrying amounts differ. 
            Positive temporary differences create deferred tax liabilities; negative differences create deferred tax assets. Use this report to support your tax computations and external auditor requirements.
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Asset Code</th>
                    <th>Asset Name</th>
                    <th class="number">Book Carrying Amount</th>
                    <th class="number">Tax Base</th>
                    <th class="number">Temporary Difference</th>
                    <th class="number">Tax Rate</th>
                    <th class="number">Deferred Tax Asset/Liability</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reconciliation as $item)
                    @php
                        $bookCarryingAmount = floatval($item['book_nbv']);
                        $taxBase = floatval($item['tax_wdv']);
                        $temporaryDifference = $bookCarryingAmount - $taxBase;
                        $deferredTax = $temporaryDifference * $taxRateDecimal;
                        $deferredTaxLabel = $deferredTax >= 0 ? ' (DTL)' : ' (DTA)';
                    @endphp
                    <tr>
                        <td>{{ $item['asset']['code'] ?? 'N/A' }}</td>
                        <td>{{ $item['asset']['name'] ?? 'N/A' }}</td>
                        <td class="number">{{ number_format($bookCarryingAmount, 2) }}</td>
                        <td class="number">{{ number_format($taxBase, 2) }}</td>
                        <td class="number {{ $temporaryDifference >= 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($temporaryDifference, 2) }}
                        </td>
                        <td class="number">{{ number_format($taxRate, 2) }}%</td>
                        <td class="number {{ $deferredTax >= 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format(abs($deferredTax), 2) }}{{ $deferredTaxLabel }}
                        </td>
                    </tr>
                @endforeach
                
                <tr class="totals-row">
                    <td colspan="2"><strong>Total</strong></td>
                    <td class="number"><strong>{{ number_format($totalBookNbv, 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($totalTaxWdv, 2) }}</strong></td>
                    <td class="number {{ $totalTempDiff >= 0 ? 'text-danger' : 'text-success' }}">
                        <strong>{{ number_format($totalTempDiff, 2) }}</strong>
                    </td>
                    <td class="number"><strong>{{ number_format($taxRate, 2) }}%</strong></td>
                    <td class="number {{ $totalDeferredTax >= 0 ? 'text-danger' : 'text-success' }}">
                        <strong>{{ number_format(abs($totalDeferredTax), 2) }}{{ $totalDeferredTax >= 0 ? ' (DTL)' : ' (DTA)' }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No reconciliation data found for the selected date.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Prepared by: {{ $user->name }} | As Of: {{ $asOfDate->format('d M Y') }}</p>
        <p style="margin-top: 10px; font-size: 9px;">
            <strong>Deferred Tax Calculation:</strong> Use this report with your applicable tax rate to calculate deferred tax assets and liabilities for financial statement preparation.
        </p>
    </div>
</body>
</html>
