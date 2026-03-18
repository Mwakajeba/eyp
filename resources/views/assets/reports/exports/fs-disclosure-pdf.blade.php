<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fixed Asset Disclosure Note (IFRS)</title>
    <style>
        body { font-family: 'Helvetica', 'DejaVu Sans', sans-serif; margin: 0; padding: 15px; color: #333; }
        .header { margin-bottom: 20px; border-bottom: 3px solid #343a40; padding-bottom: 15px; text-align: center; }
        .header h1 { color: #343a40; margin: 0; font-size: 20px; font-weight: bold; }
        .header .subtitle { color: #666; margin: 5px 0 0 0; font-size: 14px; }
        .note { background: #d1ecf1; padding: 10px; border-left: 4px solid #0c5460; margin-bottom: 15px; font-size: 11px; color: #0c5460; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        .data-table thead { background: #343a40; color: white; }
        .data-table th { padding: 8px 6px; text-align: left; font-weight: bold; font-size: 10px; }
        .data-table td { padding: 8px 6px; border-bottom: 1px solid #dee2e6; font-size: 10px; }
        .number { text-align: right; font-family: 'Courier', 'Courier New', monospace; }
        .totals-row { background: #fff3cd !important; font-weight: bold; }
        .totals-row td { border-top: 2px solid #ffc107 !important; padding: 10px 6px !important; }
        .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #dee2e6; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Note X: Property, Plant and Equipment</h1>
        <div class="subtitle">IFRS-Compliant Financial Statement Disclosure</div>
        <div class="subtitle">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
    </div>

    <div class="note">
        <strong>IAS 16 Compliance:</strong> This disclosure note shows the reconciliation of carrying amounts of Property, Plant and Equipment at the beginning and end of the period.
    </div>

    @if(count($data) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="number">Opening Balance</th>
                    <th class="number">Additions</th>
                    <th class="number">Disposals</th>
                    <th class="number">Revaluations</th>
                    <th class="number">Depreciation</th>
                    <th class="number">Closing Balance</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalOpening = 0;
                    $totalAdditions = 0;
                    $totalDisposals = 0;
                    $totalRevaluations = 0;
                    $totalDepreciation = 0;
                    $totalClosing = 0;
                @endphp
                
                @foreach($data as $item)
                    @php
                        $totalOpening += $item['opening_balance'];
                        $totalAdditions += $item['additions'];
                        $totalDisposals += $item['disposals'];
                        $totalRevaluations += $item['revaluations'];
                        $totalDepreciation += $item['depreciation'];
                        $totalClosing += $item['closing_balance'];
                    @endphp
                    <tr>
                        <td><strong>{{ $item['category'] }}</strong></td>
                        <td class="number">{{ number_format($item['opening_balance'], 2) }}</td>
                        <td class="number">{{ number_format($item['additions'], 2) }}</td>
                        <td class="number">{{ number_format($item['disposals'], 2) }}</td>
                        <td class="number">{{ number_format($item['revaluations'], 2) }}</td>
                        <td class="number">{{ number_format($item['depreciation'], 2) }}</td>
                        <td class="number"><strong>{{ number_format($item['closing_balance'], 2) }}</strong></td>
                    </tr>
                @endforeach
                
                <tr class="totals-row">
                    <td><strong>TOTAL</strong></td>
                    <td class="number"><strong>{{ number_format($totalOpening, 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($totalAdditions, 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($totalDisposals, 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($totalRevaluations, 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($totalDepreciation, 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($totalClosing, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 40px; color: #666; font-style: italic;">
            <h3>No Data Available</h3>
            <p>No asset data found for disclosure.</p>
        </div>
    @endif

    <div class="footer">
        <p>This disclosure note was generated by Smart Accounting System</p>
        <p>Compliant with IAS 16 - Property, Plant and Equipment</p>
    </div>
</body>
</html>
