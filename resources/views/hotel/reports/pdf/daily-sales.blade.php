<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Sales Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 15px; color: #333; }
        .header { margin-bottom: 20px; border-bottom: 3px solid #198754; padding-bottom: 15px; }
        .header-content {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 30px;
        }
        .logo-section {
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }
        .company-logo {
            height: 100px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
        }
        .title-section {
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .header h1 { color: #198754; margin: 0; font-size: 24px; font-weight: bold; }
        .company-name { color: #333; margin: 5px 0; font-size: 16px; font-weight: 600; }
        .header .subtitle { color: #666; margin: 5px 0 0 0; font-size: 14px; }
        .report-info { margin-bottom: 15px; font-size: 11px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 6px; }
        table.data-table th { background-color: #198754; color: white; font-weight: bold; text-align: left; }
        table.data-table tfoot { background-color: #f8f9fa; font-weight: bold; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 9px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                @if($company && $company->logo)
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="{{ $company->name }}" class="company-logo">
                @endif
            </div>
            <div class="title-section">
                <h1>Daily Sales / Revenue Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name }}</div>
                @endif
                @if($branch)
                    <div class="subtitle">{{ $branch->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ $generatedAt->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <strong>Date:</strong> {{ \Carbon\Carbon::parse($date)->format('F d, Y') }} | 
        <strong>Total Amount:</strong> TZS {{ number_format($totalAmount, 2) }}
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice No</th>
                <th>Guest Name</th>
                <th>Room No</th>
                <th>Payment Method</th>
                <th class="text-end">Amount Paid</th>
                <th>Received By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesData as $sale)
                <tr>
                    <td>{{ $sale['receipt']->date->format('M d, Y') }}</td>
                    <td>{{ $sale['receipt']->reference_number ?? 'N/A' }}</td>
                    <td>{{ $sale['guest_name'] }}</td>
                    <td>{{ $sale['room_no'] }}</td>
                    <td>{{ $sale['payment_method'] }}</td>
                    <td class="text-end"><strong>{{ number_format($sale['amount'], 2) }}</strong></td>
                    <td>{{ $sale['received_by'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No sales data found for the selected date.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-end">TOTAL:</th>
                <th class="text-end">{{ number_format($totalAmount, 2) }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Daily Sales Report - Generated on {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>
</body>
</html>
