<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .header { margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .meta { font-size: 12px; color: #374151; margin-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; }
        th { background: #f3f4f6; text-align: left; }
        td.amount, th.amount { text-align: right; }
        .total-row td { font-weight: bold; background: #f9fafb; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $title }}</div>
        <div class="meta"><strong>Project:</strong> {{ $project->project_code }} - {{ $project->name }}</div>
        <div class="meta"><strong>Project Type:</strong> {{ $project->type }}</div>
        <div class="meta"><strong>Period:</strong> {{ $dateFrom }} to {{ $dateTo }}</div>
        <div class="meta"><strong>Report:</strong> {{ $reportType }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Reference</th>
                <th>Date</th>
                <th>Payee</th>
                <th>Reference Type</th>
                <th>Reference Number</th>
                <th>Currency</th>
                <th class="amount">Amount</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->reference }}</td>
                    <td>{{ optional($row->date)->format('Y-m-d') }}</td>
                    <td>
                        {{ $row->payee_name ?: ($row->supplier->name ?? ($row->customer->name ?? '-')) }}
                    </td>
                    <td>{{ $row->reference_type }}</td>
                    <td>{{ $row->reference_number }}</td>
                    <td>{{ $row->currency }}</td>
                    <td class="amount">{{ number_format((float) $row->amount, 2) }}</td>
                    <td>{{ $row->description }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">No {{ strtolower($reportType) }} found for this filter.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="6">Total {{ $reportType }}</td>
                <td class="amount">{{ number_format((float) $totalAmount, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
