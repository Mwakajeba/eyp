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
        Schema::create('investment_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('investment_proposals')->onDelete('cascade');
            
            // Approval level (1, 2, 3, 4, 5)
            $table->integer('approval_level');
            
            // Approver information
            $table->string('approver_type')->nullable(); // 'role' or 'user'
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('approver_name'); // role name or user name
            
            // Approval status
            $table->enum('status', ['pending', 'approved', 'rejected', 'requested_info'])->default('pending');
            $table->text('comments')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('info_request')->nullable(); // if status is 'requested_info'
            
            // Timestamps
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('info_requested_at')->nullable();
            
            // Digital signature or typed approval
            $table->text('approval_signature')->nullable();
            $table->boolean('is_digital_signature')->default(false);
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index(['proposal_id', 'approval_level', 'status']);
            $table->index(['approver_id', 'status']);
            $table->index('status');
            $table->index('approval_level');
            
            // Unique constraint to prevent duplicate approvals
            $table->unique(['proposal_id', 'approval_level', 'approver_id'], 'inv_approval_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_approvals');
    }
};
