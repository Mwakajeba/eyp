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
        Schema::create('investment_deferred_tax', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained('investment_master')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Tax period
            $table->date('tax_period_start')->index();
            $table->date('tax_period_end')->index();
            $table->integer('tax_year')->index();
            
            // Temporary differences
            $table->decimal('tax_base_carrying_amount', 18, 2)->default(0); // Tax base
            $table->decimal('accounting_carrying_amount', 18, 2)->default(0); // Accounting carrying amount
            $table->decimal('temporary_difference', 18, 2)->default(0); // Difference
            
            // Deferred tax calculation
            $table->decimal('tax_rate', 10, 6)->default(30); // Tax rate (%)
            $table->decimal('deferred_tax_asset', 18, 2)->default(0); // DTA
            $table->decimal('deferred_tax_liability', 18, 2)->default(0); // DTL
            $table->decimal('net_deferred_tax', 18, 2)->default(0); // Net DTA/DTL
            
            // Movement tracking
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->decimal('movement', 18, 2)->default(0);
            $table->decimal('closing_balance', 18, 2)->default(0);
            
            // Source of temporary difference
            $table->enum('difference_type', ['REVALUATION', 'AMORTIZATION', 'IMPAIRMENT', 'ECL', 'OTHER'])->default('OTHER');
            $table->text('difference_description')->nullable();
            
            // Journal posting
            $table->foreignId('posted_journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index(['investment_id', 'tax_year']);
            $table->index(['company_id', 'tax_year']);
            $table->index('difference_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_deferred_tax');
    }
};
