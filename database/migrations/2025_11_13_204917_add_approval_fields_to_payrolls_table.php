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
            // Add missing approval fields (some already exist from previous migration)
            $table->text('approval_remarks')->nullable()->after('approved_at');
            
            // Rejection fields  
            $table->unsignedBigInteger('rejected_by')->nullable()->after('approval_remarks');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_remarks')->nullable()->after('rejected_at');
            
            // Payment fields (paid_at already exists)
            $table->unsignedBigInteger('paid_by')->nullable()->after('paid_at');
            $table->text('payment_remarks')->nullable()->after('paid_by');
            
            // Foreign key constraints
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('paid_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['paid_by']);
            
            // Drop columns
            $table->dropColumn([
                'approval_remarks',
                'rejected_by',
                'rejected_at',
                'rejection_remarks',
                'paid_by',
                'payment_remarks'
            ]);
        });
    }
};
