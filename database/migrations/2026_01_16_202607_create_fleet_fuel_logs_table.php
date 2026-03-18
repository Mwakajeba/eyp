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
        Schema::create('fleet_fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Trip and Vehicle references
            $table->foreignId('trip_id')->nullable()->constrained('fleet_trips')->onDelete('set null');
            $table->foreignId('vehicle_id')->constrained('assets')->onDelete('restrict');
            
            // Fuel details
            $table->string('fuel_station', 255)->nullable();
            $table->string('fuel_type', 50)->nullable(); // diesel, petrol, gas
            $table->decimal('liters_filled', 10, 2);
            $table->decimal('cost_per_liter', 10, 2);
            $table->decimal('total_cost', 18, 2);
            $table->decimal('odometer_reading', 12, 2);
            $table->decimal('previous_odometer', 12, 2)->nullable(); // For efficiency calculation
            
            // Fuel card integration
            $table->string('fuel_card_number', 50)->nullable();
            $table->string('fuel_card_type', 50)->nullable(); // company_card, driver_card
            $table->boolean('fuel_card_used')->default(false);
            
            // Receipt and documentation
            $table->string('receipt_number', 100)->nullable();
            $table->string('receipt_attachment', 500)->nullable();
            $table->json('attachments')->nullable();
            
            // Date and time
            $table->date('date_filled');
            $table->time('time_filled')->nullable();
            
            // Efficiency tracking
            $table->decimal('km_since_last_fill', 10, 2)->nullable();
            $table->decimal('fuel_efficiency_km_per_liter', 8, 2)->nullable(); // Calculated
            $table->decimal('cost_per_km', 10, 2)->nullable(); // Calculated
            
            // GL integration
            $table->foreignId('gl_account_id')->nullable()->constrained('chart_accounts')->onDelete('set null');
            $table->boolean('is_posted_to_gl')->default(false);
            $table->foreignId('gl_journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->date('gl_posted_date')->nullable();
            
            // Approval
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'vehicle_id', 'date_filled']);
            $table->index(['trip_id', 'date_filled']);
            $table->index(['vehicle_id', 'date_filled']);
            $table->index('approval_status');
            $table->index('is_posted_to_gl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_fuel_logs');
    }
};
