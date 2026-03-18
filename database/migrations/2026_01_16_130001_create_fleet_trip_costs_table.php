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
        Schema::create('fleet_trip_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Trip reference
            $table->foreignId('trip_id')->constrained('fleet_trips')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('assets')->onDelete('restrict'); // For direct vehicle costs
            
            // Cost category
            $table->foreignId('cost_category_id')->nullable()->constrained('fleet_cost_categories')->onDelete('set null');
            $table->string('cost_type', 50); // fuel, driver_allowance, overtime, toll, maintenance, insurance, other
            
            // Cost details
            $table->decimal('amount', 18, 2);
            $table->string('currency', 10)->default('TZS');
            $table->text('description')->nullable();
            $table->date('date_incurred');
            $table->time('time_incurred')->nullable();
            
            // Fuel-specific fields
            $table->decimal('fuel_liters', 10, 2)->nullable();
            $table->decimal('fuel_price_per_liter', 10, 2)->nullable();
            $table->string('fuel_site', 255)->nullable();
            $table->string('fuel_card_number', 50)->nullable();
            $table->decimal('odometer_reading', 12, 2)->nullable();
            
            // Driver cost fields
            $table->decimal('driver_allowance_amount', 18, 2)->nullable();
            $table->decimal('overtime_hours', 8, 2)->nullable();
            $table->decimal('overtime_rate', 18, 2)->nullable();
            
            // Toll fields
            $table->string('toll_point_name', 255)->nullable();
            $table->string('toll_receipt_number', 100)->nullable();
            
            // GL Account and accounting
            $table->foreignId('gl_account_id')->nullable()->constrained('chart_accounts')->onDelete('set null');
            $table->boolean('is_posted_to_gl')->default(false);
            $table->foreignId('gl_journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->date('gl_posted_date')->nullable();
            
            // Billing
            $table->boolean('is_billable_to_customer')->default(false);
            $table->decimal('billable_amount', 18, 2)->nullable();
            
            // Approval workflow
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Supporting documents
            $table->string('receipt_number', 100)->nullable();
            $table->string('receipt_attachment', 500)->nullable();
            $table->json('attachments')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'trip_id']);
            $table->index(['trip_id', 'date_incurred']);
            $table->index(['vehicle_id', 'date_incurred']);
            $table->index('cost_type');
            $table->index('approval_status');
            $table->index('is_posted_to_gl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_trip_costs');
    }
};
