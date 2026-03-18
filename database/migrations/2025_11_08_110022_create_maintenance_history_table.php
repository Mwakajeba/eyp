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
        if (Schema::hasTable('maintenance_history')) {
            return;
        }

        Schema::create('maintenance_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('work_order_id')->nullable();
            $table->unsignedBigInteger('maintenance_request_id')->nullable();
            $table->unsignedBigInteger('maintenance_type_id');
            $table->enum('maintenance_type', ['preventive', 'corrective', 'major_overhaul']);
            $table->date('maintenance_date');
            $table->date('completion_date')->nullable();
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('material_cost', 15, 2)->default(0);
            $table->decimal('labor_cost', 15, 2)->default(0);
            $table->decimal('other_cost', 15, 2)->default(0);
            $table->enum('cost_classification', ['expense', 'capitalized'])->default('expense');
            $table->decimal('capitalized_amount', 15, 2)->default(0);
            $table->integer('downtime_hours')->default(0);
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->string('technician_name')->nullable();
            $table->text('work_performed')->nullable();
            $table->text('notes')->nullable();
            $table->integer('life_extension_months')->nullable(); // If capitalized
            $table->date('next_maintenance_date')->nullable(); // For preventive
            $table->boolean('gl_posted')->default(false);
            $table->unsignedBigInteger('gl_journal_id')->nullable();
            $table->timestamp('gl_posted_at')->nullable();
            $table->json('attachments')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('work_order_id')->references('id')->on('asset_maintenance_work_orders')->onDelete('set null');
            $table->foreign('maintenance_request_id')->references('id')->on('maintenance_requests')->onDelete('set null');
            $table->foreign('maintenance_type_id')->references('id')->on('maintenance_types')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('technician_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('gl_journal_id')->references('id')->on('journals')->onDelete('set null');
            $table->index(['company_id', 'branch_id', 'maintenance_date']);
            $table->index(['asset_id', 'maintenance_date']);
            $table->index(['cost_classification']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_history');
    }
};
