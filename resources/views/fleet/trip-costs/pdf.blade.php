<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trip Cost Receipt - {{ $costs->first()->receipt_number ?? 'COST-' . $costs->first()->id }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .container {
            width: 100%;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        hr {
            border: none;
            border-top: 2px solid #3b82f6;
            margin: 8px 0;
        }

        /* Header */
        .logo-section {
            margin-bottom: 10px;
        }

        .company-logo {
            max-height: 80px;
            max-width: 120px;
            object-fit: contain;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }

        .company-details {
            font-size: 10px;
        }

        /* Payment methods */
        .payment-methods {
            font-size: 10px;
            margin: 8px 0;
        }

        .payment-method-bar {
            background-color: #1e3a8a;
            color: #fff;
            padding: 8px;
            font-weight: bold;
            margin-top: 10px;
        }

        .payment-details {
            padding: 8px;
            background-color: #f8fafc;
        }

        .payment-details strong {
            color: #1e40af;
        }

        /* Invoice title */
        .invoice-title {
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
            color: #1e40af;
        }

        /* Info section */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .bill-to {
            width: 48%;
            font-size: 10px;
        }

        .bill-to strong {
            color: #1e40af;
        }

        .invoice-box {
            width: 48%;
            text-align: right;
        }

        .invoice-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .invoice-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .invoice-box td:nth-child(even) {
            text-align: right;
        }

        .invoice-box strong {
            color: #1e40af;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 5px;
        }

        .items-table th {
            text-align: center;
            font-weight: bold;
            background-color: #1e3a8a;
            color: #fff;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #dbeafe;
        }

        .items-table tbody tr:nth-child(odd) {
            background-color: #fff;
        }

        /* Totals */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }

        .totals-table td {
            padding: 4px 5px;
            border: none;
        }

        .totals-table td:last-child {
            text-align: right;
            padding-right: 5px;
        }

        .totals-table tr:last-child td {
            background-color: #1e3a8a;
            color: #fff;
            font-weight: bold;
            padding: 8px 5px;
        }

        .totals-table tr:last-child td:last-child {
            background-color: #dbeafe;
            color: #1e3a8a;
            padding: 8px;
            border-radius: 3px;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            font-size: 10px;
        }

        .footer strong {
            color: #1e40af;
        }

        .signature {
            margin-top: 20px;
        }

        .footer hr {
            border-top: 1px solid #dbeafe;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">

        {{-- Header (same structure as sales invoice) --}}
        <div class="text-left">
            @if($company && $company->logo)
            @php
                $logo = $company->logo;
                $logoPath = public_path('storage/' . ltrim($logo, '/'));
                $logoBase64 = null;
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $imageInfo = @getimagesize($logoPath);
                    if ($imageInfo !== false) {
                        $mimeType = $imageInfo['mime'];
                        $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                    }
                }
            @endphp
            @if($logoBase64)
            <div class="logo-section" style="float: left; width: 45%;">
                <img src="{{ $logoBase64 }}" alt="{{ ($company->name ?? 'Company') . ' logo' }}" class="company-logo">
            </div>
            @endif
            @endif
            <div style="float: right; width: 50%; text-align: left; margin-left: 15%;">
                <div class="company-name">{{ $company->name ?? 'SMARTACCOUNTING' }}</div>
                <div class="company-details">
                    Address: {{ $company->address ?? 'P.O.BOX 00000, City, Country' }} <br>
                    Phone: {{ $company->phone ?? '+255 000 000 000' }} <br>
                    Email: {{ $company->email ?? 'company@email.com' }}
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        @php
            $bankAccounts = \App\Models\BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($company) {
                $q->where('company_id', $company->id ?? 0);
            })->get();
        @endphp
        @if($bankAccounts && $bankAccounts->count() > 0)
        <div class="payment-method-bar" style="text-align: center;">
            <strong>PAYMENT METHOD :</strong>
        </div>
        <div class="payment-details">
            @foreach($bankAccounts as $account)
            <strong>{{ strtoupper($account->name ?? $account->bank_name ?? 'BANK') }}:</strong> {{ $account->account_number ?? 'N/A' }} &nbsp;&nbsp;
            @endforeach
        </div>
        @endif

        <div class="invoice-title">TRIP COST RECEIPT</div>
        <hr>

        {{-- Bill To + Invoice Info (same layout as sales invoice) --}}
        <div class="info-section">
            <div class="bill-to" style="float: left; width: 48%;">
                <strong>Trip:</strong><br>
                <strong>{{ $trip->trip_number ?? 'N/A' }}</strong><br>
                @if($trip && isset($trip->route) && $trip->route)
                <strong>Route:</strong><br>
                {{ $trip->route->route_name }} ({{ number_format($trip->route->distance_km, 2) }} km)<br>
                @elseif($trip)
                <strong>Route:</strong><br>
                {{ $trip->origin_location ?? '-' }} → {{ $trip->destination_location ?? '-' }}<br>
                @endif
                <br>
                <strong>Vehicle:</strong><br>
                {{ $costs->first()->vehicle->name ?? 'N/A' }} ({{ $costs->first()->vehicle->registration_number ?? 'N/A' }})
            </div>

            <div class="invoice-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Receipt No:</strong></td>
                        <td>{{ $costs->first()->receipt_number ?? 'N/A' }}</td>
                        <td><strong>Date:</strong></td>
                        <td>{{ $costs->first()->date_incurred->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td colspan="3">
                            @php
                                $status = $costs->first()->approval_status;
                                echo match($status) {
                                    'pending' => 'PENDING',
                                    'approved' => 'APPROVED',
                                    'rejected' => 'REJECTED',
                                    default => strtoupper($status ?? 'N/A')
                                };
                            @endphp
                        </td>
                    </tr>
                    @if($paidFromGlTransaction && $paidFromGlTransaction->chartAccount)
                    <tr>
                        <td><strong>Paid From:</strong></td>
                        <td colspan="3">{{ $paidFromGlTransaction->chartAccount->account_name }}</td>
                    </tr>
                    @endif
                    @if($company && ($company->tin ?? null))
                    <tr>
                        <td><strong>TIN:</strong></td>
                        <td colspan="3">{{ $company->tin }}</td>
                    </tr>
                    @endif
                    @if($branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $branch->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td colspan="3">{{ $costs->first()->created_at->format('H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        {{-- Items table (same as sales invoice items-table) --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>GL Account</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($costs as $index => $cost)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        @if($cost->glAccount)
                        {{ $cost->glAccount->account_code }} - {{ $cost->glAccount->account_name }}
                        @else
                        N/A
                        @endif
                    </td>
                    <td>{{ $cost->description ?? 'Trip Cost' }}</td>
                    <td>{{ $cost->costCategory ? $cost->costCategory->name : 'N/A' }}</td>
                    <td class="text-center">1.00</td>
                    <td class="text-right">{{ number_format($cost->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals (same table structure as sales invoice totals-table) --}}
        <table class="totals-table">
            <tr>
                <td colspan="5" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>{{ number_format($totalAmount, 2) }} TZS</strong></td>
            </tr>
        </table>

        @if($costs->first()->notes)
        <div style="margin-top: 10px; font-size: 10px;">
            <strong>Notes:</strong><br>
            {{ $costs->first()->notes }}
        </div>
        @endif

        @if($costs->first()->is_billable_to_customer)
        <div style="margin-top: 8px; font-size: 10px;">
            <strong>Billing:</strong> This cost is billable to customer.
        </div>
        @endif

        {{-- Footer (same as sales invoice) --}}
        <hr>
        <div class="footer">
            <div style="margin-bottom: 10px;">Thank you for your business!</div>

            <div class="signature">
                <strong>Authorized By:</strong> ________________________________
            </div>

            <div class="text-center" style="font-size: 9px;">
                Receipt No: {{ $costs->first()->receipt_number ?? 'N/A' }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>
</body>
</html>
