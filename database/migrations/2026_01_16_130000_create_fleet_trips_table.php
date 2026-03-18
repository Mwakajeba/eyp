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
        Schema::create('fleet_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Trip identification
            $table->string('trip_number', 50)->unique();
            
            // Trip assignment
            $table->foreignId('vehicle_id')->constrained('assets')->onDelete('restrict'); // Links to Asset (truck)
            $table->foreignId('driver_id')->nullable()->constrained('fleet_drivers')->onDelete('set null');
            $table->foreignId('route_id')->nullable()->constrained('fleet_routes')->onDelete('set null');
            
            // Client/Department
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->onDelete('set null');
            
            // Trip details
            $table->enum('status', ['planned', 'dispatched', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->enum('trip_type', ['delivery', 'pickup', 'service', 'transport', 'other'])->default('transport');
            $table->text('cargo_description')->nullable();
            $table->string('origin_location', 255)->nullable();
            $table->string('destination_location', 255)->nullable();
            
            // Planning dates
            $table->dateTime('planned_start_date')->nullable();
            $table->dateTime('planned_end_date')->nullable();
            
            // Actual execution dates
            $table->dateTime('actual_start_date')->nullable();
            $table->dateTime('actual_end_date')->nullable();
            
            // Distance tracking
            $table->decimal('planned_distance_km', 10, 2)->nullable();
            $table->decimal('actual_distance_km', 10, 2)->nullable();
            $table->decimal('start_odometer', 12, 2)->nullable();
            $table->decimal('end_odometer', 12, 2)->nullable();
            
            // Fuel tracking
            $table->decimal('planned_fuel_consumption_liters', 10, 2)->nullable();
            $table->decimal('actual_fuel_consumption_liters', 10, 2)->nullable();
            $table->decimal('start_fuel_level', 8, 2)->nullable();
            $table->decimal('end_fuel_level', 8, 2)->nullable();
            
            // Revenue
            $table->decimal('planned_revenue', 18, 2)->default(0);
            $table->decimal('actual_revenue', 18, 2)->default(0);
            $table->enum('revenue_model', ['per_trip', 'per_km', 'per_hour', 'fixed_contract'])->nullable();
            $table->decimal('revenue_rate', 18, 2)->nullable();
            
            // Cost summary (calculated)
            $table->decimal('total_costs', 18, 2)->default(0);
            $table->decimal('variable_costs', 18, 2)->default(0);
            $table->decimal('fixed_costs_allocated', 18, 2)->default(0);
            $table->decimal('profit_loss', 18, 2)->default(0);
            
            // Approval and workflow
            $table->enum('approval_status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Completion
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Notes and attachments
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['vehicle_id', 'status']);
            $table->index(['driver_id', 'status']);
            $table->index('trip_number');
            $table->index('planned_start_date');
            $table->index('actual_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_trips');
    }
};
