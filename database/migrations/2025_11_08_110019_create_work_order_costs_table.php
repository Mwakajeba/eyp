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
        // Check if table already exists (may conflict with production work_order_costs)
        if (Schema::hasTable('asset_maintenance_work_order_costs')) {
            return;
        }

        Schema::create('asset_maintenance_work_order_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id');
            $table->enum('cost_type', ['material', 'labor', 'other'])->default('material');
            $table->string('description');
            $table->unsignedBigInteger('inventory_item_id')->nullable(); // If material from inventory
            $table->unsignedBigInteger('purchase_order_id')->nullable(); // If material from PO
            $table->unsignedBigInteger('purchase_invoice_id')->nullable(); // If from vendor invoice
            $table->unsignedBigInteger('supplier_id')->nullable(); // External vendor
            $table->unsignedBigInteger('employee_id')->nullable(); // Internal labor
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_with_tax', 15, 2)->default(0);
            $table->date('cost_date');
            $table->enum('status', ['estimated', 'actual', 'cancelled'])->default('estimated');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('work_order_id')->references('id')->on('asset_maintenance_work_orders')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('set null');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['work_order_id', 'cost_type', 'status'], 'am_wo_costs_wo_id_type_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance_work_order_costs');
    }
};
