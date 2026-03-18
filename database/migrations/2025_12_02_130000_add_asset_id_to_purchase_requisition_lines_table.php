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
        Schema::table('purchase_requisition_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_requisition_lines', 'asset_id')) {
                $table->foreignId('asset_id')
                    ->nullable()
                    ->after('inventory_item_id')
                    ->constrained('assets')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requisition_lines', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_requisition_lines', 'asset_id')) {
                $table->dropForeign(['asset_id']);
                $table->dropColumn('asset_id');
            }
        });
    }
};


