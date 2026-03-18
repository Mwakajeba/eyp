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
        Schema::create('store_requisition_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_requisition_return_id');
            $table->unsignedBigInteger('store_requisition_item_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->decimal('quantity_returned', 10, 2);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 14, 2);
            $table->timestamps();

            // Foreign keys
            $table->foreign('store_requisition_return_id', 'srri_return_fk')
                ->references('id')->on('store_requisition_returns')
                ->onDelete('cascade');
            $table->foreign('store_requisition_item_id', 'srri_item_fk')
                ->references('id')->on('store_requisition_items')
                ->onDelete('set null');
            $table->foreign('inventory_item_id', 'srri_inv_fk')
                ->references('id')->on('inventory_items')
                ->onDelete('cascade');

            $table->index(['store_requisition_return_id']);
            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_requisition_return_items');
    }
};
