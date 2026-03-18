<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class StockOnHandExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $itemsWithStock;
    protected $totalQuantity;
    protected $totalValue;
    protected $systemCostMethod;
    protected $company;

    public function __construct($itemsWithStock, $totalQuantity, $totalValue, $systemCostMethod, $company = null)
    {
        $this->itemsWithStock = $itemsWithStock;
        $this->totalQuantity = $totalQuantity;
        $this->totalValue = $totalValue;
        $this->systemCostMethod = $systemCostMethod;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->itemsWithStock;
    }

    public function headings(): array
    {
        return [
            'Item Code',
            'Item Name',
            'Category',
            'UOM',
            'Unit Cost (TZS)',
            'Total Stock',
            'Total Value (TZS)',
            'Locations Breakdown'
        ];
    }

    public function map($itemData): array
    {
        $locationsBreakdown = '';
        if (count($itemData['locations']) > 0) {
            $locationStrings = [];
            foreach ($itemData['locations'] as $locationData) {
                $locationStrings[] = $locationData['location']->name . ': ' . 
                    number_format($locationData['stock'], 2) . ' (' . 
                    number_format($locationData['value'], 0) . ' TZS)';
            }
            $locationsBreakdown = implode('; ', $locationStrings);
        } else {
            $locationsBreakdown = 'No stock';
        }

        return [
            $itemData['item']->code,
            $itemData['item']->name,
            $itemData['item']->category->name ?? 'N/A',
            $itemData['item']->unit_of_measure,
            number_format($itemData['unit_cost'], 2),
            number_format($itemData['total_stock'], 2),
            number_format($itemData['total_value'], 2),
            $locationsBreakdown
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Stock on Hand Report';
    }
}
