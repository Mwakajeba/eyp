<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll by Department Report</title>
    <style>
        @page {
            margin: 20px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #17a2b8;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            color: #17a2b8;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .info-section {
            margin-bottom: 15px;
        }
        .info-section p {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #17a2b8;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .totals-row {
            background-color: #28a745 !important;
            color: white;
            font-weight: bold;
        }
        .totals-row td {
            border-color: #28a745;
        }
        .summary-box {
            display: inline-block;
            width: 23%;
            margin: 5px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        .summary-box h4 {
            margin: 0 0 5px 0;
            font-size: 11px;
            color: #666;
        }
        .summary-box p {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($company && $company->logo)
            <img src="{{ public_path('storage/' . $company->logo) }}" alt="Company Logo" style="height: 40px; margin-bottom: 5px;">
        @endif
        <h2>{{ $company ? $company->name : 'Company Name' }}</h2>
        <p>Payroll by Department Report</p>
        <p><strong>Period:</strong> {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}</p>
    </div>

    <div class="info-section">
        <div class="summary-box" style="background-color: #e7f3ff;">
            <h4>Total Employees</h4>
            <p style="color: #007bff;">{{ number_format($totals['total_employees']) }}</p>
        </div>
        <div class="summary-box" style="background-color: #e7f8f0;">
            <h4>Total Gross Salary</h4>
            <p style="color: #28a745;">{{ number_format($totals['total_gross'], 2) }} TZS</p>
        </div>
        <div class="summary-box" style="background-color: #fff8e1;">
            <h4>Total Deductions</h4>
            <p style="color: #ffc107;">{{ number_format($totals['total_deductions'], 2) }} TZS</p>
        </div>
        <div class="summary-box" style="background-color: #e1f5fe;">
            <h4>Total Net Pay</h4>
            <p style="color: #17a2b8;">{{ number_format($totals['total_net'], 2) }} TZS</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Department</th>
                <th class="text-right">Employees</th>
                <th class="text-right">Gross Salary</th>
                <th class="text-right">Deductions</th>
                <th class="text-right">Net Pay</th>
                <th class="text-right">Avg. Salary</th>
            </tr>
        </thead>
        <tbody>
            @foreach($departmentData as $data)
            <tr>
                <td><strong>{{ $data['department']->name }}</strong></td>
                <td class="text-right">{{ number_format($data['employee_count']) }}</td>
                <td class="text-right">{{ number_format($data['total_gross'], 2) }} TZS</td>
                <td class="text-right">{{ number_format($data['total_deductions'], 2) }} TZS</td>
                <td class="text-right">{{ number_format($data['total_net'], 2) }} TZS</td>
                <td class="text-right">{{ number_format($data['average_salary'], 2) }} TZS</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td><strong>Total</strong></td>
                <td class="text-right">{{ number_format($totals['total_employees']) }}</td>
                <td class="text-right">{{ number_format($totals['total_gross'], 2) }} TZS</td>
                <td class="text-right">{{ number_format($totals['total_deductions'], 2) }} TZS</td>
                <td class="text-right">{{ number_format($totals['total_net'], 2) }} TZS</td>
                <td class="text-right">{{ $totals['total_employees'] > 0 ? number_format($totals['total_net'] / $totals['total_employees'], 2) : '0.00' }} TZS</td>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top: 20px; font-size: 9px; color: #999; text-align: center;">
        Generated on {{ now()->format('d/m/Y H:i') }}
    </p>
</body>
</html>
