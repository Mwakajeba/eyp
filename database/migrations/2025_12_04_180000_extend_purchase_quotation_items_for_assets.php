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
        Schema::table('purchase_quotation_items', function (Blueprint $table) {
            // Drop strict FK so non-inventory lines can be stored
            try {
                $table->dropForeign(['item_id']);
            } catch (\Throwable $e) {
                // FK might not exist yet; ignore
            }
        });

        Schema::table('purchase_quotation_items', function (Blueprint $table) {
            // Allow item_id to be nullable (only required for inventory lines)
            $table->unsignedBigInteger('item_id')->nullable()->change();

            // Add support for multiple item types similar to PR/Invoice lines
            if (!Schema::hasColumn('purchase_quotation_items', 'item_type')) {
                $table->enum('item_type', ['inventory', 'fixed_asset', 'intangible'])
                    ->default('inventory')
                    ->after('item_id');
            }

            if (!Schema::hasColumn('purchase_quotation_items', 'asset_id')) {
                $table->unsignedBigInteger('asset_id')->nullable()->after('item_type');
            }

            if (!Schema::hasColumn('purchase_quotation_items', 'fixed_asset_category_id')) {
                $table->unsignedBigInteger('fixed_asset_category_id')->nullable()->after('asset_id');
            }

            if (!Schema::hasColumn('purchase_quotation_items', 'intangible_asset_category_id')) {
                $table->unsignedBigInteger('intangible_asset_category_id')->nullable()->after('fixed_asset_category_id');
            }

            // Descriptive fields for non-inventory items
            if (!Schema::hasColumn('purchase_quotation_items', 'description')) {
                $table->string('description', 500)->nullable()->after('quantity');
            }

            if (!Schema::hasColumn('purchase_quotation_items', 'unit_of_measure')) {
                $table->string('unit_of_measure', 50)->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_quotation_items', function (Blueprint $table) {
            // Best-effort rollback: drop new columns, leave item_id nullable
            if (Schema::hasColumn('purchase_quotation_items', 'intangible_asset_category_id')) {
                $table->dropColumn('intangible_asset_category_id');
            }
            if (Schema::hasColumn('purchase_quotation_items', 'fixed_asset_category_id')) {
                $table->dropColumn('fixed_asset_category_id');
            }
            if (Schema::hasColumn('purchase_quotation_items', 'asset_id')) {
                $table->dropColumn('asset_id');
            }
            if (Schema::hasColumn('purchase_quotation_items', 'item_type')) {
                $table->dropColumn('item_type');
            }
            if (Schema::hasColumn('purchase_quotation_items', 'unit_of_measure')) {
                $table->dropColumn('unit_of_measure');
            }
            if (Schema::hasColumn('purchase_quotation_items', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};


