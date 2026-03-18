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
        Schema::create('asset_impairments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('asset_id');
            $table->string('impairment_number')->unique();
            $table->date('impairment_date');
            $table->enum('impairment_type', ['individual', 'cgu'])->default('individual');
            $table->unsignedBigInteger('cgu_id')->nullable(); // Cash Generating Unit ID
            
            // Impairment indicators
            $table->boolean('indicator_physical_damage')->default(false);
            $table->boolean('indicator_obsolescence')->default(false);
            $table->boolean('indicator_technological_change')->default(false);
            $table->boolean('indicator_idle_asset')->default(false);
            $table->boolean('indicator_market_decline')->default(false);
            $table->boolean('indicator_legal_regulatory')->default(false);
            $table->text('other_indicators')->nullable();
            
            // Recoverable amount calculation
            $table->decimal('carrying_amount', 18, 2)->default(0);
            $table->decimal('fair_value_less_costs', 18, 2)->nullable();
            $table->decimal('value_in_use', 18, 2)->nullable();
            $table->decimal('recoverable_amount', 18, 2)->default(0);
            $table->decimal('impairment_loss', 18, 2)->default(0);
            
            // Value in use calculation (for future cash flows)
            $table->decimal('discount_rate', 5, 2)->nullable();
            $table->json('cash_flow_projections')->nullable(); // Array of future cash flows
            $table->integer('projection_years')->nullable();
            
            // Reversal information
            $table->boolean('is_reversal')->default(false);
            $table->unsignedBigInteger('original_impairment_id')->nullable();
            $table->decimal('reversal_amount', 18, 2)->default(0);
            $table->date('reversal_date')->nullable();
            
            // Accounting
            $table->unsignedBigInteger('impairment_loss_account_id')->nullable();
            $table->unsignedBigInteger('impairment_reversal_account_id')->nullable();
            $table->unsignedBigInteger('accumulated_impairment_account_id')->nullable();
            $table->unsignedBigInteger('revaluation_reserve_account_id')->nullable();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->boolean('gl_posted')->default(false);
            $table->dateTime('gl_posted_at')->nullable();
            
            // Asset updates after impairment
            $table->decimal('carrying_amount_after', 18, 2)->default(0);
            $table->integer('useful_life_before')->nullable();
            $table->integer('useful_life_after')->nullable();
            $table->decimal('residual_value_before', 18, 2)->nullable();
            $table->decimal('residual_value_after', 18, 2)->nullable();
            
            // Approval workflow
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'posted'])->default('draft');
            $table->unsignedBigInteger('prepared_by')->nullable();
            $table->unsignedBigInteger('finance_manager_id')->nullable();
            $table->unsignedBigInteger('cfo_approver_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Documentation
            $table->text('impairment_test_report_path')->nullable();
            $table->json('attachments')->nullable();
            $table->text('notes')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'branch_id']);
            $table->index(['asset_id']);
            $table->index(['impairment_date']);
            $table->index(['status']);
            $table->index(['is_reversal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_impairments');
    }
};
