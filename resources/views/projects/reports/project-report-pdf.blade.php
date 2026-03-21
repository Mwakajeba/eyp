<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
        }

        :root {
            --brand-green: #0f6b3f;
            --brand-green-dark: #0b4f2e;
            --brand-green-soft: #e8f5ee;
            --brand-orange: #f08a24;
            --brand-orange-soft: #fff3e6;
            --line-gray: #cbd5e1;
        }

        .container {
            width: 100%;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: var(--brand-green);
        }

        .company-details {
            font-size: 10px;
            margin-top: 3px;
        }

        .document-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--brand-green-dark);
            text-align: center;
            margin: 10px 0;
            letter-spacing: 0.4px;
        }

        .logo-section {
            margin-bottom: 10px;
        }

        .company-logo {
            max-height: 80px;
            max-width: 120px;
            object-fit: contain;
        }

        hr {
            border: none;
            border-top: 3px solid var(--brand-orange);
            margin: 8px 0;
        }

        .info-grid {
            width: 100%;
            margin-bottom: 12px;
        }

        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding: 0 6px 0 0;
        }

        .box {
            border: 1px solid var(--line-gray);
            padding: 8px;
            border-radius: 3px;
            min-height: 60px;
        }

        .box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .box td {
            padding: 2px 0;
            border: none;
        }

        .box td:first-child {
            color: var(--brand-green-dark);
            font-weight: bold;
            width: 35%;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: var(--brand-green-dark);
            margin: 12px 0 6px 0;
            border-left: 4px solid var(--brand-orange);
            padding-left: 6px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 6px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid var(--line-gray);
            padding: 5px;
            vertical-align: top;
        }

        .items-table th {
            background-color: var(--brand-green);
            color: #fff;
            font-weight: bold;
            text-align: left;
        }

        .items-table tbody tr:nth-child(even) {
            background: var(--brand-green-soft);
        }

        .total-row td {
            font-weight: bold;
            background: var(--brand-orange-soft);
            color: var(--brand-green-dark);
        }

        .status-chip {
            display: inline-block;
            border: 1px solid var(--brand-orange);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            background: var(--brand-green-soft);
            color: var(--brand-green-dark);
        }

        .footer-note {
            margin-top: 12px;
            font-size: 9px;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-left">
            @if(!empty($logoBase64))
            <div class="logo-section" style="float: left; width: 45%;">
                <img src="{{ $logoBase64 }}" alt="Company logo" class="company-logo">
            </div>
            @endif
            <div style="float: right; width: 50%; text-align: left; margin-left: 15%;">
                <div class="company-name">{{ $company->name ?? 'Empower Youth Prosperity' }}</div>
                <div class="company-details">
                    {{ $company->address ?? '' }} | {{ $company->phone ?? '' }} | {{ $company->email ?? '' }}
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        <hr>
        <div class="document-title">{{ strtoupper($title) }}</div>

        <table class="info-grid">
            <tr>
                <td>
                    <div class="box">
                        <table>
                            <tr>
                                <td>Project Code:</td>
                                <td>{{ $project->project_code ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td>Project Name:</td>
                                <td>{{ $project->name }}</td>
                            </tr>
                            <tr>
                                <td>Project Type:</td>
                                <td>{{ ucfirst($project->type ?? 'N/A') }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td>
                    <div class="box">
                        <table>
                            <tr>
                                <td>Report Type:</td>
                                <td><span class="status-chip">{{ $reportType }}</span></td>
                            </tr>
                            <tr>
                                <td>Period From:</td>
                                <td>{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td>Period To:</td>
                                <td>{{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-title">{{ $reportType }} Details</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 12%;">Reference</th>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 16%;">Payee</th>
                    <th style="width: 10%;">Ref Type</th>
                    <th style="width: 10%;">Ref Number</th>
                    <th style="width: 6%;">Currency</th>
                    <th style="width: 12%;" class="text-right">Amount</th>
                    <th style="width: 20%;">Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $idx => $row)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $row->reference }}</td>
                        <td>{{ optional($row->date)->format('d M Y') }}</td>
                        <td>{{ $row->payee_name ?: ($row->supplier->name ?? ($row->customer->name ?? '-')) }}</td>
                        <td>{{ $row->reference_type }}</td>
                        <td>{{ $row->reference_number }}</td>
                        <td>{{ $row->currency ?? 'TZS' }}</td>
                        <td class="text-right">{{ number_format((float) $row->amount, 2) }}</td>
                        <td>{{ $row->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No {{ strtolower($reportType) }} found for this period.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="7" class="text-right">Total {{ $reportType }}</td>
                    <td class="text-right">{{ number_format((float) $totalAmount, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="footer-note">
            Generated on {{ now()->format('d M Y H:i') }} by {{ auth()->user()->name ?? 'System' }}.
        </div>
    </div>
</body>
</html>
