<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Asset Movement Schedule</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 16px;
        }
        .header p {
            margin: 3px 0;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }
        th {
            background-color: #4472C4;
            color: white;
            font-weight: bold;
            font-size: 8px;
        }
        td {
            font-size: 8px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-row {
            background-color: #FFF2CC;
            font-weight: bold;
        }
        .cost-header {
            background-color: #4472C4 !important;
        }
        .dep-header {
            background-color: #DC3545 !important;
        }
        .nbv-header {
            background-color: #28A745 !important;
        }
        .footer {
            margin-top: 20px;
            font-size: 8px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .info-box {
            background-color: #d1ecf1;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #bee5eb;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $company->name ?? 'Company Name' }}</h2>
        <p>{{ $branch->name ?? 'All Branches' }}</p>
        <h2>ASSET MOVEMENT SCHEDULE (ROLL-FORWARD)</h2>
        <p>Period: {{ $fromDate->format('d M Y') }} to {{ $toDate->format('d M Y') }}</p>
    </div>

    <div class="info-box">
        <strong>IFRS Compliance:</strong> This report satisfies IAS 16 reconciliation requirements for Property, Plant & Equipment disclosure in financial statements.
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">Asset Category</th>
                <th colspan="6" class="text-center cost-header">COST / VALUATION</th>
                <th colspan="5" class="text-center dep-header">ACCUMULATED DEPRECIATION</th>
                <th rowspan="2" class="text-center nbv-header">Closing NBV</th>
            </tr>
            <tr>
                <th class="text-right">Opening</th>
                <th class="text-right">Additions</th>
                <th class="text-right">Disposals</th>
                <th class="text-right">Transfers</th>
                <th class="text-right">Revaluation</th>
                <th class="text-right">Closing</th>
                <th class="text-right">Opening</th>
                <th class="text-right">Charge</th>
                <th class="text-right">Disposal</th>
                <th class="text-right">Impairment</th>
                <th class="text-right">Closing</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totals = [
                    'opening_cost' => 0,
                    'additions' => 0,
                    'disposals' => 0,
                    'transfers' => 0,
                    'revaluation' => 0,
                    'closing_cost' => 0,
                    'opening_accum_dep' => 0,
                    'depreciation_charge' => 0,
                    'disposal_dep_removed' => 0,
                    'impairment' => 0,
                    'closing_accum_dep' => 0,
                    'closing_nbv' => 0
                ];
            @endphp

            @foreach($data as $category)
            <tr>
                <td><strong>{{ $category['category_name'] }}</strong></td>
                <td class="text-right">{{ number_format($category['opening_cost'], 2) }}</td>
                <td class="text-right">{{ number_format($category['additions'], 2) }}</td>
                <td class="text-right">{{ number_format($category['disposals'], 2) }}</td>
                <td class="text-right">{{ number_format($category['transfers'], 2) }}</td>
                <td class="text-right">{{ number_format($category['revaluation'], 2) }}</td>
                <td class="text-right">{{ number_format($category['closing_cost'], 2) }}</td>
                <td class="text-right">{{ number_format($category['opening_accum_dep'], 2) }}</td>
                <td class="text-right">{{ number_format($category['depreciation_charge'], 2) }}</td>
                <td class="text-right">{{ number_format($category['disposal_dep_removed'], 2) }}</td>
                <td class="text-right">{{ number_format($category['impairment'], 2) }}</td>
                <td class="text-right">{{ number_format($category['closing_accum_dep'], 2) }}</td>
                <td class="text-right">{{ number_format($category['closing_nbv'], 2) }}</td>
            </tr>

            @php
                foreach ($totals as $key => $value) {
                    $totals[$key] += $category[$key];
                }
            @endphp
            @endforeach

            <tr class="totals-row">
                <td><strong>GRAND TOTAL</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['opening_cost'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['additions'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['disposals'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['transfers'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['revaluation'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['closing_cost'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['opening_accum_dep'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['depreciation_charge'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['disposal_dep_removed'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['impairment'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['closing_accum_dep'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['closing_nbv'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <table style="border: none;">
            <tr>
                <td style="border: none;"><strong>Prepared By:</strong> {{ $preparedBy }}</td>
                <td style="border: none; text-align: right;"><strong>Generated:</strong> {{ $generatedDate }}</td>
            </tr>
            <tr>
                <td colspan="2" style="border: none;"><em>Required for IFRS IAS 16 compliance. This reconciliation must be included in annual financial statements.</em></td>
            </tr>
        </table>
    </div>
</body>
</html>
