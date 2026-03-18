<?php

namespace Database\Seeders;

use App\Models\Assets\TaxDepreciationClass;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaxDepreciationClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds TRA-compliant tax depreciation classes for Tanzania
     */
    public function run(): void
    {
        $classes = [
            [
                'class_code' => 'Class 1',
                'description' => 'Computers, small vehicles (<30 seats), construction & earth-moving equipment',
                'rate' => 37.5,
                'method' => 'reducing_balance',
                'special_condition' => null,
                'legal_reference' => 'Income Tax (Depreciable Assets) Schedule – Tanzania',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'class_code' => 'Class 2',
                'description' => 'Heavy vehicles (≥30 seats), aircraft, vessels, manufacturing/agricultural machinery',
                'rate' => 25.0,
                'method' => 'reducing_balance',
                'special_condition' => '50% allowance (first two years) if used in manufacturing/tourism/fish farming',
                'legal_reference' => 'Income Tax (Depreciable Assets) Schedule – Tanzania',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'class_code' => 'Class 3',
                'description' => 'Office furniture, fixtures, and equipment; any asset not in another class',
                'rate' => 12.5,
                'method' => 'reducing_balance',
                'special_condition' => null,
                'legal_reference' => 'Income Tax (Depreciable Assets) Schedule – Tanzania',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'class_code' => 'Class 5',
                'description' => 'Agricultural permanent structures (dams, fences, reservoirs, etc.)',
                'rate' => 20.0,
                'method' => 'straight_line',
                'special_condition' => '5 years write-off',
                'legal_reference' => 'Income Tax (Depreciable Assets) Schedule – Tanzania',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'class_code' => 'Class 6',
                'description' => 'Other buildings & permanent structures',
                'rate' => 5.0,
                'method' => 'straight_line',
                'special_condition' => null,
                'legal_reference' => 'Income Tax (Depreciable Assets) Schedule – Tanzania',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'class_code' => 'Class 7',
                'description' => 'Intangible assets',
                'rate' => null,
                'method' => 'useful_life',
                'special_condition' => '1 ÷ useful life (round down to nearest half year)',
                'legal_reference' => 'Income Tax (Depreciable Assets) Schedule – Tanzania',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'class_code' => 'Class 8',
                'description' => 'Agricultural plant & machinery, EFDs for non-VAT traders',
                'rate' => 100.0,
                'method' => 'immediate_write_off',
                'special_condition' => 'Immediate write-off',
                'legal_reference' => 'Income Tax (Depreciable Assets) Schedule – Tanzania',
                'is_active' => true,
                'sort_order' => 8,
            ],
        ];

        foreach ($classes as $class) {
            TaxDepreciationClass::updateOrCreate(
                ['class_code' => $class['class_code']],
                $class
            );
        }
    }
}
