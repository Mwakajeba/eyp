<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class FleetReportExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $data;
    protected $title;
    protected $headings;

    public function __construct(array $data, string $title, array $headings = null)
    {
        $this->data = $data;
        $this->title = $title;
        
        // Extract headings from first row if not provided
        if ($headings === null && !empty($data)) {
            $this->headings = array_keys($data[0]);
        } else {
            $this->headings = $headings ?? [];
        }
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '007BFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Style totals row if present
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            // Check if last row contains 'TOTAL'
            $firstCellValue = $sheet->getCell('A' . $lastRow)->getValue();
            if (is_string($firstCellValue) && strtoupper($firstCellValue) === 'TOTAL') {
                $sheet->getStyle('A' . $lastRow . ':' . $sheet->getHighestColumn() . $lastRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E0E0E0'],
                    ],
                ]);
            }
        }
    }
}
