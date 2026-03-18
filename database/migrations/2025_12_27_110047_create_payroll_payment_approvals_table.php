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
        Schema::create('payroll_payment_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->integer('approval_level');
            $table->foreignId('approver_id')->constrained('users');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('amount_at_approval', 15, 2)->nullable(); // Store payment amount at time of approval
            
            $table->timestamps();
            
            // Indexes
            $table->index(['payroll_id', 'approval_level']);
            $table->index(['approver_id', 'status']);
            $table->index('status');
            
            // Ensure unique approver per level per payroll
            $table->unique(['payroll_id', 'approval_level', 'approver_id'], 'payroll_payment_approvals_unique_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_payment_approvals');
    }
};
