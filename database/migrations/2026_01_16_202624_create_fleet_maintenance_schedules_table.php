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
        Schema::create('fleet_maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Vehicle reference
            $table->foreignId('vehicle_id')->constrained('assets')->onDelete('cascade');
            
            // Schedule details
            $table->string('schedule_name', 255);
            $table->enum('schedule_type', ['time_based', 'mileage_based', 'both'])->default('mileage_based');
            $table->string('maintenance_category', 100); // oil_change, tire_rotation, engine_service, etc.
            $table->text('description')->nullable();
            
            // Time-based schedule
            $table->integer('interval_days')->nullable(); // Days between maintenance
            $table->integer('interval_months')->nullable(); // Months between maintenance
            $table->date('last_performed_date')->nullable();
            $table->date('next_due_date')->nullable();
            
            // Mileage-based schedule
            $table->decimal('interval_km', 10, 2)->nullable(); // Kilometers between maintenance
            $table->decimal('last_performed_odometer', 12, 2)->nullable();
            $table->decimal('next_due_odometer', 12, 2)->nullable();
            
            // Alert settings
            $table->integer('alert_days_before')->default(7); // Days before due date to alert
            $table->integer('alert_km_before')->default(500); // KM before due to alert
            $table->boolean('block_dispatch_when_overdue')->default(true);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->enum('current_status', ['up_to_date', 'due_soon', 'overdue', 'completed'])->default('up_to_date');
            
            // Estimated cost for budgeting
            $table->decimal('estimated_cost', 18, 2)->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'vehicle_id', 'is_active'], 'fleet_maint_sched_comp_veh_active_idx');
            $table->index(['next_due_date', 'is_active'], 'fleet_maint_sched_due_date_active_idx');
            $table->index(['next_due_odometer', 'is_active'], 'fleet_maint_sched_due_odo_active_idx');
            $table->index('current_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_maintenance_schedules');
    }
};
