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
        Schema::create('disposal_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('disposal_id');
            $table->enum('approval_level', ['department_head', 'finance_manager', 'cfo', 'board'])->default('department_head');
            $table->enum('status', ['pending', 'approved', 'rejected', 'requested_modification'])->default('pending');
            $table->text('comments')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            $table->index(['disposal_id']);
            $table->index(['approval_level', 'status']);
            $table->foreign('disposal_id')->references('id')->on('asset_disposals')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disposal_approvals');
    }
};
