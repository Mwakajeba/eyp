<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bank Payment Report</title>
    <style>
        @page { margin: 20px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #17a2b8; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #17a2b8; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #17a2b8; color: white; padding: 8px; font-weight: bold; border: 1px solid #ddd; }
        td { padding: 6px 8px; border: 1px solid #ddd; }
        .text-right { text-align: right; }
        .bank-section { margin-bottom: 20px; }
        .bank-section h3 { background: #f0f0f0; padding: 8px; margin: 15px 0 10px 0; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        @if($company && $company->logo)
            <img src="{{ public_path('storage/' . $company->logo) }}" alt="Company Logo" style="height: 40px; margin-bottom: 5px;">
        @endif
        <h2>{{ $company ? $company->name : 'Company Name' }}</h2>
        <p>Bank Payment Report</p>
        <p><strong>Period:</strong> {{ $dateFrom }} to {{ $dateTo }}</p>
    </div>

    @foreach($bankGroups as $bankGroup)
    <div class="bank-section">
        <h3>
            @if($bankGroup['bank_account'] && $bankGroup['bank_account_id'])
                {{ $bankGroup['bank_account']->name }} - {{ $bankGroup['bank_account']->account_number }}
            @elseif($bankGroup['bank_account_id'] && $bankGroup['bank_account_id'] !== 'no_account')
                Bank Account #{{ $bankGroup['bank_account_id'] }}
            @else
                Payments Without Bank Account
            @endif
        </h3>
        <p><strong>Payrolls:</strong> {{ $bankGroup['payroll_count'] }} | <strong>Total Amount:</strong> {{ number_format($bankGroup['total_amount'], 2) }} TZS</p>
        <table>
            <thead>
                <tr>
                    <th>Payroll Reference</th>
                    <th>Period</th>
                    <th>Payment Date</th>
                    <th class="text-right">Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bankGroup['payrolls'] as $payroll)
                <tr>
                    <td>{{ $payroll->reference }}</td>
                    <td>{{ \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y') }}</td>
                    <td>{{ $payroll->payment_date ? \Carbon\Carbon::parse($payroll->payment_date)->format('Y-m-d') : 'N/A' }}</td>
                    <td class="text-right">{{ number_format($payroll->payrollEmployees->sum('net_salary'), 2) }} TZS</td>
                    <td>{{ ucfirst($payroll->status ?? 'N/A') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    <p style="margin-top: 20px; font-size: 9px; color: #999; text-align: center;">Generated on {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
