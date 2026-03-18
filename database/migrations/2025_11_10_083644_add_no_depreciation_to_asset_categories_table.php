<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Modify the ENUM to include 'no_depreciation'
            DB::statement("ALTER TABLE `asset_categories` MODIFY COLUMN `default_depreciation_method` ENUM('straight_line', 'declining_balance', 'syd', 'units', 'no_depreciation') DEFAULT 'straight_line'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Revert back to original ENUM values (remove 'no_depreciation')
            // First, update any records with 'no_depreciation' to 'straight_line'
            DB::statement("UPDATE `asset_categories` SET `default_depreciation_method` = 'straight_line' WHERE `default_depreciation_method` = 'no_depreciation'");
            
            // Then modify the ENUM back
            DB::statement("ALTER TABLE `asset_categories` MODIFY COLUMN `default_depreciation_method` ENUM('straight_line', 'declining_balance', 'syd', 'units') DEFAULT 'straight_line'");
        }
    }
};
