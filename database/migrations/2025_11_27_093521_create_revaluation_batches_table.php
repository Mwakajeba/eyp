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
        Schema::create('revaluation_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('batch_number')->unique();
            $table->date('revaluation_date');
            $table->enum('valuation_model', ['cost', 'revaluation'])->default('cost');
            
            // Valuer information (shared across batch)
            $table->string('valuer_name')->nullable();
            $table->string('valuer_license')->nullable();
            $table->string('valuer_company')->nullable();
            $table->string('valuation_report_ref')->nullable();
            $table->text('valuation_report_path')->nullable();
            $table->text('reason')->nullable();
            
            // Approval workflow
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'partially_approved'])->default('draft');
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
            $table->index(['batch_number']);
            $table->index(['status']);
            $table->index(['revaluation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revaluation_batches');
    }
};
