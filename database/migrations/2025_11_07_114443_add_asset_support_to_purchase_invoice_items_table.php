<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            // Add item_type to distinguish between inventory and asset
            $table->enum('item_type', ['inventory', 'asset'])->default('inventory')->after('purchase_invoice_id');
            
            // Add asset_id for asset purchases
            $table->foreignId('asset_id')->nullable()->after('inventory_item_id')->constrained('assets')->onDelete('set null');
            
            // Add asset_category_id for creating new assets from purchase
            $table->foreignId('asset_category_id')->nullable()->after('asset_id')->constrained('asset_categories')->onDelete('set null');
            
            // Add fields needed for asset creation
            $table->string('asset_name')->nullable()->after('asset_category_id');
            $table->string('asset_code')->nullable()->after('asset_name');
            $table->text('asset_description')->nullable()->after('asset_code');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
            $table->dropForeign(['asset_category_id']);
            $table->dropColumn(['item_type', 'asset_id', 'asset_category_id', 'asset_name', 'asset_code', 'asset_description']);
        });
    }
};
