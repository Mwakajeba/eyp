<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Payroll Slips - {{ $payroll->month_name }} {{ $payroll->year }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .payroll-slip {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            page-break-after: always;
        }

        .payroll-slip:last-child {
            page-break-after: avoid;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            opacity: 0.3;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .company-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 20px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .company-name {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .company-details {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .payroll-period {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .period-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .period-date {
            font-size: 16px;
        }

        .content {
            padding: 40px;
        }

        .employee-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }

        .employee-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .employee-name {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }

        .employee-id {
            background: #667eea;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .employee-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 500;
        }

        .salary-breakdown {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 50px;
            height: 2px;
            background: #667eea;
        }

        .breakdown-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .breakdown-section {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
        }

        .breakdown-header {
            background: #f8f9fa;
            padding: 15px 20px;
            font-weight: 600;
            color: #495057;
            border-bottom: 1px solid #e9ecef;
        }

        .breakdown-items {
            padding: 0;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            border-bottom: 1px solid #f8f9fa;
            transition: background-color 0.2s;
            page-break-inside: avoid;
        }

        .breakdown-item:hover {
            background: #f8f9fa;
        }

        .breakdown-item:last-child {
            border-bottom: none;
        }

        .item-label {
            color: #6c757d;
            font-size: 14px;
        }

        .item-value {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .earnings .item-value {
            color: #28a745;
        }

        .deductions .item-value {
            color: #dc3545;
        }

        .total-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-top: 30px;
        }

        .total-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .total-item {
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .total-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .total-value {
            font-size: 24px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .net-pay {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .net-pay .total-value {
            font-size: 32px;
        }

        .footer {
            background: #2c3e50;
            color: white;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .print-info {
            opacity: 0.8;
        }

        .bank-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #28a745;
        }

        .bank-title {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .bank-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .payroll-slip {
                box-shadow: none !important;
                margin: 0 !important;
                border-radius: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                page-break-inside: avoid !important;
            }

            .header::before {
                display: none !important;
            }

            .total-section {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                page-break-inside: avoid !important;
            }

            .footer {
                background: #2c3e50 !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                page-break-inside: avoid !important;
            }

            .breakdown-grid {
                display: table !important;
                width: 100% !important;
            }

            .breakdown-section {
                display: table-cell !important;
                width: 50% !important;
                vertical-align: top !important;
                padding-right: 15px !important;
            }

            .breakdown-section:last-child {
                padding-right: 0 !important;
                padding-left: 15px !important;
            }

            .employee-details {
                display: table !important;
                width: 100% !important;
            }

            .detail-item {
                display: table-cell !important;
                width: 25% !important;
                vertical-align: top !important;
                padding-right: 10px !important;
            }

            .total-grid {
                display: table !important;
                width: 100% !important;
            }

            .total-item {
                display: table-cell !important;
                width: 33.33% !important;
                vertical-align: top !important;
                text-align: center !important;
            }

            .bank-info {
                display: table !important;
                width: 100% !important;
            }

            .bank-info .detail-item {
                display: table-cell !important;
                width: 33.33% !important;
                vertical-align: top !important;
            }

            .employee-header {
                display: table !important;
                width: 100% !important;
            }

            .employee-name {
                display: table-cell !important;
                width: 70% !important;
                vertical-align: middle !important;
            }

            .employee-id {
                display: table-cell !important;
                width: 30% !important;
                vertical-align: middle !important;
                text-align: right !important;
            }

            .breakdown-item {
                display: table !important;
                width: 100% !important;
                page-break-inside: avoid !important;
            }

            .item-label {
                display: table-cell !important;
                width: 70% !important;
                vertical-align: middle !important;
                padding-right: 10px !important;
            }

            .item-value {
                display: table-cell !important;
                width: 30% !important;
                vertical-align: middle !important;
                text-align: right !important;
            }

            .content {
                padding: 30px !important;
            }

            .employee-section {
                margin-bottom: 25px !important;
            }

            .salary-breakdown {
                margin-bottom: 25px !important;
            }
        }

        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }

            .breakdown-grid {
                grid-template-columns: 1fr;
            }

            .employee-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .total-grid {
                grid-template-columns: 1fr;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    @foreach($payrollEmployees as $employee)
        <div class="payroll-slip">
            <!-- Header Section -->
            <div class="header">
                <div class="header-content">
                    <div class="company-logo">
                        @if($company->logo)
                            <img src="{{ asset('storage/' . $company->logo) }}" alt="Company Logo"
                                style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        @else
                            {{ strtoupper(substr($company->name, 0, 2)) }}
                        @endif
                    </div>
                    <div class="company-name">{{ $company->name }}</div>
                    <div class="company-details">
                        @if($company->address)
                            <div>{{ $company->address }}</div>
                        @endif
                        @if($company->phone)
                            <div>Tel: {{ $company->phone }}</div>
                        @endif
                        @if($company->email)
                            <div>Email: {{ $company->email }}</div>
                        @endif
                    </div>
                    <div class="payroll-period">
                        <div class="period-title">PAYROLL PERIOD</div>
                        <div class="period-date">{{ $payroll->month_name }} {{ $payroll->year }}</div>
                    </div>
                </div>
            </div>

            <!-- Content Section -->
            <div class="content">
                <!-- Employee Details -->
                <div class="employee-section">
                    <div class="employee-header">
                        <div class="employee-name">{{ $employee->employee->full_name }}</div>
                        <div class="employee-id">ID: {{ $employee->employee->employee_id ?? 'N/A' }}</div>
                    </div>
                    <div class="employee-details">
                        <div class="detail-item">
                            <div class="detail-label">Department</div>
                            <div class="detail-value">{{ $employee->employee->department->name ?? 'N/A' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Position</div>
                            <div class="detail-value">{{ $employee->employee->position->title ?? 'N/A' }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Employee Type</div>
                            <div class="detail-value">{{ ucfirst($employee->employee->employment_type ?? 'N/A') }}
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Hire Date</div>
                            <div class="detail-value">
                                {{ $employee->employee->hire_date ? $employee->employee->hire_date->format('M d, Y') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Salary Breakdown -->
                <div class="salary-breakdown">
                    <div class="section-title">Salary Breakdown</div>
                    <div class="breakdown-grid">
                        <!-- Earnings -->
                        <div class="breakdown-section earnings">
                            <div class="breakdown-header">Earnings</div>
                            <div class="breakdown-items">
                                <div class="breakdown-item">
                                    <span class="item-label">Basic Salary</span>
                                    <span class="item-value">{{ number_format($employee->basic_salary, 2) }}</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="item-label">Allowances</span>
                                    <span class="item-value">{{ number_format($employee->allowance, 2) }}</span>
                                </div>
                                <div class="breakdown-item" style="background: #e8f5e8; font-weight: 600;">
                                    <span class="item-label">Total Earnings</span>
                                    <span class="item-value">{{ number_format($employee->gross_salary, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Deductions -->
                        <div class="breakdown-section deductions">
                            <div class="breakdown-header">Deductions</div>
                            <div class="breakdown-items">
                                <div class="breakdown-item">
                                    <span class="item-label">PAYE</span>
                                    <span class="item-value">{{ number_format($employee->paye, 2) }}</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="item-label">Pension (Employee)</span>
                                    <span class="item-value">{{ number_format($employee->pension, 2) }}</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="item-label">NHIF</span>
                                    <span class="item-value">{{ number_format($employee->insurance, 2) }}</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="item-label">WCF</span>
                                    <span class="item-value">{{ number_format($employee->wcf, 2) }}</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="item-label">SDL</span>
                                    <span class="item-value">{{ number_format($employee->sdl, 2) }}</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="item-label">HESLB</span>
                                    <span class="item-value">{{ number_format($employee->heslb, 2) }}</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="item-label">Trade Union</span>
                                    <span class="item-value">{{ number_format($employee->trade_union, 2) }}</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="item-label">Salary Advance</span>
                                    <span class="item-value">{{ number_format($employee->salary_advance, 2) }}</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="item-label">External Loans</span>
                                    <span class="item-value">{{ number_format($employee->loans, 2) }}</span>
                                </div>
                                <div class="breakdown-item">
                                    <span class="item-label">Other Deductions</span>
                                    <span class="item-value">{{ number_format($employee->other_deductions, 2) }}</span>
                                </div>
                                <div class="breakdown-item" style="background: #ffeaea; font-weight: 600;">
                                    <span class="item-label">Total Deductions</span>
                                    <span class="item-value">{{ number_format($employee->total_deductions, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bank Details -->
                @if($employee->employee->bank_account_number)
                    <div class="bank-details">
                        <div class="bank-title">Bank Transfer Details</div>
                        <div class="bank-info">
                            <div class="detail-item">
                                <div class="detail-label">Bank Name</div>
                                <div class="detail-value">{{ $employee->employee->bank_name ?? 'N/A' }}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Account Number</div>
                                <div class="detail-value">{{ $employee->employee->bank_account_number ?? 'N/A' }}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Account Name</div>
                                <div class="detail-value">
                                    {{ $employee->employee->bank_account_name ?? $employee->employee->full_name }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Total Section -->
                <div class="total-section">
                    <div class="total-grid">
                        <div class="total-item">
                            <div class="total-label">Gross Salary</div>
                            <div class="total-value">{{ number_format($employee->gross_salary, 2) }}</div>
                        </div>
                        <div class="total-item">
                            <div class="total-label">Total Deductions</div>
                            <div class="total-value">{{ number_format($employee->total_deductions, 2) }}</div>
                        </div>
                        <div class="total-item net-pay">
                            <div class="total-label">Net Pay</div>
                            <div class="total-value">{{ number_format($employee->net_salary, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <div class="footer-content">
                    <div class="print-info">
                        Generated on {{ now()->format('M d, Y \a\t h:i A') }}
                    </div>
                    <div class="print-info">
                        Payroll ID: {{ $payroll->id }} | Employee ID: {{ $employee->employee->id }}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>