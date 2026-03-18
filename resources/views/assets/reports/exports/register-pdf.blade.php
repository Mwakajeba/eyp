<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fixed Asset Register</title>
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
        .info-section {
            margin-bottom: 15px;
            font-size: 9px;
        }
        .info-section table {
            width: 100%;
        }
        .info-section td {
            padding: 2px 5px;
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
            font-size: 9px;
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
        .summary-box {
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }
        .summary-box table {
            border: none;
        }
        .summary-box td {
            border: none;
            padding: 3px 10px;
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
        <h2>FIXED ASSET REGISTER</h2>
        <p>As of {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }}</p>
    </div>

    <div class="summary-box">
        <strong>SUMMARY</strong>
        <table>
            <tr>
                <td><strong>Total Assets:</strong> {{ $summary['count'] }}</td>
                <td><strong>Total Cost:</strong> TZS {{ number_format($summary['total_cost'], 2) }}</td>
            </tr>
            <tr>
                <td><strong>Accumulated Depreciation:</strong> TZS {{ number_format($summary['total_accumulated_dep'], 2) }}</td>
                <td><strong>Net Book Value:</strong> TZS {{ number_format($summary['total_nbv'], 2) }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>Asset Code</th>
                <th>Asset Name</th>
                <th>Category</th>
                <th>Location</th>
                <th>Custodian</th>
                <th>Serial No</th>
                <th>Purchase Date</th>
                <th>Cap. Date</th>
                <th class="text-right">Cost</th>
                <th>Life (Yrs)</th>
                <th>Method</th>
                <th class="text-right">Accum. Dep</th>
                <th class="text-right">Impairment</th>
                <th class="text-right">NBV</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $asset)
            <tr>
                <td>{{ $asset['code'] }}</td>
                <td>{{ $asset['name'] }}</td>
                <td>{{ $asset['category'] }}</td>
                <td>{{ $asset['location'] }}</td>
                <td>{{ $asset['custodian'] }}</td>
                <td>{{ $asset['serial_number'] }}</td>
                <td>{{ $asset['purchase_date'] }}</td>
                <td>{{ $asset['capitalization_date'] }}</td>
                <td class="text-right">{{ number_format($asset['purchase_cost'], 2) }}</td>
                <td class="text-center">{{ $asset['useful_life'] }}</td>
                <td>{{ $asset['depreciation_method'] }}</td>
                <td class="text-right">{{ number_format($asset['accumulated_depreciation'], 2) }}</td>
                <td class="text-right">{{ number_format($asset['impairment_amount'], 2) }}</td>
                <td class="text-right">{{ number_format($asset['carrying_amount'], 2) }}</td>
                <td>{{ $asset['status'] }}</td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="8"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{ number_format($summary['total_cost'], 2) }}</strong></td>
                <td colspan="2"></td>
                <td class="text-right"><strong>{{ number_format($summary['total_accumulated_dep'], 2) }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($summary['total_nbv'], 2) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td><strong>Prepared By:</strong> {{ $preparedBy }}</td>
                <td style="text-align: right;"><strong>Generated:</strong> {{ $generatedDate }}</td>
            </tr>
            <tr>
                <td colspan="2"><em>This is a primary audit document. Must reconcile to General Ledger accounts.</em></td>
            </tr>
        </table>
    </div>
</body>
</html>
