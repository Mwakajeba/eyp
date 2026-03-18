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
        Schema::create('fleet_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Invoice identification
            $table->string('invoice_number', 50)->unique();
            
            // References
            $table->foreignId('trip_id')->nullable()->constrained('fleet_trips')->onDelete('set null'); // For per-trip invoicing
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('restrict');
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->onDelete('set null'); // For internal billing
            
            // Invoice details
            $table->date('invoice_date');
            $table->date('due_date');
            $table->enum('invoice_type', ['trip_based', 'period_based', 'contract'])->default('trip_based');
            $table->enum('status', ['draft', 'sent', 'paid', 'partially_paid', 'overdue', 'cancelled'])->default('draft');
            
            // Billing period (for period-based invoices)
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();
            
            // Revenue model and amounts
            $table->enum('revenue_model', ['per_trip', 'per_km', 'per_hour', 'fixed_contract'])->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('balance_due', 18, 2)->default(0);
            
            // Payment terms
            $table->enum('payment_terms', ['immediate', 'net_15', 'net_30', 'net_45', 'net_60', 'custom'])->default('net_30');
            $table->integer('payment_days')->default(30);
            
            // Currency
            $table->string('currency', 10)->default('TZS');
            $table->decimal('exchange_rate', 10, 4)->default(1);
            
            // Trip details summary (for trip-based invoices)
            $table->integer('number_of_trips')->default(0);
            $table->decimal('total_distance_km', 10, 2)->nullable();
            $table->decimal('total_hours', 8, 2)->nullable();
            
            // GL integration
            $table->foreignId('gl_account_id')->nullable()->constrained('chart_accounts')->onDelete('set null'); // Revenue account
            $table->boolean('is_posted_to_gl')->default(false);
            $table->foreignId('gl_journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->timestamp('gl_posted_at')->nullable();
            
            // Invoice metadata
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->json('attachments')->nullable();
            
            // Dates
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'invoice_date']);
            $table->index(['customer_id', 'status']);
            $table->index(['trip_id', 'status']);
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('status');
            $table->index('is_posted_to_gl');
        });
        
        // Create invoice items table
        Schema::create('fleet_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_invoice_id')->constrained('fleet_invoices')->onDelete('cascade');
            $table->foreignId('trip_id')->nullable()->constrained('fleet_trips')->onDelete('set null');
            
            // Item details
            $table->string('description', 500);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 50)->nullable(); // trip, km, hour, etc.
            $table->decimal('unit_rate', 18, 2);
            $table->decimal('amount', 18, 2);
            
            // Additional details
            $table->text('notes')->nullable();
            
            // Audit
            $table->timestamps();
            
            $table->index(['fleet_invoice_id', 'trip_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_invoice_items');
        Schema::dropIfExists('fleet_invoices');
    }
};
