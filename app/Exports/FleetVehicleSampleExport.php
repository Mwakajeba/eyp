<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class FleetVehicleSampleExport implements FromCollection, WithHeadings, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return collect([
            [
                'name' => 'Toyota Hilux Double Cab',
                'registration_number' => 'T 123 ABC',
                'model' => 'Hilux',
                'manufacturer' => 'Toyota',
                'serial_number' => 'VIN12345678901234567',
                'fuel_type' => 'diesel',
                'ownership_type' => 'owned',
                'capacity_tons' => '2.5',
                'capacity_volume' => '5000',
                'capacity_passengers' => 5,
                'license_expiry_date' => '2026-12-31',
                'inspection_expiry_date' => '2026-06-15',
                'operational_status' => 'available',
                'location' => 'Warehouse A',
                'description' => 'Company vehicle for goods transport'
            ],
            [
                'name' => 'Nissan Patrol',
                'registration_number' => 'T 456 DEF',
                'model' => 'Patrol',
                'manufacturer' => 'Nissan',
                'serial_number' => 'VIN98765432109876543',
                'fuel_type' => 'petrol',
                'ownership_type' => 'leased',
                'capacity_tons' => '1.8',
                'capacity_volume' => '3500',
                'capacity_passengers' => 7,
                'license_expiry_date' => '2026-08-20',
                'inspection_expiry_date' => '2026-02-10',
                'operational_status' => 'available',
                'location' => 'Warehouse B',
                'description' => 'Management vehicle'
            ]
        ]);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'name',
            'registration_number',
            'model',
            'manufacturer',
            'serial_number',
            'fuel_type',
            'ownership_type',
            'capacity_tons',
            'capacity_volume',
            'capacity_passengers',
            'license_expiry_date',
            'inspection_expiry_date',
            'operational_status',
            'location',
            'description'
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(25); // name
                $sheet->getColumnDimension('B')->setWidth(20); // registration_number
                $sheet->getColumnDimension('C')->setWidth(15); // model
                $sheet->getColumnDimension('D')->setWidth(15); // manufacturer
                $sheet->getColumnDimension('E')->setWidth(25); // serial_number
                $sheet->getColumnDimension('F')->setWidth(12); // fuel_type
                $sheet->getColumnDimension('G')->setWidth(15); // ownership_type
                $sheet->getColumnDimension('H')->setWidth(12); // capacity_tons
                $sheet->getColumnDimension('I')->setWidth(15); // capacity_volume
                $sheet->getColumnDimension('J')->setWidth(18); // capacity_passengers
                $sheet->getColumnDimension('K')->setWidth(18); // license_expiry_date
                $sheet->getColumnDimension('L')->setWidth(20); // inspection_expiry_date
                $sheet->getColumnDimension('M')->setWidth(18); // operational_status
                $sheet->getColumnDimension('N')->setWidth(15); // location
                $sheet->getColumnDimension('O')->setWidth(30); // description

                // Style the header row
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '007BFF'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ];

                $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);

                // Add data validation dropdowns for columns with fixed options

                // Fuel Type dropdown (Column F - index 5)
                for ($row = 2; $row <= 1000; $row++) {
                    $validation = $sheet->getCell("F{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Input error');
                    $validation->setError('Value is not in list.');
                    $validation->setPromptTitle('Pick from list');
                    $validation->setPrompt('Please pick a value from the drop-down list.');
                    $validation->setFormula1('"petrol,diesel,electric,hybrid,lpg,cng"');
                }

                // Ownership Type dropdown (Column G - index 6)
                for ($row = 2; $row <= 1000; $row++) {
                    $validation = $sheet->getCell("G{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Input error');
                    $validation->setError('Value is not in list.');
                    $validation->setPromptTitle('Pick from list');
                    $validation->setPrompt('Please pick a value from the drop-down list.');
                    $validation->setFormula1('"owned,leased,rented"');
                }

                // Operational Status dropdown (Column M - index 12)
                for ($row = 2; $row <= 1000; $row++) {
                    $validation = $sheet->getCell("M{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Input error');
                    $validation->setError('Value is not in list.');
                    $validation->setPromptTitle('Pick from list');
                    $validation->setPrompt('Please pick a value from the drop-down list.');
                    $validation->setFormula1('"available,assigned,in_repair,retired"');
                }

                // Add comments to explain date formats
                $sheet->getComment('K2')->getText()->createTextRun('Date format: YYYY-MM-DD (e.g., 2026-12-31)');
                $sheet->getComment('L2')->getText()->createTextRun('Date format: YYYY-MM-DD (e.g., 2026-06-15)');

                // Format numeric columns - use text format to avoid comma separators on import
                $sheet->getStyle('H:H')->getNumberFormat()->setFormatCode('@'); // capacity_tons - text format
                $sheet->getStyle('I:I')->getNumberFormat()->setFormatCode('@'); // capacity_volume - text format
                $sheet->getStyle('J:J')->getNumberFormat()->setFormatCode('0'); // capacity_passengers

                // Add instructions sheet
                $spreadsheet = $sheet->getParent();
                $instructionsSheet = $spreadsheet->createSheet();
                $instructionsSheet->setTitle('Instructions');

                $instructionsSheet->setCellValue('A1', 'Fleet Vehicle Import Instructions');
                $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructions = [
                    'A3' => 'Required Fields:',
                    'A4' => '• name - Vehicle name/description',
                    'A5' => '• registration_number - License plate number (must be unique)',
                    'A7' => 'Optional Fields:',
                    'A8' => '• model - Vehicle model',
                    'A9' => '• manufacturer - Vehicle manufacturer',
                    'A10' => '• serial_number - VIN or chassis number',
                    'A11' => '• fuel_type - Select from dropdown: petrol, diesel, electric, hybrid, lpg, cng',
                    'A12' => '• ownership_type - Select from dropdown: owned, leased, rented',
                    'A13' => '• capacity_tons - Load capacity in tons (use decimal like 2.5, NO commas)',
                    'A14' => '• capacity_volume - Volume capacity in liters (use plain number like 5000, NO commas)',
                    'A15' => '• capacity_passengers - Passenger capacity (integer)',
                    'A16' => '• license_expiry_date - License expiry (YYYY-MM-DD format)',
                    'A17' => '• inspection_expiry_date - Inspection expiry (YYYY-MM-DD format)',
                    'A18' => '• operational_status - Select from dropdown: available, assigned, in_repair, retired',
                    'A19' => '• location - Physical location',
                    'A20' => '• description - Additional notes',
                    'A22' => 'Important Notes:',
                    'A23' => '• All vehicles will be automatically categorized as "Motor Vehicles"',
                    'A24' => '• Asset codes will be auto-generated if not provided',
                    'A25' => '• Dates must be in YYYY-MM-DD format',
                    'A26' => '• Numeric fields should NOT contain commas, currency symbols, or spaces',
                    'A27' => '• Examples: capacity_tons = 2.5 (NOT 2,5), capacity_volume = 5000 (NOT 5,000)',
                    'A28' => '• Dropdown fields must match the exact values shown in the lists',
                    'A29' => '• Registration numbers must be unique - duplicates will not be imported'
                ];

                foreach ($instructions as $cell => $text) {
                    $instructionsSheet->setCellValue($cell, $text);
                }

                $instructionsSheet->getColumnDimension('A')->setWidth(80);
                $instructionsSheet->getStyle('A3:A7')->getFont()->setBold(true);
                $instructionsSheet->getStyle('A8:A21')->getFont()->setBold(true);
                $instructionsSheet->getStyle('A23:A28')->getFont()->setBold(true);

                // Set the data sheet as active
                $spreadsheet->setActiveSheetIndex(0);
            },
        ];
    }
}
