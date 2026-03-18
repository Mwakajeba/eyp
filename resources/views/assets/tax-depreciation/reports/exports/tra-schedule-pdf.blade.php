<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRA Tax Depreciation Schedule</title>
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
        
        .note {
            background: #fff3cd;
            padding: 10px;
            border-left: 4px solid #ffc107;
            margin-bottom: 15px;
            font-size: 11px;
            color: #856404;
        }
        
        .tax-class-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .tax-class-header {
            background: #17a2b8;
            color: white;
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
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
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            word-wrap: break-word;
        }
        
        .data-table th:nth-child(1) { width: 12%; }
        .data-table th:nth-child(2) { width: 20%; }
        .data-table th:nth-child(3) { width: 13%; }
        .data-table th:nth-child(4) { width: 13%; }
        .data-table th:nth-child(5) { width: 13%; }
        .data-table th:nth-child(6) { width: 14%; }
        .data-table th:nth-child(7) { width: 15%; }
        
        .data-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #dee2e6;
            font-size: 10px;
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
            padding: 10px 6px !important;
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
                <h1>TRA Tax Depreciation Schedule</h1>
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
                <div class="info-label">Tax Year:</div>
                <div class="info-value">{{ $taxYear }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Report Type:</div>
                <div class="info-value">TRA Compliant Tax Depreciation Schedule</div>
            </div>
            @if($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="note">
        <strong>Note:</strong> This schedule is prepared in accordance with Tanzania Revenue Authority (TRA) requirements for corporate tax computation. 
        All depreciation rates and classifications follow TRA guidelines. This report must be retained for audit purposes.
    </div>

    @if(count($schedule) > 0)
        @foreach($schedule as $classData)
            <div class="tax-class-section">
                <div class="tax-class-header">
                    {{ $classData['tax_class']['class_code'] }} - {{ $classData['tax_class']['description'] }}
                    (Rate: {{ number_format($classData['tax_class']['depreciation_rate'], 2) }}%)
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Asset Category</th>
                            <th>Tax Pool Class</th>
                            <th class="number">Opening Tax WDV</th>
                            <th class="number">Additions</th>
                            <th class="number">Disposals</th>
                            <th class="number">Tax Depreciation</th>
                            <th class="number">Closing Tax WDV</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($classData['categories'] as $category)
                        <tr>
                            <td>{{ $category['category']->name ?? 'N/A' }}</td>
                            <td>{{ $classData['tax_class']['class_code'] }}</td>
                            <td class="number">{{ number_format($category['opening_wdv'], 2) }}</td>
                            <td class="number">{{ number_format($category['additions'], 2) }}</td>
                            <td class="number">{{ number_format($category['disposals'], 2) }}</td>
                            <td class="number">{{ number_format($category['tax_depreciation'], 2) }}</td>
                            <td class="number">{{ number_format($category['closing_wdv'], 2) }}</td>
                        </tr>
                        @endforeach
                        
                        <tr class="totals-row">
                            <td colspan="2"><strong>Class Total</strong></td>
                            <td class="number"><strong>{{ number_format($classData['total_opening_wdv'], 2) }}</strong></td>
                            <td class="number"><strong>{{ number_format($classData['total_additions'], 2) }}</strong></td>
                            <td class="number"><strong>{{ number_format($classData['total_disposals'], 2) }}</strong></td>
                            <td class="number"><strong>{{ number_format($classData['total_tax_depreciation'], 2) }}</strong></td>
                            <td class="number"><strong>{{ number_format($classData['total_closing_wdv'], 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach

        @php
            $grandTotalOpeningWdv = collect($schedule)->sum('total_opening_wdv');
            $grandTotalAdditions = collect($schedule)->sum('total_additions');
            $grandTotalDisposals = collect($schedule)->sum('total_disposals');
            $grandTotalTaxDep = collect($schedule)->sum('total_tax_depreciation');
            $grandTotalClosingWdv = collect($schedule)->sum('total_closing_wdv');
        @endphp

        <div class="tax-class-section">
            <div class="tax-class-header" style="background: #28a745;">
                GRAND TOTAL - ALL TAX CLASSES
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th colspan="2">Description</th>
                        <th class="number">Opening WDV</th>
                        <th class="number">Additions</th>
                        <th class="number">Disposals</th>
                        <th class="number">Tax Depreciation</th>
                        <th class="number">Closing WDV</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="totals-row">
                        <td colspan="2"><strong>Total for Year {{ $taxYear }}</strong></td>
                        <td class="number"><strong>TZS {{ number_format($grandTotalOpeningWdv, 2) }}</strong></td>
                        <td class="number"><strong>TZS {{ number_format($grandTotalAdditions, 2) }}</strong></td>
                        <td class="number"><strong>TZS {{ number_format($grandTotalDisposals, 2) }}</strong></td>
                        <td class="number"><strong>TZS {{ number_format($grandTotalTaxDep, 2) }}</strong></td>
                        <td class="number"><strong>TZS {{ number_format($grandTotalClosingWdv, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No tax depreciation data found for the selected tax year.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Prepared by: {{ $user->name }} | Tax Year: {{ $taxYear }}</p>
        <p style="margin-top: 10px; font-size: 10px;">
            <strong>TRA Compliance:</strong> This schedule must be submitted as part of your annual tax return and retained for 5 years.
        </p>
    </div>
</body>
</html>
