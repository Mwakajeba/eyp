<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Production\ProductionMachine;

class EnhancedProductionMachinesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing machines with production stages and gauge info
        $machines = ProductionMachine::all();
        
        $stages = ['KNITTING', 'CUTTING', 'JOINING', 'EMBROIDERY', 'IRONING_FINISHING', 'PACKAGING'];
        $knittingGauges = ['12GG', '14GG', '16GG', '18GG'];
        
        foreach ($machines as $index => $machine) {
            $stage = $stages[$index % count($stages)];
            
            $machine->update([
                'production_stage' => $stage,
                'gauge' => $stage === 'KNITTING' ? $knittingGauges[array_rand($knittingGauges)] : null
            ]);
        }

        // If we have fewer than 12 machines, create some additional ones
        $currentCount = ProductionMachine::count();
        if ($currentCount < 12) {
            $newMachines = [
                [
                    'machine_name' => 'Brother KH-965 Knitting Machine',
                    'purchased_date' => now()->subDays(90),
                    'status' => 'new',
                    'location' => 'Floor A - Knitting Section',
                    'production_stage' => 'KNITTING',
                    'gauge' => '12GG'
                ],
                [
                    'machine_name' => 'Singer Heavy Duty Overlock',
                    'purchased_date' => now()->subDays(60),
                    'status' => 'new',
                    'location' => 'Floor B - Joining Section',
                    'production_stage' => 'JOINING',
                    'gauge' => null
                ],
                [
                    'machine_name' => 'Eastman Cutting Machine',
                    'purchased_date' => now()->subDays(120),
                    'status' => 'used',
                    'location' => 'Floor A - Cutting Section',
                    'production_stage' => 'CUTTING',
                    'gauge' => null
                ],
                [
                    'machine_name' => 'Barudan Embroidery Machine',
                    'purchased_date' => now()->subDays(45),
                    'status' => 'new',
                    'location' => 'Floor B - Embroidery Section',
                    'production_stage' => 'EMBROIDERY',
                    'gauge' => null
                ],
                [
                    'machine_name' => 'Industrial Steam Press',
                    'purchased_date' => now()->subDays(180),
                    'status' => 'used',
                    'location' => 'Floor C - Finishing Section',
                    'production_stage' => 'IRONING_FINISHING',
                    'gauge' => null
                ],
                [
                    'machine_name' => 'Automated Packaging Line',
                    'purchased_date' => now()->subDays(30),
                    'status' => 'new',
                    'location' => 'Floor C - Packaging Section',
                    'production_stage' => 'PACKAGING',
                    'gauge' => null
                ]
            ];

            foreach ($newMachines as $machineData) {
                ProductionMachine::create($machineData);
            }
        }

        $this->command->info('Enhanced production machines seeded successfully!');
    }
}
