<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll Variance Report</title>
    <style>
        @page { margin: 20px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #17a2b8; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #17a2b8; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #17a2b8; color: white; padding: 8px; font-weight: bold; border: 1px solid #ddd; }
        td { padding: 6px 8px; border: 1px solid #ddd; }
        .text-right { text-align: right; }
        .summary-box { display: inline-block; width: 30%; margin: 5px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        @if($company && $company->logo)
            <img src="{{ public_path('storage/' . $company->logo) }}" alt="Company Logo" style="height: 40px; margin-bottom: 5px;">
        @endif
        <h2>{{ $company ? $company->name : 'Company Name' }}</h2>
        <p>Payroll Variance Report</p>
        <p><strong>Period:</strong> {{ \Carbon\Carbon::create($year, $currentMonth, 1)->format('F Y') }} vs {{ \Carbon\Carbon::create($year, $compareMonth, 1)->format('F Y') }}</p>
    </div>

    <div style="margin-bottom: 15px;">
        <div class="summary-box" style="background-color: #e7f3ff;">
            <h4 style="margin: 0 0 5px 0; font-size: 11px; color: #666;">Current Period</h4>
            <p style="margin: 0; font-weight: bold;">{{ number_format($variances['net_pay']['current'], 2) }} TZS</p>
        </div>
        <div class="summary-box" style="background-color: #e7f8f0;">
            <h4 style="margin: 0 0 5px 0; font-size: 11px; color: #666;">Previous Period</h4>
            <p style="margin: 0; font-weight: bold;">{{ number_format($variances['net_pay']['compare'], 2) }} TZS</p>
        </div>
        <div class="summary-box" style="background-color: {{ $variances['net_pay']['variance'] >= 0 ? '#e7f8f0' : '#fff5f5' }};">
            <h4 style="margin: 0 0 5px 0; font-size: 11px; color: #666;">Variance</h4>
            <p style="margin: 0; font-weight: bold;">{{ number_format($variances['net_pay']['variance'], 2) }} TZS ({{ number_format($variances['net_pay']['variance_percent'], 2) }}%)</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Metric</th>
                <th class="text-right">Current Period</th>
                <th class="text-right">Previous Period</th>
                <th class="text-right">Variance</th>
                <th class="text-right">Variance %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($variances as $key => $variance)
            <tr>
                <td><strong>{{ ucwords(str_replace('_', ' ', $key)) }}</strong></td>
                <td class="text-right">{{ number_format($variance['current'], 2) }} TZS</td>
                <td class="text-right">{{ number_format($variance['compare'], 2) }} TZS</td>
                <td class="text-right">{{ $variance['variance'] >= 0 ? '+' : '' }}{{ number_format($variance['variance'], 2) }} TZS</td>
                <td class="text-right">{{ $variance['variance_percent'] >= 0 ? '+' : '' }}{{ number_format($variance['variance_percent'], 2) }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 20px; font-size: 9px; color: #999; text-align: center;">Generated on {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
