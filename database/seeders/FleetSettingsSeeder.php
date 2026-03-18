<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Fleet\FleetSystemSetting;

class FleetSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = 1; // Default company

        // Cost categories are seeded by FleetCostCategorySeeder

        // Create default system settings
        $defaultSettings = FleetSystemSetting::getDefaultSettings();

        foreach ($defaultSettings as $key => $config) {
            FleetSystemSetting::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'setting_key' => $key,
                ],
                [
                    'setting_value' => $config['value'],
                    'setting_description' => $config['description'],
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );
        }

        // Create or reuse default approval workflow
        \App\Models\Fleet\FleetApprovalWorkflow::firstOrCreate(
            [
                'company_id' => $companyId,
                'name' => 'General Trip Approval',
                'workflow_type' => 'trip_request',
            ],
            [
                'description' => 'Default approval workflow for trip requests',
                'min_amount' => 10000,
                'max_amount' => null,
                'requires_multiple_approvers' => false,
                'is_active' => true,
                'created_by' => 1,
            ]
        );

        // Note: In a real implementation, you would create workflow approvers here
        // But for now, we'll just create the workflow
    }
}