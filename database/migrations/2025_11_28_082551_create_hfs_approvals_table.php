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
        Schema::create('hfs_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hfs_id');
            
            // Approval level (matching disposal approval pattern)
            $table->enum('approval_level', ['asset_custodian', 'finance_manager', 'cfo', 'board'])->default('asset_custodian');
            
            // Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'requested_modification'])->default('pending');
            
            // Approver information
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            
            // Comments and reasons
            $table->text('comments')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('modification_request')->nullable();
            
            // Digital signature or typed approval
            $table->text('approval_signature')->nullable(); // Can store signature data or typed approval text
            $table->boolean('is_digital_signature')->default(false);
            
            // Required checks (for this approval level)
            $table->json('checks_performed')->nullable();
            // Example: {
            //   "management_commitment_verified": true,
            //   "buyer_identified": true,
            //   "marketing_active": true,
            //   "price_reasonable": true,
            //   "timeline_realistic": true
            // }
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['hfs_id']);
            $table->index(['approval_level', 'status']);
            $table->index(['approver_id']);
            $table->index(['approved_at']);
            
            // Foreign keys
            $table->foreign('hfs_id')->references('id')->on('hfs_requests')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hfs_approvals');
    }
};
