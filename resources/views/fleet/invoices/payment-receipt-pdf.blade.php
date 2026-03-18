<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt - {{ $invoice->invoice_number }}</title>
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

        /* Receipt title */
        .receipt-title {
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

        /* Payment details table */
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }

        .payment-table th,
        .payment-table td {
            border: 1px solid #cbd5e1;
            padding: 5px;
        }

        .payment-table th {
            text-align: center;
            font-weight: bold;
            background-color: #1e3a8a;
            color: #fff;
        }

        .payment-table tbody tr {
            background-color: #dbeafe;
        }

        /* Amount box */
        .amount-box {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }

        .amount-box td {
            padding: 4px 5px;
            border: none;
        }

        .amount-box td:last-child {
            text-align: right;
            padding-right: 5px;
        }

        .amount-box tr:last-child td {
            background-color: #1e3a8a;
            color: #fff;
            font-weight: bold;
            padding: 8px 5px;
        }

        .amount-box tr:last-child td:last-child {
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

        {{-- Header --}}
        <div class="text-left">
            @if($invoice->company && $invoice->company->logo)
            @php
            $logo = $invoice->company->logo;
            $logoPath = public_path('storage/' . ltrim($logo, '/'));
            $logoBase64 = null;
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                $imageInfo = getimagesize($logoPath);
                if ($imageInfo !== false) {
                    $mimeType = $imageInfo['mime'];
                    $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                }
            }
            @endphp
            @if($logoBase64)
            <div class="logo-section" style="float: left; width: 45%;">
                <img src="{{ $logoBase64 }}" alt="{{ $invoice->company->name . ' logo' }}" class="company-logo">
            </div>
            @endif
            @endif
            <div style="float: right; width: 50%; text-align: left; margin-left: 15%;">
                <div class="company-name">{{ $invoice->company->name }}</div>
                <div class="company-details">
                    Address: {{ $invoice->company->address }} <br>
                    Phone: {{ $invoice->company->phone }} <br>
                    Email: {{ $invoice->company->email }}
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        @if(isset($bankAccounts) && $bankAccounts && $bankAccounts->count() > 0)
        <div class="payment-method-bar" style="text-align: center;">
            <strong>PAYMENT METHOD :</strong>
        </div>
        <div class="payment-details">
            @foreach($bankAccounts as $account)
            <strong>{{ strtoupper($account->name ?? $account->bank_name ?? 'BANK') }}:</strong> {{ $account->account_number ?? 'N/A' }} &nbsp;&nbsp;
            @endforeach
        </div>
        @endif

        <div class="receipt-title">PAYMENT RECEIPT</div>
        <hr>

        {{-- Receipt Info + Vehicle/Driver --}}
        <div class="info-section">
            <div class="bill-to" style="float: left; width: 48%;">
                @php
                    $displayVehicle = $invoice->vehicle;
                    $displayDriver = $invoice->driver;
                    $displayCustomer = $invoice->customer;
                    if (!$displayVehicle || !$displayDriver || !$displayCustomer) {
                        $firstItem = $invoice->items->first();
                        if ($firstItem && $firstItem->trip) {
                            $displayVehicle = $displayVehicle ?? $firstItem->trip->vehicle;
                            $displayDriver = $displayDriver ?? $firstItem->trip->driver;
                            $displayCustomer = $displayCustomer ?? $firstItem->trip->customer;
                        }
                    }
                    $customerName = $displayCustomer ? ($displayCustomer->name ?? $displayCustomer->company_name ?? 'N/A') : 'N/A';
                @endphp
                <strong>Customer (Billed To):</strong><br>
                <strong>{{ $customerName }}</strong><br>
                <br>
                <strong>Vehicle:</strong><br>
                {{ $displayVehicle->name ?? 'N/A' }} ({{ $displayVehicle->registration_number ?? 'N/A' }})<br>
                <br>
                <strong>Driver:</strong><br>
                {{ $displayDriver->full_name ?? $displayDriver->name ?? 'N/A' }}<br>
                @if($displayDriver && $displayDriver->phone)
                {{ $displayDriver->phone }}<br>
                @endif
                <br>
                <strong>Payment Method:</strong><br>
                {{ $payment->bankAccount ? $payment->bankAccount->name : 'Cash' }}
            </div>

            <div class="invoice-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Receipt No:</strong></td>
                        <td>{{ 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</td>
                        <td><strong>Date:</strong></td>
                        <td>{{ $payment->payment_date ? $payment->payment_date->format('d F Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Invoice No:</strong></td>
                        <td colspan="3">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>Invoice Date:</strong></td>
                        <td colspan="3">{{ $invoice->invoice_date->format('d F Y') }}</td>
                    </tr>
                    @if($payment->reference_number)
                    <tr>
                        <td><strong>Reference:</strong></td>
                        <td colspan="3">{{ $payment->reference_number }}</td>
                    </tr>
                    @endif
                    @if($invoice->route)
                    <tr>
                        <td><strong>Route:</strong></td>
                        <td colspan="3">{{ $invoice->route->origin_location ?? '' }} &rarr; {{ $invoice->route->destination_location ?? '' }}</td>
                    </tr>
                    @endif
                    @if($invoice->branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $invoice->branch->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        @if($payment->notes)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Payment Notes:</strong><br>
            {{ $payment->notes }}
        </div>
        @endif

        {{-- Payment Details --}}
        <table class="payment-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Payment received for Invoice {{ $invoice->invoice_number }}</td>
                    <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Amount Summary --}}
        <table class="amount-box">
            <tr>
                <td colspan="1" style="text-align: right;"><strong>AMOUNT RECEIVED: </strong></td>
                <td><strong>{{ number_format($payment->amount, 2) }}</strong></td>
            </tr>
        </table>

        <div style="margin-top:5px;font-style:italic;">
            <strong>Amount in Words: {{ ucwords(\App\Helpers\AmountInWords::convert($payment->amount)) }} Only</strong>
        </div>

        {{-- Invoice Summary --}}
        <div style="margin-top:15px; padding: 10px; background-color: #f8fafc; border: 1px solid #cbd5e1;">
            <strong style="color: #1e40af;">Invoice Payment Summary:</strong><br>
            <table style="width: 100%; margin-top: 5px; font-size: 10px;">
                <tr>
                    <td>Invoice Total:</td>
                    <td class="text-right">{{ number_format($invoice->total_amount, 2) }} TZS</td>
                </tr>
                <tr>
                    <td>Total Paid:</td>
                    <td class="text-right">{{ number_format($invoice->paid_amount, 2) }} TZS</td>
                </tr>
                <tr>
                    <td><strong>Balance Due:</strong></td>
                    <td class="text-right"><strong>{{ number_format($invoice->balance_due, 2) }} TZS</strong></td>
                </tr>
            </table>
        </div>

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div style="margin-bottom: 10px;">Thank you for your payment!</div>

            <div class="signature">
                <strong>Received By:</strong> ________________________________ &nbsp;&nbsp;&nbsp;&nbsp;
                <strong>Date:</strong> ________________
            </div>

            <div style="margin-top: 15px; font-size: 9px;">
                <strong>Note:</strong> This is an official payment receipt. Please keep it for your records.
            </div>

            <div class="text-center" style="font-size:9px; margin-top: 10px;">
                Receipt No: {{ 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }} | Invoice No: {{ $invoice->invoice_number }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
