<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GL Reconciliation Report</title>
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
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-box strong {
            color: #856404;
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
            width: 33.33%;
        }
        .summary-boxes .box-title {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
        }
        .summary-boxes .box-value {
            font-size: 14px;
            font-weight: bold;
        }
        .summary-boxes .primary {
            color: #007bff;
        }
        .summary-boxes .info {
            color: #17a2b8;
        }
        .summary-boxes .success {
            color: #28a745;
        }
        .summary-boxes .danger {
            color: #dc3545;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #28A745;
            color: white;
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
        .text-success {
            color: #28a745;
        }
        .totals-row {
            background-color: #FFF2CC;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
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
        <h2>GL RECONCILIATION REPORT</h2>
        <p>As of {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }}</p>
    </div>

    <div class="alert-box">
        <strong>⚠ Month-End Critical:</strong> This reconciliation must be performed before closing the period. Any variances must be investigated and resolved.
    </div>

    <div class="summary-boxes">
        <table>
            <tr>
                <td>
                    <div class="box-title">Subledger Balance</div>
                    <div class="box-value primary">TZS {{ number_format($summary['total_subledger'], 2) }}</div>
                </td>
                <td>
                    <div class="box-title">GL Balance</div>
                    <div class="box-value info">TZS {{ number_format($summary['total_gl'], 2) }}</div>
                </td>
                <td>
                    <div class="box-title">Variance</div>
                    <div class="box-value {{ abs($summary['total_variance']) < 0.01 ? 'success' : 'danger' }}">
                        TZS {{ number_format($summary['total_variance'], 2) }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>GL Account Code</th>
                <th>GL Account Name</th>
                <th>Account Type</th>
                <th class="text-right">GL Balance</th>
                <th class="text-right">Subledger Balance</th>
                <th class="text-right">Difference</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $account)
            @php
                $isReconciled = abs($account['difference']) < 0.01;
            @endphp
            <tr>
                <td>{{ $account['gl_account_code'] }}</td>
                <td>{{ $account['gl_account_name'] }}</td>
                <td>{{ $account['account_type'] }}</td>
                <td class="text-right">{{ number_format($account['gl_balance'], 2) }}</td>
                <td class="text-right">{{ number_format($account['subledger_balance'], 2) }}</td>
                <td class="text-right {{ $isReconciled ? 'text-success' : 'text-danger' }}">
                    {{ number_format($account['difference'], 2) }}
                </td>
                <td class="text-center">
                    @if($isReconciled)
                        <span class="badge badge-success">Reconciled</span>
                    @else
                        <span class="badge badge-danger">Variance</span>
                    @endif
                </td>
            </tr>
            @endforeach
            @php
                $totalReconciled = abs($summary['total_variance']) < 0.01;
            @endphp
            <tr class="totals-row">
                <td colspan="3"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{ number_format($summary['total_gl'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($summary['total_subledger'], 2) }}</strong></td>
                <td class="text-right {{ $totalReconciled ? 'text-success' : 'text-danger' }}">
                    <strong>{{ number_format($summary['total_variance'], 2) }}</strong>
                </td>
                <td class="text-center">
                    @if($totalReconciled)
                        <span class="badge badge-success">Reconciled</span>
                    @else
                        <span class="badge badge-danger">Variance</span>
                    @endif
                </td>
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
                    <em>Note: All variances must be investigated and resolved before period close. Subledger should always reconcile to GL accounts.</em>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
