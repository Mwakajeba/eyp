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
        Schema::create('asset_revaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('asset_id');
            $table->string('revaluation_number')->unique();
            $table->date('revaluation_date');
            $table->enum('valuation_model', ['cost', 'revaluation'])->default('cost');
            
            // Valuer information
            $table->string('valuer_name')->nullable();
            $table->string('valuer_license')->nullable();
            $table->string('valuer_company')->nullable();
            $table->string('valuation_report_ref')->nullable();
            $table->text('valuation_report_path')->nullable();
            $table->text('reason')->nullable();
            
            // Financial values
            $table->decimal('carrying_amount_before', 18, 2)->default(0);
            $table->decimal('accumulated_depreciation_before', 18, 2)->default(0);
            $table->decimal('fair_value', 18, 2)->default(0);
            $table->decimal('revaluation_increase', 18, 2)->default(0);
            $table->decimal('revaluation_decrease', 18, 2)->default(0);
            $table->decimal('carrying_amount_after', 18, 2)->default(0);
            
            // Depreciation recalibration
            $table->integer('useful_life_before')->nullable();
            $table->integer('useful_life_after')->nullable();
            $table->decimal('residual_value_before', 18, 2)->nullable();
            $table->decimal('residual_value_after', 18, 2)->nullable();
            
            // Accounting
            $table->unsignedBigInteger('revaluation_reserve_account_id')->nullable();
            $table->unsignedBigInteger('impairment_reversal_account_id')->nullable();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->boolean('gl_posted')->default(false);
            $table->dateTime('gl_posted_at')->nullable();
            
            // Approval workflow
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'posted'])->default('draft');
            $table->unsignedBigInteger('valuer_user_id')->nullable();
            $table->unsignedBigInteger('finance_manager_id')->nullable();
            $table->unsignedBigInteger('cfo_approver_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Audit
            $table->json('attachments')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'branch_id']);
            $table->index(['asset_id']);
            $table->index(['revaluation_date']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_revaluations');
    }
};
