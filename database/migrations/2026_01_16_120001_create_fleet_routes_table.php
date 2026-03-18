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
        Schema::create('fleet_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Route identification
            $table->string('route_code', 50);
            $table->string('route_name', 255);
            
            // Origin and destination
            $table->string('origin_location', 255);
            $table->string('destination_location', 255);
            $table->text('route_description')->nullable();
            
            // Distance and time
            $table->decimal('distance_km', 10, 2);
            $table->decimal('estimated_duration_hours', 8, 2)->nullable();
            $table->decimal('estimated_duration_minutes', 8, 2)->nullable();
            
            // Fuel and cost estimates
            $table->decimal('estimated_fuel_consumption_liters', 10, 2)->nullable();
            $table->decimal('toll_costs', 18, 2)->default(0);
            $table->json('toll_points')->nullable(); // Array of toll points with costs
            
            // Route details
            $table->enum('route_type', ['local', 'intercity', 'highway', 'rural', 'urban'])->default('local');
            $table->text('waypoints')->nullable(); // JSON array of waypoints
            $table->text('route_notes')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['branch_id', 'is_active']);
            $table->index('route_code');
            $table->unique(['company_id', 'route_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_routes');
    }
};
