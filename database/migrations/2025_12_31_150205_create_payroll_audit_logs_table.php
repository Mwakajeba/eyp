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
        if (!Schema::hasTable('payroll_audit_logs')) {
        Schema::create('payroll_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // created, updated, deleted, processed, approved, rejected, locked, unlocked, reversed, payment_processed, payment_approved, payment_rejected
            $table->string('field_name')->nullable(); // For field-level changes
            $table->text('old_value')->nullable(); // JSON encoded old value
            $table->text('new_value')->nullable(); // JSON encoded new value
            $table->text('description')->nullable(); // Human-readable description
            $table->text('remarks')->nullable(); // User remarks/reason
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Additional context data
            $table->timestamps();
            
            // Indexes for performance
            $table->index('payroll_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_audit_logs');
    }
};
