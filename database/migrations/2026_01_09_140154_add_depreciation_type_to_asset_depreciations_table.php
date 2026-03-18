<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asset_depreciations', function (Blueprint $table) {
            // Add depreciation_type to distinguish between book and tax depreciation
            if (!Schema::hasColumn('asset_depreciations', 'depreciation_type')) {
                $table->enum('depreciation_type', ['book', 'tax'])->default('book')->after('type');
            }
            
            // Add tax-specific fields (for tax depreciation entries)
            if (!Schema::hasColumn('asset_depreciations', 'tax_class_id')) {
                $table->unsignedBigInteger('tax_class_id')->nullable()->after('depreciation_type');
            }
            if (!Schema::hasColumn('asset_depreciations', 'tax_wdv_before')) {
                $table->decimal('tax_wdv_before', 18, 2)->nullable()->after('book_value_before')->comment('Tax WDV before depreciation (for tax type only)');
            }
            if (!Schema::hasColumn('asset_depreciations', 'tax_wdv_after')) {
                $table->decimal('tax_wdv_after', 18, 2)->nullable()->after('book_value_after')->comment('Tax WDV after depreciation (for tax type only)');
            }
            if (!Schema::hasColumn('asset_depreciations', 'accumulated_tax_depreciation')) {
                $table->decimal('accumulated_tax_depreciation', 18, 2)->nullable()->after('accumulated_depreciation')->comment('Total accumulated tax depreciation (for tax type only)');
            }
            
            // Add foreign key if tax_depreciation_classes table exists and column exists
            if (Schema::hasTable('tax_depreciation_classes') && Schema::hasColumn('asset_depreciations', 'tax_class_id')) {
                // Check if foreign key doesn't already exist
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'asset_depreciations' 
                    AND COLUMN_NAME = 'tax_class_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                if (empty($foreignKeys)) {
                    $table->foreign('tax_class_id')->references('id')->on('tax_depreciation_classes')->onDelete('set null');
                }
            }
        });
        
        // Add index separately to avoid issues
        Schema::table('asset_depreciations', function (Blueprint $table) {
            // Check if index already exists
            $indexes = DB::select("
                SELECT INDEX_NAME 
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'asset_depreciations' 
                AND INDEX_NAME = 'idx_asset_depr_type_date'
            ");
            if (empty($indexes) && Schema::hasColumn('asset_depreciations', 'depreciation_type')) {
                $table->index(['asset_id', 'depreciation_type', 'depreciation_date'], 'idx_asset_depr_type_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_depreciations', function (Blueprint $table) {
            // Drop foreign key first
            if (Schema::hasTable('tax_depreciation_classes')) {
                $table->dropForeign(['tax_class_id']);
            }
            
            // Drop index
            $indexes = DB::select("
                SELECT INDEX_NAME 
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'asset_depreciations' 
                AND INDEX_NAME = 'idx_asset_depr_type_date'
            ");
            if (!empty($indexes)) {
                $table->dropIndex('idx_asset_depr_type_date');
            }
            
            // Drop columns
            $table->dropColumn([
                'depreciation_type',
                'tax_class_id',
                'tax_wdv_before',
                'tax_wdv_after',
                'accumulated_tax_depreciation',
            ]);
        });
    }
};
