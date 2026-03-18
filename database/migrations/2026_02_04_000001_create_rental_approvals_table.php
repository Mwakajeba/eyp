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
        Schema::create('rental_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approvable_type'); // RentalQuotation, RentalContract, etc.
            $table->unsignedBigInteger('approvable_id');
            $table->integer('approval_level')->default(1);
            $table->unsignedBigInteger('approver_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comments')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['approvable_type', 'approvable_id']);
            $table->index(['status', 'approval_level']);
            $table->index('approver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_approvals');
    }
};
