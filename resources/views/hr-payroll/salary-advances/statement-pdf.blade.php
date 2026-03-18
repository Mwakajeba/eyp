<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Statement - {{ $salaryAdvance->reference }}</title>
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

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        hr {
            border: none;
            border-top: 2px solid #3b82f6;
            margin: 8px 0;
        }

        .logo-section { margin-bottom: 10px; }
        .company-logo {
            max-height: 80px;
            max-width: 120px;
            object-fit: contain;
        }
        .company-name { font-size: 18px; font-weight: bold; color: #1e40af; }
        .company-details { font-size: 10px; }

        .payment-method-bar {
            background-color: #1e3a8a;
            color: #fff;
            padding: 8px;
            font-weight: bold;
            margin-top: 10px;
            text-align: center;
        }
        .payment-details {
            padding: 8px;
            background-color: #f8fafc;
            font-size: 10px;
        }
        .payment-details strong { color: #1e40af; }

        .invoice-title {
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
            color: #1e40af;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .bill-to { width: 48%; font-size: 10px; }
        .bill-to strong { color: #1e40af; }
        .invoice-box { width: 48%; text-align: right; }
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
        .invoice-box td:nth-child(even) { text-align: right; }
        .invoice-box strong { color: #1e40af; }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }
        .items-table th, .items-table td {
            border: 1px solid #cbd5e1;
            padding: 5px;
        }
        .items-table th {
            text-align: center;
            font-weight: bold;
            background-color: #1e3a8a;
            color: #fff;
        }
        .items-table tbody tr:nth-child(even) { background-color: #dbeafe; }
        .items-table tbody tr:nth-child(odd) { background-color: #fff; }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }
        .totals-table td { padding: 4px 5px; border: none; }
        .totals-table td:last-child { text-align: right; padding-right: 5px; }
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

        .receipt-history { margin-top: 15px; font-size: 10px; }
        .receipt-history-title {
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
            font-size: 11px;
        }
        .receipt-history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 5px;
        }
        .receipt-history-table th {
            background-color: #1e3a8a;
            color: #fff;
            padding: 5px;
            text-align: left;
            border: 1px solid #cbd5e1;
            font-weight: bold;
        }
        .receipt-history-table td { padding: 5px; border: 1px solid #cbd5e1; }
        .receipt-history-table tbody tr:nth-child(even) { background-color: #dbeafe; }
        .receipt-history-table tbody tr:nth-child(odd) { background-color: #fff; }
        .receipt-history-table .text-right { text-align: right; }
        .receipt-history-table .text-center { text-align: center; }

        .footer { margin-top: 20px; font-size: 10px; }
        .footer strong { color: #1e40af; }
        .footer hr { border-top: 1px solid #dbeafe; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">

        {{-- Header --}}
        <div class="text-left">
            @php
                $company = $salaryAdvance->company;
                $logoBase64 = null;
                if ($company && $company->logo) {
                    $logo = $company->logo;
                    $logoPath = public_path('storage/' . ltrim($logo, '/'));
                    if (file_exists($logoPath)) {
                        $imageData = file_get_contents($logoPath);
                        $imageInfo = @getimagesize($logoPath);
                        if ($imageInfo !== false) {
                            $mimeType = $imageInfo['mime'];
                            $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                        }
                    }
                }
            @endphp
            @if($logoBase64)
            <div class="logo-section" style="float: left; width: 45%;">
                <img src="{{ $logoBase64 }}" alt="{{ $company->name ?? 'Company' }} logo" class="company-logo">
            </div>
            @endif
            <div style="float: right; width: 50%; text-align: left; margin-left: 15%;">
                <div class="company-name">{{ $company->name ?? 'Company' }}</div>
                <div class="company-details">
                    @if($company && $company->address) Address: {{ $company->address }}<br> @endif
                    @if($company && $company->phone) Phone: {{ $company->phone }}<br> @endif
                    @if($company && $company->email) Email: {{ $company->email }} @endif
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        @if($bankAccounts && $bankAccounts->count() > 0)
        <div class="payment-method-bar"><strong>PAYMENT METHOD</strong></div>
        <div class="payment-details">
            @foreach($bankAccounts as $account)
            <strong>{{ strtoupper($account->name ?? 'BANK') }}:</strong> {{ $account->account_number ?? 'N/A' }} &nbsp;&nbsp;
            @endforeach
        </div>
        @endif

        <div class="invoice-title">EMPLOYEE STATEMENT - SALARY ADVANCE</div>
        <hr>

        {{-- Statement to (Employee) + Advance info --}}
        <div class="info-section">
            <div class="bill-to" style="float: left; width: 48%;">
                <strong>Statement for:</strong><br>
                <strong>{{ $salaryAdvance->employee->full_name ?? 'N/A' }}</strong><br>
                @if($salaryAdvance->employee && $salaryAdvance->employee->employee_number)
                Employee No: {{ $salaryAdvance->employee->employee_number }}<br>
                @endif
                @if($salaryAdvance->employee && $salaryAdvance->employee->department)
                Department: {{ $salaryAdvance->employee->department->name ?? 'N/A' }}<br>
                @endif
                @if($salaryAdvance->employee && $salaryAdvance->employee->position)
                Designation: {{ $salaryAdvance->employee->position->name ?? 'N/A' }}<br>
                @endif
                @if($salaryAdvance->employee && $salaryAdvance->employee->email)
                Email: {{ $salaryAdvance->employee->email }}<br>
                @endif
                @if($salaryAdvance->employee && $salaryAdvance->employee->phone)
                Phone: {{ $salaryAdvance->employee->phone }}<br>
                @endif
                <br>
                <strong>Reason for advance:</strong><br>
                {{ $salaryAdvance->reason ?? '-' }}
            </div>

            <div class="invoice-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Reference:</strong></td>
                        <td>{{ $salaryAdvance->reference }}</td>
                        <td><strong>Date:</strong></td>
                        <td>{{ $salaryAdvance->date ? $salaryAdvance->date->format('d F Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $salaryAdvance->branch->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Bank Account:</strong></td>
                        <td colspan="3">{{ $salaryAdvance->bankAccount->name ?? 'N/A' }} @if($salaryAdvance->bankAccount)({{ $salaryAdvance->bankAccount->account_number }})@endif</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td colspan="3">{{ $salaryAdvance->is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        {{-- Advance summary table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount (TZS)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Advance amount</td>
                    <td class="text-right">{{ number_format($salaryAdvance->amount, 2) }}</td>
                </tr>
                <tr>
                    <td>Monthly deduction</td>
                    <td class="text-right">{{ number_format($salaryAdvance->monthly_deduction, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Totals --}}
        <table class="totals-table">
            <tr>
                <td colspan="1" style="text-align: right;">Total advance:</td>
                <td>{{ number_format($salaryAdvance->amount, 2) }}</td>
            </tr>
            <tr>
                <td colspan="1" style="text-align: right;">Total repaid:</td>
                <td>{{ number_format($salaryAdvance->total_deductions ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td colspan="1" style="text-align: right;"><strong>Remaining balance:</strong></td>
                <td><strong>{{ number_format($salaryAdvance->remaining_balance, 2) }}</strong></td>
            </tr>
        </table>

        {{-- Repayment history --}}
        @php
            $repayments = $salaryAdvance->repayments->sortBy('date')->values();
        @endphp
        @if($repayments->count() > 0)
        <div class="receipt-history">
            <div class="receipt-history-title">REPAYMENT HISTORY</div>
            <table class="receipt-history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th class="text-right">Amount (TZS)</th>
                        <th>Type</th>
                        <th>Bank / Payment</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($repayments as $rep)
                    <tr>
                        <td>{{ $rep->date ? $rep->date->format('d/m/Y') : ($rep->created_at ? $rep->created_at->format('d/m/Y') : 'N/A') }}</td>
                        <td>{{ $rep->reference ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($rep->amount, 2) }}</td>
                        <td>{{ $rep->type === 'manual' ? 'Manual' : 'Payroll' }}</td>
                        <td>{{ $rep->bankAccount ? $rep->bankAccount->name : 'N/A' }}</td>
                        <td>{{ $rep->notes ?? '-' }}</td>
                    </tr>
                    @endforeach
                    <tr style="background-color: #e0f2fe; font-weight: bold;">
                        <td colspan="2" class="text-right"><strong>Total repaid:</strong></td>
                        <td class="text-right"><strong>{{ number_format($salaryAdvance->total_deductions ?? 0, 2) }}</strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        @else
        <div class="receipt-history">
            <div class="receipt-history-title">REPAYMENT HISTORY</div>
            <p style="margin: 5px 0;">No repayments recorded yet.</p>
        </div>
        @endif

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div style="margin-bottom: 10px;">This is a statement of your salary advance and repayments. Please retain for your records.</div>
            <div class="text-center" style="font-size: 9px;">
                Reference: {{ $salaryAdvance->reference }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y H:i') }} &nbsp;|&nbsp; Page 1 of 1
            </div>
        </div>
    </div>
</body>
</html>
