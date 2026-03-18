<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class FleetDriverSampleExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            [
                'full_name' => 'John Mwita',
                'license_number' => 'DL-001234',
                'license_class' => 'B',
                'license_expiry_date' => '2026-12-31',
                'license_issuing_authority' => 'TRA',
                'employment_type' => 'employee',
                'phone_number' => '0712345678',
                'email' => 'john.mwita@example.com',
                'address' => 'Dar es Salaam',
                'emergency_contact_name' => 'Jane Mwita',
                'emergency_contact_phone' => '0787654321',
                'emergency_contact_relationship' => 'Spouse',
                'status' => 'active',
            ],
            [
                'full_name' => 'Peter Mushi',
                'license_number' => 'DL-005678',
                'license_class' => 'C',
                'license_expiry_date' => '2026-06-15',
                'license_issuing_authority' => 'TRA',
                'employment_type' => 'contractor',
                'phone_number' => '0755123456',
                'email' => 'peter.mushi@example.com',
                'address' => 'Mwanza',
                'emergency_contact_name' => 'Mary Mushi',
                'emergency_contact_phone' => '0744332211',
                'emergency_contact_relationship' => 'Sister',
                'status' => 'active',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'full_name',
            'license_number',
            'license_class',
            'license_expiry_date',
            'license_issuing_authority',
            'employment_type',
            'phone_number',
            'email',
            'address',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_contact_relationship',
            'status',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $sheet->getColumnDimension('A')->setWidth(22);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(22);
                $sheet->getColumnDimension('F')->setWidth(16);
                $sheet->getColumnDimension('G')->setWidth(16);
                $sheet->getColumnDimension('H')->setWidth(28);
                $sheet->getColumnDimension('I')->setWidth(25);
                $sheet->getColumnDimension('J')->setWidth(22);
                $sheet->getColumnDimension('K')->setWidth(18);
                $sheet->getColumnDimension('L')->setWidth(18);
                $sheet->getColumnDimension('M')->setWidth(12);

                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '198754'],
                    ],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ];
                $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

                foreach (['F' => 'employee,contractor', 'M' => 'active,inactive,suspended,terminated'] as $col => $list) {
                    for ($row = 2; $row <= 1000; $row++) {
                        $validation = $sheet->getCell("{$col}{$row}")->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setAllowBlank(true);
                        $validation->setShowDropDown(true);
                        $validation->setFormula1('"' . str_replace(',', ',', $list) . '"');
                    }
                }

                $spreadsheet = $sheet->getParent();
                $instructionsSheet = $spreadsheet->createSheet();
                $instructionsSheet->setTitle('Instructions');
                $instructionsSheet->setCellValue('A1', 'Fleet Driver Import Instructions');
                $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $lines = [
                    'A3' => 'Required (same as Add Driver form): full_name, license_number, license_expiry_date, employment_type, status',
                    'A4' => 'Optional: license_class, license_issuing_authority, phone_number, email, address, emergency contacts',
                    'A5' => 'employment_type: must be employee or contractor',
                    'A6' => 'status: must be active, inactive, suspended, or terminated',
                    'A7' => 'Dates: YYYY-MM-DD format. Driver codes and user accounts are auto-created.',
                ];
                foreach ($lines as $cell => $text) {
                    $instructionsSheet->setCellValue($cell, $text);
                }
                $instructionsSheet->getColumnDimension('A')->setWidth(70);
                $spreadsheet->setActiveSheetIndex(0);
            },
        ];
    }
}
