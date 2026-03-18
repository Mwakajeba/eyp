<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class ImprestMenuSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create main Imprest Management menu
        $imprestMenu = Menu::firstOrCreate([
            'name' => 'Imprest Management',
            'route' => 'imprest.index',
        ], [
            'name' => 'Imprest Management',
            'route' => 'imprest.index',
            'icon' => 'bx bx-money',
            'parent_id' => null,
        ]);

        // Create submenu items
        $submenus = [
            [
                'name' => 'All Requests',
                'route' => 'imprest.requests.index',
                'icon' => 'bx bx-list-ul',
                'parent_id' => $imprestMenu->id,
            ],
            [
                'name' => 'Manager Review',
                'route' => 'imprest.checked.index',
                'icon' => 'bx bx-user-check',
                'parent_id' => $imprestMenu->id,
            ],
            [
                'name' => 'Finance Approval',
                'route' => 'imprest.approved.index',
                'icon' => 'bx bx-check-shield',
                'parent_id' => $imprestMenu->id,
            ],
            [
                'name' => 'Fund Disbursement',
                'route' => 'imprest.disbursed.index',
                'icon' => 'bx bx-money',
                'parent_id' => $imprestMenu->id,
            ],
            [
                'name' => 'Closed Imprests',
                'route' => 'imprest.closed.index',
                'icon' => 'bx bx-archive',
                'parent_id' => $imprestMenu->id,
            ],
        ];

        foreach ($submenus as $submenu) {
            Menu::firstOrCreate([
                'name' => $submenu['name'],
                'route' => $submenu['route'],
            ], $submenu);
        }

        $this->command->info('Imprest Management menu created successfully!');
    }
}