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
        Schema::table('asset_categories', function (Blueprint $table) {
            // Make depreciation-related fields nullable to support "no_depreciation" method
            $table->unsignedInteger('default_useful_life_months')->nullable()->change();
            $table->decimal('default_depreciation_rate', 10, 2)->nullable()->change();
            // For ENUM, we need to use DB::statement to modify it
        });
        
        // Modify depreciation_convention ENUM to allow NULL
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `asset_categories` MODIFY COLUMN `depreciation_convention` ENUM('monthly_prorata', 'mid_month', 'full_month') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_categories', function (Blueprint $table) {
            // Revert back to NOT NULL with defaults
            $table->unsignedInteger('default_useful_life_months')->default(60)->change();
            $table->decimal('default_depreciation_rate', 10, 2)->default(0)->change();
        });
        
        // Revert depreciation_convention to NOT NULL with default
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `asset_categories` MODIFY COLUMN `depreciation_convention` ENUM('monthly_prorata', 'mid_month', 'full_month') NOT NULL DEFAULT 'monthly_prorata'");
        }
    }
};
