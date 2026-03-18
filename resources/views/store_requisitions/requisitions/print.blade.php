<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Requisition - {{ $storeRequisition->requisition_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #2c3e50;
            margin-bottom: 30px;
            padding-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .logo-section {
            flex: 1;
            text-align: left;
        }

        .logo-section img {
            max-width: 100px;
            max-height: 100px;
            margin-bottom: 10px;
        }

        .company-info {
            flex: 1;
            text-align: right;
        }

        .company-info h2 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .company-info p {
            font-size: 12px;
            color: #555;
            margin: 2px 0;
        }

        /* Requisition Info */
        .requisition-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }

        .info-group {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: bold;
            color: #2c3e50;
            font-size: 13px;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 13px;
            color: #333;
            padding: 5px;
            border-bottom: 1px solid #ddd;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }

        .items-table thead {
            background: #2c3e50;
            color: white;
        }

        .items-table th {
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
            border: 1px solid #2c3e50;
        }

        .items-table td {
            padding: 10px 12px;
            font-size: 12px;
            border: 1px solid #ddd;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .items-table tbody tr:hover {
            background: #f0f0f0;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Summary */
        .summary {
            margin-top: 30px;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #2c3e50;
            border-radius: 3px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
            border-bottom: 1px dotted #ddd;
        }

        .summary-row.total {
            font-weight: bold;
            border-bottom: 2px solid #2c3e50;
            margin-top: 10px;
            padding: 10px 0;
            font-size: 14px;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            text-align: center;
            font-size: 12px;
        }

        .signature-line {
            height: 60px;
            border-top: 1px solid #333;
            margin-top: 30px;
        }

        .signature-label {
            margin-top: 5px;
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 11px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-issued {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .notes-section {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #2c3e50;
            border-radius: 3px;
        }

        .notes-section h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .notes-section p {
            font-size: 12px;
            line-height: 1.6;
        }

        @media print {
            body {
                padding: 0;
            }
            
            .container {
                margin: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                @if($branch && $branch->logo)
                    <img src="{{ public_path('storage/' . $branch->logo) }}" alt="Logo">
                @endif
            </div>
            <div class="company-info">
                <h2>{{ $branch->name ?? 'Branch' }}</h2>
                <p>{{ $branch->address ?? '' }}</p>
                <p>{{ $branch->phone ?? '' }}</p>
                <p>{{ $branch->email ?? '' }}</p>
            </div>
        </div>

        <!-- Title -->
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #2c3e50; margin-bottom: 10px;">STORE REQUISITION</h1>
            <p style="font-size: 12px; color: #555;">Requisition #: <strong>{{ $storeRequisition->requisition_number }}</strong></p>
        </div>

        <!-- Requisition Information -->
        <div class="requisition-info">
            <div class="info-group">
                <span class="info-label">Requisition Number:</span>
                <span class="info-value">{{ $storeRequisition->requisition_number }}</span>
            </div>
            <div class="info-group">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="status-badge status-{{ strtolower($storeRequisition->status) }}">
                        {{ ucfirst($storeRequisition->status) }}
                    </span>
                </span>
            </div>
            <div class="info-group">
                <span class="info-label">Requested Date:</span>
                <span class="info-value">{{ $storeRequisition->created_at->format('d-M-Y') }}</span>
            </div>
            <div class="info-group">
                <span class="info-label">Required Date:</span>
                <span class="info-value">{{ $storeRequisition->required_date ? $storeRequisition->required_date->format('d-M-Y') : 'N/A' }}</span>
            </div>
            <div class="info-group">
                <span class="info-label">Requested By:</span>
                <span class="info-value">{{ $storeRequisition->requestedBy->name ?? 'N/A' }}</span>
            </div>
            <div class="info-group">
                <span class="info-label">Department:</span>
                <span class="info-value">{{ $storeRequisition->department->name ?? 'N/A' }}</span>
            </div>
            <div class="info-group" style="grid-column: 1 / -1;">
                <span class="info-label">Purpose:</span>
                <span class="info-value">{{ $storeRequisition->purpose ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th class="text-right">Requested Qty</th>
                    <th class="text-right">Approved Qty</th>
                    <th class="text-right">Issued Qty</th>
                    <th>Unit</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($storeRequisition->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td>{{ $item->product->category->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($item->quantity_requested, 2) }}</td>
                    <td class="text-right">{{ number_format($item->quantity_approved, 2) }}</td>
                    <td class="text-right">{{ number_format($item->quantity_issued, 2) }}</td>
                    <td>{{ $item->product->unit ?? 'N/A' }}</td>
                    <td>{{ $item->item_notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-row">
                <span>Total Items:</span>
                <span><strong>{{ $storeRequisition->items->count() }}</strong></span>
            </div>
            <div class="summary-row">
                <span>Total Requested Quantity:</span>
                <span><strong>{{ number_format($storeRequisition->items->sum('quantity_requested'), 2) }}</strong></span>
            </div>
            <div class="summary-row">
                <span>Total Approved Quantity:</span>
                <span><strong>{{ number_format($storeRequisition->items->sum('quantity_approved'), 2) }}</strong></span>
            </div>
            <div class="summary-row">
                <span>Total Issued Quantity:</span>
                <span><strong>{{ number_format($storeRequisition->items->sum('quantity_issued'), 2) }}</strong></span>
            </div>
        </div>

        <!-- Notes -->
        @if($storeRequisition->notes)
        <div class="notes-section">
            <h4>Notes:</h4>
            <p>{{ $storeRequisition->notes }}</p>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">

        <!-- Print Date -->
        <div style="text-align: center; margin-top: 30px; font-size: 11px; color: #999;">
            <p>Printed on: {{ now()->format('d-M-Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
