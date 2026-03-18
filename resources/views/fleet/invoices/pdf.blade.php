<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fleet Invoice - {{ $invoice->invoice_number }}</title>
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

        /* Payment History */
        .payment-history {
            margin-top: 15px;
            font-size: 10px;
        }

        .payment-history-title {
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .payment-history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 5px;
        }

        .payment-history-table th {
            background-color: #1e3a8a;
            color: #fff;
            padding: 5px;
            text-align: left;
            border: 1px solid #cbd5e1;
            font-weight: bold;
        }

        .payment-history-table td {
            padding: 5px;
            border: 1px solid #cbd5e1;
        }

        .payment-history-table tbody tr:nth-child(even) {
            background-color: #dbeafe;
        }

        .payment-history-table tbody tr:nth-child(odd) {
            background-color: #fff;
        }

        .payment-history-table .text-right {
            text-align: right;
        }

        .payment-history-table .text-center {
            text-align: center;
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
            // Logo is stored in storage/app/public (via "public" disk)
            $logo = $invoice->company->logo; // e.g. "uploads/companies/company_1_1768466462.png"
            $logoPath = public_path('storage/' . ltrim($logo, '/'));

            // Convert image to base64 for DomPDF compatibility
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

        <div class="invoice-title">FLEET INVOICE</div>
        <hr>
        {{-- Invoice Info + Vehicle/Driver/Trip --}}
        <div class="info-section">
            <div class="bill-to" style="float: left; width: 48%;">
                @php
                    $displayVehicle = $invoice->vehicle;
                    $displayDriver = $invoice->driver;
                    $displayTrip = $invoice->trip;
                    $displayCustomer = $invoice->customer;
                    if (!$displayVehicle || !$displayDriver || !$displayTrip || !$displayCustomer) {
                        $firstItem = $invoice->items->first();
                        if ($firstItem && $firstItem->trip) {
                            $displayVehicle = $displayVehicle ?? $firstItem->trip->vehicle;
                            $displayDriver = $displayDriver ?? $firstItem->trip->driver;
                            $displayTrip = $displayTrip ?? $firstItem->trip;
                            $displayCustomer = $displayCustomer ?? $firstItem->trip->customer;
                        }
                    }
                    $customerName = $displayCustomer ? ($displayCustomer->name ?? $displayCustomer->company_name ?? 'N/A') : 'N/A';
                @endphp
                <strong>Customer (Billed To):</strong><br>
                <strong>{{ $customerName }}</strong><br>
                <br>
                <strong>Vehicle:</strong><br>
                <strong>{{ $displayVehicle->name ?? 'N/A' }} ({{ $displayVehicle->registration_number ?? 'N/A' }})</strong><br>
                <br>
                <strong>Driver:</strong><br>
                {{ $displayDriver->full_name ?? $displayDriver->name ?? 'N/A' }}<br>
                @if($displayDriver && $displayDriver->phone)
                {{ $displayDriver->phone }}<br>
                @endif
                <br>
                <strong>Created By:</strong><br>
                @php
                $creator = $invoice->createdBy ?? null;
                $creatorRole = $creator && method_exists($creator, 'roles') ? $creator->roles->first() : null;
                @endphp
                @if($creator)
                {{ $creator->name }}
                @if($creatorRole)
                ({{ $creatorRole->name }})
                @endif
                @else
                System
                @endif
            </div>

            <div class="invoice-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Invoice no:</strong></td>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $invoice->invoice_date->format('d F Y') }}</td>
                    </tr>
                    @if($invoice->due_date)
                    <tr>
                        <td><strong>Due Date:</strong></td>
                        <td colspan="3">{{ $invoice->due_date->format('d F Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Currency:</strong></td>
                        <td colspan="3">TZS</td>
                    </tr>
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
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td colspan="3">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        @if($invoice->notes)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Description:</strong><br>
            {{ $invoice->notes }}
        </div>
        @endif

        {{-- Items --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th>Trip</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Unit Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                @php
                    $tripDisplay = 'N/A';
                    if ($item->trip) {
                        $tripDate = $item->trip->actual_start_date ?? $item->trip->planned_start_date ?? $item->trip->created_at;
                        $tripDateFormatted = $tripDate ? $tripDate->format('d/m/Y') : 'N/A';
                        $tripDisplay = ($item->trip->trip_number ?? 'N/A') . ' (' . $tripDateFormatted . ')';
                    }
                @endphp
                <tr>
                    <td>{{ $tripDisplay }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-center">{{ $item->unit ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->unit_rate, 2) }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <table class="totals-table">
            <tr>
                <td colspan="5" style="text-align: right;">Sub Total: </td>
                <td>{{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if($invoice->tax_amount > 0)
            <tr>
                <td colspan="5" style="text-align: right;">Tax {{ number_format($invoice->tax_rate ?? 0, 1) }}%: </td>
                <td>{{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
            @endif
            @if($invoice->discount_amount > 0)
            <tr>
                <td colspan="5" style="text-align: right;">Total Discount:</td>
                <td>{{ number_format($invoice->discount_amount ?? 0, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="5" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>{{ number_format($invoice->total_amount, 2) }}</strong></td>
            </tr>
        </table>

        @if(method_exists($invoice, 'getAmountInWords'))
        <div style="margin-top:5px;font-style:italic;">
            <strong>{{ ucwords($invoice->getAmountInWords()) }}</strong>
        </div>
        @endif

        {{-- Outstanding --}}
        <div style="margin-top:10px;">
            <strong>Payment Status:</strong><br>
            Paid Amount: {{ number_format($invoice->paid_amount, 2) }} TZS<br>
            <strong>Balance Due: {{ number_format($invoice->balance_due, 2) }} TZS</strong>
        </div>

        {{-- Payment History --}}
        @php
        $payments = $invoice->payments ?? collect();
        @endphp
        @if($payments->count() > 0)
        <div class="payment-history">
            <div class="payment-history-title">PAYMENT HISTORY</div>
            <table class="payment-history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference No</th>
                        <th>Amount</th>
                        <th>Bank Account</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : ($payment->created_at ? $payment->created_at->format('d/m/Y') : 'N/A') }}</td>
                        <td>{{ $payment->reference_number ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->bankAccount ? $payment->bankAccount->name : 'N/A' }}</td>
                        <td>{{ $payment->notes ?? '-' }}</td>
                    </tr>
                    @endforeach
                    <tr style="background-color: #e0f2fe; font-weight: bold;">
                        <td colspan="2" style="text-align: right;"><strong>Total Paid:</strong></td>
                        <td class="text-right"><strong>{{ number_format($invoice->paid_amount, 2) }}</strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div style="margin-bottom: 10px;">Thank you for your business!</div>
            <div><strong>Terms and Conditions:</strong><br>Payment due within <b>{{ $invoice->payment_days ?? 30 }} days</b> of receiving this invoice.</div>

            <div class="signature">
                <strong>Authorized Signature:</strong> ________________________________
            </div>

            <ol>
                <li>Payment should be made to the accounts listed above</li>
                <li>Kindly verify amounts and payment terms</li>
                <li>Contact us for any queries regarding this invoice</li>
            </ol>

            <div class="text-center" style="font-size:9px;">
                Invoice No: {{ $invoice->invoice_number }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
