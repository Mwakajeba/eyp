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
        Schema::create('hfs_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            
            // Request identification
            $table->string('request_no')->unique();
            
            // Initiator
            $table->unsignedBigInteger('initiator_id');
            
            // Status workflow
            $table->enum('status', ['draft', 'in_review', 'approved', 'rejected', 'cancelled', 'sold'])->default('draft');
            
            // Sale plan information
            $table->date('intended_sale_date');
            $table->date('expected_close_date')->nullable();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_contact')->nullable();
            $table->text('buyer_address')->nullable();
            $table->text('justification')->nullable();
            
            // Financial estimates
            $table->decimal('expected_costs_to_sell', 18, 2)->default(0);
            $table->decimal('expected_fair_value', 18, 2)->default(0);
            $table->decimal('probability_pct', 5, 2)->nullable(); // 0-100
            
            // Marketing and sale information
            $table->text('marketing_actions')->nullable();
            $table->text('sale_price_range')->nullable();
            $table->boolean('management_committed')->default(false);
            $table->date('management_commitment_date')->nullable();
            
            // 12-month rule handling
            $table->boolean('exceeds_12_months')->default(false);
            $table->text('extension_justification')->nullable();
            $table->unsignedBigInteger('extension_approved_by')->nullable();
            $table->dateTime('extension_approved_at')->nullable();
            
            // Disposal group flag
            $table->boolean('is_disposal_group')->default(false);
            $table->text('disposal_group_description')->nullable();
            
            // Notes and attachments
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable(); // Management minutes, valuer reports, marketing evidence, etc.
            
            // Approval tracking
            $table->unsignedBigInteger('current_approval_level')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Audit trail
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'branch_id']);
            $table->index(['status']);
            $table->index(['initiator_id']);
            $table->index(['intended_sale_date']);
            $table->index(['request_no']);
            $table->index(['created_at']);
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('initiator_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('extension_approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hfs_requests');
    }
};
