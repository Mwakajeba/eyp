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
            // Payment approval workflow tracking
            $table->boolean('requires_payment_approval')->default(false)->after('is_fully_approved');
            $table->integer('current_payment_approval_level')->default(0)->after('requires_payment_approval');
            $table->boolean('is_payment_fully_approved')->default(false)->after('current_payment_approval_level');
            $table->timestamp('payment_approved_at')->nullable()->after('is_payment_fully_approved');
            $table->unsignedBigInteger('payment_approved_by')->nullable()->after('payment_approved_at');
            $table->text('payment_approval_remarks')->nullable()->after('payment_approved_by');
            $table->unsignedBigInteger('payment_rejected_by')->nullable()->after('payment_approval_remarks');
            $table->timestamp('payment_rejected_at')->nullable()->after('payment_rejected_by');
            $table->text('payment_rejection_remarks')->nullable()->after('payment_rejected_at');
            
            // Foreign key constraints
            $table->foreign('payment_approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('payment_rejected_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('requires_payment_approval');
            $table->index('is_payment_fully_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['payment_approved_by']);
            $table->dropForeign(['payment_rejected_by']);
            
            // Drop columns
            $table->dropColumn([
                'requires_payment_approval',
                'current_payment_approval_level',
                'is_payment_fully_approved',
                'payment_approved_at',
                'payment_approved_by',
                'payment_approval_remarks',
                'payment_rejected_by',
                'payment_rejected_at',
                'payment_rejection_remarks'
            ]);
        });
    }
};
