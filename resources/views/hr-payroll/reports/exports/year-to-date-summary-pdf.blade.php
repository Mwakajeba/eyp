<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Year-to-Date Summary Report</title>
    <style>
        @page { margin: 20px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #17a2b8; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #17a2b8; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #17a2b8; color: white; padding: 8px; text-align: left; font-weight: bold; border: 1px solid #ddd; }
        td { padding: 6px 8px; border: 1px solid #ddd; }
        .text-right { text-align: right; }
        .totals-row { background-color: #28a745 !important; color: white; font-weight: bold; }
        .summary-box { display: inline-block; width: 23%; margin: 5px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        @if($company && $company->logo)
            <img src="{{ public_path('storage/' . $company->logo) }}" alt="Company Logo" style="height: 40px; margin-bottom: 5px;">
        @endif
        <h2>{{ $company ? $company->name : 'Company Name' }}</h2>
        <p>Year-to-Date Summary Report</p>
        <p><strong>Year:</strong> {{ $year }}</p>
    </div>

    <div style="margin-bottom: 15px;">
        <div class="summary-box" style="background-color: #e7f3ff;">
            <h4 style="margin: 0 0 5px 0; font-size: 11px; color: #666;">Total Payrolls</h4>
            <p style="margin: 0; font-weight: bold;">{{ number_format($ytdTotals['total_payrolls']) }}</p>
        </div>
        <div class="summary-box" style="background-color: #e7f8f0;">
            <h4 style="margin: 0 0 5px 0; font-size: 11px; color: #666;">Total Employees</h4>
            <p style="margin: 0; font-weight: bold;">{{ number_format($ytdTotals['total_employees']) }}</p>
        </div>
        <div class="summary-box" style="background-color: #fff8e1;">
            <h4 style="margin: 0 0 5px 0; font-size: 11px; color: #666;">Gross Salary</h4>
            <p style="margin: 0; font-weight: bold;">{{ number_format($ytdTotals['total_gross_salary'], 2) }} TZS</p>
        </div>
        <div class="summary-box" style="background-color: #e1f5fe;">
            <h4 style="margin: 0 0 5px 0; font-size: 11px; color: #666;">Net Pay</h4>
            <p style="margin: 0; font-weight: bold;">{{ number_format($ytdTotals['total_net_pay'], 2) }} TZS</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th class="text-right">Payrolls</th>
                <th class="text-right">Employees</th>
                <th class="text-right">Gross Salary</th>
                <th class="text-right">Net Pay</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthlyBreakdown as $monthData)
            <tr>
                <td><strong>{{ $monthData['month'] }}</strong></td>
                <td class="text-right">{{ number_format($monthData['payroll_count']) }}</td>
                <td class="text-right">{{ number_format($monthData['employee_count']) }}</td>
                <td class="text-right">{{ number_format($monthData['gross_salary'], 2) }} TZS</td>
                <td class="text-right">{{ number_format($monthData['net_pay'], 2) }} TZS</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td><strong>Total</strong></td>
                <td class="text-right">{{ number_format($ytdTotals['total_payrolls']) }}</td>
                <td class="text-right">{{ number_format($ytdTotals['total_employees']) }}</td>
                <td class="text-right">{{ number_format($ytdTotals['total_gross_salary'], 2) }} TZS</td>
                <td class="text-right">{{ number_format($ytdTotals['total_net_pay'], 2) }} TZS</td>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top: 20px; font-size: 9px; color: #999; text-align: center;">Generated on {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
