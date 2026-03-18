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
        Schema::table('assets', function (Blueprint $table) {
            // Replace tax_pool_class string with proper foreign key
            if (Schema::hasColumn('assets', 'tax_pool_class')) {
                $table->dropColumn('tax_pool_class');
            }

            // Add proper tax class foreign key
            $table->unsignedBigInteger('tax_class_id')->nullable()->after('asset_category_id');
            
            // Tax depreciation tracking fields
            $table->decimal('tax_value_opening', 18, 2)->nullable()->after('current_nbv')->comment('Tax WDV at opening/capitalization');
            $table->decimal('accumulated_tax_dep', 18, 2)->default(0)->after('tax_value_opening')->comment('Total accumulated tax depreciation');
            $table->decimal('current_tax_wdv', 18, 2)->nullable()->after('accumulated_tax_dep')->comment('Current tax written down value');
            
            // Tax method and rate (stored for reference, but can be derived from tax_class)
            $table->enum('tax_method', ['reducing_balance', 'straight_line', 'immediate_write_off', 'useful_life'])->nullable()->after('current_tax_wdv');
            $table->decimal('tax_rate', 10, 6)->nullable()->after('tax_method')->comment('Tax depreciation rate (%)');
            
            // Deferred tax tracking
            $table->decimal('deferred_tax_diff', 18, 2)->default(0)->after('tax_rate')->comment('Temporary difference (NBV_book - WDV_tax)');
            $table->decimal('deferred_tax_liability', 18, 2)->default(0)->after('deferred_tax_diff')->comment('Deferred tax liability amount');
            
            // Add foreign key constraint if tax_depreciation_classes table exists
            if (Schema::hasTable('tax_depreciation_classes')) {
                $table->foreign('tax_class_id')->references('id')->on('tax_depreciation_classes')->onDelete('set null');
            }
            
            $table->index('tax_class_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Drop foreign key first
            if (Schema::hasTable('tax_depreciation_classes')) {
                $table->dropForeign(['tax_class_id']);
            }
            
            // Drop indexes
            $table->dropIndex(['tax_class_id']);
            
            // Drop columns
            $table->dropColumn([
                'tax_class_id',
                'tax_value_opening',
                'accumulated_tax_dep',
                'current_tax_wdv',
                'tax_method',
                'tax_rate',
                'deferred_tax_diff',
                'deferred_tax_liability',
            ]);
            
            // Restore tax_pool_class if needed
            if (!Schema::hasColumn('assets', 'tax_pool_class')) {
                $table->string('tax_pool_class')->nullable()->after('asset_category_id');
            }
        });
    }
};
