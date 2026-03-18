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
        Schema::create('fleet_maintenance_work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Work order identification
            $table->string('wo_number', 50)->unique();
            $table->unsignedBigInteger('maintenance_schedule_id')->nullable();
            $table->foreignId('vehicle_id')->constrained('assets')->onDelete('restrict');
            
            // Maintenance type
            $table->enum('maintenance_type', ['preventive', 'corrective', 'major_overhaul'])->default('corrective');
            $table->string('maintenance_category', 100)->nullable(); // oil_change, tire_replacement, engine_repair, etc.
            
            // Execution
            $table->enum('execution_type', ['in_house', 'external_vendor', 'mixed'])->default('in_house');
            $table->foreignId('vendor_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('assigned_technician_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Scheduling
            $table->dateTime('scheduled_date')->nullable();
            $table->dateTime('estimated_start_date')->nullable();
            $table->dateTime('estimated_completion_date')->nullable();
            $table->dateTime('actual_start_date')->nullable();
            $table->dateTime('actual_completion_date')->nullable();
            
            // Cost estimates
            $table->decimal('estimated_cost', 18, 2)->default(0);
            $table->decimal('estimated_labor_cost', 18, 2)->default(0);
            $table->decimal('estimated_material_cost', 18, 2)->default(0);
            $table->decimal('estimated_other_cost', 18, 2)->default(0);
            
            // Actual costs
            $table->decimal('actual_cost', 18, 2)->default(0);
            $table->decimal('actual_labor_cost', 18, 2)->default(0);
            $table->decimal('actual_material_cost', 18, 2)->default(0);
            $table->decimal('actual_other_cost', 18, 2)->default(0);
            
            // Downtime
            $table->decimal('estimated_downtime_hours', 8, 2)->default(0);
            $table->decimal('actual_downtime_hours', 8, 2)->nullable();
            $table->dateTime('downtime_start')->nullable();
            $table->dateTime('downtime_end')->nullable();
            
            // Work details
            $table->text('work_description')->nullable();
            $table->text('work_performed')->nullable();
            $table->text('technician_notes')->nullable();
            $table->text('parts_used')->nullable(); // JSON or text description
            
            // Status and workflow
            $table->enum('status', ['draft', 'scheduled', 'in_progress', 'on_hold', 'completed', 'cancelled'])->default('draft');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            // Cost classification
            $table->enum('cost_classification', ['expense', 'capitalized', 'pending_review'])->default('pending_review');
            $table->boolean('is_capital_improvement')->default(false);
            $table->decimal('capitalization_threshold', 18, 2)->nullable();
            $table->integer('life_extension_months')->nullable();
            
            // Approval and completion
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            
            // GL integration
            $table->boolean('gl_posted')->default(false);
            $table->foreignId('gl_journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->timestamp('gl_posted_at')->nullable();
            
            // Attachments and notes
            $table->json('attachments')->nullable();
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'vehicle_id', 'status'], 'fleet_wo_comp_veh_status_idx');
            $table->index(['vehicle_id', 'maintenance_type'], 'fleet_wo_veh_type_idx');
            $table->index(['scheduled_date', 'status'], 'fleet_wo_sched_status_idx');
            $table->index('maintenance_schedule_id');
            $table->index('status');
        });

        // Add foreign key constraint after schedules table is created
        // This will be added in a separate migration that runs after schedules
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_maintenance_work_orders');
    }
};
