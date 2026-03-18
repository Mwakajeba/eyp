<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Depreciation Expense Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
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
        .alert-box {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .summary-boxes {
            margin-bottom: 20px;
        }
        .summary-boxes table {
            width: 100%;
            border: none;
        }
        .summary-boxes td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            width: 25%;
        }
        .summary-boxes .box-title {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
        }
        .summary-boxes .box-value {
            font-size: 13px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #FFC107;
            color: #000;
            font-weight: bold;
            font-size: 9px;
        }
        td {
            font-size: 9px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-danger {
            color: #dc3545;
            font-weight: bold;
        }
        .totals-row {
            background-color: #FFF2CC;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            font-size: 8px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $company->name ?? 'Company Name' }}</h2>
        <p>{{ $branch->name ?? 'All Branches' }}</p>
        <h2>DEPRECIATION EXPENSE REPORT</h2>
        <p>Period: {{ $fromDate->format('d M Y') }} to {{ $toDate->format('d M Y') }}</p>
    </div>

    <div class="alert-box">
        <strong>ℹ P&L Verification:</strong> This report shows depreciation charges to be recognized in the Profit & Loss statement for the selected period.
    </div>

    <div class="summary-boxes">
        <table>
            <tr>
                <td>
                    <div class="box-title">Total Depreciation</div>
                    <div class="box-value">TZS {{ number_format($summary['total_depreciation'], 2) }}</div>
                </td>
                <td>
                    <div class="box-title">Assets Depreciated</div>
                    <div class="box-value">{{ $summary['asset_count'] }}</div>
                </td>
                <td>
                    <div class="box-title">Avg Per Asset</div>
                    <div class="box-value">TZS {{ number_format($summary['avg_per_asset'], 2) }}</div>
                </td>
                <td>
                    <div class="box-title">Monthly Avg</div>
                    <div class="box-value">TZS {{ number_format($summary['monthly_avg'], 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>Asset Code</th>
                <th>Asset Name</th>
                <th>Category</th>
                <th class="text-right">Cost</th>
                <th class="text-right">Opening NBV</th>
                <th class="text-center">Rate</th>
                <th class="text-right">Depreciation</th>
                <th class="text-right">Accum. Dep</th>
                <th class="text-right">Closing NBV</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
            <tr>
                <td>{{ $item['asset_code'] }}</td>
                <td>{{ $item['asset_name'] }}</td>
                <td>{{ $item['category_name'] }}</td>
                <td class="text-right">{{ number_format($item['cost'], 2) }}</td>
                <td class="text-right">{{ number_format($item['opening_nbv'], 2) }}</td>
                <td class="text-center">{{ $item['depreciation_rate'] }}%</td>
                <td class="text-right text-danger">{{ number_format($item['period_depreciation'], 2) }}</td>
                <td class="text-right">{{ number_format($item['accumulated_depreciation'], 2) }}</td>
                <td class="text-right">{{ number_format($item['closing_nbv'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="3"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{ number_format($summary['total_cost'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($summary['total_opening_nbv'], 2) }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($summary['total_depreciation'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($summary['total_accumulated'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($summary['total_closing_nbv'], 2) }}</strong></td>
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
                <td colspan="2" style="border: none;">
                    <em>This depreciation charge should be posted to the Depreciation Expense account in the P&L statement.</em>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
