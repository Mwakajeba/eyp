<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fleet\FleetCostCategory;

class FleetCostCategorySeeder extends Seeder
{
    /**
     * Seed default fleet cost categories. Safe to run multiple times (updateOrCreate by company + name).
     */
    public function run(): void
    {
        $companyId = 1;

        $categories = [
            [
                'name' => 'Petrol',
                'category_type' => 'fuel',
                'description' => 'Petrol fuel costs',
                'unit_of_measure' => 'liters',
                'is_active' => true,
            ],
            [
                'name' => 'Diesel',
                'category_type' => 'fuel',
                'description' => 'Diesel fuel costs',
                'unit_of_measure' => 'liters',
                'is_active' => true,
            ],
            [
                'name' => 'Engine Oil',
                'category_type' => 'maintenance',
                'description' => 'Engine oil replacement',
                'unit_of_measure' => 'liters',
                'is_active' => true,
            ],
            [
                'name' => 'Tire Replacement',
                'category_type' => 'maintenance',
                'description' => 'Tire replacement and repair',
                'unit_of_measure' => 'fixed',
                'is_active' => true,
            ],
            [
                'name' => 'Vehicle Insurance',
                'category_type' => 'insurance',
                'description' => 'Vehicle insurance premiums',
                'unit_of_measure' => 'fixed',
                'is_active' => true,
            ],
            [
                'name' => 'Driver Salary',
                'category_type' => 'driver_cost',
                'description' => 'Driver monthly salary',
                'unit_of_measure' => 'fixed',
                'is_active' => true,
            ],
            [
                'name' => 'Toll Fees',
                'category_type' => 'toll',
                'description' => 'Highway and bridge tolls',
                'unit_of_measure' => 'fixed',
                'is_active' => true,
            ],
            [
                'name' => 'Parking Fees',
                'category_type' => 'other',
                'description' => 'Parking and other miscellaneous fees',
                'unit_of_measure' => 'fixed',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $cat) {
            FleetCostCategory::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $cat['name'],
                ],
                array_merge($cat, [
                    'company_id' => $companyId,
                    'created_by' => 1,
                    'updated_by' => 1,
                ])
            );
        }

        $this->command->info('Fleet cost categories seeded for company ' . $companyId . '.');
    }
}
