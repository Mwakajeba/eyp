<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Imprest Request - {{ $imprestRequest->request_number }}</title>
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

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }

        .company-details {
            font-size: 10px;
            margin-top: 3px;
        }

        .document-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
            text-align: center;
            margin: 10px 0;
        }

        hr {
            border: none;
            border-top: 2px solid #3b82f6;
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
            border: 1px solid #cbd5e1;
            padding: 8px;
            border-radius: 3px;
            min-height: 96px;
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
            color: #1e40af;
            font-weight: bold;
            width: 45%;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e40af;
            margin: 12px 0 6px 0;
        }

        .desc-box {
            border: 1px solid #cbd5e1;
            padding: 8px;
            font-size: 10px;
            margin-bottom: 8px;
            background: #f8fafc;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 6px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 5px;
            vertical-align: top;
        }

        .items-table th {
            background-color: #1e3a8a;
            color: #fff;
            font-weight: bold;
            text-align: left;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .total-row td {
            font-weight: bold;
            background: #e2e8f0;
        }

        .status-chip {
            display: inline-block;
            border: 1px solid #94a3b8;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            background: #f8fafc;
        }

        .footer-note {
            margin-top: 12px;
            font-size: 9px;
            color: #475569;
        }
    </style>
</head>
<body>
    @php
        $approvalRows = collect();

        if ($imprestRequest->created_at) {
            $approvalRows->push([
                'level' => 'Initiation',
                'action' => 'Request Created',
                'by' => $imprestRequest->creator->name ?? $imprestRequest->employee->name ?? 'N/A',
                'date' => $imprestRequest->created_at,
                'comments' => null,
            ]);
        }

        if ($imprestRequest->checked_at) {
            $approvalRows->push([
                'level' => 'Level 1',
                'action' => 'Checked',
                'by' => $imprestRequest->checker->name ?? 'N/A',
                'date' => $imprestRequest->checked_at,
                'comments' => $imprestRequest->check_comments,
            ]);
        }

        if ($imprestRequest->approved_at) {
            $approvalRows->push([
                'level' => 'Level 2',
                'action' => 'Approved',
                'by' => $imprestRequest->approver->name ?? 'N/A',
                'date' => $imprestRequest->approved_at,
                'comments' => $imprestRequest->approval_comments,
            ]);
        }

        if ($imprestRequest->rejected_at) {
            $approvalRows->push([
                'level' => 'Decision',
                'action' => 'Rejected',
                'by' => $imprestRequest->rejecter->name ?? 'N/A',
                'date' => $imprestRequest->rejected_at,
                'comments' => $imprestRequest->rejection_reason,
            ]);
        }

        foreach (($completedApprovals ?? collect()) as $approval) {
            $approvalRows->push([
                'level' => 'Level ' . ($approval->level ?? 'N/A'),
                'action' => ucfirst((string) ($approval->status ?? 'approved')),
                'by' => $approval->approver->name ?? 'N/A',
                'date' => $approval->action_date ?? $approval->updated_at,
                'comments' => $approval->comments ?? null,
            ]);
        }

        $approvalRows = $approvalRows
            ->filter(fn($r) => !empty($r['date']))
            ->sortBy(function ($r) {
                return $r['date'] instanceof \Carbon\CarbonInterface ? $r['date']->timestamp : strtotime((string) $r['date']);
            })
            ->values();
    @endphp

    <div class="container">
        <div class="text-center">
            <div class="company-name">{{ $imprestRequest->company->name ?? config('app.name') }}</div>
            <div class="company-details">
                {{ $imprestRequest->branch->name ?? 'Main Branch' }}
                @if(!empty($imprestRequest->company->address)) | {{ $imprestRequest->company->address }} @endif
                @if(!empty($imprestRequest->company->phone)) | {{ $imprestRequest->company->phone }} @endif
                @if(!empty($imprestRequest->company->email)) | {{ $imprestRequest->company->email }} @endif
            </div>
        </div>

        <hr>
        <div class="document-title">IMPREST REQUEST EXPORT</div>

        <table class="info-grid">
            <tr>
                <td>
                    <div class="box">
                        <table>
                            <tr>
                                <td>Request No:</td>
                                <td>{{ $imprestRequest->request_number }}</td>
                            </tr>
                            <tr>
                                <td>Employee:</td>
                                <td>{{ $imprestRequest->employee->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td>Department:</td>
                                <td>{{ $imprestRequest->department->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td>Created Date:</td>
                                <td>{{ optional($imprestRequest->created_at)->format('d M Y H:i') ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td>
                    <div class="box">
                        <table>
                            <tr>
                                <td>Status:</td>
                                <td><span class="status-chip">{{ $imprestRequest->getStatusLabel() }}</span></td>
                            </tr>
                            <tr>
                                <td>Date Required:</td>
                                <td>{{ $imprestRequest->date_required ? $imprestRequest->date_required->format('d M Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td>Project:</td>
                                <td>
                                    @if($imprestRequest->project)
                                        {{ $imprestRequest->project->project_code ? $imprestRequest->project->project_code . ' - ' : '' }}{{ $imprestRequest->project->name }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>Activity:</td>
                                <td>
                                    @if($imprestRequest->projectActivity)
                                        {{ $imprestRequest->projectActivity->activity_code ? $imprestRequest->projectActivity->activity_code . ' - ' : '' }}{{ $imprestRequest->projectActivity->description }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-title">Request Descriptions</div>
        <div class="desc-box"><strong>Purpose:</strong> {{ $imprestRequest->purpose }}</div>
        <div class="desc-box"><strong>Detailed Description:</strong> {{ $imprestRequest->description ?: 'N/A' }}</div>

        <div class="section-title">Requested Line Items</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 28%;">Chart Account</th>
                    <th style="width: 47%;">Requested Description</th>
                    <th style="width: 20%;" class="text-right">Amount (TZS)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($imprestRequest->imprestItems as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $item->chartAccount->account_code ?? 'N/A' }}</strong><br>
                            {{ $item->chartAccount->account_name ?? 'N/A' }}
                        </td>
                        <td>{{ $item->notes ?: 'N/A' }}</td>
                        <td class="text-right">{{ number_format((float) $item->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No line items available.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="text-right">Total Requested</td>
                    <td class="text-right">{{ number_format((float) $imprestRequest->amount_requested, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="section-title">Approval History</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 14%;">Level</th>
                    <th style="width: 15%;">Action</th>
                    <th style="width: 23%;">By</th>
                    <th style="width: 16%;">Date</th>
                    <th style="width: 32%;">Comments</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvalRows as $row)
                    <tr>
                        <td>{{ $row['level'] }}</td>
                        <td>{{ $row['action'] }}</td>
                        <td>{{ $row['by'] }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y H:i') }}</td>
                        <td>{{ $row['comments'] ?: 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No approval history available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer-note">
            Generated on {{ now()->format('d M Y H:i') }} by {{ auth()->user()->name ?? 'System' }}.
        </div>
    </div>
</body>
</html>
