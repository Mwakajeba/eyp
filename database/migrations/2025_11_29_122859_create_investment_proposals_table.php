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
        Schema::create('investment_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Proposal identification
            $table->string('proposal_number', 50)->unique();
            
            // Investment details
            $table->enum('instrument_type', ['T_BILL', 'T_BOND', 'FIXED_DEPOSIT', 'CORP_BOND', 'EQUITY', 'MMF', 'OTHER'])->default('OTHER');
            $table->string('issuer', 200)->nullable();
            $table->decimal('proposed_amount', 18, 2)->default(0);
            $table->decimal('expected_yield', 10, 6)->nullable();
            $table->string('risk_rating', 50)->nullable();
            $table->integer('tenor_days')->nullable(); // tenor in days
            $table->enum('proposed_accounting_class', ['AMORTISED_COST', 'FVOCI', 'FVPL'])->default('AMORTISED_COST');
            
            // Proposal metadata
            $table->text('description')->nullable();
            $table->text('rationale')->nullable(); // why this investment
            $table->foreignId('recommended_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Approval workflow
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'APPROVED', 'REJECTED', 'CANCELLED'])->default('DRAFT');
            $table->integer('current_approval_level')->default(1);
            $table->boolean('is_fully_approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Conversion to investment
            $table->foreignId('converted_to_investment_id')->nullable()->constrained('investment_master')->onDelete('set null');
            $table->timestamp('converted_at')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index('proposal_number');
            $table->index('status');
            $table->index('current_approval_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_proposals');
    }
};
