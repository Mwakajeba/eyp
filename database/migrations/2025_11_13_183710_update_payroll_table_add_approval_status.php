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
        Schema::table('payrolls', function (Blueprint $table) {
            // Update status enum to include new approval workflow states
            // Status flow: draft -> processing -> completed -> paid (or cancelled at any point)
            $table->enum('status', ['draft', 'processing', 'completed', 'paid', 'cancelled'])->default('draft')->change();
            
            // Add approval workflow tracking
            $table->boolean('requires_approval')->default(false);
            $table->integer('current_approval_level')->default(0);
            $table->boolean('is_fully_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Revert status enum to original values
            $table->string('status')->change();
            
            // Drop approval workflow columns
            $table->dropColumn([
                'requires_approval',
                'current_approval_level', 
                'is_fully_approved',
                'approved_at',
                'paid_at',
                'cancelled_at',
                'cancellation_reason'
            ]);
        });
    }
};
