<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class CustomerStatementExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $customer;
    protected $transactions;
    protected $dateFrom;
    protected $dateTo;
    protected $company;

    public function __construct($customer, $transactions, $dateFrom, $dateTo, $company = null)
    {
        $this->customer = $customer;
        $this->transactions = $transactions;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Type',
            'Reference',
            'Description',
            'Debit',
            'Credit',
            'Balance'
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->date->format('Y-m-d'),
            $transaction->type,
            $transaction->reference,
            $transaction->description,
            $transaction->debit > 0 ? number_format($transaction->debit, 2) : '',
            $transaction->credit > 0 ? number_format($transaction->credit, 2) : '',
            number_format($transaction->balance, 2)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Customer Statement - ' . $this->customer->name;
    }
}
