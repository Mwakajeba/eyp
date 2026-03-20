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

        .logo-wrap {
            text-align: center;
            margin-bottom: 6px;
        }

        .company-logo {
            max-width: 90px;
            max-height: 90px;
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
            color: var(--brand-green-dark);
            font-weight: bold;
            width: 45%;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: var(--brand-green-dark);
            margin: 12px 0 6px 0;
            border-left: 4px solid var(--brand-orange);
            padding-left: 6px;
        }

        .desc-box {
            border: 1px solid var(--line-gray);
            padding: 8px;
            font-size: 10px;
            margin-bottom: 8px;
            background: var(--brand-orange-soft);
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
                'level' => !empty($approval->level) ? 'Level ' . $approval->level : null,
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

        $autoLevel = 1;
        $approvalRows = $approvalRows->map(function ($row) use (&$autoLevel) {
            if (empty($row['level']) || $row['level'] === 'Level N/A') {
                $row['level'] = 'Level ' . $autoLevel;
                $autoLevel++;
            } elseif (preg_match('/^Level\s*(\d+)/i', (string) $row['level'], $m)) {
                $autoLevel = max($autoLevel, ((int) $m[1]) + 1);
            }

            return $row;
        });
    @endphp

    <div class="container">
        @if(!empty($logoBase64))
            <div class="logo-wrap">
                <img src="{{ $logoBase64 }}" alt="Logo" class="company-logo">
            </div>
        @endif

        <div class="text-center">
            <div class="company-name">Empower Youth Preosperity</div>
            <div class="company-details">
                Mbeya - HQ | Sisimba, Uzunguni,Mbeya,Tanzania. | 0789439900 | Eyp@eyp.or.tz
            </div>
        </div>

        <hr>
        <div class="document-title">IMPREST REQUEST FORM</div>

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
