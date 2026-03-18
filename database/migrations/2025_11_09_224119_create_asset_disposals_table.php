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
        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('asset_id');
            $table->string('disposal_number')->unique();
            
            // Disposal Type: sale, scrap, write_off, donation, loss
            $table->enum('disposal_type', ['sale', 'scrap', 'write_off', 'donation', 'loss'])->default('sale');
            $table->unsignedBigInteger('disposal_reason_code_id')->nullable();
            $table->text('disposal_reason')->nullable();
            
            // Dates
            $table->date('proposed_disposal_date');
            $table->date('actual_disposal_date')->nullable();
            
            // Financial Information
            $table->decimal('net_book_value', 18, 2)->default(0); // NBV = Cost - Accumulated Depreciation - Accumulated Impairment
            $table->decimal('accumulated_depreciation', 18, 2)->default(0);
            $table->decimal('accumulated_impairment', 18, 2)->default(0);
            $table->decimal('asset_cost', 18, 2)->default(0);
            $table->decimal('disposal_proceeds', 18, 2)->default(0); // Sale amount or fair value
            $table->decimal('fair_value', 18, 2)->nullable(); // For donations/write-offs
            $table->decimal('gain_loss', 18, 2)->default(0); // Calculated: Proceeds - NBV
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('withholding_tax', 18, 2)->default(0);
            
            // Buyer/Recipient Information
            $table->string('buyer_name')->nullable();
            $table->string('buyer_contact')->nullable();
            $table->string('buyer_address')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('receipt_number')->nullable();
            
            // Insurance Recovery (for loss/theft)
            $table->decimal('insurance_recovery_amount', 18, 2)->default(0);
            $table->string('insurance_claim_number')->nullable();
            $table->date('insurance_recovery_date')->nullable();
            
            // Revaluation Reserve Transfer
            $table->decimal('revaluation_reserve_transferred', 18, 2)->default(0);
            $table->boolean('reserve_transferred_to_retained_earnings')->default(false);
            
            // Partial Disposal
            $table->boolean('is_partial_disposal')->default(false);
            $table->decimal('partial_disposal_percentage', 5, 2)->nullable(); // 0-100
            $table->text('partial_disposal_description')->nullable();
            
            // Status and Workflow
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'completed', 'cancelled'])->default('draft');
            $table->text('rejection_reason')->nullable();
            
            // GL Integration
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->boolean('gl_posted')->default(false);
            $table->timestamp('gl_posted_at')->nullable();
            
            // Chart Accounts
            $table->unsignedBigInteger('accumulated_depreciation_account_id')->nullable();
            $table->unsignedBigInteger('disposal_proceeds_account_id')->nullable();
            $table->unsignedBigInteger('gain_loss_account_id')->nullable();
            $table->unsignedBigInteger('donation_expense_account_id')->nullable();
            $table->unsignedBigInteger('loss_account_id')->nullable();
            $table->unsignedBigInteger('insurance_recovery_account_id')->nullable();
            $table->unsignedBigInteger('retained_earnings_account_id')->nullable();
            $table->unsignedBigInteger('vat_account_id')->nullable();
            
            // Documents
            $table->string('valuation_report_path')->nullable();
            $table->json('attachments')->nullable(); // Board approval, police report, receipts, etc.
            $table->text('notes')->nullable();
            
            // Audit Trail
            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'branch_id']);
            $table->index(['asset_id']);
            $table->index(['status']);
            $table->index(['disposal_type']);
            $table->index(['disposal_number']);
            
            // Foreign Keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            // Foreign key will be added in a separate migration after disposal_reason_codes is created
            // $table->foreign('disposal_reason_code_id')->references('id')->on('disposal_reason_codes')->onDelete('set null');
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
