<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Central Discount Rate Governance Table
     * Manages discount rates for provisions, VIU calculations, pensions, etc.
     */
    public function up(): void
    {
        Schema::create('discount_rates', function (Blueprint $table) {
            $table->id();
            
            // Classification
            $table->string('ifrs_standard', 20)->default('IAS 37'); // IAS 37, IAS 36, IAS 19, etc.
            $table->enum('usage_context', [
                'provision',
                'viu', // Value in Use (IAS 36)
                'pension', // IAS 19
                'lease', // IFRS 16
                'other',
            ])->default('provision');
            
            // Rate details
            $table->string('currency_code', 3)->default('TZS');
            $table->enum('rate_type', ['pre_tax', 'post_tax'])->default('pre_tax');
            $table->string('risk_category', 100)->nullable(); // e.g., "Low Risk", "High Risk", "Government Bond Rate"
            $table->decimal('rate_percent', 8, 4); // e.g., 12.5000 for 12.5%
            $table->text('basis')->nullable(); // Description of how rate was determined
            
            // Effective dates
            $table->date('effective_from');
            $table->date('effective_to')->nullable(); // NULL = currently active
            
            // Approval & governance
            $table->enum('approval_status', ['draft', 'pending_approval', 'approved', 'rejected'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Company context
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            // Audit
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'usage_context', 'currency_code']);
            $table->index(['effective_from', 'effective_to']);
            $table->index('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_rates');
    }
};

