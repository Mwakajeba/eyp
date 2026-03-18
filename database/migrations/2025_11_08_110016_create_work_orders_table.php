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
        // Use a different table name to avoid conflict with production work_orders
        Schema::create('asset_maintenance_work_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('wo_number')->unique();
            $table->unsignedBigInteger('maintenance_request_id')->nullable();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('maintenance_type_id');
            $table->enum('maintenance_type', ['preventive', 'corrective', 'major_overhaul'])->default('corrective');
            $table->enum('execution_type', ['in_house', 'external_vendor', 'mixed'])->default('in_house');
            $table->unsignedBigInteger('vendor_id')->nullable(); // Supplier for external work
            $table->unsignedBigInteger('assigned_technician_id')->nullable(); // Internal technician
            $table->date('estimated_start_date');
            $table->date('estimated_completion_date');
            $table->date('actual_start_date')->nullable();
            $table->date('actual_completion_date')->nullable();
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->decimal('estimated_labor_cost', 15, 2)->default(0);
            $table->decimal('estimated_material_cost', 15, 2)->default(0);
            $table->decimal('estimated_other_cost', 15, 2)->default(0);
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->decimal('actual_labor_cost', 15, 2)->default(0);
            $table->decimal('actual_material_cost', 15, 2)->default(0);
            $table->decimal('actual_other_cost', 15, 2)->default(0);
            $table->integer('estimated_downtime_hours')->default(0);
            $table->integer('actual_downtime_hours')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable(); // Department/budget center
            $table->unsignedBigInteger('budget_reference_id')->nullable();
            $table->enum('status', ['draft', 'approved', 'in_progress', 'on_hold', 'completed', 'cancelled'])->default('draft');
            $table->text('work_description')->nullable();
            $table->text('work_performed')->nullable(); // Completed work details
            $table->text('technician_notes')->nullable();
            $table->enum('cost_classification', ['expense', 'capitalized', 'pending_review'])->default('pending_review');
            $table->boolean('is_capital_improvement')->default(false);
            $table->decimal('capitalization_threshold', 15, 2)->nullable();
            $table->integer('life_extension_months')->nullable(); // If capitalized, how much life extended
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable(); // Finance/Asset Accountant
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->boolean('gl_posted')->default(false);
            $table->unsignedBigInteger('gl_journal_id')->nullable();
            $table->timestamp('gl_posted_at')->nullable();
            $table->json('attachments')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('maintenance_request_id')->references('id')->on('maintenance_requests')->onDelete('set null');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('maintenance_type_id')->references('id')->on('maintenance_types')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('assigned_technician_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('gl_journal_id')->references('id')->on('journals')->onDelete('set null');
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['asset_id', 'status']);
            $table->index(['wo_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance_work_orders');
    }
};
