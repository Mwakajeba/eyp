<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Imprest Request - {{ $imprestRequest->request_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 13px;
            line-height: 1.6;
            color: #2d3748;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .document-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            max-width: 210mm;
            margin: 0 auto;
            position: relative;
        }
        
        .document-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        }
        
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 40px 40px 30px;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" patternUnits="userSpaceOnUse" width="100" height="100"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .company-info {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        
        .company-name {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .company-details {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 25px;
        }
        
        .document-title {
            font-size: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 15px 30px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            display: inline-block;
            backdrop-filter: blur(10px);
        }
        
        .content-wrapper {
            padding: 40px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 35px;
        }
        
        .info-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e2e8f0;
            position: relative;
        }
        
        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 12px 12px 0 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-weight: 600;
            color: #1e293b;
            text-align: right;
        }
        
        .amount-highlight {
            font-size: 18px;
            color: #059669;
            font-weight: 700;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin: 40px 0 25px;
            position: relative;
        }
        
        .section-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            padding: 0 20px 0 0;
            background: white;
            position: relative;
            z-index: 2;
        }
        
        .section-header::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            height: 2px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            z-index: 1;
        }
        
        .purpose-card {
            background: linear-gradient(135deg, #fef7cd 0%, #fef3c7 100%);
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
        }
        
        .purpose-card::before {
            content: '📝';
            position: absolute;
            top: -15px;
            left: 25px;
            background: #f59e0b;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .purpose-text {
            font-size: 14px;
            line-height: 1.7;
            color: #92400e;
            font-weight: 500;
        }
        
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .modern-table thead {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        }
        
        .modern-table th {
            padding: 18px 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .purpose-text {
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #f9f9f9;
            min-height: 20px;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #f59e0b;
        }
        
        .status-pending::before {
            background: #f59e0b;
        }
        
        .status-approved {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border: 1px solid #22c55e;
        }
        
        .status-approved::before {
            background: #22c55e;
        }
        
        .status-rejected {
            background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        .status-rejected::before {
            background: #ef4444;
        }
        
        .status-disbursed {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #3b82f6;
        }
        
        .status-disbursed::before {
            background: #3b82f6;
        }
        
        .status-checked {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #3730a3;
            border: 1px solid #6366f1;
        }
        
        .status-checked::before {
            background: #6366f1;
        }
        
        .status-liquidated, .status-closed {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #374151;
            border: 1px solid #6b7280;
        }
        
        .status-liquidated::before, .status-closed::before {
            background: #6b7280;
        }
        
        .signatures-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }
        
        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            gap: 20px;
        }
        
        .signature-item {
            flex: 1;
            text-align: center;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 5px;
            min-height: 50px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        
        .signature-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        
        .signature-name {
            font-size: 11px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .footer {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 25px 40px;
            text-align: center;
            font-size: 11px;
            opacity: 0.9;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .generated-info {
            display: flex;
            align-items: center;
        }
        
        .generated-info::before {
            content: '🤖';
            margin-right: 8px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }
            
            .document-container {
                box-shadow: none;
                border-radius: 0;
                max-width: none;
            }
            
            .signatures-section {
                page-break-inside: avoid;
                margin-top: 30px;
            }
            
            .signature-row {
                margin-top: 15px;
            }
            
            .header::before {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="document-container">
        <div class="header">
            <div class="company-info">
                <div class="company-name">{{ $imprestRequest->company->name ?? config('app.name', 'Smart Accounting') }}</div>
                <div class="company-details">
                    <div>{{ $imprestRequest->branch->name ?? 'Main Branch' }}</div>
                    @if($imprestRequest->company->address)
                    <div>{{ $imprestRequest->company->address }}</div>
                    @endif
                    <div>
                        @if($imprestRequest->company->phone)
                            Tel: {{ $imprestRequest->company->phone }}
                        @endif
                        @if($imprestRequest->company->email)
                            @if($imprestRequest->company->phone) | @endif
                            Email: {{ $imprestRequest->company->email }}
                        @endif
                    </div>
                </div>
                <div class="document-title">Imprest Request Form</div>
            </div>
        </div>

        <div class="content-wrapper">

            <div class="info-grid">
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Request Number</span>
                        <span class="info-value">{{ $imprestRequest->request_number }}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Employee</span>
                        <span class="info-value">{{ $imprestRequest->employee->name ?? 'N/A' }}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Department</span>
                        <span class="info-value">{{ $imprestRequest->department->name ?? 'N/A' }}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="status-badge status-{{ str_replace([' ', '-'], ['', ''], strtolower($imprestRequest->getStatusLabel())) }}">
                                {{ $imprestRequest->getStatusLabel() }}
                            </span>
                        </span>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Amount Requested</span>
                        <span class="info-value amount-highlight">TZS {{ number_format($imprestRequest->amount_requested, 2) }}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Date Required</span>
                        <span class="info-value">{{ $imprestRequest->date_required ? $imprestRequest->date_required->format('M j, Y') : 'N/A' }}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Date Created</span>
                        <span class="info-value">{{ $imprestRequest->created_at->format('M j, Y') }}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Created By</span>
                        <span class="info-value">{{ $imprestRequest->creator->name ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <div class="purpose-card">
                <div class="purpose-text">
                    <strong>Purpose:</strong> {{ $imprestRequest->purpose }}
                    @if($imprestRequest->description)
                        <br><br><strong>Description:</strong> {{ $imprestRequest->description }}
                    @endif
                </div>
            </div>

            <div class="section-header">
                <h3>💰 Imprest Items Breakdown</h3>
            </div>
            <table class="modern-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="25%">Chart Account</th>
                <th width="50%">Description</th>
                <th width="20%">Amount (TZS)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($imprestRequest->imprestItems as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    {{ $item->chartAccount->account_code ?? 'N/A' }}<br>
                    <small>{{ $item->chartAccount->account_name ?? 'N/A' }}</small>
                </td>
                <td>{{ $item->notes }}</td>
                <td class="amount-cell">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;"><strong>Total Amount:</strong></td>
                <td class="amount-cell"><strong>{{ number_format($imprestRequest->amount_requested, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    @if($imprestRequest->disbursed_amount)
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Amount Disbursed:</span>
            <span class="info-value"><strong>TZS {{ number_format($imprestRequest->disbursed_amount, 2) }}</strong></span>
        </div>
        
        @if($imprestRequest->disbursed_at)
        <div class="info-row">
            <span class="info-label">Disbursed Date:</span>
            <span class="info-value">{{ $imprestRequest->disbursed_at->format('F j, Y') }}</span>
        </div>
        @endif
    </div>
    @endif


    <!-- Approval Levels Table -->
    @php
        $approvals = [];
        $levelNumber = 1;
        
        // Level 1: Manager Check
        if ($imprestRequest->checked_by && $imprestRequest->checker) {
            $approvals[] = [
                'level' => 'Level ' . $levelNumber,
                'approver' => $imprestRequest->checker->name,
                'date' => $imprestRequest->checked_at->format('d/m/Y')
            ];
            $levelNumber++;
        }
        
        // Level 2: Finance Approval
        if ($imprestRequest->approved_by && $imprestRequest->approver) {
            $approvals[] = [
                'level' => 'Level ' . $levelNumber,
                'approver' => $imprestRequest->approver->name,
                'date' => $imprestRequest->approved_at->format('d/m/Y')
            ];
        }
    @endphp

    @if(count($approvals) > 0)
    <div style="margin-top: 30px; margin-bottom: 20px;">
        <h3 style="font-size: 14px; font-weight: bold; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 8px;">Approved By</h3>
        <table class="items-table" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th width="20%">Level</th>
                    <th width="50%">Approver Name</th>
                    <th width="30%">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($approvals as $approval)
                <tr>
                    <td><strong>{{ $approval['level'] }}</strong></td>
                    <td>{{ $approval['approver'] }}</td>
                    <td style="text-align: center;">{{ $approval['date'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Auto-print script -->
    <script nonce="{{ $cspNonce ?? '' }}">
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>